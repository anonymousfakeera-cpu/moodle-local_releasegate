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
 * Report Builder entity for the audit log.
 *
 * Exposes an explicit allowlist from {local_releasegate_audit}. The hash chain values and
 * the pseudonymous actor reference are deliberately not exposed.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\reportbuilder\local\entities;

use lang_string;
use stdClass;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\{date, text};
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\{column, filter};
use html_writer;
use moodle_url;


/**
 * Audit log entity.
 */
class audit extends base {
    /**
     * Database tables that this entity uses.
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return ['local_releasegate_audit'];
    }

    /**
     * The default title for this entity.
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('entity_audit', 'local_releasegate');
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
            $this->add_filter($filter)->add_condition($filter);
        }
        return $this;
    }

    /**
     * Returns list of all available columns (the allowlist).
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $alias = $this->get_table_alias('local_releasegate_audit');

        // Action, localised where known.
        $columns[] = (new column(
            'action',
            new lang_string('action', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$alias}.action")
            ->set_is_sortable(true)
            ->add_callback(static function (?string $value): string {
                if ($value === null || $value === '') {
                    return '';
                }
                $stringmanager = get_string_manager();
                if ($stringmanager->string_exists('action_' . $value, 'local_releasegate')) {
                    return get_string('action_' . $value, 'local_releasegate');
                }
                return s($value);
            });

        // Course, or "System" for site-level actions.
        $columns[] = (new column(
            'course',
            new lang_string('course', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_field("{$alias}.courseid")
            ->set_is_sortable(true)
            ->add_callback(static function ($courseid): string {
                if (empty($courseid)) {
                    return get_string('system', 'local_releasegate');
                }
                return html_writer::link(
                    new moodle_url('/local/releasegate/course.php', ['id' => (int) $courseid]),
                    get_string('course', 'local_releasegate') . ' ' . $courseid
                );
            });

        // When the action happened.
        $columns[] = (new column(
            'timecreated',
            new lang_string('timecreated', 'local_releasegate'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$alias}.timecreated")
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
        $alias = $this->get_table_alias('local_releasegate_audit');

        $filters[] = (new filter(
            text::class,
            'action',
            new lang_string('action', 'local_releasegate'),
            $this->get_entity_name(),
            "{$alias}.action"
        ))
            ->add_joins($this->get_joins());

        $filters[] = (new filter(
            date::class,
            'timecreated',
            new lang_string('timecreated', 'local_releasegate'),
            $this->get_entity_name(),
            "{$alias}.timecreated"
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
