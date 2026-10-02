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
 * Course gate results system report.
 *
 * Shows rule results for one course. Row scoping is enforced in the report
 * query itself (course id from the report context plus the run id passed as
 * a report parameter), never only in can_view().
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\reportbuilder\local\systemreports;

use core\lang_string;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\system_report;
use local_releasegate\reportbuilder\local\entities\result;
use local_releasegate\reportbuilder\local\entities\run;


/**
 * Course gate results system report.
 */
class course_results extends system_report {
    /**
     * Initialise report, set the main table, load entities and set columns/filters.
     *
     * @return void
     */
    protected function initialise(): void {
        $resultentity = new result();
        $resultalias = $resultentity->get_table_alias('local_rg_result');

        $this->set_main_table('local_rg_result', $resultalias);
        $this->add_entity($resultentity);

        $runentity = new run();
        $runalias = $runentity->get_table_alias('local_rg_run');
        $this->add_entity($runentity->add_join(
            "JOIN {local_rg_run} {$runalias} ON {$runalias}.id = {$resultalias}.runid"
        ));

        // Row scoping: only rows of runs belonging to the report's course.
        $context = $this->get_context();
        $paramcourse = database::generate_param_name();
        $this->add_base_condition_sql(
            "{$runalias}.courseid = :{$paramcourse}",
            [$paramcourse => $context->instanceid]
        );

        // When the embedding page passes a run id, restrict to that run.
        $runid = $this->get_parameter('runid', 0, PARAM_INT);
        if (!empty($runid)) {
            $paramrun = database::generate_param_name();
            $this->add_base_condition_sql(
                "{$resultalias}.runid = :{$paramrun}",
                [$paramrun => $runid]
            );
        }

        // Fields always available for row callbacks.
        $this->add_base_fields("{$resultalias}.id");

        $this->add_columns();
        $this->add_filters();

        $this->set_initial_sort_column('result:rule', SORT_ASC);
        $this->set_default_no_results_notice(new lang_string('noresults', 'local_releasegate'));

        // Downloadable only for users who may export.
        $this->set_downloadable(has_capability('local/releasegate:export', $context));
    }

    /**
     * Validates access to view this report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('local/releasegate:viewresults', $this->get_context());
    }

    /**
     * Report name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('report_courseresults', 'local_releasegate');
    }

    /**
     * Adds the columns we want to display in the report.
     *
     * @return void
     */
    protected function add_columns(): void {
        $this->add_columns_from_entities([
            'result:rule',
            'result:area',
            'result:severity',
            'result:status',
            'result:message',
            'run:verdict',
            'run:timecreated',
        ]);
    }

    /**
     * Adds the filters we want to display in the report.
     *
     * @return void
     */
    protected function add_filters(): void {
        $this->add_filters_from_entities([
            'result:severity',
            'result:status',
            'result:area',
        ]);
    }
}
