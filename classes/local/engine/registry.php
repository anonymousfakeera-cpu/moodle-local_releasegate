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
 * Rule registry.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\local\engine;

/**
 * Knows every rule and the ruleset version.
 */
class registry {
    /** @var string Bump whenever rule behaviour changes. */
    const RULESET_VERSION = '2026.10.2';

    /**
     * All rules, in evaluation order.
     *
     * @return rule_base[]
     */
    public static function rules(): array {
        $names = ['course', 'enrolment', 'enrol_self_inactivity', 'completion', 'completion_activity',
            'completion_missing', 'completion_self', 'completion_viewonly', 'passgrade',
            'quiz_empty', 'quiz_passgrade', 'quiz_review', 'quiz_attempts', 'quiz_minattempts',
            'scorm_launch', 'h5p_manualgrade', 'restriction_missing'];
        $rules = [];
        foreach ($names as $name) {
            $class = '\\local_releasegate\\local\\rule\\' . $name;
            $rules[] = new $class();
        }
        return $rules;
    }
}
