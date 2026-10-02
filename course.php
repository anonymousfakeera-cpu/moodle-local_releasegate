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
 * Course release gate page.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_login($course);
$context = context_course::instance($id);
require_capability('local/releasegate:view', $context);

$PAGE->set_url('/local/releasegate/course.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('coursegate', 'local_releasegate'));
$PAGE->set_heading($course->fullname);

if (optional_param('run', 0, PARAM_BOOL) && confirm_sesskey()) {
    require_capability('local/releasegate:run', $context);
    \local_releasegate\local\runner::run($id, (int) $USER->id);
    redirect($PAGE->url);
}

// Allowlisted columns only; never select actorref.
// "Latest" is defined plugin-wide as the highest id for the course (id is monotonic,
// one row per scan). site_overview.php uses the same definition via MAX(id).
$run = $DB->get_record_sql(
    "SELECT id, verdict, coverage, rulesetversion, fingerprint, timecreated
       FROM {local_rg_run}
      WHERE courseid = :c
   ORDER BY id DESC",
    ['c' => $id],
    IGNORE_MULTIPLE
);

$canrun = has_capability('local/releasegate:run', $context);
$canviewevidence = has_capability('local/releasegate:viewevidence', $context);
$showevevidence = $run && $canviewevidence && optional_param('evidence', 0, PARAM_BOOL) && confirm_sesskey();

// Access log: viewing the gate is always recorded.
\local_releasegate\event\gate_viewed::create([
    'context' => $context,
    'courseid' => $id,
    'other' => ['runid' => $run ? (int) $run->id : 0],
])->trigger();

if ($showevevidence) {
    \local_releasegate\event\evidence_viewed::create([
        'context' => $context,
        'courseid' => $id,
        'other' => ['runid' => (int) $run->id],
    ])->trigger();
}

$data = [
    'norun' => $run ? '' : get_string('norun', 'local_releasegate'),
    'hasrun' => (bool) $run,
    'canrun' => $canrun,
    'runscan' => get_string('runscan', 'local_releasegate'),
    'hasreport' => false,
    'canviewevidence' => false,
];

if ($canrun) {
    $data['runurl'] = (new moodle_url($PAGE->url, ['run' => 1, 'sesskey' => sesskey()]))->out(false);
}

if ($run) {
    $classes = [
        'READY' => 'alert-success',
        'CONDITIONAL' => 'alert-warning',
        'BLOCKED' => 'alert-danger',
        'INSUFFICIENT DATA' => 'alert-secondary',
    ];
    $keys = [
        'READY' => 'ready',
        'CONDITIONAL' => 'conditional',
        'BLOCKED' => 'blocked',
        'INSUFFICIENT DATA' => 'insufficient',
    ];
    $data['verdict'] = get_string('verdict_' . ($keys[$run->verdict] ?? 'insufficient'), 'local_releasegate');
    $data['verdictclass'] = $classes[$run->verdict] ?? 'alert-secondary';
    $data['coverage'] = (int) $run->coverage;
    $data['coverage_label'] = get_string('coverage', 'local_releasegate');
    $data['rulesetversion'] = s($run->rulesetversion);
    $data['rulesetversion_label'] = get_string('rulesetversion', 'local_releasegate');
    $data['timecreated'] = userdate($run->timecreated);
    $data['timecreated_label'] = get_string('timecreated', 'local_releasegate');
    $data['fingerprint'] = s(substr($run->fingerprint, 0, 12)) . '...';
    $data['fingerprint_label'] = get_string('fingerprint', 'local_releasegate');

    if (has_capability('local/releasegate:viewresults', $context)) {
        $report = \core_reportbuilder\system_report_factory::create(
            \local_releasegate\reportbuilder\local\systemreports\course_results::class,
            $context,
            'local_releasegate',
            'course_results',
            0,
            ['runid' => (int) $run->id]
        );
        $data['hasreport'] = true;
        $data['reporthtml'] = $report->output();
    }

    if ($canviewevidence) {
        $data['canviewevidence'] = true;
        $data['evidencetoggle'] = get_string('evidencetoggle', 'local_releasegate');
        $data['evidenceurl'] = (new moodle_url($PAGE->url, ['evidence' => 1, 'sesskey' => sesskey()]))->out(false);
        $data['showevevidence'] = $showevevidence;
        $data['noevidence'] = get_string('noevidence', 'local_releasegate');
        $data['evidencelist'] = [];

        if ($showevevidence) {
            foreach (
                $DB->get_records(
                    'local_rg_result',
                    ['runid' => $run->id],
                    'id ASC',
                    'id, ruleid, status, message, evidence'
                ) as $result
            ) {
                if ($result->evidence === null || $result->evidence === '' || $result->evidence === '[]') {
                    continue;
                }
                $decoded = json_decode($result->evidence, true);
                $pretty = (json_last_error() === JSON_ERROR_NONE)
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                    : $result->evidence;
                $label = get_string_manager()->string_exists('rule_' . $result->ruleid, 'local_releasegate')
                    ? get_string('rule_' . $result->ruleid, 'local_releasegate') . ' (' . $result->ruleid . ')'
                    : $result->ruleid;
                // Never show the raw message of a crashed rule.
                $message = ($result->status === 'ERROR')
                    ? get_string('error_generic', 'local_releasegate')
                    : (string) $result->message;
                $data['evidencelist'][] = [
                    'rule' => $label,
                    'hasmessage' => ($message !== ''),
                    'message' => $message,
                    'evidence' => $pretty,
                ];
            }
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_releasegate/course_page', $data);
echo $OUTPUT->footer();
