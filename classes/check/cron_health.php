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
 * Check that cron is running, since gate scans depend on it.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\check;

use core\check\check;
use core\check\result;

/**
 * Cron health check.
 */
class cron_health extends check {
    /**
     * Name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('check_cron', 'local_releasegate');
    }

    /**
     * Result.
     *
     * @return result
     */
    public function get_result(): result {
        global $DB;
        $last = (int) $DB->get_field_sql("SELECT MAX(lastruntime) FROM {task_scheduled}");
        if ($last > 0 && time() - $last < HOURSECS) {
            return new result(result::OK, get_string('check_cron_ok', 'local_releasegate'));
        }
        return new result(result::WARNING, get_string('check_cron_bad', 'local_releasegate'));
    }
}
