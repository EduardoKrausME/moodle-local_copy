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
 * @copyright 2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
    return {
        init: function(courseid, pastetext) {
            $(".section .divider.bulk-hidden,.section-modchooser").each(function(id, courseSection) {
                var $coursesection = $(courseSection);

                var $button = $coursesection.find("[data-action=open-chooser]");
                var sectionId = $button.attr("data-sectionnum") || $button.attr("data-sectionid") || $button.attr("data-sectionreturnid");
                var beforeModule = $button.attr("data-beforemod");
                if (!beforeModule) {
                    beforeModule = "";
                }

                var urlreturn = encodeURIComponent(location.href.replace(M.cfg.wwwroot, ""));
                var url = `${M.cfg.wwwroot}/local/copy/paste.php?courseid=${courseid}&section=${sectionId}` +
                    `&beforemodule=${beforeModule}&returnurl=${urlreturn}`;

                $coursesection
                    .addClass("d-flex align-items-center justify-content-center")
                    .append(`
                        <form action="${url}"
                              method="post">
                            <button class="btn add-content local_copy-paste-content p-1"
                                    type="submit">${pastetext}</button>
                        </form>`);
                $coursesection.find("*").css({position: "initial"});
                $coursesection.find("button").css({
                    borderRadius: "10px",
                    width: "auto",
                });
            });

            $(".section-modchooser.activity-add.bulk-hidden").each(function(id, courseSection) {
                var $coursesection = $(courseSection);

                var sectionId = $coursesection.attr("data-sectionnum") || $coursesection.attr("data-sectionid") || $coursesection.attr("data-sectionreturnid");

                var urlreturn = encodeURIComponent(location.href.replace(M.cfg.wwwroot, ""));
                var url = `${M.cfg.wwwroot}/local/copy/paste.php?courseid=${courseid}&section=${sectionId}` +
                    `&returnurl=${urlreturn}`;

                $coursesection.before(`
                        <form action="${url}"
                              method="post">
                            <button class="btn add-content local_copy-paste-content p-1"
                                    type="submit">${pastetext}</button>
                        </form>`);
                $coursesection.find("*").css({position: "initial"});
            });
        }
    };
});
