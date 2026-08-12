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
 * Class injector
 *
 * @package    local_copy
 * @copyright  2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_copy;

defined('MOODLE_INTERNAL') || die;

/**
 * Loads the clipboard UI only for users who can actually use it.
 */
class core_hook_output {
    public static function before_standard_head_html_generation(): void {
        global $PAGE, $COURSE;

        if (!$PAGE->user_is_editing() || empty($COURSE->id) || (int)$COURSE->id === SITEID) {
            return;
        }

        $context = \context_course::instance($COURSE->id, IGNORE_MISSING);
        if (!$context || !has_capability('local/copy:manage', $context)) {
            return;
        }

        $PAGE->requires->strings_for_js([
            'copy',
            'copyselected',
            'copyedsuccess',
            'copyedsuccessbulk',
            'clipboardtitleone',
            'clipboardtitlemany',
            'clipboardfrom',
            'paste',
            'clear',
            'clipboardcleared',
            'pastemodaltitle',
            'pastesection',
            'pasteposition',
            'positionstart',
            'positionend',
            'positionbefore',
            'positionafter',
            'pastebuttonone',
            'pastebuttonmany',
            'pasting',
            'pasteerror',
            'sectionfallback',
            'unexpectederror',
        ], 'local_copy');

        $PAGE->requires->js_call_amd('local_copy/clipboard', 'init', [
            (int)$COURSE->id,
            clipboard::get(),
        ]);
    }
}
