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
 * Report Builder entity for role capability overrides.
 *
 * Exposes an explicit allowlist of columns from core {role_capabilities},
 * {role} and {context}. Used by the access review system report.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\reportbuilder\local\entities;

use core\context;
use core\context\system;
use core\context_helper;
use lang_string;
use stdClass;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{select, text};
use core_reportbuilder\local\report\{column, filter};


/**
 * Role capability entity.
 */
class role_capability extends base {
    /**
     * Database tables that this entity uses.
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['role_capabilities', 'role', 'context', 'role_assignments'];
    }

    /**
     * The default title for this entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_role_capability', 'local_releasegate');
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
        $rcalias = $this->get_table_alias('role_capabilities');
        $rolealias = $this->get_table_alias('role');
        $contextalias = $this->get_table_alias('context');

        // Capability, shown with its localised name.
        $columns[] = (new column(
            'capability',
            new lang_string('capability', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$rcalias}.capability")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                return get_capability_string($value);
            });

        // Role name.
        $columns[] = (new column(
            'role',
            new lang_string('role', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$rolealias}.name")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                return format_string($value);
            });

        // Context, shown with its human name.
        $columns[] = (new column(
            'context',
            new lang_string('context', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields(context_helper::get_preload_record_columns_sql($contextalias))
            ->set_is_sortable(false)
            ->add_callback(static function ($value, stdClass $row): string {
                if ($value === null || $row->ctxid === null) {
                    return '';
                }
                context_helper::preload_from_record(clone $row);
                $context = context::instance_by_id($row->ctxid, IGNORE_MISSING);
                return $context ? $context->get_context_name() : '';
            });

        // Permission value.
        $columns[] = (new column(
            'permission',
            new lang_string('permission', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$rcalias}.permission")
            ->set_is_sortable(true)
            ->add_callback(static function ($value): string {
                switch ((int) $value) {
                    case CAP_ALLOW:
                        return get_string('permission_allow', 'local_releasegate');
                    case CAP_PROHIBIT:
                        return get_string('permission_prohibit', 'local_releasegate');
                    default:
                        return get_string('permission_notset', 'local_releasegate');
                }
            });

        // Number of role assignments in this context. Only shown to users with the
        // RISK_PERSONAL capability.
        $usercount = (new column(
            'usercount',
            new lang_string('effectiveusers', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field("(SELECT COUNT(ra.id) FROM {role_assignments} ra
                          WHERE ra.roleid = {$rolealias}.id AND ra.contextid = {$contextalias}.id)")
            ->set_is_sortable(false)
            ->add_callback(static function ($value): string {
                return (string) (int) $value;
            });
        $usercount->set_is_available(has_capability('local/releasegate:viewaccessusers', system::instance()));
        $columns[] = $usercount;

        return $columns;
    }

    /**
     * Return list of all available filters.
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $rcalias = $this->get_table_alias('role_capabilities');
        $rolealias = $this->get_table_alias('role');
        $contextalias = $this->get_table_alias('context');

        // Capability.
        $filters[] = (new filter(
            select::class,
            'capability',
            new lang_string('capability', 'local_releasegate'),
            $this->get_entity_name(),
            "{$rcalias}.capability"
        ))
            ->add_joins($this->get_joins())
            ->set_options_callback(static function (): array {
                $options = [];
                foreach (self::gate_capabilities() as $capability) {
                    $options[$capability] = get_capability_string($capability);
                }
                return $options;
            });

        // Role name.
        $filters[] = (new filter(
            text::class,
            'role',
            new lang_string('role', 'local_releasegate'),
            $this->get_entity_name(),
            "{$rolealias}.name"
        ))
            ->add_joins($this->get_joins());

        // Context level.
        $filters[] = (new filter(
            select::class,
            'contextlevel',
            new lang_string('contextlevel', 'local_releasegate'),
            $this->get_entity_name(),
            "{$contextalias}.contextlevel"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                CONTEXT_SYSTEM => context_helper::get_level_name(CONTEXT_SYSTEM),
                CONTEXT_COURSECAT => context_helper::get_level_name(CONTEXT_COURSECAT),
                CONTEXT_COURSE => context_helper::get_level_name(CONTEXT_COURSE),
            ]);

        // Permission.
        $filters[] = (new filter(
            select::class,
            'permission',
            new lang_string('permission', 'local_releasegate'),
            $this->get_entity_name(),
            "{$rcalias}.permission"
        ))
            ->add_joins($this->get_joins())
            ->set_options([
                CAP_ALLOW => new lang_string('permission_allow', 'local_releasegate'),
                CAP_PROHIBIT => new lang_string('permission_prohibit', 'local_releasegate'),
                CAP_INHERIT => new lang_string('permission_notset', 'local_releasegate'),
            ]);

        return $filters;
    }

    /**
     * The Release Gate capabilities shown in access review.
     *
     * Keep in step with db/access.php.
     *
     * @return string[]
     */
    public static function gate_capabilities(): array {
        return [
            'local/releasegate:view',
            'local/releasegate:viewresults',
            'local/releasegate:viewevidence',
            'local/releasegate:run',
            'local/releasegate:export',
            'local/releasegate:viewaudit',
            'local/releasegate:managesettings',
            'local/releasegate:waive',
            'local/releasegate:approve',
            'local/releasegate:viewaccessreview',
            'local/releasegate:viewaccessusers',
        ];
    }
}
