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
 * Audit system report.
 *
 * Lists the hash-chained audit log. Never exposes prevhash/hash or actorref.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\reportbuilder\local\systemreports;

use core\lang_string;
use core_reportbuilder\system_report;
use local_releasegate\reportbuilder\local\entities\audit;


/**
 * Audit system report.
 */
class audit_log extends system_report {
    /**
     * Initialise report.
     *
     * @return void
     */
    protected function initialise(): void {
        $entity = new audit();
        $alias = $entity->get_table_alias('local_releasegate_audit');

        $this->set_main_table('local_releasegate_audit', $alias);
        $this->add_entity($entity);

        $this->add_columns_from_entities([
            'audit:action',
            'audit:course',
            'audit:timecreated',
        ]);

        $this->add_filters_from_entities([
            'audit:action',
            'audit:timecreated',
        ]);

        $this->set_initial_sort_column('audit:timecreated', SORT_DESC);
        $this->set_default_no_results_notice(new lang_string('noauditrows', 'local_releasegate'));
        $this->set_downloadable(has_capability('local/releasegate:viewaudit', $this->get_context()));
    }

    /**
     * Validates access to view this report.
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('local/releasegate:viewaudit', $this->get_context());
    }

    /**
     * Report name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('report_auditlog', 'local_releasegate');
    }
}
