<?php

/**
 * Plugin Name: Token / NFT / Blockchain Page Gating
 * Plugin URI: https://litprotocol.com
 * Description: Token-gate your post/page using <a href="https://litprotocol.com">Lit-Protocol</a>
 * Version: 0.0.3
 * Author: LitProtocol.com
 * Author URI:  https://litprotocol.com
 * License: GPLv3
 */
 
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// =================================================================================
// +                                Define Constants                               +
// =================================================================================

// -- define libraries
define('LIT_ADMIN_HOOK', 'toplevel_page_lit-gated');
define('LIT_ACC_MODAL_CSS', plugin_dir_url(__FILE__) . 'resources/lit-access-control-conditions-modal-vanilla-js.css');
define('LIT_ACC_MODAL_JS', plugin_dir_url(__FILE__) . 'resources/lit-access-control-conditions-modal-vanilla-js.js');
define('LIT_VERIFY_JS', plugin_dir_url(__FILE__) . 'resources/litjssdk.js');
define('LIT_ADMIN_CSS', plugin_dir_url(__FILE__) . 'wp-lit-gated-admin.css');
define('LIT_APP_CSS', plugin_dir_url(__FILE__) . 'wp-lit-gated-app.css');
define('LIT_JWT_API', 'https://jwt-verification-service.lit-protocol.workers.dev');
define('LIT_JWT_TEST_TOKEN', "eyJhbGciOiJCTFMxMi0zODEiLCJ0eXAiOiJKV1QifQ.eyJpc3MiOiJMSVQiLCJzdWIiOiIweGRiZDM2MGYzMDA5N2ZiNmQ5MzhkY2M4YjdiNjI4NTRiMzYxNjBiNDUiLCJjaGFpbiI6InBvbHlnb24iLCJpYXQiOjE2NDIwOTU2ODcsImV4cCI6MTY0MjEzODg4NywiYmFzZVVybCI6Im15LWR5bmFtaWMtY29udGVudC1zZXJ2ZXIuY29tIiwicGF0aCI6Ii9jYnJ0MjdrOW5lZnh6endudHYweWgiLCJvcmdJZCI6IiIsInJvbGUiOiIiLCJleHRyYURhdGEiOiIifQ.qT9tHi1jOwQ4ha89Sn-WyvQK9GVjjQrPzRK20IskkmxkQJy_cLLGuCNFgRQiDcNiBgajZ83qITlJye1ZbciNrcJiM-uNs8LuEOfftxegOgj_WY-o17G3ZUtte1ehZoNT");

// -- define admin menu page
define('LIT_ICON', plugin_dir_url(__FILE__) . 'assets/favicon-16x16.png');
define('LIT_MENU_NAME', 'Lit-Gated');
define('LIT_MENU_SLUG', 'lit-gated');
define('LIT_MENU_PAGE_CONTENT', plugin_dir_path(__FILE__) . "/setup/menu-page.php");
define('LIT_MENU_GROUP', 'lit-settings');

// -- define assets
define('LIT_LOGO', plugin_dir_url(__FILE__) . 'assets/lit-logo.png');

include(plugin_dir_path(__FILE__) . "/setup/Setup.php");


// ================================================================================
// +                        Hooking up all required scripts                       +
// ================================================================================
// --- Load scripts in the admin panel on our specific option page to use the access control conditions modal
function lit_enqueue_acc_modal_css($hook) {
    if( $hook != LIT_ADMIN_HOOK ) return;
    wp_enqueue_style('lit-modal-css', LIT_ACC_MODAL_CSS);
}
function lit_enqueue_acc_modal_js($hook) {
    if( $hook != LIT_ADMIN_HOOK ) return;
    wp_enqueue_script('lit-modal-js', LIT_ACC_MODAL_JS);
}
function lit_enqueue_admin_css($hook) {
    if( $hook != LIT_ADMIN_HOOK ) return;
    wp_enqueue_style( 'custom-css', LIT_ADMIN_CSS);
}
function lit_enqueue_verify_js($hook) {
    wp_enqueue_script( 'lit-verify-js', LIT_VERIFY_JS);
}
// --- Load script for front-end
function lit_enqueue_app_css($hook) {
    wp_enqueue_style( 'lit-app-css', LIT_APP_CSS);
}

// -- execute admin scripts
add_action( 'admin_enqueue_scripts', 'lit_enqueue_acc_modal_css' );
add_action( 'admin_enqueue_scripts', 'lit_enqueue_acc_modal_js' );
add_action( 'admin_enqueue_scripts', 'lit_enqueue_admin_css' );
add_action( 'admin_enqueue_scripts', 'lit_enqueue_verify_js' );

// -- execute app scripts
add_action( 'wp_enqueue_scripts', 'lit_enqueue_app_css' );
add_action( 'wp_enqueue_scripts', 'lit_enqueue_verify_js' );


// ================================================================================
// +                                     Helper                                   +
// ================================================================================

/**
 * Request Headers
 * @return { Object } 
 */
function lwlgf_request_headers(){

    $obj = new stdClass();
    $obj->protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
    $obj->url = get_permalink();
    $obj->base_url = "$_SERVER[HTTP_HOST]";

    // remove http:// or https:// in the url
    // remve base url
    // remove trailing slash (so it's compatible for resourceId->path)
    $obj->path = rtrim(str_replace([$obj->protocol, $obj->base_url], ['', ''], $obj->url), "/");

    return $obj;
}

/**
 * Simple Log to the screen
 * @param { String } title
 * @param { String } slug
 * @return { void } 
 */
function lwlgf_console($title, $log){
    $debug = false;
    if( ! $debug ) return;

    echo '<div class="lit-debug">';
    echo '<br>============ '.esc_attr($title).' ============<br>';
    echo '<pre><code>';
    print_r($log);
    echo '</code></pre>';
    echo '</div>';
}

/**
 * JS Style API Fetching
 * @param { String } $url
 * @param { Array } $body
 * @return { Object } response
 *  eg.------------------------
 *   $res = fetch(LIT_JWT_API, [
 *      "jwt" => LIT_JWT_TEST_TOKEN
 *  ]);
 *  var_dump($res);
 */
function lwlgf_fetch($url, $body){

    // -- prepare
    $headers = [
        "User-Agent: Lit-Gated Wordpress Plugin",
        "Content-Type: application/json"
    ];

    $data = [
        'body' => $body,
        'headers' => $headers,
    ];


    // -- execute
    $response = wp_remote_get($url, $data);
    
    return json_decode($response['body']);

}

// =================================================================================
// +                          ↓↓↓↓↓ Start Capturing ↓↓↓↓↓                          +
// =================================================================================

// Start scanning the whole page from the wp_head wordpress hook
add_action('wp_head', function(){
    ob_start();
});

// Stop scanning the page from the wp_footer hook and store its
// value into $content
add_action('wp_footer', function ($callback){

    $content = sanitize_text_field(htmlentities(ob_get_clean()));
    
// =================================================================================
// +                        ↑↑↑↑↑ Completed Capturing ↑↑↑↑↑                         +
// =================================================================================

    //
    //  Check if it's elementor editing mode
    //
    $isElementorEditingMode = strpos($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], 'elementor-preview=1') !== false;

    if($isElementorEditingMode) {
        echo html_entity_decode($content);
    }
    
    // -- prepare admin page data
    $settings = json_decode(base64_decode(get_option('lit-settings')));

    // ================================================================================
    // +                 No Page Has Been Setup for Lit-Gated Content                 +
    // ================================================================================
    if($settings == null){
        echo html_entity_decode($content);
        // exit();
    }

    // =================================================================================
    // +                                Preparing Data                                 +
    // =================================================================================
    
    // -- A list of pages that's Lit-Gated
    $locked_list = array_map(function($data){
        return array_map(function($path){
            return $path->anchor;
        }, $data->paths);
    }, $settings);

    $locked_list = array_merge(...$locked_list);

    lwlgf_console("List", $locked_list);

    $page_url = strtok(lwlgf_request_headers()->url, '?'); // this strips out any URL vars
    // lwlgf_console("Path URL", $page_url);

    // -- Find that particular Lit-Gated entry for this page
    $found_entry = null;
    for($i = 0; $i < count($settings); $i++){
        
        $data = $settings[$i];

        // lwlgf_console("Number: $i", $data);

        foreach($data->paths as $path){
            if($path->anchor == $page_url){
                $found_entry = $data;
            }
        }
    }

    lwlgf_console("Found Match", $found_entry);


    // ==================================================================================
    // +                             Non-Lit-Gated Page                             +
    // ==================================================================================
    if( ! in_array($page_url, $locked_list)){
        lwlgf_console('***** Non-Lit-Gated Page *****', $page_url);
        echo html_entity_decode($content);
        // exit();
        return;
    }

    // ==================================================================================
    // +                               Lit-Gated Page                               +
    // ==================================================================================
 
    // -- get data from database
    $access_controls = $found_entry->accs;
    $created_at = $found_entry->created_at;

    $resource_id = '{"baseUrl":"'.lwlgf_request_headers()->base_url.'","path":"'.lwlgf_request_headers()->path.'","orgId":"","role":"","extraData":"'.$created_at.'"}';
    
    // ==================================================================================
    // +                              BEFORE POST REQUEST                               +
    // ==================================================================================
    if(empty($_POST) && $found_entry != null){
        if( ! $found_entry->signed){
            echo '<b>This page is not signed yet. You will not be able to unlock this page even if you\'ve met the requirements.</b>';
        }

        echo '
            <div class="lit-gated">
                <section>
                    <img src="'.LIT_LOGO.'" alt="Lit Protocol" />
                    <h4>This page is Lit-Gated</h4>
                    <div id="lit-msg"></div>
                    <form action="'.htmlspecialchars(lwlgf_request_headers()->url).'" method="POST" id="lit-form">
                        <input type="hidden" id="jwt" name="jwt" value="">
                        <input type="submit" id="lit-submit" value="Unlock Page">
                    </form>
                </section>
            </div>
        ';
    }else{   
    // ==================================================================================
    // +                                AFTER POST REQUEST                               +
    // ==================================================================================

        add_filter( 'http_request_timeout', function( $timeout ) { return 60; });

        $res = lwlgf_fetch(LIT_JWT_API, ["jwt" => sanitize_text_field($_POST["jwt"])]);

        // LIT Developers: change this to the baseUrl you are authenticating, path, and other params in the payload
        // so that they match the resourceId that you used when you saved the signing condition to the Lit Protocol
        if($res->verified == false || 
            $res->payload->baseUrl !== $_SERVER["HTTP_HOST"] ||
            $res->payload->path !== lwlgf_request_headers()->path ||
            $res->payload->orgId !== '' ||
            $res->payload->role !== '' ||
            $res->payload->extraData !== $created_at){
            echo "Not Authorized";
        }else{
            // LIT Developers: This is the success condition. Change this to whatever URL you want to redirect to if auth works properly
            echo html_entity_decode($content);
        }
        // exit();
    }
    
    // =================================================================================
    // + WARNING! Following Javascript is rendered client-side, which means is public. +
    // =================================================================================
    echo '<script>

        // -- connect LitProtocol
        LitJsSdk.litJsSdkLoadedInALIT();

        (async () => {
            console.log("--- Mounted ---");

            // -- prepare dom
            const btnSubmit = document.getElementById("lit-submit");
            const form = document.getElementById("lit-form");

            // -- prepare args for jwt
            const conditionObject = '.$access_controls.';
            const resourceId = '.$resource_id.';
            const accessControlConditions = conditionObject.accessControlConditions ?? conditionObject;
            console.log("________");
            console.log("conditionObject:", conditionObject);
            console.log("accessControlConditions:", accessControlConditions);
            console.log("RESOURCE_ID:", resourceId);

            let readable;
            
            try{
                readable = await LitJsSdk.humanizeAccessControlConditions({accessControlConditions});
            }catch(e){
                console.warn(e);
            }

            if(readable === undefined || readable === ""){
                readable = await LitJsSdk.humanizeAccessControlConditions({ solRpcConditions: accessControlConditions });
            }

            // -- set display
            document.getElementById("lit-msg").innerHTML = readable;
            btnSubmit.classList.add("lit-active");
            
            // -- when "Unlock" button is clicked
            btnSubmit.addEventListener("click", async (e) => {
                e.preventDefault();

                // -- prepare lit network
                const litNodeClient = new LitJsSdk.LitNodeClient();
                await litNodeClient.connect();
                
                // -- validate web3
                let ethAuthSig;
                let solAuthSig;

                try {
                    ethAuthSig = await LitJsSdk.checkAndSignAuthMessage({chain: "ethereum"});
                } catch (error) {
                    console.log("Error:", error);
                    if (error.errorCode === "no_wallet") {
                        alert("Please install an Ethereum wallet to use this feature.  You can do this by installing MetaMask from https://metamask.io/");
                    } else {
                        alert("An unknown error occurred when trying to get a signature from your wallet.  You can find it in the console.  Please email support@litprotocol.com with a bug report");
                    }
                    return;
                }

                try{
                    solAuthSig = await LitJsSdk.checkAndSignAuthMessage({chain: "solana"});
                }catch (error) {
                    console.log("Error:", error);
                    if (error.errorCode === "no_wallet") {
                        alert("Please install an Solana wallet to use this feature.  You can do this by installing Phantom from https://phantom.app/download/");
                    } else {
                        alert("An unknown error occurred when trying to get a signature from your wallet.  You can find it in the console.  Please email support@litprotocol.com with a bug report");
                    }
                    return;
                }

                // -- request token
                let jwt;
                
                try{
                    jwt = await litNodeClient.getSignedToken({ 
                        unifiedAccessControlConditions: accessControlConditions,
                        authSig: {
                            solana: solAuthSig,
                            ethereum: ethAuthSig,
                        },
                        resourceId 
                    });
                }catch(e){
                    console.error("Failed to get JWT!");
                }
                
                console.log("🤌 JWT:", jwt);                
                document.getElementById("jwt").setAttribute("value", jwt);

                // -- submit form to verify token
                form.submit();
            });

        })();
    </script>';
    // exit();

});