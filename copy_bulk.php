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

require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

$modulesjson = required_param('modules', PARAM_RAW);
$returnurl = required_param('returnurl', PARAM_LOCALURL);
$moduleids = json_decode($modulesjson, true);

try {
    if (!is_array($moduleids)) {
        throw new moodle_exception('copyederror', 'local_copy');
    }
    $clipboard = \local_copy\copy_service::copy($moduleids);
    redirect(
        new moodle_url($returnurl),
        get_string('copyedsuccessbulk', 'local_copy', count($clipboard['items'])),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
} catch (Throwable $exception) {
    redirect(new moodle_url($returnurl), get_string('copyederror', 'local_copy'), null, \core\output\notification::NOTIFY_ERROR);
}
