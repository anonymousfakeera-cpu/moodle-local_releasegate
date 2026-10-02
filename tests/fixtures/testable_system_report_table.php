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
 * Test-only system report table that exposes the rows a report would render.
 *
 * Copied from the core test fixture
 * public/reportbuilder/tests/fixtures/testable_system_report_table.php:31-74 (Moodle 5.1).
 * The row-reading approach (create() + setup() + query_db()) is from
 * public/reportbuilder/classes/table/system_report_table.php:191-193.
 *
 * @package    local_releasegate
 * @category   test
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate;

use core_reportbuilder\table\system_report_table;
use stdClass;

/**
 * Testable system report table.
 */
class testable_system_report_table extends system_report_table {
    /**
     * Format a row, re-keying the aliased columns back to their report column names.
     *
     * @param array|stdClass $row
     * @return array
     */
    public function format_row($row): array {
        $record = parent::format_row($row);
        $result = [];

        foreach ($this->report->get_columns() as $column) {
            $result[$column->get_name()] = $record[$column->get_column_alias()];
        }

        return $result;
    }

    /**
     * Return all rows the report would render.
     *
     * @return array
     */
    public function get_table_rows(): array {
        global $PAGE;

        $PAGE->set_url('/');

        $result = [];

        $this->guess_base_url();
        $this->setup();
        $this->query_db(0, false);
        foreach ($this->rawdata as $record) {
            $result[] = $this->format_row($record);
        }
        $this->close_recordset();

        return $result;
    }
}
