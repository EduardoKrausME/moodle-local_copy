<?php

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
