jQuery(document).ready(function($) {
    console.log(ajax_login_object);


    // window.addEventListener('ajx_validation_error', function (e) {
    //     console.log(e);
    // });

    if(ajax_login_object.loggedin) {
        getPage();
    } else {
        $('form#login').on('submit', function(e){
            outputStatus(ajax_login_object.form_loading_message);
            window.dispatchEvent( new Event('ajx_form_loading') );

            $.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajax_login_object.ajaxurl,
                data: { 
                    'action': 'ajaxlogin',
                    'username': $('form#login #username').val(), 
                    'password': $('form#login #password').val(), 
                    'security': $('form#login #security').val(),
                    'pageid': ajax_login_object.pageid
                },
                success: function(data){
                    if (data.loggedin == true){    
                        if(ajax_login_object.redirect) {
                            outputStatus(ajax_login_object.redirect_message);
                            window.dispatchEvent( new Event('ajx_redirecting') );
                            document.location.href = ajax_login_object.redirecturl;
                        }
                        else {
                            outputStatus(ajax_login_object.page_loading_message);
                            window.dispatchEvent( new Event('ajx_page_load_start') );
                            getPage();
                        }
                    } else {
                        outputStatus(ajax_login_object.validation_error_message);
                        window.dispatchEvent( new Event('ajx_validation_error') );
                    }
                }
            });
            e.preventDefault();
        });
    }

    function getPage() {
        //document trigger ajx_pageload
        $.ajax({
            type: 'POST',
            dataType: 'json',
            url: ajax_login_object.ajaxurl,
            data: { 
                'action': 'ajaxgetpage', 
                'pageid': ajax_login_object.pageid
            },
            success: function(data){
                $('.ajx-content').html(data.content);
                outputStatus(ajax_login_object.page_loaded_message);
                window.dispatchEvent( new Event('ajx_page_load') );
            },
            error: function(xhr, status, err){
                outputStatus(ajax_login_object.page_load_error_message);
                window.dispatchEvent( new Event('ajx_page_load_error') );
            }
        });
    }

    var timer;
    function outputStatus(message) {
        if(ajax_login_object.showmessages) {
            clearTimeout(timer);
            timer = setTimeout(function(){
                $('form#login p.status').text("").hide();
            }, 1700);
            $('form#login p.status').show().text(message);
        }
    } 
});