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
 * Base class for rules.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\local\engine;

/**
 * A deterministic rule. Rules read Moodle configuration and never change it.
 */
abstract class rule_base {
    /** @var string Severity blocker. */
    const BLOCKER = 'blocker';
    /** @var string Severity critical. */
    const CRITICAL = 'critical';
    /** @var string Severity major. */
    const MAJOR = 'major';
    /** @var string Severity minor. */
    const MINOR = 'minor';

    /**
     * Stable rule id, for example RG-CMP-001.
     *
     * @return string
     */
    abstract public function id(): string;

    /**
     * Release area, for example completion.
     *
     * @return string
     */
    abstract public function area(): string;

    /**
     * Severity when this rule fails.
     *
     * @return string
     */
    abstract public function severity(): string;

    /**
     * Evaluate the rule.
     *
     * @param course_context $ctx Course data.
     * @return result
     */
    abstract public function evaluate(course_context $ctx): result;

    /**
     * Build a PASS result.
     *
     * @return result
     */
    protected function pass(): result {
        return new result($this, result::PASS);
    }

    /**
     * Build a NOT APPLICABLE result: there is nothing in this course for the rule to check.
     *
     * @param string $why Reason.
     * @return result
     */
    protected function skip(string $why): result {
        return new result($this, result::NOTAPPLICABLE, $why);
    }

    /**
     * Build a FAIL result with the standard language string.
     *
     * @param string|null $a Detail for the string.
     * @param array $evidence Evidence.
     * @return result
     */
    protected function fail(?string $a = null, array $evidence = []): result {
        $message = get_string('rule_' . $this->id() . '_fail', 'local_releasegate', $a);
        return new result($this, result::FAIL, $message, $evidence);
    }
}
