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
 * Broken-vs-fixed tests for every Phase 1 rule, plus verdict, runner and audit tests.
 *
 * @package    local_releasegate
 * @category   test
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate;

use local_releasegate\local\audit;
use local_releasegate\local\engine\course_context;
use local_releasegate\local\engine\result;
use local_releasegate\local\engine\rule_base;
use local_releasegate\local\engine\verdict;
use local_releasegate\local\runner;

/**
 * Gate tests.
 *
 * @covers \local_releasegate\local\runner
 */
final class gate_test extends \advanced_testcase {
    /**
     * Evaluate one rule by id against a course.
     *
     * @param int $courseid Course id.
     * @param string $ruleid Rule id.
     * @return string Status.
     */
    private function status(int $courseid, string $ruleid): string {
        global $DB;
        $ctx = new course_context($DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST));
        foreach (runner::evaluate($ctx) as $r) {
            if ($r->ruleid === $ruleid) {
                return $r->status;
            }
        }
        $this->fail("Rule $ruleid not found");
    }

    /**
     * Course with completion tracking on.
     *
     * @return \stdClass
     */
    private function course(): \stdClass {
        global $CFG;
        $CFG->enablecompletion = 1;
        return $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
    }

    /**
     * Add an activity completion criterion for a course module.
     *
     * @param \stdClass $course Course.
     * @param int $cmid Course module id (may be nonexistent on purpose).
     * @param string $modname Module name.
     */
    private function add_criterion(\stdClass $course, int $cmid, string $modname = 'page'): void {
        global $DB;
        $DB->insert_record('course_completion_criteria', (object) ['course' => $course->id,
            'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY, 'module' => $modname, 'moduleinstance' => $cmid]);
    }

    /**
     * RG-CRS-001.
     */
    public function test_course_completion_enabled(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-CRS-001'));
        $DB->set_field('course', 'enablecompletion', 0, ['id' => $course->id]);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-CRS-001'));
    }

    /**
     * RG-ENR-001.
     */
    public function test_enrolment_enabled(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-ENR-001'));
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['courseid' => $course->id]);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-ENR-001'));
    }

    /**
     * RG-CMP-001.
     */
    public function test_criteria_defined(): void {
        $this->resetAfterTest();
        $course = $this->course();
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-CMP-001'));
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);
        $this->add_criterion($course, $page->cmid);
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-CMP-001'));
    }

    /**
     * RG-CMP-001 is not applicable when completion is off.
     */
    public function test_criteria_defined_skipped_when_completion_off(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 0]);
        $this->assertSame(result::NOTAPPLICABLE, $this->status($course->id, 'RG-CMP-001'));
    }

    /**
     * RG-CMP-002: criterion points at an activity with completion tracking off.
     */
    public function test_criteria_activity_tracks_completion(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 0]);
        $this->add_criterion($course, $page->cmid);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-CMP-002'));
        $DB->set_field('course_modules', 'completion', COMPLETION_TRACKING_MANUAL, ['id' => $page->cmid]);
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-CMP-002'));
    }

    /**
     * RG-CMP-003: criterion points at a missing or deleting activity.
     */
    public function test_criteria_activity_exists(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);
        $this->add_criterion($course, $page->cmid);
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-CMP-003'));
        $DB->set_field('course_modules', 'deletioninprogress', 1, ['id' => $page->cmid]);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-CMP-003'));
        $DB->set_field('course_modules', 'deletioninprogress', 0, ['id' => $page->cmid]);
        $this->add_criterion($course, 987654);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-CMP-003'));
    }

    /**
     * RG-GRD-001 and RG-QUZ-002: pass grade configuration.
     */
    public function test_passgrade_and_quiz_passgrade(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'grade' => 10,
            'completion' => COMPLETION_TRACKING_AUTOMATIC, 'completionusegrade' => 1, 'completionpassgrade' => 1]);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-GRD-001'));

        $gradeitem = ['courseid' => $course->id, 'itemmodule' => 'quiz', 'iteminstance' => $quiz->id, 'itemnumber' => 0];
        $DB->set_field('grade_items', 'gradepass', 5, $gradeitem);
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-GRD-001'));
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-QUZ-002'));

        $DB->set_field('grade_items', 'gradepass', 50, $gradeitem);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-QUZ-002'));
    }

    /**
     * RG-QUZ-001.
     */
    public function test_quiz_has_questions(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        $this->assertSame(result::NOTAPPLICABLE, $this->status($course->id, 'RG-QUZ-001'));
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-QUZ-001'));
        $DB->insert_record('quiz_slots', (object) ['quizid' => $quiz->id, 'slot' => 1, 'page' => 1, 'maxmark' => 1]);
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-QUZ-001'));
    }

    /**
     * RG-SCM-001.
     */
    public function test_scorm_launchable(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->course();
        $scorm = $this->getDataGenerator()->create_module('scorm', ['course' => $course->id]);
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-SCM-001'));
        $DB->delete_records('scorm_scoes', ['scorm' => $scorm->id]);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-SCM-001'));
    }

    /**
     * RG-RST-001.
     */
    public function test_restriction_targets_exist(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        $first = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);
        $second = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);
        $this->assertSame(result::NOTAPPLICABLE, $this->status($course->id, 'RG-RST-001'));

        $json = function (int $cmid): string {
            return json_encode(['op' => '&', 'c' => [['type' => 'completion', 'cm' => $cmid, 'e' => 1]], 'showc' => [true]]);
        };
        $DB->set_field('course_modules', 'availability', $json($first->cmid), ['id' => $second->cmid]);
        $this->assertSame(result::PASS, $this->status($course->id, 'RG-RST-001'));
        $DB->set_field('course_modules', 'availability', $json(987654), ['id' => $second->cmid]);
        $this->assertSame(result::FAIL, $this->status($course->id, 'RG-RST-001'));
    }

    /**
     * Verdict rules.
     */
    public function test_verdict(): void {
        $blocker = new local\rule\course();
        $major = new class extends rule_base {
            /**
             * Rule id.
             *
             * @return string
             */
            public function id(): string {
                return 'RG-TST-001';
            }
            /**
             * Rule area.
             *
             * @return string
             */
            public function area(): string {
                return 'test';
            }
            /**
             * Rule severity.
             *
             * @return string
             */
            public function severity(): string {
                return self::MAJOR;
            }
            /**
             * Evaluate.
             * @param course_context $ctx Ctx.
             * @return result
             */
            public function evaluate(course_context $ctx): result {
                return $this->pass();
            }
        };
        $pass = new result($major, result::PASS);
        $this->assertSame(verdict::READY, verdict::compute([$pass, $pass]));
        $this->assertSame(verdict::CONDITIONAL, verdict::compute([$pass, new result($major, result::FAIL, 'x')]));
        $this->assertSame(verdict::BLOCKED, verdict::compute([$pass, new result($blocker, result::FAIL, 'x')]));
        // Low coverage is never READY.
        $err = new result($major, result::ERROR, 'boom');
        $this->assertSame(verdict::INSUFFICIENT, verdict::compute([$pass, $err, $err]));
        // N/A does not hurt coverage.
        $na = new result($major, result::NOTAPPLICABLE);
        $this->assertSame(verdict::READY, verdict::compute([$pass, $na, $na, $na]));
        $this->assertSame(verdict::INSUFFICIENT, verdict::compute([]));
    }

    /**
     * Full runs: broken course is BLOCKED, fixed course is READY, fingerprints are deterministic, audit chain is intact.
     */
    public function test_runner_end_to_end(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 0]);
        $broken = runner::run($course->id);
        $this->assertSame(verdict::BLOCKED, $broken->verdict);

        $course = $this->course();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);
        $this->add_criterion($course, $page->cmid);
        $one = runner::run($course->id);
        $two = runner::run($course->id);
        $this->assertSame(verdict::READY, $one->verdict);
        $this->assertSame($one->fingerprint, $two->fingerprint);
        $this->assertSame(2, $DB->count_records('local_rg_run', ['courseid' => $course->id]));
        $this->assertSame(count($one->results), $DB->count_records('local_rg_result', ['runid' => $one->id]));
        $this->assertTrue(audit::verify());
    }

    /**
     * Tampering with the audit log is detected.
     */
    public function test_audit_chain_detects_tampering(): void {
        global $DB;
        $this->resetAfterTest();
        $first = audit::log('a', 1, 0);
        audit::log('b', 1, 0);
        audit::log('c', 1, 0);
        $this->assertTrue(audit::verify());
        $DB->set_field('local_rg_audit', 'action', 'edited', ['id' => $first]);
        $this->assertFalse(audit::verify());
    }

    /**
     * The audit log never stores the raw user id.
     */
    public function test_audit_actor_is_pseudonymous(): void {
        $this->resetAfterTest();
        $this->assertNotSame('42', audit::actorref(42));
        $this->assertSame(64, strlen(audit::actorref(42)));
        $this->assertSame(audit::actorref(42), audit::actorref(42));
        $this->assertNotSame(audit::actorref(42), audit::actorref(43));
    }

    /**
     * Scan task skips deleted courses and scans existing ones.
     */
    public function test_scan_task(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        $task = new task\scan_course();
        $task->set_custom_data(['courseid' => $course->id]);
        $task->execute();
        $this->assertSame(1, $DB->count_records('local_rg_run', ['courseid' => $course->id]));
        $task->set_custom_data(['courseid' => 99999]);
        $task->execute();
        $this->assertSame(1, $DB->count_records('local_rg_run'));
    }
}
