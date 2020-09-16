jQuery(document).ready(function($) {
    //console.log(ajax_login_object);

    window.addEventListener('ajx_page_load_success', function (e) {
        $('form#login').css('display', 'none');
        console.log(e);
    });

    if(ajax_login_object.loggedin) {
        getPage();
    } else {
        $('form#login').on('submit', function(e){
            $('form#login p.status').show().text(ajax_login_object.loadingmessage);
            outputStatus('loading');
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
                            window.dispatchEvent( new Event('ajx_redirecting') );
                            document.location.href = ajax_login_object.redirecturl;
                        }
                        else {
                            outputStatus('Page load start');
                            window.dispatchEvent( new Event('ajx_page_load_start') );
                            getPage();
                        }
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
                setTimeout(function(){
                    outputStatus('');
                    $('form#login p.status').hide();
                }, 2000);
                $('.ajx-content').html(data.content);
                //initialize stuff here
                outputStatus('Page loaded');
                window.dispatchEvent( new Event('ajx_page_load_success') );
            },
            error: function(xhr, status, err){
                outputStatus('Page load error');
                window.dispatchEvent( new Event('ajx_page_load_error') );
            }
        });
    }

    function outputStatus(message) {
        $('form#login p.status').text(message);
    } 
});