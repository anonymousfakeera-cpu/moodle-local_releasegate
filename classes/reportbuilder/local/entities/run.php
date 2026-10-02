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
 * Report Builder entity for gate scans.
 *
 * Exposes an explicit allowlist of columns from {local_rg_run}. The
 * pseudonymous actor reference is deliberately not exposed.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{date, select, text};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\{column, filter};


/**
 * Scan (run) entity.
 */
class run extends base {
    /**
     * Database tables that this entity uses.
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['local_rg_run'];
    }

    /**
     * The default title for this entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_run', 'local_releasegate');
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
        $runalias = $this->get_table_alias('local_rg_run');

        // Verdict, shown as a localised label, never as a bare code.
        $columns[] = (new column(
            'verdict',
            new lang_string('verdict', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$runalias}.verdict")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value): string {
                $map = [
                    'READY' => 'verdict_ready',
                    'CONDITIONAL' => 'verdict_conditional',
                    'BLOCKED' => 'verdict_blocked',
                    'INSUFFICIENT DATA' => 'verdict_insufficient',
                ];
                if ($value === null || $value === '') {
                    return get_string('verdict_notscanned', 'local_releasegate');
                }
                if (isset($map[$value])) {
                    return get_string($map[$value], 'local_releasegate');
                }
                return s($value);
            });

        // Coverage percentage.
        $columns[] = (new column(
            'coverage',
            new lang_string('coverage', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("{$runalias}.coverage")
            ->set_is_sortable(true)
            ->add_callback(static function ($value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                return (int) $value . '%';
            });

        // Ruleset version that produced the run.
        $columns[] = (new column(
            'rulesetversion',
            new lang_string('rulesetversion', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$runalias}.rulesetversion")
            ->set_is_sortable(true);

        // Config fingerprint, shortened for display.
        $columns[] = (new column(
            'fingerprint',
            new lang_string('fingerprint', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$runalias}.fingerprint")
            ->set_is_sortable(false)
            ->add_callback(static function (?string $value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                return s(substr($value, 0, 12)) . '...';
            });

        // When the scan ran.
        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$runalias}.timecreated")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate']);

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $runalias = $this->get_table_alias('local_rg_run');

        // Verdict. COALESCE maps never-scanned courses (no joined run) to an explicit option.
        $filters[] = (new filter(
            select::class,
            'verdict',
            new lang_string('verdict', 'local_releasegate'),
            $this->get_entity_name(),
            "COALESCE({$runalias}.verdict, '')"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                '' => new lang_string('verdict_notscanned', 'local_releasegate'),
                'READY' => new lang_string('verdict_ready', 'local_releasegate'),
                'CONDITIONAL' => new lang_string('verdict_conditional', 'local_releasegate'),
                'BLOCKED' => new lang_string('verdict_blocked', 'local_releasegate'),
                'INSUFFICIENT DATA' => new lang_string('verdict_insufficient', 'local_releasegate'),
            ]);

        // Ruleset version.
        $filters[] = (new filter(
            text::class,
            'rulesetversion',
            new lang_string('rulesetversion', 'local_releasegate'),
            $this->get_entity_name(),
            "{$runalias}.rulesetversion"
        ))
            ->add_joins($this->get_joins());

        // When the scan ran.
        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'local_releasegate'),
            $this->get_entity_name(),
            "{$runalias}.timecreated"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
