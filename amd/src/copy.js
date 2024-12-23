define(["jquery"], function($) {
    return {
        init: function(copytext) {
            $(".section .activity").each(function(id, element) {
                var $element = $(element);

                var modId = $element.attr("data-id");
                var name = encodeURIComponent($element.find(".activity-item").attr("data-activityname"));
                var urlreturn = encodeURIComponent(location.href);
                $element.find(".editing_delete")
                    .before(`<a href="/local/copy/copy.php?module=${modId}&name=${name}&returnurl=${urlreturn}"
                                class="dropdown-item menu-action cm-edit-action">
                                 <i class="icon fa fa-copy fa-fw"></i>
                                 <span class="menu-action-text">${copytext}</span>
                             </a>`);
            });
        }
    };
});
