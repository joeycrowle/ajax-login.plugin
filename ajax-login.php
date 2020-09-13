<?php
/**
 * 
 * Plugin Name: Ajax Login
 * Description: Adds front-end AJAX login functionality [ajax-login] [ajax-logout]
 * Author: <a href="https://line49.ca">Line49</a>
 * Version: 1.0
 */

//page load function
function ajx_page_load() {
    //Check login cookie
    $hash = md5(get_site_url());
    $loggedin = isset($_COOKIE['wordpress_logged_in_'.$hash]) ? true : false;
    $pageid = $_POST['pageid'];

    if($loggedin) {
        echo json_encode(array(
            'content' => get_post($pageid)->post_content,
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


function ajax_login_init(){

    if(!get_option('ajax_login_load_css')) {
        add_option('ajax_login_load_css', true);
        add_option('ajax_login_redirect_url', home_url());
    }

    function ajax_login(){
        // First check the nonce, if it fails the function will break
        check_ajax_referer( 'ajax-login-nonce', 'security' );
    
        // Nonce is checked, get the POST data and sign user on
        $info = array();
        $info['user_login'] = $_POST['username'];
        $info['user_password'] = $_POST['password'];
        $info['remember'] = true;
    
        $user_signon = wp_signon($info, is_ssl() ? true : false);
        if ( is_wp_error($user_signon) ){
            echo json_encode(array('loggedin'=>false, 'message'=>__('Wrong username or password.')));
        } else {
            echo json_encode(array('info'=>$user_signon, 'loggedin'=>true, 'message'=>__('Login successful, redirecting...')));
        }
    
        die();
    }


        setcookie('ajx_lg', '', time() - 1000);
  

    if(!is_user_logged_in()) {
        wp_register_script('ajax-login-script', plugins_url() . '/ajax-login-l49/scripts/ajax-login.js', array('jquery') ); 
        wp_enqueue_script('ajax-login-script');

        wp_localize_script( 'ajax-login-script', 'ajax_login_object', array( 
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'redirecturl' => get_option('ajax_login_redirect_url'),
            'loadingmessage' => __('Sending user info, please wait...')
        ));

        // Enable the user with no privileges to run ajax_login() in AJAX
        add_action( 'wp_ajax_nopriv_ajaxlogin', 'ajax_login' );
    }
}
add_action('init', 'ajax_login_init');




function enqueue_ajx_assets() {
    
    $page = get_queried_object();
    $id = $page->ID;
    $template_slug = get_page_template_slug($id);

    //load default style if option is selected
    if( get_option('ajax_login_load_css') ) {
        wp_register_style( 'ajax-login-css', plugins_url() . '/ajax-login-l49/styles/ajax-login.css' );
        wp_enqueue_style( 'ajax-login-css' );
    }

    //check if template slug contains 'ajx'
    if( strpos($template_slug, 'ajx') !== false ) {
        wp_register_script('ajax-page-script', plugins_url() . '/ajax-login-l49/scripts/ajax-page.js', array('jquery'));
        wp_enqueue_script('ajax-page-script');
        wp_localize_script('ajax-page-script', 'ajax_page_object', array(
            'pageid' => json_encode(get_queried_object()->ID),
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'slug' => $template_slug
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'enqueue_ajx_assets' ); 


function register_ajax_login_settings() {
    //Register Plugin Settings
    register_setting('ajax_login_settings', 'ajax_login_redirect_url');
    register_setting('ajax_login_settings', 'ajax_login_load_css');
}
add_action('admin_init', 'register_ajax_login_settings');


 //SHORTCODE [ajax-login]
function ajax_form_shortcode(){
    if(!is_user_logged_in()) {
        return '<form id="login" class="ajax-login-form" action="login" method="post">
        <ul>
            <li>
                <label for="username">Username</label>
                <input id="username" type="text" name="username">
            </li>
            <li>
                <label for="password">Password</label>
                <input id="password" type="password" name="password">
            </li>
            <li>
                <input class="submit_button" type="submit" value="Login" name="submit">
            </li>
            <li>
                <p class="status"></p>
            </li>
        </ul>' .
        wp_nonce_field( 'ajax-login-nonce', 'security' ) .
        '</form>';
    }
}
add_shortcode('ajax-login', 'ajax_form_shortcode');

//LOGOUT BUTTON SHORTCODE [ajax-logout]
function logout_shortcode() {
    if(is_user_logged_in()) {
        return '<a class="logout_button" href='. wp_logout_url( home_url() ) .'>Logout</a>';
    }
}
add_shortcode('ajax-logout', 'logout_shortcode');

//SETTINGS SUBMENU
function ajax_login_register_settings() {
    register_setting( 'ajax_login_settings', 'ajax_login_load_css' );
	register_setting( 'ajax_login_settings', 'ajax_login_redirect_url' );
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
        <th scope="row">Redirect URL</th>
        <td><input type="text" name="ajax_login_redirect_url" value="<?php echo esc_attr( get_option('ajax_login_redirect_url') ); ?>" /></td>
        </tr>
        
    </table>
    <?php submit_button(); ?>
    </form>

    </div>

    <?php
}



?>
