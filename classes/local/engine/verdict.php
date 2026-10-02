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
 * Verdict computation.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\local\engine;

/**
 * Turns a list of results into one deterministic verdict.
 */
class verdict {
    /** @var string */
    const READY = 'READY';
    /** @var string */
    const CONDITIONAL = 'CONDITIONAL';
    /** @var string */
    const BLOCKED = 'BLOCKED';
    /** @var string */
    const INSUFFICIENT = 'INSUFFICIENT DATA';
    /** @var int Minimum percentage of rules that must have been evaluated. */
    const MIN_COVERAGE = 60;

    /**
     * Percentage of applicable rules that were evaluated without error. N/A rules are left out.
     *
     * @param result[] $results Results.
     * @return int
     */
    public static function coverage(array $results): int {
        $applicable = 0;
        $evaluated = 0;
        foreach ($results as $r) {
            if ($r->status !== result::NOTAPPLICABLE) {
                $applicable++;
            }
            if ($r->status === result::PASS || $r->status === result::FAIL) {
                $evaluated++;
            }
        }
        return $applicable ? (int) floor(100 * $evaluated / $applicable) : 0;
    }

    /**
     * Compute the verdict. Blocker and critical failures block. Low coverage is never reported as ready.
     *
     * @param result[] $results Results.
     * @return string
     */
    public static function compute(array $results): string {
        $hasfail = false;
        foreach ($results as $r) {
            if ($r->status === result::FAIL) {
                if ($r->severity === rule_base::BLOCKER || $r->severity === rule_base::CRITICAL) {
                    return self::BLOCKED;
                }
                $hasfail = true;
            }
        }
        if (self::coverage($results) < self::MIN_COVERAGE) {
            return self::INSUFFICIENT;
        }
        return $hasfail ? self::CONDITIONAL : self::READY;
    }
}
