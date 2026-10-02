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
 * Rule RG-ENR-006.
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
 * Rule RG-ENR-006.
 */
class enrol_self_inactivity extends rule_base {
    /**
     * Rule id.
     *
     * @return string
     */
    public function id(): string {
        return 'RG-ENR-006';
    }

    /**
     * Area.
     *
     * @return string
     */
    public function area(): string {
        return 'enrolment';
    }

    /**
     * Severity.
     *
     * @return string
     */
    public function severity(): string {
        return self::MAJOR;
    }

    /**
     * Evaluate.
     *
     * Source: public/enrol/self/lib.php:935 (customint2 = longtimenosee),
     * :660 (timeend moved by customint2), :492 (days = customint2 / DAYSECS).
     *
     * @param course_context $ctx Course data.
     * @return result
     */
    public function evaluate(course_context $ctx): result {
        global $DB;
        $instances = $DB->get_records(
            'enrol',
            ['courseid' => $ctx->course->id, 'enrol' => 'self', 'status' => ENROL_INSTANCE_ENABLED]
        );
        if (!$instances) {
            return $this->skip('No active self enrolment.');
        }
        $bad = [];
        $evidence = [];
        foreach ($instances as $instance) {
            $period = (int) ($instance->customint2 ?? 0);
            if ($period > 0) {
                $days = (int) round($period / 86400);
                $bad[] = 'enrol id ' . $instance->id . ' (' . $days . ' days)';
                $evidence[] = ['enrolid' => (int) $instance->id, 'seconds' => $period, 'days' => $days];
            }
        }
        if ($bad) {
            return $this->fail(implode(', ', $bad), ['instances' => $evidence]);
        }
        return $this->pass();
    }
}
