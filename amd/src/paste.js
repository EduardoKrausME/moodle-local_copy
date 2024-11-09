define(["jquery"], function($) {
    return {
        init : function(courseid, pastetext) {
            $(".course-section").each(function(id, course_section) {
                var $coursesection = $(course_section);

                var section_id = $coursesection.attr("data-id");

                $coursesection.find(".activity").each(function(id, activity) {
                    var $activity = $(activity);
                    var mod_id = $activity.attr("data-id");

                    var urlreturn = encodeURIComponent(location.href);
                    $activity.find(".divider")
                        .addClass("d-flex")
                        .addClass("align-items-center")
                        .append(`&nbsp;&nbsp;
                            <form action="/local/copy/paste.php?courseid=${courseid}&section=${section_id}&beforemodule=${mod_id}&returnurl=${urlreturn}" method="post">
                                <button class="btn btn-link" 
                                        style="width:auto;border-radius:14px;" 
                                        type="submit">&nbsp;&nbsp;${pastetext}&nbsp;&nbsp;</button>
                            </form>`)
                });
            });
        }
    };
});
