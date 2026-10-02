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
 * Read-only view of a course used by rules.
 *
 * @package    local_releasegate
 * @copyright  2026 Release Gate contributors
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_releasegate\local\engine;

/**
 * Loads and caches the course data that rules need, so rules stay small.
 */
class course_context {
    /** @var \stdClass */
    public $course;
    /** @var array|null */
    private $modules = null;
    /** @var array Cached activity instances per modname, keyed by instance id. */
    private $instancecache = [];
    /** @var array|null Cached module-available flags per modname. */
    private $availablecache = [];

    /**
     * Extra completion columns per module that count as "another rule".
     *
     * Source: public/mod/quiz/db/install.xml:48-49 (completionattemptsexhausted,
     * completionminattempts), public/mod/scorm/db/install.xml:46-48
     * (completionstatusrequired, completionscorerequired, completionstatusallscos),
     * public/mod/assign/db/install.xml:24 (completionsubmit),
     * public/mod/lesson/db/install.xml:48-49 (completionendreached, completiontimespent).
     * H5P declares no custom completion rules (public/mod/h5pactivity/lib.php:60).
     * Quiz has no completionpass column; pass grade lives on course_modules.completionpassgrade
     * (public/lib/db/install.xml:343), so it is checked defensively.
     *
     * @var array
     */
    const COMPLETION_RULE_COLUMNS = [
        'quiz' => ['completionattemptsexhausted', 'completionminattempts', 'completionpass'],
        'scorm' => ['completionstatusrequired', 'completionscorerequired', 'completionstatusallscos'],
        'assign' => ['completionsubmit'],
        'lesson' => ['completionendreached', 'completiontimespent'],
        'h5pactivity' => [],
    ];

    /**
     * Constructor.
     *
     * @param \stdClass $course Course record.
     */
    public function __construct(\stdClass $course) {
        $this->course = $course;
    }

    /**
     * Course modules with their module type name, keyed by cmid. Includes those pending deletion.
     *
     * @return \stdClass[]
     */
    public function modules(): array {
        global $DB;
        if ($this->modules === null) {
            $sql = "SELECT cm.*, m.name AS modname
                      FROM {course_modules} cm
                      JOIN {modules} m ON m.id = cm.module
                     WHERE cm.course = :courseid";
            $this->modules = $DB->get_records_sql($sql, ['courseid' => $this->course->id]);
        }
        return $this->modules;
    }

    /**
     * Live modules (not being deleted) of a given type.
     *
     * @param string $modname Module name, for example quiz.
     * @return \stdClass[]
     */
    public function modules_of(string $modname): array {
        $out = [];
        foreach ($this->modules() as $cm) {
            if ($cm->modname === $modname && empty($cm->deletioninprogress)) {
                $out[$cm->id] = $cm;
            }
        }
        return $out;
    }

    /**
     * Display name for an activity instance.
     *
     * Uses the cached instances() row where possible so existing rules keep
     * the same result with fewer queries.
     *
     * @param \stdClass $cm Course module with modname.
     * @return string
     */
    public function label(\stdClass $cm): string {
        global $DB;
        $instances = $this->instances($cm->modname);
        if (array_key_exists((int) $cm->instance, $instances) && isset($instances[(int) $cm->instance]->name)) {
            $name = $instances[(int) $cm->instance]->name;
            return ($name === null || $name === '' ? $cm->modname : $name) . ' (cmid ' . $cm->id . ')';
        }
        $name = $DB->get_field($cm->modname, 'name', ['id' => $cm->instance], IGNORE_MISSING);
        return ($name === false ? $cm->modname : $name) . ' (cmid ' . $cm->id . ')';
    }

    /**
     * Live modules (not being deleted and visible) of a given type.
     *
     * @param string $modname Module name, for example quiz.
     * @return \stdClass[]
     */
    public function live_modules_of(string $modname): array {
        $out = [];
        foreach ($this->modules() as $cm) {
            if ($cm->modname !== $modname) {
                continue;
            }
            if (!empty($cm->deletioninprogress)) {
                continue;
            }
            if (isset($cm->visible) && (int) $cm->visible !== 1) {
                continue;
            }
            $out[$cm->id] = $cm;
        }
        return $out;
    }

    /**
     * Bulk-load all rows of one module table for this course in one query, keyed by instance id.
     *
     * @param string $modname Module name, for example quiz.
     * @return \stdClass[] Rows keyed by instance id.
     */
    public function instances(string $modname): array {
        global $DB;
        if (array_key_exists($modname, $this->instancecache)) {
            return $this->instancecache[$modname];
        }
        $this->instancecache[$modname] = [];
        if (!$this->module_available($modname)) {
            return $this->instancecache[$modname];
        }
        $ids = [];
        foreach ($this->modules() as $cm) {
            if ($cm->modname === $modname && empty($cm->deletioninprogress)) {
                $ids[(int) $cm->instance] = (int) $cm->instance;
            }
        }
        if (!$ids) {
            return $this->instancecache[$modname];
        }
        try {
            $rows = $DB->get_records_list($modname, 'id', array_values($ids));
        } catch (\Throwable $e) {
            return $this->instancecache[$modname];
        }
        foreach ($rows as $id => $row) {
            $this->instancecache[$modname][(int) $id] = $row;
        }
        return $this->instancecache[$modname];
    }

    /**
     * Whether an optional or core module is available (row in {modules} and table exists).
     *
     * Rules for optional or disabled modules must return skip() when this is false, never throw.
     *
     * @param string $modname Module name, for example h5pactivity.
     * @return bool
     */
    public function module_available(string $modname): bool {
        global $DB;
        if (array_key_exists($modname, $this->availablecache)) {
            return $this->availablecache[$modname];
        }
        try {
            $exists = $DB->record_exists('modules', ['name' => $modname]);
            $available = $exists && $DB->get_manager()->table_exists($modname);
        } catch (\Throwable $e) {
            $available = false;
        }
        $this->availablecache[$modname] = $available;
        return $available;
    }

    /**
     * Whether the activity instance sets any module-specific completion rule besides view/grade.
     *
     * Each column is read defensively with property_exists because columns differ
     * between Moodle 4.5 and 5.1.
     *
     * @param \stdClass $cm Course module.
     * @param \stdClass $instance Activity instance row.
     * @return bool
     */
    public function completion_rules_used(\stdClass $cm, \stdClass $instance): bool {
        $columns = self::COMPLETION_RULE_COLUMNS[$cm->modname] ?? null;
        if ($columns === null) {
            return false;
        }
        foreach ($columns as $column) {
            if (!property_exists($instance, $column)) {
                continue;
            }
            $value = $instance->{$column};
            if ($value === null || $value === '' || $value === false) {
                continue;
            }
            if ((int) $value !== 0) {
                return true;
            }
        }
        return false;
    }
}
