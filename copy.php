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
 * copy file
 *
 * @package   local_copy
 * @copyright 2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;

require("../../config.php");

$copymoduleid = required_param("module", PARAM_INT);
$copymodulename = required_param("name", PARAM_TEXT);

require_login();
$context = \context_module::instance($copymoduleid);
require_capability("local/copy:manage", $context);

$returnurl = required_param("returnurl", PARAM_RAW);

if ($USER->editing) {
    $USER->copymodule_id = $copymoduleid;
    $USER->copymodule_name = $copymodulename;
    redirect(new moodle_url($returnurl), get_string("copyedsuccess", "local_copy"), null, notification::NOTIFY_SUCCESS);
} else {
    redirect(new moodle_url($returnurl), get_string("copyederror", "local_copy"), null, notification::NOTIFY_WARNING);
}
