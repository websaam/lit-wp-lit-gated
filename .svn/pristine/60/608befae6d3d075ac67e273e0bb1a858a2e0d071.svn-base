<?php

/**
 * Class for registering a new menu page
 */
class lwlgf_SetupMenu{

    /**
     * Properties
     */
    public $menu_name;
    public $menu_slug;
    public $menu_icon;
    public $menu_order;
    public $menu_page;
    public $option_name;
    public $settings_id;
    public $settings_group;

    /**
     * Constructor
     * @param { Array } options
     * @return { void } 
     */
    function __construct($options){
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        $this->option_name = $options['option_name'];
        $this->settings_id = $options['settings_id'];
        $this->menu_name = $options['menu_name'];
        $this->menu_slug = $options['menu_slug'];
        $this->menu_icon = $options['menu_icon'];
        $this->menu_order = $options['menu_order'];
        $this->menu_page = $options['menu_page'];
        $this->settings_group = $options['settings_group'];
    }

    /** ----- MENU ----- */
    /**
     * Register a new menu 
     * @return { void } 
     */
    function register_menu(){
        add_menu_page(
            $this->menu_name, // page <title>Title</title>
            $this->menu_name, // menu link text
            'manage_options', // capability to access the page
            $this->menu_slug, // page URL slug
            [$this, 'content'], // callback function /w content
            $this->menu_icon, // menu icon
            $this->menu_order // priority
        );
    }

    /**
     * Content page of the menu
     * @return { void }
     */
    function content(){
        include_once($this->menu_page);
    }
    
    /** ----- MENU ----- */
    /**
     * Register settings
     */
    function register_settings(){
    
        register_setting(
            $this->settings_group, // settings group name
            $this->option_name, // option name
            'sanitize_text_field' // sanitization function
        );
    
        add_settings_section(
            $this->settings_id, // section ID
            '', // title (if needed)
            '', // callback function (if needed)
            $this->menu_slug // page slug
        );
    
        add_settings_field(
            $this->option_name,
            'Lit Settings',
            [$this, 'field_html'], // function which prints the field
            $this->menu_slug, // page slug
            $this->settings_id // section ID
        );
    }

    /**
     * Settings HTML
     */
    function field_html(){
        $text = get_option( $this->option_name );
        echo '<input type="text" id="'.esc_attr($this->option_name).'" name="'.esc_attr($this->option_name).'" value="'.esc_attr( $text ).'" />';
    }
}

new lwlgf_SetupMenu([
    "menu_name" => LIT_MENU_NAME,
    "menu_slug" => LIT_MENU_SLUG,
    "menu_icon" => LIT_ICON,
    "menu_order" => 999,
    "menu_page" => LIT_MENU_PAGE_CONTENT,
    "settings_group" => LIT_MENU_GROUP,
    "option_name" => 'lit-settings',
    "settings_id" => 1
]);