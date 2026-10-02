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
 * Library functions.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Add a course-level navigation link for users who can view the gate.
 *
 * @param navigation_node $navigation Course navigation.
 * @param stdClass $course Course.
 * @param context $context Course context.
 */
function local_releasegate_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    if (has_capability('local/releasegate:view', $context)) {
        $url = new moodle_url('/local/releasegate/course.php', ['id' => $course->id]);
        $navigation->add(get_string('coursegate', 'local_releasegate'), $url, navigation_node::TYPE_SETTING);
    }
}

/**
 * Site-level status checks (Moodle 3.9+ Check API, shown in Site admin > Reports > Status).
 *
 * @return \core\check\check[]
 */
function local_releasegate_status_checks(): array {
    return [new \local_releasegate\check\cron_health()];
}
