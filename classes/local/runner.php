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
 * Runs the gate for a course.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\local;

use local_releasegate\local\engine\course_context;
use local_releasegate\local\engine\registry;
use local_releasegate\local\engine\result;
use local_releasegate\local\engine\verdict;

/**
 * Evaluates every rule for a course, stores the outcome and writes the audit log.
 */
class runner {
    /**
     * Scan one course. Only one scan per course runs at a time; a second caller waits briefly then fails.
     *
     * @param int $courseid Course id.
     * @param int $userid Acting user, 0 for system.
     * @return \stdClass The stored run, with results.
     */
    public static function run(int $courseid, int $userid = 0): \stdClass {
        global $DB;
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_releasegate_scan');
        $lock = $lockfactory->get_lock('course' . $courseid, 60);
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'error');
        }
        try {
            $results = self::evaluate(new course_context($course));
            return self::store($course, $results, $userid);
        } finally {
            $lock->release();
        }
    }

    /**
     * Evaluate all rules. A crashing rule becomes an ERROR result and never aborts the scan.
     *
     * @param course_context $ctx Context.
     * @return result[]
     */
    public static function evaluate(course_context $ctx): array {
        $results = [];
        foreach (registry::rules() as $rule) {
            try {
                $results[] = $rule->evaluate($ctx);
            } catch (\Throwable $e) {
                $results[] = new result($rule, result::ERROR, $e->getMessage());
            }
        }
        return $results;
    }

    /**
     * Persist a run.
     *
     * @param \stdClass $course Course.
     * @param result[] $results Results.
     * @param int $userid Acting user.
     * @return \stdClass
     */
    private static function store(\stdClass $course, array $results, int $userid): \stdClass {
        global $DB;
        $fingerprint = hash('sha256', json_encode(array_map(function ($r) {
            return [$r->ruleid, $r->status, $r->evidence];
        }, $results)));
        $run = (object) [
            'courseid' => $course->id,
            'verdict' => verdict::compute($results),
            'coverage' => verdict::coverage($results),
            'rulesetversion' => registry::RULESET_VERSION,
            'fingerprint' => $fingerprint,
            'actorref' => audit::actorref($userid),
            'timecreated' => time(),
        ];
        $transaction = $DB->start_delegated_transaction();
        $run->id = $DB->insert_record('local_rg_run', $run);
        foreach ($results as $r) {
            $DB->insert_record('local_rg_result', (object) [
                'runid' => $run->id,
                'ruleid' => $r->ruleid,
                'area' => $r->area,
                'severity' => $r->severity,
                'status' => $r->status,
                'message' => $r->message,
                'evidence' => json_encode($r->evidence),
            ]);
        }
        $transaction->allow_commit();
        audit::log('scan', (int) $course->id, $userid, ['runid' => $run->id, 'verdict' => $run->verdict]);
        $run->results = $results;
        return $run;
    }
}
