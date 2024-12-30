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
 * File that generates the link to paste the module
 *
 * @package   local_copy
 * @copyright 2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
    return {
        init: function(courseid, pastetext) {
            $(".course-section").each(function(id, courseSection) {
                var $coursesection = $(courseSection);

                var sectionId = $coursesection.attr("data-id");

                $coursesection.find(".activity").each(function(id, activity) {
                    var $activity = $(activity);
                    var modId = $activity.attr("data-id");

                    var urlreturn = encodeURIComponent(location.href);
                    var url = `${M.cfg.wwwroot}/local/copy/paste.php?courseid=${courseid}&section=${sectionId}` +
                        `&beforemodule=${modId}&returnurl=${urlreturn}`;
                    $activity.find(".divider")
                        .addClass("d-flex")
                        .addClass("align-items-center")
                        .append(`&nbsp;&nbsp;
                            <form action="${url}"
                                  method="post">
                                <button class="btn btn-link"
                                        style="width:auto;border-radius:14px;"
                                        type="submit">&nbsp;&nbsp;${pastetext}&nbsp;&nbsp;</button>
                            </form>`);
                });
            });
        }
    };
});
