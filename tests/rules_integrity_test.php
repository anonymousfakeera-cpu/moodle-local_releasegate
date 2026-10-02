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
 * Broken-vs-fixed tests for batch-1 integrity rules (RG-CMP-015/016, RG-GRD-015/016/018, RG-H5P-006, RG-ENR-006).
 *
 * @package    local_releasegate
 * @category   test
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate;

use local_releasegate\local\engine\course_context;
use local_releasegate\local\engine\registry;
use local_releasegate\local\engine\result;
use local_releasegate\local\runner;

/**
 * Integrity rule tests.
 *
 * @covers \local_releasegate\local\runner
 */
final class rules_integrity_test extends \advanced_testcase {
    /**
     * Evaluate one rule by id against a course.
     *
     * @param int $courseid Course id.
     * @param string $ruleid Rule id.
     * @return result
     */
    private function res(int $courseid, string $ruleid): result {
        global $DB;
        $ctx = new course_context($DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST));
        foreach (runner::evaluate($ctx) as $r) {
            if ($r->ruleid === $ruleid) {
                return $r;
            }
        }
        $this->fail("Rule $ruleid not found");
    }

    /**
     * Status shortcut.
     *
     * @param int $courseid Course id.
     * @param string $ruleid Rule id.
     * @return string
     */
    private function rule_status(int $courseid, string $ruleid): string {
        return $this->res($courseid, $ruleid)->status;
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
     * Set course-module fields. Generator drops some completion keys, so set them directly.
     *
     * @param int $cmid Course module id.
     * @param array $fields Fields.
     */
    private function set_cm(int $cmid, array $fields): void {
        global $DB;
        foreach ($fields as $field => $value) {
            $DB->set_field('course_modules', $field, $value, ['id' => $cmid]);
        }
    }

    /**
     * Set activity instance fields.
     *
     * @param string $table Table.
     * @param int $id Instance id.
     * @param array $fields Fields.
     */
    private function set_instance(string $table, int $id, array $fields): void {
        global $DB;
        foreach ($fields as $field => $value) {
            $DB->set_field($table, $field, $value, ['id' => $id]);
        }
    }

    /**
     * Add an activity completion criterion.
     *
     * @param \stdClass $course Course.
     * @param int $cmid Course module id.
     * @param string $modname Module name.
     * @param int $type Criterion type.
     */
    private function add_criterion(\stdClass $course, int $cmid, string $modname = 'page', int $type = 4): void {
        global $DB;
        $DB->insert_record('course_completion_criteria', (object) ['course' => $course->id,
            'criteriatype' => $type, 'module' => $modname, 'moduleinstance' => $cmid]);
    }

    /**
     * Add a self-enrolment instance. enrol_self has no data generator, so use the plugin API.
     *
     * @param int $courseid Course id.
     * @param int $customint2 Inactivity seconds.
     * @param int $status Enrol status.
     */
    private function add_self_enrol(int $courseid, int $customint2, int $status = 0): void {
        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $plugin = enrol_get_plugin('self');
        $instanceid = $plugin->add_instance($course, ['customint2' => $customint2, 'status' => $status]);
        $DB->update_record('enrol', (object) ['id' => $instanceid, 'customint2' => $customint2, 'status' => $status]);
    }

    /**
     * RG-CMP-015.
     */
    public function test_cmp015_self_completion(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        // No criteria at all: SKIP (RG-CMP-001 owns that).
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-CMP-015'));
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id, 'completion' => 1]);
        $this->add_criterion($course, $page->cmid);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-CMP-015'));
        $DB->insert_record(
            'course_completion_criteria',
            (object) ['course' => $course->id, 'criteriatype' => 1]
        );
        $this->assertSame(result::FAIL, $this->rule_status($course->id, 'RG-CMP-015'));
        // Self + activity criteria still FAILs.
        $this->assertSame(result::FAIL, $this->rule_status($course->id, 'RG-CMP-015'));
        // Completion disabled SKIPs.
        $DB->set_field('course', 'enablecompletion', 0, ['id' => $course->id]);
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-CMP-015'));
    }

    /**
     * RG-CMP-016.
     */
    public function test_cmp016_view_only_completion(): void {
        $this->resetAfterTest();
        $course = $this->course();
        // No assessable activity: SKIP.
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-CMP-016'));
        // View-only assignment FAILs.
        $assign = $this->getDataGenerator()->create_module(
            'assign',
            ['course' => $course->id, 'completion' => 2, 'completionview' => 1]
        );
        $this->set_cm($assign->cmid, ['completion' => 2, 'completionview' => 1,
            'completionpassgrade' => 0, 'completiongradeitemnumber' => null]);
        $this->set_instance('assign', $assign->id, ['completionsubmit' => 0]);
        $this->assertSame(result::FAIL, $this->rule_status($course->id, 'RG-CMP-016'));
        // Same assignment with completionpassgrade = 1 PASSes.
        $this->set_cm($assign->cmid, ['completionpassgrade' => 1]);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-CMP-016'));
        $this->set_cm($assign->cmid, ['completionpassgrade' => 0]);
        // Quiz with completionminattempts set PASSes.
        $quiz = $this->getDataGenerator()->create_module(
            'quiz',
            ['course' => $course->id, 'attempts' => 3, 'completion' => 2, 'completionview' => 1]
        );
        $this->set_cm($quiz->cmid, ['completion' => 2, 'completionview' => 1,
            'completionpassgrade' => 0, 'completiongradeitemnumber' => null]);
        $this->set_instance('quiz', $quiz->id, ['completionminattempts' => 1, 'completionattemptsexhausted' => 0]);
        $this->set_cm($assign->cmid, ['completion' => 1]);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-CMP-016'));
        // Completiongradeitemnumber = 0 is VALID ("receive a grade") and PASSes.
        $this->set_instance('quiz', $quiz->id, ['completionminattempts' => 0]);
        $this->set_cm($quiz->cmid, ['completiongradeitemnumber' => 0]);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-CMP-016'));
        // Manual-completion module is ignored: only manual left, so SKIP.
        $this->set_cm($quiz->cmid, ['completion' => 1, 'completiongradeitemnumber' => null]);
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-CMP-016'));
        // Hidden module is ignored.
        $this->set_cm($quiz->cmid, ['completion' => 2, 'completionview' => 1]);
        $this->set_cm($quiz->cmid, ['visible' => 0]);
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-CMP-016'));
    }

    /**
     * RG-GRD-015.
     */
    public function test_grd015_review_answers(): void {
        $this->resetAfterTest();
        $course = $this->course();
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-GRD-015'));
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'attempts' => 3]);
        // Only AFTER_CLOSE (0x00010) set PASSes.
        $this->set_instance('quiz', $quiz->id, ['reviewrightanswer' => 0x00010, 'reviewcorrectness' => 0x00010]);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-GRD-015'));
        // DURING alone FAILs.
        $this->set_instance('quiz', $quiz->id, ['reviewrightanswer' => 0x10000, 'reviewcorrectness' => 0x00010]);
        $this->assertSame(result::FAIL, $this->rule_status($course->id, 'RG-GRD-015'));
        // LATER_WHILE_OPEN alone FAILs.
        $this->set_instance('quiz', $quiz->id, ['reviewrightanswer' => 0x00100, 'reviewcorrectness' => 0x00010]);
        $this->assertSame(result::FAIL, $this->rule_status($course->id, 'RG-GRD-015'));
        // Reviewcorrectness alone FAILs.
        $this->set_instance('quiz', $quiz->id, ['reviewrightanswer' => 0x00010, 'reviewcorrectness' => 0x10000]);
        $this->assertSame(result::FAIL, $this->rule_status($course->id, 'RG-GRD-015'));
    }

    /**
     * RG-GRD-016.
     */
    public function test_grd016_unlimited_attempts(): void {
        $this->resetAfterTest();
        $course = $this->course();
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-GRD-016'));
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'attempts' => 0]);
        $this->set_instance('quiz', $quiz->id, ['attempts' => 0]);
        $this->assertSame(result::FAIL, $this->rule_status($course->id, 'RG-GRD-016'));
        $this->set_instance('quiz', $quiz->id, ['attempts' => 3]);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-GRD-016'));
        // Hidden quiz is ignored, so SKIP.
        $this->set_cm($quiz->cmid, ['visible' => 0]);
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-GRD-016'));
    }

    /**
     * RG-GRD-018.
     */
    public function test_grd018_min_attempts_reachable(): void {
        $this->resetAfterTest();
        $course = $this->course();
        $quiz = $this->getDataGenerator()->create_module('quiz', ['course' => $course->id, 'attempts' => 3]);
        $this->set_cm($quiz->cmid, ['completion' => 2]);
        $this->set_instance('quiz', $quiz->id, ['attempts' => 3, 'completionminattempts' => 5]);
        $this->assertSame(result::FAIL, $this->rule_status($course->id, 'RG-GRD-018'));
        $this->set_instance('quiz', $quiz->id, ['attempts' => 3, 'completionminattempts' => 3]);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-GRD-018'));
        // Attempts 0 (unlimited) PASSes.
        $this->set_instance('quiz', $quiz->id, ['attempts' => 0, 'completionminattempts' => 5]);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-GRD-018'));
        // Completion != 2 SKIPs.
        $this->set_cm($quiz->cmid, ['completion' => 1]);
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-GRD-018'));
        // No quiz at all SKIPs.
        $course2 = $this->course();
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course2->id, 'RG-GRD-018'));
    }

    /**
     * RG-H5P-006.
     */
    public function test_h5p006_manual_grading(): void {
        $this->resetAfterTest();
        $course = $this->course();
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-H5P-006'));
        $h5p = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course->id]);
        $this->set_instance('h5pactivity', $h5p->id, ['grademethod' => 0]);
        $this->set_cm($h5p->cmid, ['completion' => 2, 'completionpassgrade' => 1,
            'completiongradeitemnumber' => null]);
        $this->assertSame(result::FAIL, $this->rule_status($course->id, 'RG-H5P-006'));
        // Automatic method PASSes.
        $this->set_instance('h5pactivity', $h5p->id, ['grademethod' => 1]);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-H5P-006'));
        // Manual + view-only completion (no pass grade, no grade item) PASSes by design.
        $this->set_instance('h5pactivity', $h5p->id, ['grademethod' => 0]);
        $this->set_cm($h5p->cmid, ['completionpassgrade' => 0, 'completiongradeitemnumber' => null,
            'completionview' => 1]);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-H5P-006'));
    }

    /**
     * RG-ENR-006.
     */
    public function test_enr006_self_inactivity(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->course();
        $DB->delete_records('enrol', ['courseid' => $course->id, 'enrol' => 'self']);
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-ENR-006'));
        $this->add_self_enrol($course->id, 86400 * 30);
        $r = $this->res($course->id, 'RG-ENR-006');
        $this->assertSame(result::FAIL, $r->status);
        $this->assertStringContainsString('30', $r->message);
        // Disabled self instance SKIPs.
        $DB->delete_records('enrol', ['courseid' => $course->id, 'enrol' => 'self']);
        $this->add_self_enrol($course->id, 86400 * 30, 1);
        $this->assertSame(result::NOTAPPLICABLE, $this->rule_status($course->id, 'RG-ENR-006'));
        // Customint2 = 0 PASSes.
        $DB->delete_records('enrol', ['courseid' => $course->id, 'enrol' => 'self']);
        $this->add_self_enrol($course->id, 0);
        $this->assertSame(result::PASS, $this->rule_status($course->id, 'RG-ENR-006'));
    }

    /**
     * Meta: ids unique and well-formed.
     */
    public function test_meta_ids_unique_and_shaped(): void {
        $ids = array_map(function ($r) {
            return $r->id();
        }, registry::rules());
        $this->assertSame(count($ids), count(array_unique($ids)));
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^RG-[A-Z0-9]+-\d{3}$/', $id);
        }
    }

    /**
     * Meta: every rule has its fail string.
     */
    public function test_meta_lang_strings_exist(): void {
        $manager = get_string_manager();
        foreach (registry::rules() as $rule) {
            $this->assertTrue(
                $manager->string_exists('rule_' . $rule->id() . '_fail', 'local_releasegate'),
                'Missing rule_' . $rule->id() . '_fail'
            );
        }
    }

    /**
     * Meta: every class file is registered.
     */
    public function test_meta_all_files_registered(): void {
        global $CFG;
        $files = glob($CFG->dirroot . '/local/releasegate/classes/local/rule/*.php');
        $this->assertNotEmpty($files);
        $registered = [];
        foreach (registry::rules() as $rule) {
            $ref = new \ReflectionClass($rule);
            $registered[basename($ref->getFileName())] = true;
        }
        foreach ($files as $file) {
            $this->assertArrayHasKey(basename($file), $registered, basename($file) . ' is not registered');
        }
    }

    /**
     * Meta: every registered rule is covered by a test.
     */
    public function test_meta_every_rule_has_test(): void {
        global $CFG;
        $haystack = '';
        foreach (glob($CFG->dirroot . '/local/releasegate/tests/*.php') as $file) {
            $haystack .= file_get_contents($file);
        }
        foreach (registry::rules() as $rule) {
            $this->assertStringContainsString($rule->id(), $haystack, $rule->id() . ' has no test');
        }
    }

    /**
     * Meta: ruleset version is a non-empty string.
     */
    public function test_meta_ruleset_version(): void {
        $this->assertIsString(registry::RULESET_VERSION);
        $this->assertNotSame('', registry::RULESET_VERSION);
    }
}
