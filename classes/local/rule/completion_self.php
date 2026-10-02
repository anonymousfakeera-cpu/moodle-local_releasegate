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
 * Rule RG-CMP-015.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\local\rule;

use local_releasegate\local\engine\course_context;
use local_releasegate\local\engine\result;
use local_releasegate\local\engine\rule_base;

/**
 * Rule RG-CMP-015.
 */
class completion_self extends rule_base {
    /**
     * Rule id.
     *
     * @return string
     */
    public function id(): string {
        return 'RG-CMP-015';
    }

    /**
     * Area.
     *
     * @return string
     */
    public function area(): string {
        return 'completion';
    }

    /**
     * Severity.
     *
     * @return string
     */
    public function severity(): string {
        return self::CRITICAL;
    }

    /**
     * Evaluate.
     *
     * Source: public/completion/criteria/completion_criteria.php (criteriatype 1 = self).
     *
     * @param course_context $ctx Course data.
     * @return result
     */
    public function evaluate(course_context $ctx): result {
        global $DB;
        if (empty($ctx->course->enablecompletion)) {
            return $this->skip('Completion is disabled.');
        }
        $total = $DB->count_records('course_completion_criteria', ['course' => $ctx->course->id]);
        if (!$total) {
            return $this->skip('No completion criteria; RG-CMP-001 owns that.');
        }
        $self = $DB->count_records(
            'course_completion_criteria',
            ['course' => $ctx->course->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_SELF]
        );
        if ($self > 0) {
            return $this->fail(null, ['selfcriteria' => $self]);
        }
        return $this->pass();
    }
}
