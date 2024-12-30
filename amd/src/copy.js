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
 * File that copies the modules and saves them in memory
 *
 * @package   local_copy
 * @copyright 2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
    return {
        init: function(copytext) {
            $(".section .activity").each(function(id, element) {
                var $element = $(element);

                var modId = $element.attr("data-id");
                var name = encodeURIComponent($element.find(".activity-item").attr("data-activityname"));
                var urlreturn = encodeURIComponent(location.href);
                $element.find(".editing_delete")
                    .before(`<a href="${M.cfg.wwwroot}/local/copy/copy.php?module=${modId}&name=${name}&returnurl=${urlreturn}"
                                class="dropdown-item menu-action cm-edit-action">
                                 <i class="icon fa fa-copy fa-fw"></i>
                                 <span class="menu-action-text">${copytext}</span>
                             </a>`);
            });
        }
    };
});
