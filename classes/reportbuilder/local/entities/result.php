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
 * Report Builder entity for rule results.
 *
 * Exposes an explicit allowlist of columns from {local_rg_result}. Rule ids
 * are always shown with their localised rule title, never as a bare code.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\reportbuilder\local\entities;

use lang_string;
use stdClass;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{select, text};
use core_reportbuilder\local\report\{column, filter};


/**
 * Rule result entity.
 */
class result extends base {
    /**
     * Database tables that this entity uses.
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['local_rg_result'];
    }

    /**
     * The default title for this entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_result', 'local_releasegate');
    }

    /**
     * Initialise the entity.
     *
     * @return base
     */
    public function initialise(): base {
        foreach ($this->get_all_columns() as $column) {
            $this->add_column($column);
        }
        foreach ($this->get_all_filters() as $filter) {
            $this
                ->add_filter($filter)
                ->add_condition($filter);
        }
        return $this;
    }

    /**
     * Returns list of all available columns (the allowlist).
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $resultalias = $this->get_table_alias('local_rg_result');

        // Rule, shown as its localised title with the id in brackets for traceability.
        $columns[] = (new column(
            'rule',
            new lang_string('rule', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$resultalias}.ruleid")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                $stringmanager = get_string_manager();
                if ($stringmanager->string_exists('rule_' . $value, 'local_releasegate')) {
                    return get_string('rule_' . $value, 'local_releasegate') . ' (' . s($value) . ')';
                }
                return s($value);
            });

        // Area.
        $columns[] = (new column(
            'area',
            new lang_string('area', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$resultalias}.area")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                $stringmanager = get_string_manager();
                if ($stringmanager->string_exists('area_' . $value, 'local_releasegate')) {
                    return get_string('area_' . $value, 'local_releasegate');
                }
                return s($value);
            });

        // Severity.
        $columns[] = (new column(
            'severity',
            new lang_string('severity', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$resultalias}.severity")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                $stringmanager = get_string_manager();
                if ($stringmanager->string_exists('severity_' . $value, 'local_releasegate')) {
                    return get_string('severity_' . $value, 'local_releasegate');
                }
                return s($value);
            });

        // Status.
        $columns[] = (new column(
            'status',
            new lang_string('status', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$resultalias}.status")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value): string {
                $map = [
                    'PASS' => 'status_pass',
                    'FAIL' => 'status_fail',
                    'NOT EVALUATED' => 'status_notevaluated',
                    'N/A' => 'status_notapplicable',
                    'ERROR' => 'status_error',
                ];
                if ($value === null || $value === '') {
                    return '';
                }
                if (isset($map[$value])) {
                    return get_string($map[$value], 'local_releasegate');
                }
                return s($value);
            });

        // Finding message. A crashed rule (ERROR) never exposes its raw exception text.
        $columns[] = (new column(
            'message',
            new lang_string('message', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_fields("{$resultalias}.message, {$resultalias}.status")
            ->set_is_sortable(false)
            ->add_callback(static function (?string $value, stdClass $row): string {
                if ($value === null || $value === '') {
                    return '';
                }
                if (($row->status ?? '') === 'ERROR') {
                    return get_string('error_generic', 'local_releasegate');
                }
                return $value;
            });

        // Evidence detail (configuration facts only, shortened for the list view).
        $columns[] = (new column(
            'evidence',
            new lang_string('evidence', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_LONGTEXT)
            ->add_field("{$resultalias}.evidence")
            ->set_is_sortable(false)
            ->add_callback(static function (?string $value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                return s(substr($value, 0, 300)) . (strlen($value) > 300 ? '...' : '');
            });

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $resultalias = $this->get_table_alias('local_rg_result');

        // Severity.
        $filters[] = (new filter(
            select::class,
            'severity',
            new lang_string('severity', 'local_releasegate'),
            $this->get_entity_name(),
            "{$resultalias}.severity"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                'blocker' => new lang_string('severity_blocker', 'local_releasegate'),
                'critical' => new lang_string('severity_critical', 'local_releasegate'),
                'major' => new lang_string('severity_major', 'local_releasegate'),
                'minor' => new lang_string('severity_minor', 'local_releasegate'),
            ]);

        // Status.
        $filters[] = (new filter(
            select::class,
            'status',
            new lang_string('status', 'local_releasegate'),
            $this->get_entity_name(),
            "{$resultalias}.status"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                'PASS' => new lang_string('status_pass', 'local_releasegate'),
                'FAIL' => new lang_string('status_fail', 'local_releasegate'),
                'NOT EVALUATED' => new lang_string('status_notevaluated', 'local_releasegate'),
                'N/A' => new lang_string('status_notapplicable', 'local_releasegate'),
                'ERROR' => new lang_string('status_error', 'local_releasegate'),
            ]);

        // Area.
        $filters[] = (new filter(
            select::class,
            'area',
            new lang_string('area', 'local_releasegate'),
            $this->get_entity_name(),
            "{$resultalias}.area"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                'completion' => new lang_string('area_completion', 'local_releasegate'),
                'course' => new lang_string('area_course', 'local_releasegate'),
                'enrolment' => new lang_string('area_enrolment', 'local_releasegate'),
                'quiz' => new lang_string('area_quiz', 'local_releasegate'),
                'grades' => new lang_string('area_grades', 'local_releasegate'),
                'scorm' => new lang_string('area_scorm', 'local_releasegate'),
                'restrictions' => new lang_string('area_restrictions', 'local_releasegate'),
            ]);

        // Rule id (free text, for finding one rule).
        $filters[] = (new filter(
            text::class,
            'rule',
            new lang_string('rule', 'local_releasegate'),
            $this->get_entity_name(),
            "{$resultalias}.ruleid"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
