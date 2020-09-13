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
                console.log(ajax_page_object);
                console.log(data);
                $('.ajx-content').html(data.content);
            }
        });
});