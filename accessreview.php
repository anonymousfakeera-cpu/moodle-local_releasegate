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
 * Release gate access review page.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/releasegate:viewaccessreview', $context);

$PAGE->set_url('/local/releasegate/accessreview.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('report_accessreview', 'local_releasegate'));
$PAGE->set_heading(get_string('report_accessreview', 'local_releasegate'));

echo $OUTPUT->header();

$report = \core_reportbuilder\system_report_factory::create(
    \local_releasegate\reportbuilder\local\systemreports\access_review::class,
    $context,
    'local_releasegate',
    'access_review'
);
echo $report->output();

echo $OUTPUT->footer();
