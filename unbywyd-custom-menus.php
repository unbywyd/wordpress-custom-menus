<?php
/*
Plugin Name: Unbywyd custom menus
Version: 0.1
Author: Unbywyd
Author URI: unbywyd.com
*/

/*
*   We will use the Handlebars template engine
*/
if(!class_exists('LightnCandy\LightnCandy')) {
    require_once ("handlebars/autoload.php");
}
use LightnCandy\LightnCandy;
define('UCMS_DIR', plugin_dir_path(__FILE__));

class unbywydCustomMenus {
    public $menus = [];
    function __construct($menus=array()) {
       $this->menus = $menus;
        add_action('init', array($this, 'init'));

        /*  
        *   Create use_custom_menu shortcode 
        *   You can use [use_custom_menu location="navbar"] in wordpress posts to display navbar menu
        *   And also for using from php  <php print do_shortcode('[use_custom_menu location="navbar" customparam="test" class="test"]'); ?>
        */
        add_shortcode( 'use_custom_menu', array($this, 'use_custom_menu'));
    }
    function use_custom_menu($attrs) { // Create shortcode handler
        if(!isset($attrs['location'])) {
            $attrs['location'] =  '';
        }
        return $this->get_nav($attrs['location'], $attrs);
    }
    function get_template( $template_name ) { // Get a template by its name from templates/ directory
        $path_to_file = wp_normalize_path(UCMS_DIR . 'templates/' . $template_name . '.hbs');
        if(!file_exists($path_to_file)) {
            return '';
        }
        return file_get_contents($path_to_file);
    }
    function get_handlebars_partials() { // Get all partials from partials/ directory
        $path_to_partials = wp_normalize_path(UCMS_DIR . 'partials/');
        if(!file_exists($path_to_partials)) {
            return array();
        }
        $list_files = scandir($path_to_partials);
        $partials = array();
        foreach($list_files as $path) {
            $path_to_file = wp_normalize_path($path_to_partials . '/'. $path);
            if(is_file($path_to_file)) {
                $ext = pathinfo($path_to_file, PATHINFO_EXTENSION);               
                if($ext == 'hbs') {         
                   $partials[pathinfo($path, PATHINFO_FILENAME)] = function($name, $context) use($path_to_file) {                        
                        return $this->prepare_template(file_get_contents($path_to_file))($context, array('partials' => $this->get_handlebars_partials()));
                   };
                }
            }
        }
        return $partials;
    }
    function get_handlebars_helpers() {
        return array(
            /*
            *   You can use debug helper to display the incoming data into the template
            *   {{{debug}}}
            */
            'debug' => function($context, $options=array()) {
                if(!current_user_can('editor') && !current_user_can('administrator')) {
                    return '';
                }
                return '<pre class="debug" dir="ltr" style="text-align:left !important;">'.json_encode($context['data']['root'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</pre>';
            }
        );
    }
    // You can use this function to render any of your templates from /template directory
    function template_render($template_name, $data) {  
        $prepared_template = $this->prepare_template($this->get_template( $template_name ));   
        $partials = $this->get_handlebars_partials();
        return $prepared_template($data, array('partials' => $partials));    
    }
    function prepare_template($template) {        
        $template = do_shortcode($template);       
        $prepared = LightnCandy::compile($template, array(
            'flags' => LightnCandy::FLAG_HANDLEBARSJS | LightnCandy::FLAG_ADVARNAME | LightnCandy::FLAG_RUNTIMEPARTIAL | LightnCandy::FLAG_ERROR_SKIPPARTIAL,
            
            'helpers' => $this->get_handlebars_helpers()
        ));
        return LightnCandy::prepare($prepared);
    }
    function init() {
        /*
        *   Registering custom menu locations 
        */
        foreach($this->menus as $menu) {
            register_nav_menu($menu['id'], $menu['label']);
        }
    }
    function build_tree(Array $data, $parent = 0) {
        $tree = array();
        foreach ($data as $d) {
            if ($d['parent'] == $parent) {
                $children = $this->build_tree($data, $d['id']);
                if (!empty($children)) {
                    $d['children'] = $children;
                }
                $tree[] = $d;
            }
        }
        return $tree;
    }
    // Get generated HTML of navigation
    function get_nav( $location, $other_params=array() ) {
        $menu_data = $this->get_nav_menu_items_by_location($location);
        if(empty($menu_data)) {
            return 'You can do nothing, or you can display a notification that there are no links in the menu';            
        } else {
            return $this->template_render($location, array_merge($other_params, array('menu' => $menu_data)));            
        }
    }
    // Simple handler to build data of custom navigation
    function get_nav_menu_items_by_location( $location, $args = [] ) {
        $locations = get_nav_menu_locations();
        if(!isset($locations[$location])) {
            return array();
        }
        $object = wp_get_nav_menu_object( $locations[$location] );

        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $link = "https";
        } else {
            $link = "http";
        }

        $link .= "://";
        $link .= $_SERVER['HTTP_HOST'];
        $link .= $_SERVER['REQUEST_URI'];

        if(!$object) {
            return array();
        }
        $menu_items = wp_get_nav_menu_items( $object->name, $args );
        if(!$menu_items) {
            return array();
        } else {
            $menu = array();
            foreach( $menu_items as $i) {
                  $data = array(
                    'url' => $i->url,
                    'id' => $i->ID,
                    'parent' => $i->menu_item_parent,
                    'title' => $i->title,            
                    'active' => $i->url == $link || $i->url . '/' == $link,
                    'attr_title' => $i->attr_title,
                    'description' => $i->description,
                    'class' => implode(' ', $i->classes)
                );
                if(!empty($i->target)) {
                    $data['target'] = $i->target;
                }
                $menu[] = $data;
            }
            return $this->build_tree($menu);
        }
        return $menu_items;
    }
}

/*
*   Сreate our custom menus
*   Of course, you can add this complexity and bring it up to the plugin settings and display it in the wordpress admin panel as option page.
*/
new unbywydCustomMenus(
    [
        ['id' => 'navbar', 'label' => 'Navbar menu'], 
        ['id' => 'footer', 'label'=> 'Menu in footer']
    ]);