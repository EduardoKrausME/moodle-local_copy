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
 * Ajax file
 *
 * @package   local_copy
 * @copyright 2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_sesskey();

header('Content-Type: application/json; charset=utf-8');

$action = required_param('action', PARAM_ALPHA);

try {
    switch ($action) {
        case 'copy':
            $modulesjson = required_param('modules', PARAM_RAW);
            $moduleids = json_decode($modulesjson, true);
            if (!is_array($moduleids)) {
                throw new moodle_exception('copyederror', 'local_copy');
            }
            $clipboard = \local_copy\copy_service::copy($moduleids);
            $count = count($clipboard['items']);
            $message = $count === 1
                ? get_string('copyedsuccess', 'local_copy')
                : get_string('copyedsuccessbulk', 'local_copy', $count);
            echo json_encode([
                'success' => true,
                'clipboard' => $clipboard,
                'message' => $message,
            ]);
            break;

        case 'clear':
            \local_copy\clipboard::clear();
            echo json_encode([
                'success' => true,
                'clipboard' => [],
                'message' => get_string('clipboardcleared', 'local_copy'),
            ]);
            break;

        case 'paste':
            $courseid = required_param('courseid', PARAM_INT);
            $sectionid = optional_param('sectionid', 0, PARAM_INT);
            $sectionnum = optional_param('sectionnum', -1, PARAM_INT);
            $beforemodule = optional_param('beforemodule', 0, PARAM_INT);
            $result = \local_copy\paste_service::paste($courseid, $sectionid, $sectionnum, $beforemodule);
            echo json_encode([
                'success' => true,
                'result' => $result,
                'clipboard' => \local_copy\clipboard::get(),
            ]);
            break;

        default:
            throw new moodle_exception('invalidaction', 'local_copy');
    }
} catch (Throwable $exception) {
    http_response_code(400);
    debugging('local_copy ajax error: ' . $exception->getMessage(), DEBUG_DEVELOPER);
    echo json_encode([
        'success' => false,
        'message' => $exception instanceof moodle_exception
            ? $exception->getMessage()
            : get_string('unexpectederror', 'local_copy'),
    ]);
}
exit;
