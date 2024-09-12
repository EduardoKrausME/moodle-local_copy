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

require('../../config.php');

require_login();
require_capability('moodle/site:config', \context_system::instance());

if ($USER->editing) {
    $USER->copymodule_id = required_param('module', PARAM_INT);
    $USER->copymodule_name = required_param('name', PARAM_TEXT);
}

$returnurl = required_param('returnurl', PARAM_RAW);
$returnurl = str_replace($CFG->wwwroot, '', $returnurl);
redirect($returnurl, get_string("copyedsuccess", "local_copy"), null, \core\output\notification::NOTIFY_SUCCESS);
