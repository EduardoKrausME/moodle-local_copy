define(["jquery"], function($) {
    return {
        init : function(copytext) {
            $('.section .activity').each(function(id, element) {
                var $element = $(element);

                var mod_id = $element.attr('data-id');
                var name = encodeURIComponent($element.find('.activity-item').attr('data-activityname'));
                var urlreturn = encodeURIComponent(location.href);
                $element.find('.activity-actions')
                    .addClass('d-flex')
                    .addClass('align-items-center')
                    .append(`<a href="/local/copy/copy.php?module=${mod_id}&name=${name}&returnurl=${urlreturn}">${copytext}</a>`)
            });
        }
    };
});
