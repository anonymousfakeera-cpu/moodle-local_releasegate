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
 * Read-only view of a course used by rules.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\local\engine;

/**
 * Loads and caches the course data that rules need, so rules stay small.
 */
class course_context {
    /** @var \stdClass */
    public $course;
    /** @var array|null */
    private $modules = null;

    /**
     * Constructor.
     *
     * @param \stdClass $course Course record.
     */
    public function __construct(\stdClass $course) {
        $this->course = $course;
    }

    /**
     * Course modules with their module type name, keyed by cmid. Includes those pending deletion.
     *
     * @return \stdClass[]
     */
    public function modules(): array {
        global $DB;
        if ($this->modules === null) {
            $sql = "SELECT cm.*, m.name AS modname
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                     WHERE cm.course = :courseid";
            $this->modules = $DB->get_records_sql($sql, ['courseid' => $this->course->id]);
        }
        return $this->modules;
    }

    /**
     * Live modules (not being deleted) of a given type.
     *
     * @param string $modname Module name, for example quiz.
     * @return \stdClass[]
     */
    public function modules_of(string $modname): array {
        $out = [];
        foreach ($this->modules() as $cm) {
            if ($cm->modname === $modname && empty($cm->deletioninprogress)) {
                $out[$cm->id] = $cm;
            }
        }
        return $out;
    }

    /**
     * Display name for an activity instance.
     *
     * @param \stdClass $cm Course module with modname.
     * @return string
     */
    public function label(\stdClass $cm): string {
        global $DB;
        $name = $DB->get_field($cm->modname, 'name', ['id' => $cm->instance], IGNORE_MISSING);
        return ($name === false ? $cm->modname : $name) . ' (cmid ' . $cm->id . ')';
    }
}
