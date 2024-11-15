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
