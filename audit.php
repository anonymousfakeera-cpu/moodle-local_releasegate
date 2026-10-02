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
 * Release gate audit page.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/releasegate:viewaudit', $context);

$PAGE->set_url('/local/releasegate/audit.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('report_auditlog', 'local_releasegate'));
$PAGE->set_heading(get_string('report_auditlog', 'local_releasegate'));

$verification = null;
if (optional_param('verify', 0, PARAM_BOOL) && confirm_sesskey()) {
    // The verify method returns a boolean only; never surface any internal detail.
    $verification = \local_releasegate\local\audit::verify();
}

echo $OUTPUT->header();

if ($verification !== null) {
    $message = $verification
        ? get_string('audit_intact', 'local_releasegate')
        : get_string('audit_broken', 'local_releasegate');
    echo $OUTPUT->notification($message, $verification ? 'success' : 'error');
}

echo $OUTPUT->single_button(
    new moodle_url($PAGE->url, ['verify' => 1, 'sesskey' => sesskey()]),
    get_string('verifychain', 'local_releasegate')
);

$report = \core_reportbuilder\system_report_factory::create(
    \local_releasegate\reportbuilder\local\systemreports\audit_log::class,
    $context,
    'local_releasegate',
    'audit_log'
);
echo $report->output();

echo $OUTPUT->footer();
