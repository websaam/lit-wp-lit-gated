<?php

/**
 * Plugin Name: LitProtocol WP:: Lit-Gated
 * Plugin URI: https://litprotocol.com
 * Description: Token-gate your post/page using <a href="https://litprotocol.com">Lit-Protocol</a>
 * Version: 0.0.1
 * Author: WebSaam.com
 * Author URI:  https://websaam.com
 * License: GPLv3
 */
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );


// =================================================================================
// +                                Define Constants                               +
// =================================================================================

// -- define libraries
define("WEB_URL", plugin_dir_url(__FILE__));
define("DIR_URL", plugin_dir_path(__FILE__));
define('LIT_ADMIN_HOOK', 'toplevel_page_lit-gated');
define('LIT_ACC_MODAL_CSS', 'https://cdn.jsdelivr.net/npm/lit-access-control-conditions-modal-vanilla-js/dist/main.css');
define('LIT_ACC_MODAL_JS', 'https://cdn.jsdelivr.net/npm/lit-access-control-conditions-modal-vanilla-js/dist/index.js');
define('LIT_VERIFY_JS', 'https://jscdn.litgateway.com/index.web.js');
define('LIT_ADMIN_CSS', WEB_URL . 'wp-lit-gated-admin.css');
define('LIT_APP_CSS', WEB_URL . 'wp-lit-gated-app.css');

// -- define admin menu page
define('LIT_ICON', site_url() . '/wp-content/plugins/wp-lit-gated/assets/favicon-16x16.png');
define('LIT_MENU_NAME', 'Lit-Gated');
define('LIT_MENU_SLUG', 'lit-gated');
define('LIT_MENU_PAGE_CONTENT', DIR_URL . "/setup/menu-page.php");
define('LIT_MENU_GROUP', 'lit-settings');

include(DIR_URL . "/setup/Setup.php");


// ================================================================================
// +                        Hooking up all required scripts                       +
// ================================================================================
// --- Load scripts in the admin panel on our specific option page to use the access control conditions modal
function lit_enqueue_acc_modal_css($hook) {
    // echo '<script>alert("'.$hook.'");</script>';
    if( $hook != LIT_ADMIN_HOOK )
    return;
    wp_enqueue_style('lit-modal-css', LIT_ACC_MODAL_CSS);
}
function lit_enqueue_acc_modal_js($hook) {
    if( $hook != LIT_ADMIN_HOOK )
    return;
    wp_enqueue_script('lit-modal-js', LIT_ACC_MODAL_JS);
}
function lit_enqueue_admin_css($hook) {
    if( $hook != LIT_ADMIN_HOOK )
    return;
    wp_enqueue_style( 'custom-css', LIT_ADMIN_CSS);
}
function lit_enqueue_verify_js($hook) {
    if( $hook != LIT_ADMIN_HOOK )
    return;
    wp_enqueue_script( 'lit-verify-js', LIT_VERIFY_JS);
}

// --- Load script for front-end
function lit_enqueue_app_css($hook) {
    wp_enqueue_style( 'lit-app-css', LIT_APP_CSS);
}
add_action( 'wp_enqueue_scripts', 'lit_enqueue_app_css' );

// -- execute
add_action( 'admin_enqueue_scripts', 'lit_enqueue_acc_modal_css' );
add_action( 'admin_enqueue_scripts', 'lit_enqueue_acc_modal_js' );
add_action( 'admin_enqueue_scripts', 'lit_enqueue_admin_css' );
add_action( 'admin_enqueue_scripts', 'lit_enqueue_verify_js' );


// ================================================================================
// +                                     Helper                                   +
// ================================================================================
//
// provide request header
// @returns { Object } 
//
function lit_request(){
    $obj = new stdClass();
    $obj->protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
    $obj->url = "$obj->protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $obj->base_url = "$_SERVER[HTTP_HOST]";
    $obj->path = rtrim($_SERVER['REQUEST_URI'],"/");
    return $obj;
}

//
// Simple debug
//
function console($title, $log){
    $debug = false;
    if( ! $debug ) return;

    echo '<div class="lit-debug">';
    echo '<br>============ '.$title.' ============<br>';
    echo '<pre><code>';
    print_r($log);
    echo '</code></pre>';
    echo '</div>';
}

// =================================================================================
// +                                Start Capturing                                +
// =================================================================================
//
// scan through the whole page and store its content to a variable
// all HTML pages are essential a string
//
add_action('wp_head', function(){
    ob_start();
});
add_action('wp_footer', function ($callback){
    $content = ob_get_clean();

// =================================================================================
// +                              Completed Capturing                              +
// =================================================================================
    
    $settings = json_decode(base64_decode(get_option('lit-settings')));
    $data = get_fields( 'options' )['lit_gated_content'];

    // ================================================================================
    // +                        No Acccess Control Pages Setup                        +
    // ================================================================================
    if($settings == null){
        echo $content;
        exit();
    }

    // =================================================================================
    // +                                   All Pages                                   +
    // =================================================================================
    
    // -- New 
    $locked_list = array_map(function($data){
        return $data->anchor;
    }, $settings);
    console("New List", $locked_list);

    // -- find current object
    $found_entry = null;
    for($i = 0; $i < count($settings); $i++){
        $data = $settings[$i];
        if($data->anchor == lit_request()->url){
            $found_entry = $data;
        }
    }
    console('New Found Match', $found_entry);



    // ==================================================================================
    // +                             Non-Lit-Gated Page                             +
    // ==================================================================================
    if( ! in_array(lit_request()->url, $locked_list)){
        console('***** Non-Lit-Gated Page *****', lit_request()->url);
        echo $content;
        exit();
    }

    // ==================================================================================
    // +                               Lit-Gated Page                               +
    // ==================================================================================
 
    // -- get data from database
    $access_controls = $found_entry->accs;
    $resource_id = '{"baseUrl":"'.lit_request()->base_url.'","path":"'.lit_request()->path.'","orgId":"","role":"","extraData":""}';
    
    // ==================================================================================
    // +                                   NOT AUTHED                                   +
    // ==================================================================================
    if(empty($_POST)){
        echo '
            <div class="lit-gated">
                <section>
                    <img src="https://litprotocol.com/lit-logo.png" alt="Lit Protocol" />
                    <h4>This page is Lit-Gated</h4>
                    <div id="lit-msg"></div>
                    <form action="'.htmlspecialchars(lit_request()->url).'" method="POST" id="lit-form">
                        <input type="hidden" id="verified" name="verified" value="">
                        <input type="hidden" id="header" name="header" value="">
                        <input type="hidden" id="payload" name="payload" value="">
                        <input type="submit" id="lit-submit" value="Unlock Page">
                    </form>
                </section>
            </div>
        ';
    }else{   
    // ==================================================================================
    // +                                     AUTHED                                     +
    // ==================================================================================
        // {alg: 'BLS12-381', typ: 'JWT'}
        $header = json_decode(stripcslashes($_POST["header"]));

        // {"iss":"LIT","sub":"___addr___","chain":"ethereum","iat":1641180354,"exp":1641223554,"baseUrl":"localhost","path":"/test2","orgId":"","role":"","extraData":""}
        $payload = json_decode(stripcslashes($_POST["payload"]));
        $verified = $_POST["verified"];

        // LIT Developers: change this to the baseUrl you are authenticating, path, and other params in the payload
        // so that they match the resourceId that you used when you saved the signing condition to the Lit Protocol
        if($verified == 'false' || 
            $payload->baseUrl !== $_SERVER["HTTP_HOST"] ||
            $payload->orgId !== '' ||
            $payload->role !== '' ||
            $payload->extraData !== ''){
            echo "Not Authorized";
        }else{
            // LIT Developers: This is the success condition. Change this to whatever URL you want to redirect to if auth works properly
            echo $content;
        }
        
    }
    
    // ================================================================================
    // + WARNING! Following Javascript is rendered client-side, which means is public.+
    // ================================================================================
    echo '<script src="'.LIT_VERIFY_JS.'"></script>';
    echo '<script>
        LitJsSdk.litJsSdkLoadedInALIT();
        (async () => {
            console.log("---Mounted---");
            const litNodeClient = new LitJsSdk.LitNodeClient();
            await litNodeClient.connect();

            const chain = "ethereum";
            const authSig = await LitJsSdk.checkAndSignAuthMessage({chain: chain});
            const accessControlConditions = '.$access_controls.';
            const resourceId = '.$resource_id.';

            console.log("________");
            console.log(accessControlConditions);
            console.log(resourceId);
            const readable = await LitJsSdk.humanizeAccessControlConditions({accessControlConditions});
            document.getElementById("lit-msg").innerHTML = readable;

            setTimeout(async () => {

                // -- singing
                // const test = await litNodeClient.saveSigningCondition({ accessControlConditions, chain, authSig, resourceId });
                // console.log("Signed:", test);

                // -- retrieve token
                const jwt = await litNodeClient.getSignedToken({ accessControlConditions, chain, authSig, resourceId });
                console.log("🤌 JWT:", jwt);
                console.log("🤌 accessControls: ", accessControlConditions);
                console.log("🤌 resource_id: ", resourceId);
                console.log("🤌 readable: ", readable);

                const { verified, header, payload } = LitJsSdk.verifyJwt({jwt})
                document.getElementById("verified").setAttribute("value", JSON.stringify(verified));
                document.getElementById("header").setAttribute("value", JSON.stringify(header));
                document.getElementById("payload").setAttribute("value", JSON.stringify(payload));

                if(verified){
                    document.getElementById("lit-submit").classList.add("lit-active");
                    console.log(verified);
                    console.log(header);
                    console.log(payload);
                    // document.getElementById("lit-form").submit();
                }

            }, 1000);

        })();
    </script>';
    exit();

});