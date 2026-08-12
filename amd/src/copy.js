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
 * @copyright 2024 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(["jquery"], function($) {
    return {
        init: function(copytext, copyselectedtext) {
            $(".section .activity").each(function(id, element) {
                var $element = $(element);

                var modId = $element.attr("id").replace(/\D/g, '');
                var name = encodeURIComponent($element.find("[data-activityname]").attr("data-activityname"));
                var urlreturn = encodeURIComponent(location.href.replace(M.cfg.wwwroot, ""));
                var copylink = `<a href="${M.cfg.wwwroot}/local/copy/copy.php?module=${modId}&name=${name}&returnurl=${urlreturn}"
                                class="dropdown-item menu-action cm-edit-action local-copy-action">
                                 <i class="icon fa fa-copy fa-fw"></i>
                                 <span class="menu-action-text">${copytext}</span>
                              </a>`;

                if ($element.find(".editing_delete").length) {
                    $element.find(".editing_delete").before(copylink);
                } else if ($element.find(".dropdown-menu").length) {
                    $element.find(".dropdown-menu").first().append(copylink);
                } else if ($element.find(".activity-actions").length) {
                    $element.find(".activity-actions").first().append(copylink);
                }
            });

            var $bulkButton = $(
                `<button type="button" class="btn py-0 d-flex flex-column disabled local-copy-bulk-btn" data-action="cmCopyClipboard" data-bulk="cm" data-for="bulkaction" title="${copyselectedtext}" tabindex="-1">
                    <span class="bulkaction-icon w-100 ps-2"><i class="icon fa fa-copy fa-fw" aria-hidden="true"></i></span>
                    <span class="bulkaction-name">${copyselectedtext}</span>
                </button>`
            );
            var $bulkButtonItem = $("<li class='nav-item local-copy-bulk-item'></li>").append($bulkButton);
            var $bulkActions = $("#sticky-footer [data-for='bulkactions']").first();
            if ($bulkActions.length) {
                $bulkActions.append($bulkButtonItem);
            }

            var getSelectedModules = function() {
                var selected = [];
                $(".section .activity").each(function() {
                    var $activity = $(this);
                    var checked = $activity.find("input[type='checkbox']:checked").length > 0;
                    if (!checked) {
                        return;
                    }

                    var modId = ($activity.attr("id") || "").replace(/\D/g, "");
                    if (!modId) {
                        return;
                    }

                    var activityname = $activity.find("[data-activityname]").attr("data-activityname") || "";
                    selected.push({
                        id: parseInt(modId, 10),
                        name: activityname
                    });
                });

                return selected;
            };

            var updateBulkButtonVisibility = function() {
                var selected = getSelectedModules();
                if ($bulkActions.length && selected.length > 0) {
                    $bulkButton.removeClass("disabled").removeAttr("tabindex");
                } else {
                    $bulkButton.addClass("disabled").attr("tabindex", "-1");
                }
            };

            $(document).on("change", ".section .activity input[type='checkbox']", function() {
                updateBulkButtonVisibility();
            });

            $bulkButton.on("click", function() {
                var selected = getSelectedModules();
                if (!selected.length) {
                    return;
                }

                var modules = selected.map(function(item) {
                    return item.id;
                });
                var names = selected.map(function(item) {
                    return item.name;
                });

                var $form = $("<form method='post'></form>");
                $form.attr("action", `${M.cfg.wwwroot}/local/copy/copy_bulk.php`);
                $form.append($("<input type='hidden' name='sesskey'>").val(M.cfg.sesskey));
                $form.append($("<input type='hidden' name='modules'>").val(JSON.stringify(modules)));
                $form.append($("<input type='hidden' name='names'>").val(JSON.stringify(names)));
                $form.append($("<input type='hidden' name='returnurl'>").val(location.href.replace(M.cfg.wwwroot, "")));
                $("body").append($form);
                $form.submit();
            });

            updateBulkButtonVisibility();
        }
    };
});
