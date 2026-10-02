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
 * CLI: run the release gate for a course. Exit code 0 ready, 1 conditional, 2 blocked, 3 insufficient data.
 *
 * Usage: php local/releasegate/cli/gate.php --courseid=2 [--json]
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(['courseid' => 0, 'json' => false, 'help' => false], ['h' => 'help']);
if ($options['help'] || !$options['courseid']) {
    cli_writeln("Usage: php local/releasegate/cli/gate.php --courseid=ID [--json]");
    exit($options['help'] ? 0 : 1);
}

$run = \local_releasegate\local\runner::run((int) $options['courseid']);

if ($options['json']) {
    cli_writeln(json_encode(
        ['verdict' => $run->verdict, 'coverage' => $run->coverage, 'results' => $run->results],
        JSON_PRETTY_PRINT
    ));
} else {
    cli_writeln("Verdict: {$run->verdict} (coverage {$run->coverage}%)");
    foreach ($run->results as $r) {
        if ($r->status === 'FAIL' || $r->status === 'ERROR') {
            cli_writeln("  [{$r->severity}] {$r->ruleid} {$r->status}: {$r->message}");
        }
    }
}

$codes = ['READY' => 0, 'CONDITIONAL' => 1, 'BLOCKED' => 2, 'INSUFFICIENT DATA' => 3];
exit($codes[$run->verdict]);
