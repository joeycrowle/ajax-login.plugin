<?php
/**
 * 
 * Plugin Name: Ajax Login
 * Description: Adds front-end AJAX login functionality [ajax-login] [ajax-logout]
 * Author: <a href="https://line49.ca">Line49</a>
 * Version: 1.0
 */

 define('AJX_URL', plugins_url() . '/ajax-login-l49');

////////////////////////////////////////////
//INIT PLUGIN///////////////////////////////
////////////////////////////////////////////

function ajax_login_init(){
    if(!get_option('ajax_login_load_css')) {
        add_option('ajax_login_load_css', true);
        add_option('ajax_login_redirect_url', home_url());
        add_option('ajax_login_logout_url', home_url());
        add_option('ajax_login_use_redirect', false);
        add_option('ajax_login_use_logout_redirect', false);
        add_option('ajax_login_show_messages', false);
        add_option('ajax_login_loading_message', 'Loading..');
        add_option('ajax_login_page_loading_message', 'Page Loading..');
        add_option('ajax_login_redirecting_message', 'Redirecting..');
        add_option('ajax_login_page_load_error_message', 'Error Loading Page');
        add_option('ajax_login_page_load_message', 'Loaded');
        add_option('ajax_login_validation_error_message', 'Incorrect Username or Password');
    }
}
add_action('init', 'ajax_login_init');



////////////////////////////////////////////
//LOAD ASSETS///////////////////////////////
////////////////////////////////////////////

function enqueue_ajx_assets() {
    $page = get_queried_object();
    $id = $page->ID;
    $template_slug = get_page_template_slug($id);

    //load default form style if option is selected
    if( get_option('ajax_login_load_css') ) {
        wp_register_style( 'ajax-login-css', AJX_URL . '/styles/ajax-login.css' );
        wp_enqueue_style( 'ajax-login-css' );
    }

    //load assets if template slug contains 'ajx'
    if( strpos($template_slug, 'ajx') !== false ) {        
        wp_register_script('ajax-login-script', AJX_URL . '/scripts/ajax-login.js', array('jquery') ); 
        wp_enqueue_script('ajax-login-script');
        wp_localize_script( 'ajax-login-script', 'ajax_login_object', array( 
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'redirect' => get_option('ajax_login_use_redirect'),
            'redirecturl' => get_option('ajax_login_redirect_url'),
            'form_loading_message' => __(get_option('ajax_login_loading_message')),
            'page_loading_message' => __(get_option('ajax_login_page_loading_message')),
            'page_loaded_message' => __(get_option('ajax_login_page_load_message')),
            'page_load_error_message' => __(get_option('ajax_login_page_load_error_message')),
            'validation_error_message' => __(get_option('ajax_login_validation_error_message')),
            'redirect_message' => __(get_option('ajax_login_redirecting_message')),
            'pageid' => get_the_ID(),
            'loggedin' => is_user_logged_in(),
            'showmessages' => get_option('ajax_login_show_messages')
        ));  
    }
}

add_action( 'wp_enqueue_scripts', 'enqueue_ajx_assets', 0 ); 



////////////////////////////////////////////
//AJAX FUNCTIONS////////////////////////////
////////////////////////////////////////////

function ajx_page_load() {
    $hash = md5(get_site_url());
    $loggedin = isset($_COOKIE['wordpress_logged_in_'.$hash]);
    $pageid = $_POST['pageid'];
    $content = get_post($pageid)->post_content;

    if($loggedin) {
        echo json_encode(array(
            'content' => do_shortcode($content),
            'user' => wp_get_current_user()
        ));
    }else {
        echo json_encode(array(
            'content' => 'not logged in',
            'user' => null,
        ));
    }
    die();
}
add_action('wp_ajax_ajaxpageload', 'ajx_page_load');
add_action('wp_ajax_nopriv_ajaxpageload', 'ajx_page_load');

function ajax_login(){
    check_ajax_referer( 'ajax-login-nonce', 'security' );
    $info = array();
    $info['user_login'] = $_POST['username'];
    $info['user_password'] = $_POST['password'];
    $info['remember'] = true;
    $user_signon = wp_signon($info, is_ssl() ? true : false);
    
    if ( is_wp_error($user_signon) ){
        echo json_encode(array(
            'loggedin'=>false, 
            'message'=>__('Wrong username or password.'
        )));
    } else {
        echo json_encode(array(
            'info'=>$user_signon, 
            'loggedin'=>true, 'message'=>__('Login successful, redirecting...'),
        ));
    }
    die();
}  
add_action( 'wp_ajax_nopriv_ajaxlogin', 'ajax_login' );

function ajax_get_page() {
    $pageid = $_POST['pageid'];
    $content = get_post($pageid)->post_content;

    echo json_encode(array(
        'content' => do_shortcode($content)
    ));
    die();
}
add_action('wp_ajax_ajaxgetpage', 'ajax_get_page');



////////////////////////////////////////////
//SHORTCODES////////////////////////////////
////////////////////////////////////////////
function statusField() {
    if(get_option('ajax_login_show_messages')) { 
        return '<li>
        <p class="status"></p>
    </li>';
    }
    
}

 //[ajax-login]
function ajax_form_shortcode(){
    if(!is_user_logged_in()) {
        return '<form id="login" class="ajax-login-form" action="login" method="post">
        <ul>
            <li>
                <input id="username" type="text" placeholder="Username" name="username">
            </li>
            <li>
                <input id="password" type="password" placeholder="Password" name="password">
            </li>
            <li>
                <input class="submit_button" type="submit" value="Login" name="submit">
            </li>' .
            statusField() .
         '</ul>' .
        wp_nonce_field( 'ajax-login-nonce', 'security' ) .
        '</form>';
    }
}
add_shortcode('ajax-login', 'ajax_form_shortcode');

//[ajax-logout]
function logout_shortcode() {
    $redirect = get_option('ajax_login_use_logout_redirect') ? get_option('ajax_login_logout_url') : get_permalink();
    if(is_user_logged_in()) {
        return '<a class="logout_button" href='. wp_logout_url( $redirect ) .'>Logout</a>';
    }
}
add_shortcode('ajax-logout', 'logout_shortcode');




////////////////////////////////////////////
//ADMIN SETTINGS////////////////////////////
////////////////////////////////////////////

//SETTINGS SUBMENU
function ajax_login_register_settings() {
    register_setting( 'ajax_login_settings', 'ajax_login_load_css' );
    register_setting( 'ajax_login_settings', 'ajax_login_redirect_url' );
    register_setting( 'ajax_login_settings', 'ajax_login_logout_url' );
    register_setting( 'ajax_login_settings', 'ajax_login_use_redirect' );
    register_setting( 'ajax_login_settings', 'ajax_login_use_logout_redirect' );
    register_setting( 'ajax_login_settings', 'ajax_login_show_messages' );
    register_setting( 'ajax_login_settings', 'ajax_login_loading_message' );
    register_setting( 'ajax_login_settings', 'ajax_login_page_loading_message' );
    register_setting( 'ajax_login_settings', 'ajax_login_redirecting_message' );
    register_setting( 'ajax_login_settings', 'ajax_login_page_load_error_message' );
    register_setting( 'ajax_login_settings', 'ajax_login_page_load_message' );
    register_setting( 'ajax_login_settings', 'ajax_login_validation_error_message' );
}

function ajax_login_menu() {
    add_options_page( 'Ajax Login Options', 'Ajax Login', 'manage_options', 'ajax_login_menu', 'ajax_login_options' );
    add_action('admin_init', 'ajax_login_register_settings');
}
add_action( 'admin_menu', 'ajax_login_menu' );

function ajax_login_options() {
    if (!current_user_can('manage_options')){
        wp_die( __('Admin area', 'ajax-login') );
    }

function register_ajax_login_settings() {
    //Register Plugin Settings
    register_setting('ajax_login_settings', 'ajax_login_redirect_url');
    register_setting('ajax_login_settings', 'ajax_login_load_css');
}
add_action('admin_init', 'register_ajax_login_settings');
?>

    <div class="wrap">
    <h1>Ajax Login Options</h1>
    <form method="post" action="options.php">
    <?php 
        settings_fields( 'ajax_login_settings' ); 
        do_settings_sections( 'ajax_login_settings' );
    ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Load CSS</th>
                <td><input type="checkbox" name="ajax_login_load_css" value="1" <?php checked(1, get_option('ajax_login_load_css'), true); ?> /></td>
            </tr>
            <tr valign="top">
            <tr valign="top">
                <th scope="row">Redirect After Login</th>
                <td><input type="checkbox" name="ajax_login_use_redirect" value="1" <?php checked(1, get_option('ajax_login_use_redirect'), true); ?> /></td>
            </tr>  
            <tr valign="top">
            <tr valign="top">
                <th scope="row">Redirect After Logout</th>
                <td><input type="checkbox" name="ajax_login_use_logout_redirect" value="1" <?php checked(1, get_option('ajax_login_use_logout_redirect'), true); ?> /></td>            
            </tr>  
            <tr valign="top">
                <th scope="row">Login URL</th>
                <td><input type="text" name="ajax_login_redirect_url" value="<?php echo esc_attr( get_option('ajax_login_redirect_url') ); ?>" /></td>
            </tr>      
            <tr valign="top">
                <th scope="row">Logout URL</th>
                <td><input type="text" name="ajax_login_logout_url" value="<?php echo esc_attr( get_option('ajax_login_logout_url') ); ?>" /></td>
            </tr>    

            <tr valign="top">
                <th scope="row">Show Messages</th>
                <td><input type="checkbox" name="ajax_login_show_messages" value="1" <?php checked(1, get_option('ajax_login_show_messages'), true); ?> /></td>            
            </tr> 

            <tr valign="top">
                <th scope="row">Loading Message</th>
                <td><input type="text" name="ajax_login_loading_message" value="<?php echo esc_attr( get_option('ajax_login_loading_message') ); ?>" /></td>
            </tr>    
            <tr valign="top">
                <th scope="row">Page Loading Message</th>
                <td><input type="text" name="ajax_login_page_loading_message" value="<?php echo esc_attr( get_option('ajax_login_page_loading_message') ); ?>" /></td>
            </tr>    
            <tr valign="top">
                <th scope="row">Redirecting Message</th>
                <td><input type="text" name="ajax_login_redirecting_message" value="<?php echo esc_attr( get_option('ajax_login_redirecting_message') ); ?>" /></td>
            </tr>    
            <tr valign="top">
                <th scope="row">Page Load Error Message</th>
                <td><input type="text" name="ajax_login_page_load_error_message" value="<?php echo esc_attr( get_option('ajax_login_page_load_error_message') ); ?>" /></td>
            </tr>    
            <tr valign="top">
                <th scope="row">Page Load Message</th>
                <td><input type="text" name="ajax_login_page_load_message" value="<?php echo esc_attr( get_option('ajax_login_page_load_message') ); ?>" /></td>
            </tr>    
            <tr valign="top">
                <th scope="row">Validation Error Message</th>
                <td><input type="text" name="ajax_login_validation_error_message" value="<?php echo esc_attr( get_option('ajax_login_validation_error_message') ); ?>" /></td>
            </tr>        
        </table>
    <?php submit_button(); ?>
    </form>
    </div>
    <?php
}
?>
