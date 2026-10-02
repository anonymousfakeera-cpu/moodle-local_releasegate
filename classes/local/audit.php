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
 * Hash-chained audit log.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\local;

/**
 * Append-only log where each row hashes the previous one, so edits are detectable.
 */
class audit {
    /**
     * One-way pseudonymous reference for a user id.
     *
     * @param int $userid User id, 0 for system.
     * @return string
     */
    public static function actorref(int $userid): string {
        global $CFG;
        $salt = get_config('local_releasegate', 'auditsalt');
        if (!$salt) {
            $salt = random_string(32);
            set_config('auditsalt', $salt, 'local_releasegate');
        }
        return hash('sha256', $salt . ':' . $userid);
    }

    /**
     * Append an entry.
     *
     * @param string $action Action name.
     * @param int $courseid Course id or 0.
     * @param int $userid Acting user id or 0.
     * @param array $detail Extra data.
     * @return int New row id.
     */
    public static function log(string $action, int $courseid, int $userid, array $detail = []): int {
        global $DB;
        $lockfactory = \core\lock\lock_config::get_lock_factory('local_releasegate_audit');
        $lock = $lockfactory->get_lock('chain', 30);
        if (!$lock) {
            throw new \moodle_exception('locktimeout', 'error');
        }
        try {
            $last = $DB->get_records('local_rg_audit', null, 'id DESC', 'id, hash', 0, 1);
            $prev = $last ? reset($last)->hash : str_repeat('0', 64);
            $row = (object) [
                'courseid' => $courseid,
                'action' => $action,
                'actorref' => self::actorref($userid),
                'detail' => json_encode($detail),
                'prevhash' => $prev,
                'timecreated' => time(),
            ];
            $row->hash = self::hash_row($row);
            return $DB->insert_record('local_rg_audit', $row);
        } finally {
            $lock->release();
        }
    }

    /**
     * Hash of a row together with its predecessor.
     *
     * @param \stdClass $row Row.
     * @return string
     */
    private static function hash_row(\stdClass $row): string {
        return hash('sha256', implode('|', [$row->prevhash, $row->courseid, $row->action, $row->actorref,
            $row->detail, $row->timecreated]));
    }

    /**
     * Verify the whole chain.
     *
     * @return bool True when intact.
     */
    public static function verify(): bool {
        global $DB;
        $prev = str_repeat('0', 64);
        $rs = $DB->get_recordset('local_rg_audit', null, 'id ASC');
        foreach ($rs as $row) {
            if ($row->prevhash !== $prev || $row->hash !== self::hash_row($row)) {
                $rs->close();
                return false;
            }
            $prev = $row->hash;
        }
        $rs->close();
        return true;
    }
}
