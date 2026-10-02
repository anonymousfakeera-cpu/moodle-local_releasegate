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
 * Rule RG-GRD-001.
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
 * Rule RG-GRD-001.
 */
class passgrade extends rule_base {
    /**
     * Rule id.
     *
     * @return string
     */
    public function id(): string {
        return 'RG-GRD-001';
    }

    /**
     * Area.
     *
     * @return string
     */
    public function area(): string {
        return 'grades';
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
     * @param course_context $ctx Course data.
     * @return result
     */
    public function evaluate(course_context $ctx): result {
        global $DB;
        $bad = [];
        $any = false;
        foreach ($ctx->modules() as $cm) {
            if (!empty($cm->deletioninprogress) || empty($cm->completionpassgrade)) {
                continue;
            }
            $any = true;
            $gradepass = $DB->get_field(
                'grade_items',
                'gradepass',
                ['courseid' => $ctx->course->id,
                'itemtype' => 'mod', 'itemmodule' => $cm->modname, 'iteminstance' => $cm->instance, 'itemnumber' => 0],
                IGNORE_MISSING
            );
            if ($gradepass === false || (float) $gradepass <= 0) {
                $bad[] = $ctx->label($cm);
            }
        }
        if (!$any) {
            return $this->skip('No activity requires a pass grade.');
        }
        return $bad ? $this->fail(implode(', ', $bad), ['activities' => $bad]) : $this->pass();
    }
}
