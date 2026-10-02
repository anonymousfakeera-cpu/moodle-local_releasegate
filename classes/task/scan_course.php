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
 * Adhoc task scanning one course.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\task;

use core\task\adhoc_task;
use local_releasegate\local\runner;

/**
 * Scan one course.
 */
class scan_course extends adhoc_task {
    /**
     * Name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('scancourse', 'local_releasegate');
    }

    /**
     * Run the scan. A deleted course is skipped quietly.
     */
    public function execute() {
        global $DB;
        $data = $this->get_custom_data();
        if (empty($data->courseid) || !$DB->record_exists('course', ['id' => $data->courseid])) {
            return;
        }
        runner::run((int) $data->courseid);
    }
}
