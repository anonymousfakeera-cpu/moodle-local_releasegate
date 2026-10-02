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
 * Access-model tests: prove row scoping and capability rules on a real database.
 *
 * These tests build the actual system reports and read the rows they would render,
 * so they assert the query scoping, not only can_view().
 *
 * @package    local_releasegate
 * @category   test
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate;

use context_course;
use context_system;
use core_reportbuilder\exception\report_access_exception;
use core_reportbuilder\manager;
use core_reportbuilder\system_report_factory;
use local_releasegate\reportbuilder\local\systemreports\course_results;
use local_releasegate\reportbuilder\local\systemreports\site_overview;

/**
 * Access scope tests.
 *
 * @covers \local_releasegate\reportbuilder\local\systemreports\site_overview
 * @covers \local_releasegate\reportbuilder\local\systemreports\course_results
 */
final class access_scope_test extends \advanced_testcase {
    /**
     * Load the test-only system report table fixture.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        require_once(__DIR__ . '/fixtures/testable_system_report_table.php');
        parent::setUpBeforeClass();
    }

    /**
     * Read the rows a system report would render for the given context and parameters.
     *
     * @param string $source System report class.
     * @param \context $context Report context.
     * @param array $parameters Report parameters.
     * @return array
     */
    private function rows(string $source, \context $context, array $parameters = []): array {
        // Report Builder caches report instances per report id and user for the whole request
        // (manager::get_report_from_persistent), ignoring parameters, so reset between reads.
        manager::reset_caches();

        $report = system_report_factory::create($source, $context, 'local_releasegate', '', 0, $parameters);
        $reportid = (int) $report->get_report_persistent()->get('id');

        $table = testable_system_report_table::create($reportid, $parameters);

        return $table->get_table_rows();
    }

    /**
     * Two courses and one editingteacher enrolled in both.
     *
     * @return array [$coursea, $courseb, $user]
     */
    private function two_courses(): array {
        $generator = $this->getDataGenerator();
        $coursea = $generator->create_course(['fullname' => 'Course A', 'shortname' => 'CA']);
        $courseb = $generator->create_course(['fullname' => 'Course B', 'shortname' => 'CB']);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $coursea->id, 'editingteacher');
        $generator->enrol_user($user->id, $courseb->id, 'editingteacher');

        return [$coursea, $courseb, $user];
    }

    /**
     * Insert a minimal run row.
     *
     * @param int $courseid Course id.
     * @param string $verdict Verdict value.
     * @return int Run id.
     */
    private function create_run(int $courseid, string $verdict = 'READY'): int {
        global $DB;

        return (int) $DB->insert_record('local_releasegate_run', (object) [
            'courseid' => $courseid,
            'verdict' => $verdict,
            'coverage' => 100,
            'rulesetversion' => 'test',
            'fingerprint' => str_repeat('a', 64),
            'actorref' => str_repeat('b', 64),
            'timecreated' => time(),
        ]);
    }

    /**
     * Insert a minimal result row.
     *
     * @param int $runid Run id.
     * @param array $overrides Field overrides.
     * @return int Result id.
     */
    private function create_result(int $runid, array $overrides = []): int {
        global $DB;

        return (int) $DB->insert_record('local_releasegate_result', (object) array_merge([
            'runid' => $runid,
            'ruleid' => 'RG-CRS-001',
            'area' => 'course',
            'severity' => 'blocker',
            'status' => 'FAIL',
            'message' => 'Test finding.',
            'evidence' => '[]',
        ], $overrides));
    }

    /**
     * The editingteacher role id.
     *
     * @return int
     */
    private function editingteacher_roleid(): int {
        global $DB;

        return (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher'], MUST_EXIST);
    }

    /**
     * Case 1: a user with view in course A only sees course A in the site overview.
     */
    public function test_site_overview_scopes_to_allowed_courses(): void {
        global $DB;
        $this->resetAfterTest();

        [$coursea, $courseb, $user] = $this->two_courses();
        $this->create_run($coursea->id);
        $this->create_run($courseb->id);

        assign_capability(
            'local/releasegate:view',
            CAP_PROHIBIT,
            $this->editingteacher_roleid(),
            context_course::instance($courseb->id)->id,
            true
        );
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);

        $rows = $this->rows(site_overview::class, context_system::instance());

        $this->assertCount(1, $rows);
        $this->assertStringContainsString('Course A', $rows[0]['coursefullnamewithlink']);
        $this->assertStringNotContainsString('Course B', $rows[0]['coursefullnamewithlink']);
    }

    /**
     * Case 2: a user with the capability in no course cannot view the report and gets no rows.
     */
    public function test_site_overview_fails_closed_without_capability(): void {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_course(['fullname' => 'Course A', 'shortname' => 'CA']);
        $this->setUser($user);

        // The report checks can_view() while it is built, so creating it must fail closed.
        manager::reset_caches();
        $this->expectException(report_access_exception::class);
        system_report_factory::create(
            site_overview::class,
            context_system::instance(),
            'local_releasegate',
            '',
            0,
            []
        );
    }

    /**
     * Case 3: course results never leak rows for a run id that belongs to another course.
     */
    public function test_course_results_ignores_foreign_runid(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $coursea = $generator->create_course(['fullname' => 'Course A', 'shortname' => 'CA']);
        $courseb = $generator->create_course(['fullname' => 'Course B', 'shortname' => 'CB']);

        $runa = $this->create_run($coursea->id);
        $this->create_result($runa);
        $runb = $this->create_run($courseb->id);
        $this->create_result($runb);

        $contexta = context_course::instance($coursea->id);

        // Sanity check: the course's own run returns its row.
        $this->assertCount(1, $this->rows(course_results::class, $contexta, ['runid' => $runa]));

        // A foreign run id must return nothing.
        $this->assertCount(0, $this->rows(course_results::class, $contexta, ['runid' => $runb]));
    }

    /**
     * Case 4: editingteacher gets viewresults but not viewevidence by default.
     */
    public function test_default_capabilities_for_editingteacher(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');
        $context = context_course::instance($course->id);

        $this->assertTrue(has_capability('local/releasegate:viewresults', $context, $user->id));
        $this->assertFalse(has_capability('local/releasegate:viewevidence', $context, $user->id));
    }

    /**
     * Case 5: removing the capability from the role removes access on the next call.
     */
    public function test_removing_capability_removes_access(): void {
        $this->resetAfterTest();

        [$coursea, $courseb, $user] = $this->two_courses();
        $this->create_run($coursea->id);
        $this->create_run($courseb->id);

        $roleid = $this->editingteacher_roleid();
        $contexta = context_course::instance($coursea->id);
        $contextb = context_course::instance($courseb->id);

        // Restrict to course A only.
        assign_capability('local/releasegate:view', CAP_PROHIBIT, $roleid, $contextb->id, true);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);
        $this->assertCount(1, $this->rows(site_overview::class, context_system::instance()));

        // Remove the capability in course A too.
        assign_capability('local/releasegate:view', CAP_PROHIBIT, $roleid, $contexta->id, true);
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);

        // With no allowed course left the report refuses to build at all (fail closed).
        manager::reset_caches();
        $this->expectException(report_access_exception::class);
        system_report_factory::create(site_overview::class, context_system::instance(), 'local_releasegate', '', 0, []);
    }

    /**
     * Case 6: a never-scanned course still appears, with the "Not scanned" verdict.
     */
    public function test_site_overview_shows_not_scanned_course(): void {
        $this->resetAfterTest();

        [$coursea, $courseb, $user] = $this->two_courses();
        $this->create_run($coursea->id);
        // Course B is never scanned.

        $this->setUser($user);
        $rows = $this->rows(site_overview::class, context_system::instance());

        $this->assertCount(2, $rows);
        $this->assertContains(get_string('verdict_notscanned', 'local_releasegate'), array_column($rows, 'verdict'));
    }

    /**
     * Case 7: an ERROR result message is never shown raw.
     */
    public function test_error_message_is_not_exposed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $run = $this->create_run($course->id);
        $this->create_result($run, [
            'status' => 'ERROR',
            'message' => 'SECRET EXCEPTION TEXT',
            'evidence' => '[]',
        ]);

        $rows = $this->rows(course_results::class, context_course::instance($course->id), ['runid' => $run]);

        $this->assertCount(1, $rows);
        $this->assertSame(get_string('error_generic', 'local_releasegate'), $rows[0]['message']);
        $this->assertStringNotContainsString('SECRET', (string) $rows[0]['message']);
    }
}
