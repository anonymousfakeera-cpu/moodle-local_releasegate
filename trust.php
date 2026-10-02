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
 * Release gate trust sheet page.
 *
 * Static, localised summary of what the plugin reads, never reads, who can see
 * it by default, and which events it logs. The table and column lists below were
 * taken from the rule and engine source, not invented.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/releasegate:viewaccessreview', $context);

$PAGE->set_url('/local/releasegate/trust.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('trustsheet', 'local_releasegate'));
$PAGE->set_heading(get_string('trustsheet', 'local_releasegate'));

// Tier A: course configuration read by the rules and the collector (from code).
$readtables = [
    ['name' => 'course', 'columns' => 'id, enablecompletion'],
    ['name' => 'course_modules', 'columns' => 'id, course, module, instance, deletioninprogress, availability'],
    ['name' => 'modules', 'columns' => 'id, name'],
    ['name' => 'course_completion_criteria', 'columns' => 'course, criteriatype, moduleinstance'],
    ['name' => 'enrol', 'columns' => 'courseid, status'],
    ['name' => 'grade_items', 'columns' => 'courseid, itemtype, itemmodule, iteminstance, itemnumber, gradepass'],
    ['name' => 'quiz', 'columns' => 'id, grade'],
    ['name' => 'quiz_slots', 'columns' => 'quizid'],
    ['name' => 'scorm_scoes', 'columns' => 'scorm, launch'],
    ['name' => 'task_scheduled', 'columns' => 'lastruntime'],
];

// Tier C: never read by this plugin.
$denylist = [
    'user',
    'user_enrolments',
    'grade_grades',
    'course_modules_completion',
    'course_completions',
    'quiz_attempts',
    'logstore_standard_log',
];

// Default access, mirroring db/access.php.
$defaultaccess = [
    ['capability' => 'local/releasegate:view', 'roles' => 'manager, editingteacher'],
    ['capability' => 'local/releasegate:viewresults', 'roles' => 'manager, editingteacher'],
    ['capability' => 'local/releasegate:viewevidence', 'roles' => 'manager'],
    ['capability' => 'local/releasegate:run', 'roles' => 'manager'],
    ['capability' => 'local/releasegate:export', 'roles' => 'manager'],
    ['capability' => 'local/releasegate:viewaudit', 'roles' => 'manager'],
    ['capability' => 'local/releasegate:managesettings', 'roles' => 'manager'],
    ['capability' => 'local/releasegate:waive', 'roles' => 'manager'],
    ['capability' => 'local/releasegate:approve', 'roles' => 'manager'],
    ['capability' => 'local/releasegate:viewaccessreview', 'roles' => 'manager'],
    ['capability' => 'local/releasegate:viewaccessusers', 'roles' => 'manager'],
];

$events = [
    [
        'name' => 'local_releasegate\\event\\gate_viewed',
        'when' => get_string('trust_event_gate_viewed', 'local_releasegate'),
    ],
    [
        'name' => 'local_releasegate\\event\\evidence_viewed',
        'when' => get_string('trust_event_evidence_viewed', 'local_releasegate'),
    ],
    [
        'name' => 'local_releasegate\\event\\settings_changed',
        'when' => get_string('trust_event_settings_changed', 'local_releasegate'),
    ],
    [
        'name' => 'local_releasegate\\event\\results_exported',
        'when' => get_string('trust_event_results_exported', 'local_releasegate'),
    ],
    [
        'name' => 'audit: scan',
        'when' => get_string('trust_event_scan', 'local_releasegate'),
    ],
];

$data = [
    'intro' => get_string('trustsheet_intro', 'local_releasegate'),
    'readheading' => get_string('trust_read_heading', 'local_releasegate'),
    'readnote' => get_string('trust_read_note', 'local_releasegate'),
    'coltable' => get_string('trust_table', 'local_releasegate'),
    'colcolumns' => get_string('trust_columns', 'local_releasegate'),
    'readtables' => $readtables,
    'neverheading' => get_string('trust_never_heading', 'local_releasegate'),
    'nevernote' => get_string('trust_never_note', 'local_releasegate'),
    'denylist' => array_map(static fn(string $table): array => ['name' => $table], $denylist),
    'accessheading' => get_string('trust_access_heading', 'local_releasegate'),
    'accessnote' => get_string('trust_access_note', 'local_releasegate'),
    'colcapability' => get_string('trust_capability', 'local_releasegate'),
    'colroles' => get_string('trust_default_roles', 'local_releasegate'),
    'defaultaccess' => $defaultaccess,
    'eventsheading' => get_string('trust_events_heading', 'local_releasegate'),
    'eventsnote' => get_string('trust_events_note', 'local_releasegate'),
    'colevent' => get_string('trust_event', 'local_releasegate'),
    'colwhen' => get_string('trust_event_when', 'local_releasegate'),
    'events' => $events,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_releasegate/trust_sheet', $data);
echo $OUTPUT->footer();
