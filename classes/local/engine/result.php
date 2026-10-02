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
 * Outcome of a single rule.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\local\engine;

/**
 * Immutable outcome of one rule evaluation.
 */
class result {
    /** @var string Rule passed. */
    const PASS = 'PASS';
    /** @var string Rule failed. */
    const FAIL = 'FAIL';
    /** @var string Rule could not be evaluated (missing data). */
    const NOTEVALUATED = 'NOT EVALUATED';
    /** @var string Nothing in this course for the rule to check (for example no quizzes). Not counted in coverage. */
    const NOTAPPLICABLE = 'N/A';
    /** @var string Rule crashed. */
    const ERROR = 'ERROR';

    /** @var string */
    public $ruleid;
    /** @var string */
    public $area;
    /** @var string */
    public $severity;
    /** @var string */
    public $status;
    /** @var string */
    public $message;
    /** @var array */
    public $evidence;

    /**
     * Constructor.
     *
     * @param rule_base $rule The rule.
     * @param string $status One of the status constants.
     * @param string $message Human message.
     * @param array $evidence Structured evidence.
     */
    public function __construct(rule_base $rule, string $status, string $message = '', array $evidence = []) {
        $this->ruleid = $rule->id();
        $this->area = $rule->area();
        $this->severity = $rule->severity();
        $this->status = $status;
        $this->message = $message;
        $this->evidence = $evidence;
    }
}
