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
 * Nightly sweep that queues one scan task per visible course.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\task;

use core\task\manager;
use core\task\scheduled_task;

/**
 * Sweep task.
 */
class sweep extends scheduled_task {
    /**
     * Name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('sweepcourses', 'local_releasegate');
    }

    /**
     * Queue scans. Each course is its own adhoc task so one bad course cannot stop the rest.
     */
    public function execute() {
        global $DB;
        $courses = $DB->get_records_select('course', 'id <> :site AND visible = 1', ['site' => SITEID], '', 'id');
        foreach ($courses as $course) {
            $task = new scan_course();
            $task->set_custom_data(['courseid' => (int) $course->id]);
            manager::queue_adhoc_task($task, true);
        }
    }
}
