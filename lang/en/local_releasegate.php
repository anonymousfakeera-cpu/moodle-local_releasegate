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
 * Language strings.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Release Gate';
$string['privacy:metadata'] = 'The Release Gate plugin stores QA scan results about courses. It stores only a pseudonymous reference to the user who triggered a scan or waiver.';
$string['privacy:metadata:audit'] = 'Tamper-evident log of gate actions.';
$string['privacy:metadata:audit:actorref'] = 'A one-way pseudonymous reference to the acting user.';
$string['privacy:metadata:audit:action'] = 'The action that was performed.';
$string['privacy:metadata:audit:timecreated'] = 'When the action happened.';
$string['releasegate:view'] = 'View course release gate results';
$string['releasegate:viewresults'] = 'View release gate rule results';
$string['releasegate:viewevidence'] = 'View release gate evidence detail';
$string['releasegate:run'] = 'Run the release gate scan';
$string['releasegate:export'] = 'Export release gate results';
$string['releasegate:viewaudit'] = 'View the release gate audit log';
$string['releasegate:managesettings'] = 'Manage release gate settings';
$string['releasegate:waive'] = 'Waive a release gate rule';
$string['releasegate:approve'] = 'Approve a course release';
$string['releasegate:viewaccessreview'] = 'View the release gate access review';
$string['releasegate:viewaccessusers'] = 'View effective user counts in access review';
$string['eventgate_viewed'] = 'Release gate viewed';
$string['eventevidence_viewed'] = 'Release gate evidence viewed';
$string['eventresults_exported'] = 'Release gate results exported';
$string['eventsettings_changed'] = 'Release gate setting changed';
$string['report_courseresults'] = 'Course gate results';
$string['noresults'] = 'No gate results match these filters for this course.';
$string['report_siteoverview'] = 'Release gate site overview';
$string['nooverviewrows'] = 'No courses with a release gate verdict are visible to you.';
$string['evidencetoggle'] = 'Show evidence';
$string['noevidence'] = 'No evidence detail was recorded for this scan.';
$string['entity_run'] = 'Release gate scan';
$string['entity_result'] = 'Release gate rule result';
$string['verdict'] = 'Verdict';
$string['evidence'] = 'Evidence';
$string['timecreated'] = 'Time scanned';
$string['rulesetversion'] = 'Ruleset version';
$string['fingerprint'] = 'Fingerprint';
$string['severity_blocker'] = 'Blocker';
$string['severity_critical'] = 'Critical';
$string['severity_major'] = 'Major';
$string['severity_minor'] = 'Minor';
$string['area_completion'] = 'Completion';
$string['area_course'] = 'Course';
$string['area_enrolment'] = 'Enrolment';
$string['area_quiz'] = 'Quiz';
$string['area_grades'] = 'Grades';
$string['area_scorm'] = 'SCORM';
$string['area_restrictions'] = 'Restrictions';
$string['verdict_ready'] = 'Ready';
$string['verdict_conditional'] = 'Conditional';
$string['verdict_blocked'] = 'Blocked';
$string['verdict_insufficient'] = 'Insufficient data';
$string['verdict_notscanned'] = 'Not scanned';
$string['error_generic'] = 'This rule could not be evaluated. No detail is shown here.';
$string['report_accessreview'] = 'Release gate access review';
$string['noaccessreviewrows'] = 'No Release Gate capability assignments match these filters.';
$string['entity_role_capability'] = 'Role capability assignment';
$string['capability'] = 'Capability';
$string['role'] = 'Role';
$string['context'] = 'Context';
$string['contextlevel'] = 'Context level';
$string['permission'] = 'Permission';
$string['permission_allow'] = 'Allow';
$string['permission_prohibit'] = 'Prohibit';
$string['permission_notset'] = 'Not set';
$string['effectiveusers'] = 'Effective users';
$string['checkpermissions'] = 'Check permissions';
$string['trustsheet'] = 'Release gate trust sheet';
$string['trustsheet_intro'] = 'This page states what the Release Gate plugin reads, what it never reads, who can see results by default, and which events it logs.';
$string['trust_read_heading'] = 'Data read (course configuration)';
$string['trust_read_note'] = 'The scan reads course configuration only. No personal or learner data is read.';
$string['trust_table'] = 'Table';
$string['trust_columns'] = 'Columns';
$string['trust_never_heading'] = 'Data never read';
$string['trust_never_note'] = 'The plugin never reads these tables and never writes outside its own local_rg_* tables.';
$string['trust_access_heading'] = 'Default access';
$string['trust_access_note'] = 'After install, only the roles below can see anything. Administrators can change this per role, cohort, category or course.';
$string['trust_capability'] = 'Capability';
$string['trust_default_roles'] = 'Default roles';
$string['trust_events_heading'] = 'Events logged';
$string['trust_events_note'] = 'Viewing a gate, opening evidence, exporting and changing a setting each raise a Moodle event. Scans are also written to the hash-chained audit log.';
$string['trust_event'] = 'Event';
$string['trust_event_when'] = 'When';
$string['trust_event_gate_viewed'] = 'A user viewed the gate for a course.';
$string['trust_event_evidence_viewed'] = 'A user opened evidence detail.';
$string['trust_event_settings_changed'] = 'An administrator changed a setting.';
$string['trust_event_results_exported'] = 'A user exported results. Not triggered by plugin code today; see the known limitations.';
$string['trust_event_scan'] = 'A scan ran. Also written to the hash-chained audit.';
$string['report_auditlog'] = 'Release gate audit log';
$string['noauditrows'] = 'No audit entries match these filters.';
$string['entity_audit'] = 'Audit entry';
$string['action'] = 'Action';
$string['course'] = 'Course';
$string['system'] = 'System';
$string['action_scan'] = 'Scan run';
$string['verifychain'] = 'Verify chain';
$string['audit_intact'] = 'The audit chain is intact.';
$string['audit_broken'] = 'The audit chain could not be verified. The log may have been changed outside the application.';
$string['status_pass'] = 'Pass';
$string['status_fail'] = 'Fail';
$string['status_notevaluated'] = 'Not evaluated';
$string['status_notapplicable'] = 'Not applicable';
$string['status_error'] = 'Error';
$string['dashboard'] = 'Release gate dashboard';
$string['coursegate'] = 'Release gate for this course';
$string['runscan'] = 'Run scan now';
$string['norun'] = 'No scan has been run for this course yet.';
$string['coverage'] = 'Coverage';
$string['rule'] = 'Rule';
$string['area'] = 'Area';
$string['severity'] = 'Severity';
$string['message'] = 'Finding';
$string['status'] = 'Status';
$string['scancourse'] = 'Scan course for release readiness';
$string['sweepcourses'] = 'Sweep visible courses for release readiness';
$string['check_cron'] = 'Release gate: cron health';
$string['check_cron_ok'] = 'Cron ran recently.';
$string['check_cron_bad'] = 'Cron has not run for over an hour. Gate scans and monitoring are not running.';

// Rule titles and messages.
$string['rule_RG-CRS-001'] = 'Course completion is enabled';
$string['rule_RG-CRS-001_fail'] = 'Completion tracking is disabled for this course, so learners will never see completion.';
$string['rule_RG-ENR-001'] = 'At least one enrolment method is enabled';
$string['rule_RG-ENR-001_fail'] = 'No enrolment method is enabled, so nobody can enter the course.';
$string['rule_RG-CMP-001'] = 'Course completion criteria are defined';
$string['rule_RG-CMP-001_fail'] = 'Completion is enabled but no completion criteria are defined, so the course can never complete.';
$string['rule_RG-CMP-002'] = 'Activity criteria use activities that track completion';
$string['rule_RG-CMP-002_fail'] = 'Completion criteria reference activities with completion tracking turned off: {$a}.';
$string['rule_RG-CMP-003'] = 'Completion criteria reference existing activities';
$string['rule_RG-CMP-003_fail'] = 'Completion criteria reference activities that no longer exist or are being deleted: {$a}.';
$string['rule_RG-GRD-001'] = 'Pass-grade completion has a pass grade';
$string['rule_RG-GRD-001_fail'] = 'Activities require a passing grade for completion but the grade to pass is 0: {$a}.';
$string['rule_RG-QUZ-001'] = 'Quizzes contain questions';
$string['rule_RG-QUZ-001_fail'] = 'Quizzes have no questions: {$a}.';
$string['rule_RG-QUZ-002'] = 'Quiz pass grade is achievable';
$string['rule_RG-QUZ-002_fail'] = 'Quiz grade to pass is higher than the maximum grade, so nobody can pass: {$a}.';
$string['rule_RG-SCM-001'] = 'SCORM packages have a launchable item';
$string['rule_RG-SCM-001_fail'] = 'SCORM activities have no launchable item: {$a}.';
$string['rule_RG-RST-001'] = 'Access restrictions reference existing activities';
$string['rule_RG-RST-001_fail'] = 'Access restrictions point to activities that no longer exist: {$a}.';
