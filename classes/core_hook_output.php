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
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_copy;

defined('MOODLE_INTERNAL') || die;
require_once(__DIR__ . "/../lib.php");

/**
 * Class core_hook_output
 *
 * @package local_copy
 */
class core_hook_output {
    /**
     * Function before_http_headers
     */
    public static function before_standard_head_html_generation() {
        global $PAGE, $USER, $COURSE;

        if ($PAGE->user_is_editing()) {
            $PAGE->requires->js_call_amd("local_copy/copy", "init",
                [get_string("copy", "local_copy")]);

            if (isset($USER->copymodule_id) && $USER->copymodule_id) {
                $PAGE->requires->js_call_amd("local_copy/paste", "init",
                    [$COURSE->id, get_string("pastehere", "local_copy", $USER->copymodule_name)]);
            }
        }
    }
}
