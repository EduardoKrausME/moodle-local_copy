<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class clipboard
 *
 * @package    local_copy
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_copy;

/**
 * Session clipboard for copied course modules.
 */
class clipboard {
    /**
     * Store clipboard data in the current Moodle session.
     *
     * @param \stdClass $course Source course.
     * @param array $items Clipboard items.
     * @return array
     */
    public static function set(\stdClass $course, array $items): array {
        global $SESSION;

        $SESSION->local_copy = [
            'courseid' => (int)$course->id,
            'coursename' => format_string($course->fullname, true, ['context' => \context_course::instance($course->id)]),
            'timecreated' => time(),
            'items' => array_values($items),
        ];

        return $SESSION->local_copy;
    }

    /**
     * Get clipboard data and migrate data from the old USER based storage when needed.
     *
     * @return array
     */
    public static function get(): array {
        global $SESSION, $USER, $DB;

        if (!empty($SESSION->local_copy) && is_array($SESSION->local_copy)) {
            return self::normalise($SESSION->local_copy);
        }

        $ids = [];
        $names = [];
        if (isset($USER->copymodule_ids) && is_array($USER->copymodule_ids) && $USER->copymodule_ids) {
            $ids = array_values(array_filter(array_map('intval', $USER->copymodule_ids)));
            $names = isset($USER->copymodule_names) && is_array($USER->copymodule_names)
                ? array_values($USER->copymodule_names) : [];
        } else if (!empty($USER->copymodule_id)) {
            $ids = [(int)$USER->copymodule_id];
            $names = [(string)($USER->copymodule_name ?? '')];
        }

        if (!$ids) {
            return [];
        }

        $items = [];
        $courseid = 0;
        foreach ($ids as $index => $cmid) {
            $cm = get_coursemodule_from_id(null, $cmid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }
            if (!$courseid) {
                $courseid = (int)$cm->course;
            }
            if ((int)$cm->course !== $courseid) {
                continue;
            }
            $items[] = [
                'cmid' => (int)$cmid,
                'name' => clean_param((string)($names[$index] ?? ''), PARAM_TEXT),
                'modname' => (string)$cm->modname,
            ];
        }

        if (!$items || !$courseid) {
            self::clear_legacy();
            return [];
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', IGNORE_MISSING);
        if (!$course) {
            self::clear_legacy();
            return [];
        }

        $clipboard = self::set($course, $items);
        self::clear_legacy();
        return $clipboard;
    }

    /**
     * Clear clipboard data.
     */
    public static function clear(): void {
        global $SESSION;

        unset($SESSION->local_copy);
        self::clear_legacy();
    }

    /**
     * Clean clipboard structure before exposing it to JavaScript.
     *
     * @param array $data Raw clipboard.
     * @return array
     */
    private static function normalise(array $data): array {
        $items = [];
        foreach (($data['items'] ?? []) as $item) {
            $cmid = clean_param($item['cmid'] ?? 0, PARAM_INT);
            if (!$cmid) {
                continue;
            }
            $items[] = [
                'cmid' => (int)$cmid,
                'name' => clean_param((string)($item['name'] ?? ''), PARAM_TEXT),
                'modname' => clean_param((string)($item['modname'] ?? ''), PARAM_ALPHANUMEXT),
            ];
        }

        if (!$items) {
            return [];
        }

        return [
            'courseid' => clean_param($data['courseid'] ?? 0, PARAM_INT),
            'coursename' => clean_param((string)($data['coursename'] ?? ''), PARAM_TEXT),
            'timecreated' => clean_param($data['timecreated'] ?? 0, PARAM_INT),
            'items' => $items,
        ];
    }

    /**
     * Remove values used by versions <= 1.1.x.
     */
    private static function clear_legacy(): void {
        global $USER;

        unset($USER->copymodule_id, $USER->copymodule_name, $USER->copymodule_ids, $USER->copymodule_names);
    }
}
