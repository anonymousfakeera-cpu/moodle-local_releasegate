<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Site release gate overview system report.
 *
 * One row per course, showing the latest run. Row scoping is enforced in the
 * report query: only courses where the viewer holds local/releasegate:view are
 * included. The allowed course ids come from core get_user_capability_course().
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\reportbuilder\local\systemreports;

use core\lang_string;
use core_course\reportbuilder\local\entities\course_category;
use core_reportbuilder\local\entities\course as course_entity;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\column;
use core_reportbuilder\system_report;
use local_releasegate\reportbuilder\local\entities\run;
use html_writer;
use moodle_url;
use stdClass;


/**
 * Site release gate overview system report.
 */
class site_overview extends system_report {
    /** @var int[]|null Cached list of course ids the viewer may see. */
    private ?array $allowedcourseids = null;

    /**
     * Initialise report.
     *
     * @return void
     */
    protected function initialise(): void {
        // Main table: course, so every course shows (at most) once.
        $courseentity = new course_entity();
        $coursealias = $courseentity->get_table_alias('course');
        $this->set_main_table('course', $coursealias);
        $this->add_entity($courseentity);

        // Latest run per course. Plugin-wide definition of "latest": the row with the
        // highest id for the course (id is monotonic, one row is inserted per scan).
        // course.php uses the same definition, ORDER BY id DESC.
        // LEFT JOIN so courses that were never scanned still appear with a "Not scanned" verdict.
        $runentity = new run();
        $runalias = $runentity->get_table_alias('local_releasegate_run');
        $this->add_entity($runentity->add_join(
            "LEFT JOIN {local_releasegate_run} {$runalias} ON {$runalias}.id = (
                SELECT MAX(r2.id) FROM {local_releasegate_run} r2 WHERE r2.courseid = {$coursealias}.id
             )"
        ));

        // Course category, joined by course.category.
        $categoryentity = new course_category();
        $categoryalias = $categoryentity->get_table_alias('course_categories');
        $this->add_entity($categoryentity->add_join(
            "JOIN {course_categories} {$categoryalias} ON {$categoryalias}.id = {$coursealias}.category"
        ));

        // Row scoping: only courses where the viewer holds the view capability.
        $allowed = $this->get_allowed_course_ids();
        if (!empty($allowed)) {
            // Report Builder only accepts parameter names it generated itself
            // (database::validate_params), so $DB->get_in_or_equal() cannot be used here.
            $placeholders = [];
            $inparams = [];
            foreach ($allowed as $courseid) {
                $name = database::generate_param_name();
                $placeholders[] = ':' . $name;
                $inparams[$name] = $courseid;
            }
            $this->add_base_condition_sql("{$coursealias}.id IN (" . implode(',', $placeholders) . ')', $inparams);
        } else {
            // No allowed courses: match nothing rather than every row.
            $this->add_base_condition_sql('1 = 0');
        }

        $this->add_columns();
        $this->add_filters();

        $this->set_initial_sort_column('course:coursefullnamewithlink', SORT_ASC);
        $this->set_default_no_results_notice(new lang_string('nooverviewrows', 'local_releasegate'));

        // Download is disabled at site level: export is a course-context capability.
        $this->set_downloadable(false);
    }

    /**
     * Validates access to view this report. A user who can see no course must not see the report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return !empty($this->get_allowed_course_ids());
    }

    /**
     * Report name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('report_siteoverview', 'local_releasegate');
    }

    /**
     * Adds the columns we want to display in the report.
     *
     * @return void
     */
    protected function add_columns(): void {
        $this->add_columns_from_entities([
            'course:coursefullnamewithlink',
            'course_category:name',
            'run:verdict',
            'run:coverage',
            'run:timecreated',
        ]);

        // Gate link column, built on the course entity so never-scanned courses also link.
        $coursealias = $this->get_entity('course')->get_table_alias('course');
        $this->add_column((new column(
            'courselink',
            new lang_string('coursegate', 'local_releasegate'),
            'course'
        ))
            ->add_joins($this->get_entity('course')->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$coursealias}.id")
            ->set_is_sortable(false)
            ->add_callback(static function ($courseid): string {
                if (empty($courseid)) {
                    return '';
                }
                return html_writer::link(
                    new moodle_url('/local/releasegate/course.php', ['id' => (int) $courseid]),
                    get_string('coursegate', 'local_releasegate')
                );
            }));
    }

    /**
     * Adds the filters we want to display in the report.
     *
     * @return void
     */
    protected function add_filters(): void {
        $this->add_filters_from_entities([
            'run:verdict',
            'course_category:name',
        ]);
    }

    /**
     * Course ids the current viewer holds local/releasegate:view in.
     *
     * @return int[]
     */
    private function get_allowed_course_ids(): array {
        if ($this->allowedcourseids === null) {
            $courses = get_user_capability_course('local/releasegate:view', null, true, '');
            $this->allowedcourseids = $courses
                ? array_map(static fn(stdClass $course): int => (int) $course->id, $courses)
                : [];
        }
        return $this->allowedcourseids;
    }
}
