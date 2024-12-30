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
 * @copyright 2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");

require_once("{$CFG->dirroot}/lib/modinfolib.php");
require_once("{$CFG->dirroot}/course/lib.php");
require_once("{$CFG->dirroot}/backup/util/includes/backup_includes.php");
require_once("{$CFG->dirroot}/backup/util/includes/restore_includes.php");
require_once("{$CFG->libdir}/filelib.php");

$coursemoduleorigem = $USER->copymodule_id;
$coursedestino = required_param("courseid", PARAM_INT);
$sectiondestino = required_param("section", PARAM_INT);
$beforemodule = required_param("beforemodule", PARAM_INT);

$USER->copymodule_id = null;

require_course_login($coursedestino);
$context = \context_course::instance($coursedestino);
require_capability("local/copy:manage", $context);
$PAGE->set_context($context);

// Backup the activity.
$bc = new backup_controller(backup::TYPE_1ACTIVITY, $coursemoduleorigem, backup::FORMAT_MOODLE,
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

$cmcontext = context_module::instance($coursemoduleorigem);
if (!$rc->execute_precheck()) {
    $precheckresults = $rc->get_precheck_results();
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

$rc->destroy();

fulldelete($backupbasepath);

if ($newcmid) {
    $newcm = get_coursemodule_from_id(null, $newcmid, $coursedestino);
    $section = $DB->get_record("course_sections", ["id" => $sectiondestino, "course" => $coursedestino]);
    moveto_module($newcm, $section, $beforemodule);

    $returnurl = required_param("returnurl", PARAM_RAW);
    $returnurl = str_replace($CFG->wwwroot, "", $returnurl);
    list($return, $outro) = explode("#", $returnurl);
    redirect($return . "#module-{$newcmid}", get_string("pastesuccess", "local_copy"), null,
        \core\output\notification::NOTIFY_SUCCESS);
} else {
    $returnurl = required_param("returnurl", PARAM_RAW);
    $returnurl = str_replace($CFG->wwwroot, "", $returnurl);
    redirect($returnurl, get_string("pastesuccess", "local_copy"), null, \core\output\notification::NOTIFY_SUCCESS);
}
