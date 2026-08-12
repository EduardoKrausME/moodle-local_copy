<?php

namespace local_copy;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->libdir . '/filelib.php');

/**
 * Pastes clipboard activities into a target course section.
 */
class paste_service {
    /**
     * Paste all current clipboard items.
     *
     * @param int $courseid Destination course ID.
     * @param int $sectionid Destination course_sections ID, or 0 when unavailable.
     * @param int $sectionnum Destination section number, or -1 when unavailable.
     * @param int $beforemodule Insert before this cmid, or 0 for the section end.
     * @return array
     */
    public static function paste(int $courseid, int $sectionid, int $sectionnum, int $beforemodule = 0): array {
        global $DB, $USER;

        $clipboard = clipboard::get();
        $items = $clipboard['items'] ?? [];
        if (!$items) {
            throw new \moodle_exception('pasteempty', 'local_copy');
        }

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        require_course_login($course);
        $coursecontext = \context_course::instance($courseid);
        require_capability('local/copy:manage', $coursecontext);

        $section = self::resolve_section($courseid, $sectionid, $sectionnum);
        $validbeforemodule = self::validate_before_module($courseid, (int)$section->id, $beforemodule);

        $newcmids = [];
        $errors = [];
        foreach ($items as $item) {
            $sourcecmid = (int)($item['cmid'] ?? 0);
            $itemname = (string)($item['name'] ?? '');
            if (!$sourcecmid) {
                continue;
            }

            $sourcecm = get_coursemodule_from_id(null, $sourcecmid, 0, false, IGNORE_MISSING);
            if (!$sourcecm) {
                $errors[] = [
                    'name' => $itemname,
                    'message' => get_string('pastesourcemissing', 'local_copy'),
                ];
                continue;
            }

            $sourcecontext = \context_module::instance($sourcecmid, IGNORE_MISSING);
            if (!$sourcecontext || !has_capability('local/copy:manage', $sourcecontext)) {
                $errors[] = [
                    'name' => $itemname,
                    'message' => get_string('pastesourcepermission', 'local_copy'),
                ];
                continue;
            }

            $backupcontroller = null;
            $restorecontroller = null;
            $backupbasepath = null;

            try {
                $backupcontroller = new \backup_controller(
                    \backup::TYPE_1ACTIVITY,
                    $sourcecmid,
                    \backup::FORMAT_MOODLE,
                    \backup::INTERACTIVE_NO,
                    \backup::MODE_IMPORT,
                    $USER->id
                );

                $backupid = $backupcontroller->get_backupid();
                $backupbasepath = $backupcontroller->get_plan()->get_basepath();
                $backupcontroller->execute_plan();
                $backupcontroller->destroy();
                $backupcontroller = null;

                $restorecontroller = new \restore_controller(
                    $backupid,
                    $courseid,
                    \backup::INTERACTIVE_NO,
                    \backup::MODE_IMPORT,
                    $USER->id,
                    \backup::TARGET_CURRENT_ADDING
                );

                $plan = $restorecontroller->get_plan();
                try {
                    $groupsetting = $plan->get_setting('groups');
                    if ($groupsetting && empty($groupsetting->get_value())) {
                        $groupsetting->set_value(true);
                    }
                } catch (\Throwable $ignored) {
                    // Some activity backup plans do not expose a groups setting.
                }

                if (!$restorecontroller->execute_precheck()) {
                    throw new \moodle_exception('pasteprecheckfailed', 'local_copy');
                }

                $restorecontroller->execute_plan();
                $newcmid = self::find_restored_module_id($restorecontroller, $sourcecontext->id);
                if (!$newcmid) {
                    throw new \moodle_exception('pastenewmodulemissing', 'local_copy');
                }

                $newcm = get_coursemodule_from_id(null, $newcmid, $courseid, false, IGNORE_MISSING);
                if (!$newcm) {
                    throw new \moodle_exception('pastenewmodulemissing', 'local_copy');
                }

                moveto_module($newcm, $section, $validbeforemodule ?: null);
                $newcmids[] = (int)$newcmid;
            } catch (\Throwable $exception) {
                debugging('local_copy paste failed for cmid ' . $sourcecmid . ': ' . $exception->getMessage(), DEBUG_DEVELOPER);
                $message = get_string('pasteitemerror', 'local_copy');
                if (has_capability('moodle/site:config', \context_system::instance())) {
                    $message .= ' ' . clean_text($exception->getMessage());
                }
                $errors[] = [
                    'name' => $itemname ?: '#' . $sourcecmid,
                    'message' => $message,
                ];
            } finally {
                if ($restorecontroller) {
                    try {
                        $restorecontroller->destroy();
                    } catch (\Throwable $ignored) {
                    }
                }
                if ($backupcontroller) {
                    try {
                        $backupcontroller->destroy();
                    } catch (\Throwable $ignored) {
                    }
                }
                if ($backupbasepath && is_dir($backupbasepath)) {
                    fulldelete($backupbasepath);
                }
            }
        }

        $successcount = count($newcmids);
        $total = count($items);
        if ($successcount === $total) {
            $message = $total === 1
                ? get_string('pastesuccess', 'local_copy')
                : get_string('pastesuccessbulk', 'local_copy', ['success' => $successcount, 'total' => $total]);
        } else if ($successcount > 0) {
            $message = get_string('pastepartial', 'local_copy', ['success' => $successcount, 'total' => $total]);
        } else {
            $message = get_string('pasteerror', 'local_copy');
        }

        return [
            'successcount' => $successcount,
            'total' => $total,
            'newcmids' => $newcmids,
            'errors' => $errors,
            'message' => $message,
        ];
    }

    /**
     * Resolve a destination section using an explicit DB ID first and section number second.
     *
     * @param int $courseid Course ID.
     * @param int $sectionid course_sections.id.
     * @param int $sectionnum course_sections.section.
     * @return \stdClass
     */
    private static function resolve_section(int $courseid, int $sectionid, int $sectionnum): \stdClass {
        global $DB;

        $section = false;
        if ($sectionid > 0) {
            $section = $DB->get_record('course_sections', ['id' => $sectionid, 'course' => $courseid]);
        }
        if (!$section && $sectionnum >= 0) {
            $section = $DB->get_record('course_sections', ['section' => $sectionnum, 'course' => $courseid]);
        }
        if (!$section) {
            throw new \moodle_exception('pasteinvalidsection', 'local_copy');
        }

        return $section;
    }

    /**
     * Ensure the requested insertion reference belongs to the selected section.
     *
     * @param int $courseid Course ID.
     * @param int $sectionid Section DB ID.
     * @param int $beforemodule Course module ID.
     * @return int
     */
    private static function validate_before_module(int $courseid, int $sectionid, int $beforemodule): int {
        if ($beforemodule <= 0) {
            return 0;
        }

        $beforecm = get_coursemodule_from_id(null, $beforemodule, $courseid, false, IGNORE_MISSING);
        if (!$beforecm || (int)$beforecm->section !== $sectionid) {
            return 0;
        }

        return $beforemodule;
    }

    /**
     * Find the restored course module ID from the restore plan.
     *
     * @param \restore_controller $restorecontroller Restore controller.
     * @param int $oldcontextid Source module context ID.
     * @return int
     */
    private static function find_restored_module_id(\restore_controller $restorecontroller, int $oldcontextid): int {
        foreach ($restorecontroller->get_plan()->get_tasks() as $task) {
            if (is_subclass_of($task, 'restore_activity_task') && (int)$task->get_old_contextid() === $oldcontextid) {
                return (int)$task->get_moduleid();
            }
        }
        return 0;
    }
}
