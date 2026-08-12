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
 * paste file
 *
 * @package   local_copy
 * @copyright 2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$courseid = required_param('courseid', PARAM_INT);
$sectionid = optional_param('sectionid', 0, PARAM_INT);
$sectionnum = optional_param('section', -1, PARAM_INT);
$beforemodule = optional_param('beforemodule', 0, PARAM_INT);
$returnurl = required_param('returnurl', PARAM_LOCALURL);

try {
    $result = \local_copy\paste_service::paste($courseid, $sectionid, $sectionnum, $beforemodule);
    $type = $result['successcount'] > 0
        ? \core\output\notification::NOTIFY_SUCCESS
        : \core\output\notification::NOTIFY_ERROR;
    redirect(new moodle_url($returnurl), $result['message'], null, $type);
} catch (Throwable $exception) {
    redirect(new moodle_url($returnurl), get_string('pasteerror', 'local_copy'), null, \core\output\notification::NOTIFY_ERROR);
}
