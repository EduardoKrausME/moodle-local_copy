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
 * Class copy
 *
 * @package    local_copy
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_copy;

defined('MOODLE_INTERNAL') || die;

/**
 * Validates course modules and places them in the session clipboard.
 */
class copy_service {
    /**
     * Copy course modules to the session clipboard.
     *
     * @param array $moduleids Course module IDs.
     * @return array Clipboard data.
     */
    public static function copy(array $moduleids): array {
        global $DB, $USER;

        $moduleids = array_values(array_unique(array_filter(array_map('intval', $moduleids))));
        if (!$moduleids) {
            throw new \moodle_exception('copyederror', 'local_copy');
        }

        $items = [];
        $courseid = 0;
        foreach ($moduleids as $moduleid) {
            $cm = get_coursemodule_from_id(null, $moduleid, 0, false, IGNORE_MISSING);
            if (!$cm) {
                continue;
            }

            if (!$courseid) {
                $courseid = (int)$cm->course;
            }
            if ((int)$cm->course !== $courseid) {
                continue;
            }

            $context = \context_module::instance($moduleid, IGNORE_MISSING);
            if (!$context || !has_capability('local/copy:manage', $context)) {
                continue;
            }

            $modinfo = get_fast_modinfo($cm->course, $USER->id);
            $cminfo = $modinfo->get_cm($moduleid);
            $items[] = [
                'cmid' => (int)$moduleid,
                'name' => format_string($cminfo->name, true, ['context' => $context]),
                'modname' => (string)$cm->modname,
            ];
        }

        if (!$items || !$courseid) {
            throw new \moodle_exception('copyederror', 'local_copy');
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        return clipboard::set($course, $items);
    }
}
