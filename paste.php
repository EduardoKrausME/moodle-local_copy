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

require_once(__DIR__ . "/../../config.php");

require_once("{$CFG->dirroot}/lib/modinfolib.php");
require_once("{$CFG->dirroot}/course/lib.php");
require_once("{$CFG->dirroot}/backup/util/includes/backup_includes.php");
require_once("{$CFG->dirroot}/backup/util/includes/restore_includes.php");
require_once("{$CFG->libdir}/filelib.php");

$coursemoduleorigem = [];
if (isset($USER->copymodule_ids) && is_array($USER->copymodule_ids) && $USER->copymodule_ids) {
    $coursemoduleorigem = array_values(array_filter(array_map("intval", $USER->copymodule_ids)));
} else if (!empty($USER->copymodule_id)) {
    $coursemoduleorigem = [(int)$USER->copymodule_id];
}
$coursedestino = required_param("courseid", PARAM_INT);
$sectiondestino = required_param("section", PARAM_INT);
$sectiondestinoid = optional_param("sectionid", 0, PARAM_INT);
$beforemodule = optional_param("beforemodule", false, PARAM_INT);
$returnurl = required_param("returnurl", PARAM_RAW);

$USER->copymodule_id = null;
$USER->copymodule_name = null;
$USER->copymodule_ids = null;
$USER->copymodule_names = null;

require_course_login($coursedestino);
$context = \context_course::instance($coursedestino);
require_capability("local/copy:manage", $context);
$PAGE->set_context($context);

$PAGE->set_url(new moodle_url("/local/copy/paste.php",
    [
        "courseid" => $coursedestino,
        "section" => $sectiondestino,
        "beforemodule" => $beforemodule,
    ]));

if (!$coursemoduleorigem) {
    redirect(new moodle_url($returnurl), get_string("pasteempty", "local_copy"), null, \core\output\notification::NOTIFY_ERROR);
}

// Move to local.
$section = $DB->get_record("course_sections", ["id" => $sectiondestino, "course" => $coursedestino]);
if (!$section) {
    $section = $DB->get_record("course_sections", ["section" => $sectiondestino, "course" => $coursedestino]);
}
if (!$section && !empty($sectiondestinoid)) {
    $section = $DB->get_record("course_sections", ["id" => $sectiondestinoid, "course" => $coursedestino]);
}
if ($section && !empty($sectiondestinoid) && (int)$section->id !== (int)$sectiondestinoid) {
    $sectionbyid = $DB->get_record("course_sections", ["id" => $sectiondestinoid, "course" => $coursedestino]);
    if ($sectionbyid) {
        $section = $sectionbyid;
    }
}
if (!$section) {
    redirect(new moodle_url($returnurl), get_string("pasteerror", "local_copy"), null, \core\output\notification::NOTIFY_ERROR);
}

$validbeforemodule = null;
if (!empty($beforemodule)) {
    $beforecm = get_coursemodule_from_id(null, $beforemodule, $coursedestino, false, IGNORE_MISSING);
    if ($beforecm && (int)$beforecm->section === (int)$section->id) {
        $validbeforemodule = (int)$beforemodule;
    }
}
$newcmids = [];

foreach ($coursemoduleorigem as $singlemoduleorigem) {
    $singlemoduleorigem = (int)$singlemoduleorigem;
    if (!$singlemoduleorigem) {
        continue;
    }

    $backupbasepath = null;
    $rc = null;

    try {
        // Backup the activity.
        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $singlemoduleorigem, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id);

        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();

        $bc->execute_plan();
        $bc->destroy();

        // Restore the backup immediately.
        $rc = new restore_controller($backupid, $coursedestino,
            backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);

        // Make sure that the restore_general_groups setting is always enabled when duplicating an activity.
        $plan = $rc->get_plan();
        $groupsetting = $plan->get_setting("groups");
        if (empty($groupsetting->get_value())) {
            $groupsetting->set_value(true);
        }

        $cmcontext = context_module::instance($singlemoduleorigem);
        if (!$rc->execute_precheck()) {
            if ($rc) {
                $rc->destroy();
                $rc = null;
            }
            if ($backupbasepath) {
                fulldelete($backupbasepath);
                $backupbasepath = null;
            }
            continue;
        }

        $rc->execute_plan();

        // Now a bit hacky part follows - we try to get the cmid of the newly
        // restored copy of the module.
        $newcmid = null;
        $tasks = $rc->get_plan()->get_tasks();
        foreach ($tasks as $task) {
            if (is_subclass_of($task, "restore_activity_task")) {
                if ($task->get_old_contextid() == $cmcontext->id) {
                    $newcmid = $task->get_moduleid();
                    break;
                }
            }
        }

        // Delete files backup.
        $rc->destroy();
        $rc = null;
        if ($backupbasepath) {
            fulldelete($backupbasepath);
            $backupbasepath = null;
        }

        if ($newcmid) {
            $newcm = get_coursemodule_from_id(null, $newcmid, $coursedestino);
            if ($newcm) {
                if ($beforemodule) {
                    moveto_module($newcm, $section, $validbeforemodule);
                } else {
                    moveto_module($newcm, $section, null);
                }
                $newcmids[] = $newcmid;
            }
        }
    } catch (\Throwable $exception) {
        if ($rc) {
            $rc->destroy();
        }
        if ($backupbasepath) {
            fulldelete($backupbasepath);
        }
        continue;
    }
}

if ($newcmids) {
    if (count($newcmids) == 1) {
        $message = get_string("pastesuccess", "local_copy");
    } else {
        $message = get_string("pastesuccessbulk", "local_copy", [
            "success" => count($newcmids),
            "total" => count($coursemoduleorigem),
        ]);
    }

    redirect(new moodle_url($returnurl) . "#module-{$newcmids[0]}", $message, null, \core\output\notification::NOTIFY_SUCCESS);
} else {
    redirect(new moodle_url($returnurl), get_string("pasteerror", "local_copy"), null, \core\output\notification::NOTIFY_ERROR);
}
