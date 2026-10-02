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
 * Rule RG-GRD-015.
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
 * Rule RG-GRD-015.
 */
class quiz_review extends rule_base {
    /**
     * Rule id.
     *
     * @return string
     */
    public function id(): string {
        return 'RG-GRD-015';
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
     * Source: public/mod/quiz/classes/question/display_options.php:40-49
     * (DURING 0x10000, IMMEDIATELY_AFTER 0x01000, LATER_WHILE_OPEN 0x00100, AFTER_CLOSE 0x00010);
     * columns public/mod/quiz/db/install.xml:32 (reviewrightanswer, reviewcorrectness).
     *
     * @param course_context $ctx Course data.
     * @return result
     */
    public function evaluate(course_context $ctx): result {
        if (class_exists('\mod_quiz\question\display_options')) {
            $during = \mod_quiz\question\display_options::DURING;
            $immediately = \mod_quiz\question\display_options::IMMEDIATELY_AFTER;
            $later = \mod_quiz\question\display_options::LATER_WHILE_OPEN;
        } else {
            // Fallback values verified in display_options.php:40-49.
            $during = 0x10000;
            $immediately = 0x01000;
            $later = 0x00100;
        }
        $forbidden = (int) $during | (int) $immediately | (int) $later;
        $quizzes = $ctx->live_modules_of('quiz');
        if (!$quizzes) {
            return $this->skip('No quizzes.');
        }
        $instances = $ctx->instances('quiz');
        $bad = [];
        $evidence = [];
        foreach ($quizzes as $cm) {
            $quiz = $instances[(int) $cm->instance] ?? null;
            if (!$quiz) {
                continue;
            }
            $fields = [];
            foreach (['reviewrightanswer' => 'right answer', 'reviewcorrectness' => 'correctness'] as $field => $label) {
                $value = (int) ($quiz->{$field} ?? 0);
                if (($value & $forbidden) !== 0) {
                    $phases = [];
                    if ($value & (int) $during) {
                        $phases[] = 'during';
                    }
                    if ($value & (int) $immediately) {
                        $phases[] = 'immediately after';
                    }
                    if ($value & (int) $later) {
                        $phases[] = 'later while open';
                    }
                    $fields[] = $label . ' (' . implode(', ', $phases) . ')';
                }
            }
            if ($fields) {
                $bad[] = $ctx->label($cm);
                $evidence[] = ['activity' => $ctx->label($cm), 'fields' => $fields];
            }
        }
        return $bad ? $this->fail(implode(', ', $bad), ['quizzes' => $evidence]) : $this->pass();
    }
}
