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
 * Rule RG-RST-001.
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
 * Rule RG-RST-001.
 */
class restriction_missing extends rule_base {
    /**
     * Rule id.
     *
     * @return string
     */
    public function id(): string {
        return 'RG-RST-001';
    }

    /**
     * Area.
     *
     * @return string
     */
    public function area(): string {
        return 'restrictions';
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
        $modules = $ctx->modules();
        $bad = [];
        $any = false;
        foreach ($modules as $cm) {
            if (!empty($cm->deletioninprogress) || empty($cm->availability)) {
                continue;
            }
            $tree = json_decode($cm->availability);
            if (!$tree) {
                continue;
            }
            $any = true;
            foreach ($this->referenced_cmids($tree) as $ref) {
                if (!isset($modules[$ref]) || !empty($modules[$ref]->deletioninprogress)) {
                    $bad[] = $ctx->label($cm) . ' -> cmid ' . $ref;
                }
            }
        }
        if (!$any) {
            return $this->skip('No access restrictions.');
        }
        return $bad ? $this->fail(implode(', ', $bad), ['links' => $bad]) : $this->pass();
    }

    /**
     * Collect activity ids referenced by completion conditions in an availability tree.
     *
     * @param \stdClass $node Decoded availability node.
     * @return int[]
     */
    private function referenced_cmids(\stdClass $node): array {
        $ids = [];
        if (isset($node->type) && $node->type === 'completion' && isset($node->cm) && $node->cm > 0) {
            $ids[] = (int) $node->cm;
        }
        if (isset($node->c) && is_array($node->c)) {
            foreach ($node->c as $child) {
                if ($child instanceof \stdClass) {
                    $ids = array_merge($ids, $this->referenced_cmids($child));
                }
            }
        }
        return $ids;
    }
}
