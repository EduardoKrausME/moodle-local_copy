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
 * Copy many modules to clipboard.
 *
 * @package   local_copy
 * @copyright 2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;

require("../../config.php");

require_login();
require_sesskey();

$modulesjson = required_param("modules", PARAM_RAW);
$namesjson = optional_param("names", "[]", PARAM_RAW);
$returnurl = required_param("returnurl", PARAM_RAW);

$moduleids = json_decode($modulesjson, true);
$modulenames = json_decode($namesjson, true);

if (!is_array($moduleids) || !$moduleids) {
    redirect(new moodle_url($returnurl), get_string("copyederror", "local_copy"), null, notification::NOTIFY_WARNING);
}

$copiedids = [];
$copiednames = [];
foreach ($moduleids as $index => $moduleid) {
    $moduleid = clean_param($moduleid, PARAM_INT);
    if (!$moduleid) {
        continue;
    }

    try {
        $context = \context_module::instance($moduleid);
    } catch (Exception $exception) {
        continue;
    }

    if (!has_capability("local/copy:manage", $context)) {
        continue;
    }

    $name = "";
    if (is_array($modulenames) && array_key_exists($index, $modulenames)) {
        $name = clean_param((string)$modulenames[$index], PARAM_TEXT);
    }

    $copiedids[] = (int)$moduleid;
    $copiednames[] = $name;
}

if (!$copiedids) {
    redirect(new moodle_url($returnurl), get_string("copyederror", "local_copy"), null, notification::NOTIFY_WARNING);
}

$USER->copymodule_id = $copiedids[0];
$USER->copymodule_name = $copiednames[0] ?? "";
$USER->copymodule_ids = $copiedids;
$USER->copymodule_names = $copiednames;

$message = get_string("copyedsuccessbulk", "local_copy", count($copiedids));
redirect(new moodle_url($returnurl), $message, null, notification::NOTIFY_SUCCESS);
