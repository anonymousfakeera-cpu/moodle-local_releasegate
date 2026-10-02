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
 * Rule RG-GRD-018.
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
 * Rule RG-GRD-018.
 */
class quiz_minattempts extends rule_base {
    /**
     * Rule id.
     *
     * @return string
     */
    public function id(): string {
        return 'RG-GRD-018';
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
     * Source: public/mod/quiz/mod_form.php:564-566 (form-only check min <= attempts);
     * columns public/mod/quiz/db/install.xml:21 (attempts), :49 (completionminattempts).
     *
     * @param course_context $ctx Course data.
     * @return result
     */
    public function evaluate(course_context $ctx): result {
        $quizzes = $ctx->live_modules_of('quiz');
        if (!$quizzes) {
            return $this->skip('No quizzes.');
        }
        $auto = [];
        foreach ($quizzes as $cm) {
            if ((int) ($cm->completion ?? 0) === 2) {
                $auto[$cm->id] = $cm;
            }
        }
        if (!$auto) {
            return $this->skip('No quiz uses automatic completion.');
        }
        $instances = $ctx->instances('quiz');
        $bad = [];
        $evidence = [];
        foreach ($auto as $cm) {
            $quiz = $instances[(int) $cm->instance] ?? null;
            if (!$quiz) {
                continue;
            }
            $attempts = (int) ($quiz->attempts ?? 0);
            $min = (int) ($quiz->completionminattempts ?? 0);
            if ($attempts > 0 && $min > $attempts) {
                $bad[] = $ctx->label($cm);
                $evidence[] = ['activity' => $ctx->label($cm), 'attempts' => $attempts, 'minattempts' => $min];
            }
        }
        return $bad ? $this->fail(implode(', ', $bad), ['quizzes' => $evidence]) : $this->pass();
    }
}
