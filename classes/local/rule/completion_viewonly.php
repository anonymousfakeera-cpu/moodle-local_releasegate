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
 * Rule RG-CMP-016.
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
 * Rule RG-CMP-016.
 */
class completion_viewonly extends rule_base {
    /**
     * Rule id.
     *
     * @return string
     */
    public function id(): string {
        return 'RG-CMP-016';
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
     * Source: public/lib/db/install.xml:340-343 (completiongradeitemnumber null,
     * completionview, completionpassgrade on course_modules).
     * Item number 0 is VALID ("receive a grade"); see public/course/moodleform_mod.php:426-433.
     *
     * @param course_context $ctx Course data.
     * @return result
     */
    public function evaluate(course_context $ctx): result {
        $modnames = ['quiz', 'scorm', 'h5pactivity', 'assign', 'lesson'];
        $candidates = [];
        foreach ($modnames as $modname) {
            if (!$ctx->module_available($modname)) {
                continue;
            }
            foreach ($ctx->live_modules_of($modname) as $cm) {
                if ((int) ($cm->completion ?? 0) === 2) {
                    $candidates[$cm->id] = $cm;
                }
            }
        }
        if (!$candidates) {
            return $this->skip('No automatically completed quiz, scorm, h5pactivity, assign or lesson.');
        }
        $instancesbytype = [];
        foreach ($modnames as $modname) {
            $instancesbytype[$modname] = $ctx->instances($modname);
        }
        $bad = [];
        foreach ($candidates as $cm) {
            if (empty($cm->completionview)) {
                continue;
            }
            if (!empty($cm->completionpassgrade)) {
                continue;
            }
            // Item number 0 is valid; only null/empty-string means "no grade item".
            $itemnumber = $cm->completiongradeitemnumber ?? null;
            if ($itemnumber !== null && $itemnumber !== '') {
                continue;
            }
            $instance = $instancesbytype[$cm->modname][(int) $cm->instance] ?? null;
            if ($instance && $ctx->completion_rules_used($cm, $instance)) {
                continue;
            }
            $bad[] = $ctx->label($cm);
        }
        return $bad ? $this->fail(implode(', ', $bad), ['activities' => $bad]) : $this->pass();
    }
}
