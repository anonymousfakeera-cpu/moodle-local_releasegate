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
 * Capability definitions.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/releasegate:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Rule results mirror the existing view capability: teachers who can see the
    // gate can also see which rules passed or failed.
    'local/releasegate:viewresults' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Evidence detail carries configuration facts that only the people who manage
    // the course release should need, so this stays manager-only.
    'local/releasegate:viewevidence' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/releasegate:run' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/releasegate:export' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    // The audit chain is site-wide (courseid 0 for system actions), so the audit
    // report is a system-context screen and this capability is defined at system level.
    'local/releasegate:viewaudit' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/releasegate:managesettings' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'riskbitmask' => RISK_CONFIG,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/releasegate:waive' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    'local/releasegate:approve' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    // Access review lists which roles hold Release Gate capabilities in which
    // contexts. It is a site-level admin report, hence CONTEXT_SYSTEM.
    'local/releasegate:viewaccessreview' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
    // Showing how many users effectively hold a capability exposes assignment
    // patterns, so it carries RISK_PERSONAL and is a separate switch.
    'local/releasegate:viewaccessusers' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'riskbitmask' => RISK_PERSONAL,
        'archetypes' => ['manager' => CAP_ALLOW],
    ],
];
