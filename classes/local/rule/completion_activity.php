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
 * Rule RG-CMP-002.
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
 * Rule RG-CMP-002.
 */
class completion_activity extends rule_base {
    /**
     * Rule id.
     *
     * @return string
     */
    public function id(): string {
        return 'RG-CMP-002';
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
        return self::BLOCKER;
    }

    /**
     * Evaluate.
     *
     * @param course_context $ctx Course data.
     * @return result
     */
    public function evaluate(course_context $ctx): result {
        global $DB;
        $criteria = $DB->get_records(
            'course_completion_criteria',
            ['course' => $ctx->course->id, 'criteriatype' => COMPLETION_CRITERIA_TYPE_ACTIVITY]
        );
        if (!$criteria) {
            return $this->skip('No activity completion criteria.');
        }
        $modules = $ctx->modules();
        $bad = [];
        $ids = [];
        foreach ($criteria as $c) {
            if (isset($modules[$c->moduleinstance]) && empty($modules[$c->moduleinstance]->completion)) {
                $bad[] = $ctx->label($modules[$c->moduleinstance]);
                $ids[] = (int) $c->moduleinstance;
            }
        }
        return $bad ? $this->fail(implode(', ', $bad), ['cmids' => $ids]) : $this->pass();
    }
}
