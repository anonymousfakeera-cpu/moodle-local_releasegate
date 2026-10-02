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
 * Site release gate overview page.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();

// A course-context capability cannot be required at system level, so gate the page
// on the set of courses the viewer actually holds the capability in.
$allowedcourses = get_user_capability_course('local/releasegate:view', null, true, '');
if (empty($allowedcourses)) {
    throw new \core\exception\required_capability_exception(
        $context,
        'local/releasegate:view',
        'nopermissions',
        'error'
    );
}

$PAGE->set_url('/local/releasegate/index.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('report_siteoverview', 'local_releasegate'));
$PAGE->set_heading(get_string('report_siteoverview', 'local_releasegate'));

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \local_releasegate\reportbuilder\local\systemreports\site_overview::class,
    $context,
    'local_releasegate',
    'site_overview'
);
echo $report->output();

echo $OUTPUT->footer();
