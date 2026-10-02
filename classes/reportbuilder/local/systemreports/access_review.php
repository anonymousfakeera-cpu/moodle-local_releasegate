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
 * Access review system report.
 *
 * Lists which roles hold each Release Gate capability, in which context, and
 * links to core Check permissions for the context. The effective-user count is
 * only available behind the RISK_PERSONAL capability.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\reportbuilder\local\systemreports;

use core\lang_string;
use core_reportbuilder\local\helpers\database;
use core_reportbuilder\local\report\column;
use core_reportbuilder\system_report;
use local_releasegate\reportbuilder\local\entities\role_capability;
use html_writer;
use moodle_url;


/**
 * Access review system report.
 */
class access_review extends system_report {
    /**
     * Initialise report.
     *
     * @return void
     */
    protected function initialise(): void {
        global $DB;

        $entity = new role_capability();
        $rcalias = $entity->get_table_alias('role_capabilities');
        $rolealias = $entity->get_table_alias('role');
        $contextalias = $entity->get_table_alias('context');

        $entity->add_join("JOIN {role} {$rolealias} ON {$rolealias}.id = {$rcalias}.roleid");
        $entity->add_join("JOIN {context} {$contextalias} ON {$contextalias}.id = {$rcalias}.contextid");

        $this->set_main_table('role_capabilities', $rcalias);
        $this->add_entity($entity);

        // Restrict to Release Gate capabilities.
        // Report Builder only accepts param names from database::generate_param_name().
        $capplaceholders = [];
        $capparams = [];
        foreach (role_capability::gate_capabilities() as $cap) {
            $name = database::generate_param_name();
            $capplaceholders[] = ':' . $name;
            $capparams[$name] = $cap;
        }
        $this->add_base_condition_sql(
            "{$rcalias}.capability IN (" . implode(',', $capplaceholders) . ')',
            $capparams
        );

        // Restrict to contexts where a course-level capability can be held.
        $lvlplaceholders = [];
        $lvlparams = [];
        foreach ([CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE] as $level) {
            $name = database::generate_param_name();
            $lvlplaceholders[] = ':' . $name;
            $lvlparams[$name] = $level;
        }
        $this->add_base_condition_sql(
            "{$contextalias}.contextlevel IN (" . implode(',', $lvlplaceholders) . ')',
            $lvlparams
        );

        $this->add_columns();
        $this->add_filters();

        $this->set_initial_sort_column('role_capability:capability', SORT_ASC);
        $this->set_default_no_results_notice(new lang_string('noaccessreviewrows', 'local_releasegate'));
        $this->set_downloadable(has_capability('local/releasegate:viewaccessreview', $this->get_context()));
    }

    /**
     * Validates access to view this report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('local/releasegate:viewaccessreview', $this->get_context());
    }

    /**
     * Report name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('report_accessreview', 'local_releasegate');
    }

    /**
     * Adds the columns we want to display in the report.
     *
     * @return void
     */
    protected function add_columns(): void {
        $this->add_columns_from_entities([
            'role_capability:capability',
            'role_capability:role',
            'role_capability:context',
            'role_capability:permission',
            'role_capability:usercount',
        ]);

        // Link to core Check permissions for the row context.
        $contextalias = $this->get_entity('role_capability')->get_table_alias('context');
        $this->add_column((new column(
            'checkpermissions',
            new lang_string('checkpermissions', 'local_releasegate'),
            'role_capability'
        ))
            ->add_joins($this->get_entity('role_capability')->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$contextalias}.id")
            ->set_is_sortable(false)
            ->add_callback(static function ($contextid): string {
                if (empty($contextid)) {
                    return '';
                }
                return html_writer::link(
                    new moodle_url('/admin/roles/check.php', ['contextid' => (int) $contextid]),
                    get_string('checkpermissions', 'local_releasegate')
                );
            }));
    }

    /**
     * Adds the filters we want to display in the report.
     *
     * @return void
     */
    protected function add_filters(): void {
        $this->add_filters_from_entities([
            'role_capability:capability',
            'role_capability:role',
            'role_capability:contextlevel',
            'role_capability:permission',
        ]);
    }
}
