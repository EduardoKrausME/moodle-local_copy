define([
    "jquery",
    "core/modal_save_cancel",
    "core/modal_events",
    "core/templates",
    "core/notification"
], function($, ModalSaveCancel, ModalEvents, Templates, Notification) {
    var state = {
        courseid: 0,
        clipboard: {},
        observer: null,
        scanTimer: null
    };

    var getString = function(identifier, a) {
        return M.util.get_string(identifier, "local_copy", a);
    };

    var getModuleId = function($activity) {
        var id = String($activity.attr("id") || "");
        var match = id.match(/^module-(\d+)$/);
        if (match) {
            return parseInt(match[1], 10);
        }

        var candidates = [
            $activity.attr("data-cmid"),
            $activity.attr("data-id"),
            $activity.find("[data-cmid]").first().attr("data-cmid")
        ];
        for (var i = 0; i < candidates.length; i++) {
            if (/^\d+$/.test(String(candidates[i] || ""))) {
                return parseInt(candidates[i], 10);
            }
        }

        return 0;
    };

    var getActivityName = function($activity) {
        var name = $activity.find("[data-activityname]").first().attr("data-activityname");
        if (name) {
            return $.trim(name);
        }

        var $title = $activity.find(".activityname, .instancename, [data-region='activity-name']").first();
        return $.trim($title.text());
    };

    var request = function(data) {
        data.sesskey = M.cfg.sesskey;
        return $.ajax({
            url: M.cfg.wwwroot + "/local/copy/ajax.php",
            method: "POST",
            dataType: "json",
            data: data
        }).then(function(response) {
            if (!response || response.success !== true) {
                return $.Deferred().reject({responseJSON: response}).promise();
            }
            return response;
        });
    };

    var showNotification = function(message, type) {
        if (!message) {
            return;
        }
        Notification.addNotification({
            message: message,
            type: type || "success"
        });
    };

    var consumeFlash = function() {
        try {
            var raw = window.sessionStorage.getItem("localCopyFlash");
            if (!raw) {
                return;
            }
            window.sessionStorage.removeItem("localCopyFlash");
            var flash = JSON.parse(raw);
            showNotification(flash.message, flash.type);
        } catch (e) {
            window.sessionStorage.removeItem("localCopyFlash");
        }
    };

    var storeFlash = function(message, type) {
        try {
            window.sessionStorage.setItem("localCopyFlash", JSON.stringify({
                message: message,
                type: type || "success"
            }));
        } catch (e) {
            // sessionStorage can be disabled without breaking the paste itself.
        }
    };

    var clipboardCount = function() {
        return state.clipboard && Array.isArray(state.clipboard.items) ? state.clipboard.items.length : 0;
    };

    var renderClipboard = function() {
        $("[data-region='local-copy-clipboard']").remove();

        var count = clipboardCount();
        if (!count) {
            return;
        }

        var summary = count === 1
            ? getString("clipboardtitleone")
            : getString("clipboardtitlemany", count);
        var sourcecourse = state.clipboard.coursename
            ? getString("clipboardfrom", state.clipboard.coursename)
            : "";

        Templates.render("local_copy/clipboard", {
            summary: summary,
            sourcecourse: sourcecourse
        }).then(function(html) {
            $("body").append(html);
            if ($("#sticky-footer:visible").length) {
                $("[data-region='local-copy-clipboard']")
                    .addClass("local-copy-clipboard--with-sticky-footer");
            }
        }).catch(Notification.exception);
    };

    var copyModules = function(moduleids) {
        if (!moduleids.length) {
            return;
        }

        request({
            action: "copy",
            modules: JSON.stringify(moduleids)
        }).done(function(response) {
            state.clipboard = response.clipboard || {};
            renderClipboard();
            showNotification(response.message, "success");
        }).fail(function(xhr) {
            var response = xhr.responseJSON || {};
            showNotification(response.message || getString("copyederror"), "error");
        });
    };

    var injectCopyActions = function() {
        $(".section .activity, .course-section .activity").each(function() {
            var $activity = $(this);
            var moduleid = getModuleId($activity);
            if (!moduleid || $activity.find("[data-action='local-copy-copy']").length) {
                return;
            }

            var $button = $("<button>", {
                type: "button",
                "class": "dropdown-item menu-action cm-edit-action local-copy-action",
                "data-action": "local-copy-copy",
                "data-cmid": moduleid
            });
            $button.append($("<i>", {
                "class": "icon fa fa-copy fa-fw",
                "aria-hidden": "true"
            }));
            $button.append($("<span>", {
                "class": "menu-action-text",
                text: getString("copy")
            }));

            var $menu = $activity.find(".dropdown-menu").first();
            if ($menu.length) {
                var $delete = $menu.find(".editing_delete").first();
                if ($delete.length) {
                    $delete.before($button);
                } else {
                    $menu.append($button);
                }
                return;
            }

            var $actions = $activity.find(".activity-actions").first();
            if ($actions.length) {
                $actions.append($button);
            }
        });
    };

    var getSelectedModules = function() {
        var selected = [];
        $(".section .activity, .course-section .activity").each(function() {
            var $activity = $(this);
            if (!$activity.find("input[type='checkbox']:checked").length) {
                return;
            }
            var moduleid = getModuleId($activity);
            if (moduleid && selected.indexOf(moduleid) === -1) {
                selected.push(moduleid);
            }
        });
        return selected;
    };

    var updateBulkButton = function() {
        var $button = $("[data-action='local-copy-copy-selected']");
        if (!$button.length) {
            return;
        }
        var hasSelection = getSelectedModules().length > 0;
        $button.toggleClass("disabled", !hasSelection);
        $button.prop("disabled", !hasSelection);
    };

    var injectBulkButton = function() {
        if ($("[data-action='local-copy-copy-selected']").length) {
            updateBulkButton();
            return;
        }

        var $bulkActions = $("#sticky-footer [data-for='bulkactions']").first();
        if (!$bulkActions.length) {
            return;
        }

        var $button = $("<button>", {
            type: "button",
            "class": "btn py-0 d-flex flex-column local-copy-bulk-btn disabled",
            "data-action": "local-copy-copy-selected",
            "data-bulk": "cm",
            "data-for": "bulkaction",
            title: getString("copyselected"),
            disabled: true
        });
        $button.append($("<span>", {"class": "bulkaction-icon w-100 ps-2"})
            .append($("<i>", {"class": "icon fa fa-copy fa-fw", "aria-hidden": "true"})));
        $button.append($("<span>", {"class": "bulkaction-name", text: getString("copyselected")}));
        $bulkActions.append($("<li>", {"class": "nav-item local-copy-bulk-item"}).append($button));
        updateBulkButton();
    };

    var collectSections = function() {
        var sections = [];
        var seen = {};

        $(".course-section, .section").each(function() {
            var $section = $(this);
            var $chooser = $section.find("[data-action='open-chooser']").first();
            var sectionidRaw = $section.attr("data-sectionid") || $chooser.attr("data-sectionid") || "";
            var sectionnumRaw = $section.attr("data-number") || $section.attr("data-sectionnum") ||
                $chooser.attr("data-sectionnum") || $chooser.attr("data-sectionreturnid") || "";

            if (sectionnumRaw === "") {
                var match = String($section.attr("id") || "").match(/^section-(\d+)$/);
                if (match) {
                    sectionnumRaw = match[1];
                }
            }

            var sectionid = /^\d+$/.test(String(sectionidRaw)) ? parseInt(sectionidRaw, 10) : 0;
            var sectionnum = /^\d+$/.test(String(sectionnumRaw)) ? parseInt(sectionnumRaw, 10) : -1;
            if (!sectionid && sectionnum < 0) {
                return;
            }

            var key = sectionid ? "id:" + sectionid : "num:" + sectionnum;
            if (seen[key]) {
                return;
            }
            seen[key] = true;

            var name = $.trim($section.find(".sectionname, [data-for='section_title']").first().text());
            if (!name) {
                name = getString("sectionfallback", sectionnum >= 0 ? sectionnum : sections.length);
            }

            var activities = [];
            $section.find(".activity").each(function() {
                var $activity = $(this);
                var moduleid = getModuleId($activity);
                if (!moduleid) {
                    return;
                }
                activities.push({
                    id: moduleid,
                    name: getActivityName($activity) || "#" + moduleid
                });
            });

            sections.push({
                id: sectionid,
                num: sectionnum,
                name: name,
                activities: activities
            });
        });

        return sections;
    };

    var fillPositionSelect = function($root, section) {
        var $select = $root.find("[data-region='local-copy-position']");
        $select.empty();

        var firstid = section.activities.length ? section.activities[0].id : 0;
        $select.append($("<option>", {
            value: firstid,
            text: getString("positionstart")
        }));
        $select.append($("<option>", {
            value: 0,
            text: getString("positionend")
        }));

        section.activities.forEach(function(activity, index) {
            $select.append($("<option>", {
                value: activity.id,
                text: getString("positionbefore", activity.name)
            }));
            var nextid = section.activities[index + 1] ? section.activities[index + 1].id : 0;
            $select.append($("<option>", {
                value: nextid,
                text: getString("positionafter", activity.name)
            }));
        });
    };

    var showPasteErrors = function($root, errors, fallbackMessage) {
        var $errors = $root.find("[data-region='local-copy-errors']");
        $errors.empty().removeClass("d-none");
        if (!errors || !errors.length) {
            $errors.text(fallbackMessage || getString("pasteerror"));
            return;
        }

        var $list = $("<ul>", {"class": "mb-0 ps-3"});
        errors.forEach(function(error) {
            var label = error.name ? error.name + ": " : "";
            $list.append($("<li>").text(label + (error.message || getString("pasteerror"))));
        });
        $errors.append($list);
    };

    var openPasteModal = function() {
        var sections = collectSections();
        if (!sections.length) {
            showNotification(getString("pasteerror"), "error");
            return;
        }

        var createPasteModal = function(body) {
            var config = {
                title: getString("pastemodaltitle"),
                body: body,
                removeOnClose: true
            };

            if (typeof ModalSaveCancel.create === "function") {
                return ModalSaveCancel.create(config);
            }

            var deferred = $.Deferred();
            require(["core/modal_factory"], function(ModalFactory) {
                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: config.title,
                    body: config.body
                }).then(deferred.resolve).catch(deferred.reject);
            }, deferred.reject);
            return deferred.promise();
        };

        Templates.render("local_copy/paste_modal", {}).then(function(body) {
            return createPasteModal(body);
        }).then(function(modal) {
            var $root = modal.getRoot();
            var $section = $root.find("[data-region='local-copy-section']");

            sections.forEach(function(section, index) {
                $section.append($("<option>", {
                    value: index,
                    text: section.name
                }));
            });
            fillPositionSelect($root, sections[0]);

            $section.on("change", function() {
                var index = parseInt($(this).val(), 10) || 0;
                fillPositionSelect($root, sections[index]);
                $root.find("[data-region='local-copy-errors']").addClass("d-none").empty();
            });

            var count = clipboardCount();
            if (typeof modal.setSaveButtonText === "function") {
                modal.setSaveButtonText(count === 1
                    ? getString("pastebuttonone")
                    : getString("pastebuttonmany", count));
            }

            $root.on(ModalEvents.save, function(event) {
                event.preventDefault();
                var index = parseInt($section.val(), 10) || 0;
                var selectedSection = sections[index];
                var beforemodule = parseInt($root.find("[data-region='local-copy-position']").val(), 10) || 0;
                var $save = $root.find("[data-action='save']");
                $save.prop("disabled", true);
                if (typeof modal.setSaveButtonText === "function") {
                    modal.setSaveButtonText(getString("pasting"));
                }

                request({
                    action: "paste",
                    courseid: state.courseid,
                    sectionid: selectedSection.id,
                    sectionnum: selectedSection.num,
                    beforemodule: beforemodule
                }).done(function(response) {
                    state.clipboard = response.clipboard || state.clipboard;
                    var result = response.result || {};
                    if ((result.successcount || 0) > 0) {
                        var type = result.errors && result.errors.length ? "warning" : "success";
                        var message = result.message || getString("pasteerror");
                        if (result.errors && result.errors.length) {
                            var details = result.errors.map(function(error) {
                                return (error.name ? error.name + ": " : "") + (error.message || "");
                            }).join(" | ");
                            message += " " + details;
                        }
                        storeFlash(message, type);
                        window.location.reload();
                        return;
                    }

                    showPasteErrors($root, result.errors, result.message);
                    $save.prop("disabled", false);
                    if (typeof modal.setSaveButtonText === "function") {
                        modal.setSaveButtonText(count === 1
                            ? getString("pastebuttonone")
                            : getString("pastebuttonmany", count));
                    }
                }).fail(function(xhr) {
                    var response = xhr.responseJSON || {};
                    showPasteErrors($root, [], response.message || getString("pasteerror"));
                    $save.prop("disabled", false);
                    if (typeof modal.setSaveButtonText === "function") {
                        modal.setSaveButtonText(count === 1
                            ? getString("pastebuttonone")
                            : getString("pastebuttonmany", count));
                    }
                });
            });

            modal.show();
        }).catch(Notification.exception);
    };

    var clearClipboard = function() {
        request({action: "clear"}).done(function(response) {
            state.clipboard = {};
            renderClipboard();
            showNotification(response.message || getString("clipboardcleared"), "success");
        }).fail(function(xhr) {
            var response = xhr.responseJSON || {};
            showNotification(response.message || getString("unexpectederror"), "error");
        });
    };

    var scheduleScan = function() {
        window.clearTimeout(state.scanTimer);
        state.scanTimer = window.setTimeout(function() {
            injectCopyActions();
            injectBulkButton();
        }, 80);
    };

    var bindEvents = function() {
        $(document).off("click.localCopy", "[data-action='local-copy-copy']")
            .on("click.localCopy", "[data-action='local-copy-copy']", function(event) {
                event.preventDefault();
                event.stopPropagation();
                var moduleid = parseInt($(this).attr("data-cmid"), 10) || 0;
                if (moduleid) {
                    copyModules([moduleid]);
                }
            });

        $(document).off("click.localCopyBulk", "[data-action='local-copy-copy-selected']")
            .on("click.localCopyBulk", "[data-action='local-copy-copy-selected']", function(event) {
                event.preventDefault();
                copyModules(getSelectedModules());
            });

        $(document).off("change.localCopyBulk", ".section .activity input[type='checkbox'], .course-section .activity input[type='checkbox']")
            .on("change.localCopyBulk", ".section .activity input[type='checkbox'], .course-section .activity input[type='checkbox']", updateBulkButton);

        $(document).off("click.localCopyPaste", "[data-action='local-copy-paste']")
            .on("click.localCopyPaste", "[data-action='local-copy-paste']", openPasteModal);

        $(document).off("click.localCopyClear", "[data-action='local-copy-clear']")
            .on("click.localCopyClear", "[data-action='local-copy-clear']", clearClipboard);
    };

    var observePageChanges = function() {
        if (state.observer) {
            state.observer.disconnect();
        }
        state.observer = new MutationObserver(scheduleScan);
        state.observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    };

    return {
        init: function(courseid, clipboard) {
            state.courseid = parseInt(courseid, 10) || 0;
            state.clipboard = clipboard || {};
            bindEvents();
            injectCopyActions();
            injectBulkButton();
            renderClipboard();
            consumeFlash();
            observePageChanges();
        }
    };
});
