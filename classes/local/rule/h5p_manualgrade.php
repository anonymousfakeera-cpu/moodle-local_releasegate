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
 * Rule RG-H5P-006.
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
 * Rule RG-H5P-006.
 */
class h5p_manualgrade extends rule_base {
    /**
     * Rule id.
     *
     * @return string
     */
    public function id(): string {
        return 'RG-H5P-006';
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
        return self::BLOCKER;
    }

    /**
     * Evaluate.
     *
     * Source: public/mod/h5pactivity/classes/local/manager.php:50 (GRADEMANUAL = 0),
     * public/mod/h5pactivity/classes/local/grader.php:148 (manual grading deletes automatic).
     * View-only completion (no pass grade and no grade item) PASSes; only grade-based
     * completion with manual grading FAILs.
     *
     * @param course_context $ctx Course data.
     * @return result
     */
    public function evaluate(course_context $ctx): result {
        if (!$ctx->module_available('h5pactivity')) {
            return $this->skip('H5P activity module is not available.');
        }
        $activities = $ctx->live_modules_of('h5pactivity');
        if (!$activities) {
            return $this->skip('No H5P activities.');
        }
        $instances = $ctx->instances('h5pactivity');
        $bad = [];
        foreach ($activities as $cm) {
            $activity = $instances[(int) $cm->instance] ?? null;
            if (!$activity) {
                continue;
            }
            if ((int) ($activity->grademethod ?? 1) !== 0) {
                continue;
            }
            if ((int) ($cm->completion ?? 0) !== 2) {
                continue;
            }
            $itemnumber = $cm->completiongradeitemnumber ?? null;
            $hasgradeitem = $itemnumber !== null && $itemnumber !== '';
            if (!empty($cm->completionpassgrade) || $hasgradeitem) {
                $bad[] = $ctx->label($cm);
            }
        }
        return $bad ? $this->fail(implode(', ', $bad), ['activities' => $bad]) : $this->pass();
    }
}
