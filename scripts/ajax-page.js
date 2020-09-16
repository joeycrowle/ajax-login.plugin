jQuery(document).ready(function($) {
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajax_page_object.ajaxurl,
            data: { 
                'action': 'ajaxpageload',
                'pageid': ajax_page_object.pageid
            },
            success: function(data){
                $('.ajx-content').html(data.content);
                //initialize stuff here
            },
            error: function(xhr, status, err) {
                $('.ajx-content').html(status);
            }
        });
});