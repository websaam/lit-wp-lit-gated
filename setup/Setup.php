<?php

/**
 * ========== CONSTANTS ==========
 */


/**
 * Class for registering a new menu page
 */
class SetupMenu{

    /**
     * Constructor
     * @returns { void } 
     */
    function __construct(){
        add_action('admin_menu', [$this, 'register_menu']);
    }

    /** ----- MENU ----- */
    /**
     * Register a new menu 
     * @returns { void } 
     */
    function register_menu(){
        add_menu_page(
            LIT_MENU_NAME, // page <title>Title</title>
            LIT_MENU_NAME, // menu link text
            'manage_options', // capability to access the page
            LIT_MENU_SLUG, // page URL slug
            [$this, 'content'], // callback function /w content
            LIT_ICON, // menu icon
            999 // priority
        );
    }

    /**
     * Content page of the menu
     * @returns { void }
     */
    function content(){
        include_once(LIT_MENU_PAGE_CONTENT);
    }
    
    /** ----- MENU ----- */
    /**
     * Register settings
     */
    function register_settings(){

    }
}

new SetupMenu();

add_action( 'admin_init',  'register_settings' );

function register_settings(){

    $settings_id = 1;
    $option_name = 'lit-settings';

	register_setting(
		LIT_MENU_GROUP, // settings group name
		$option_name, // option name
		'sanitize_text_field' // sanitization function
	);

	add_settings_section(
		$settings_id, // section ID
		'', // title (if needed)
		'', // callback function (if needed)
		LIT_MENU_SLUG // page slug
	);

	add_settings_field(
		$option_name,
		'Lit Settings',
		'lit_text_field_html', // function which prints the field
		LIT_MENU_SLUG, // page slug
		$settings_id // section ID
	);

}

function lit_text_field_html(){

    $option_name = 'lit-settings';
	$text = get_option( $option_name );

    echo '<input type="text" id="'.$option_name.'" name="'.$option_name.'" value="'.esc_attr( $text ).'" />';

}
