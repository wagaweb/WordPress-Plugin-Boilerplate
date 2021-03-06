<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    PluginName
 * @subpackage PluginName/includes
 */

namespace PluginName\includes;
use PluginName\admin\Admin;
use PluginName\pub\Pub;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    PluginName
 * @subpackage PluginName/includes
 * @author     Your Name <email@example.com>
 */
class Plugin {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The plugin dir
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected $plugin_dir;

	/**
	 * The full path to main plugin file
	 *
	 * @since 0.10.0
	 * @access   protected
	 * @var string
	 */
	protected $plugin_path;

	/**
	 * The path relative to WP_PLUGIN_DIR
	 *
	 * @var
	 */
	protected $plugin_relative_dir;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	protected $debug_mode = false;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct($slug,$dir = null,$version = "1.0.0") {
		$this->plugin_name = $slug;
		$this->plugin_dir = isset($dir) ? $dir : plugin_dir_path(dirname(dirname(__FILE__)));
		$this->version = $version;
		$this->plugin_path = $this->plugin_dir.$this->plugin_name.".php";

		//Set relative path
		$pinfo = pathinfo($dir);
		$this->plugin_relative_dir = "/".$pinfo['basename'];

		//Get the version
		if(function_exists("get_plugin_data")){
			$pluginHeader = get_plugin_data($this->plugin_path, false, false);
			if ( isset($pluginHeader['Version']) ) {
				$this->version = $pluginHeader['Version'];
			} else {
				$this->version = $version;
			}
		}else{
			$this->version = $version;
		}

		//Check if debug mode must be activated
		if( (defined("WP_DEBUG") && WP_DEBUG) ){
			$this->debug_mode = true;
		}

		$this->load_dependencies();
		$this->set_locale();
		$this->define_global_hooks();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files\classes that make up the plugin:
	 *
	 * - Loader. Orchestrates the hooks of the plugin.
	 * - i18n. Defines internationalization functionality.
	 * - Admin. Defines all hooks for the admin area.
	 * - Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		$this->loader = new Loader($this);
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the PluginName_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new i18n();
		$plugin_i18n->set_domain( $this->get_name() );
		$plugin_i18n->set_language_dir( $this->plugin_relative_dir."/languages/" );
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	private function define_global_hooks(){
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_main_script' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_main_script' );
	}

	/**
	 * Localize and enqueue the plugin main js file.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	public function enqueue_main_script(){
		if($this->is_debug()){
			wp_register_script( $this->get_name()."-js", $this->get_uri() . 'assets/src/js/bundle.js', array( 'jquery', 'underscore', 'backbone' ), $this->get_version(), false );
		}else{
			wp_register_script( $this->get_name()."-js", $this->get_uri() . 'assets/dist/js/plugin-name.js', array( 'jquery', 'underscore', 'backbone' ), $this->get_version(), false );
		}
		wp_localize_script($this->get_name()."-js",lcfirst("PluginName"),[
			'ajaxurl' => admin_url('admin-ajax.php'),
			'wpurl' => get_bloginfo('wpurl'),
			'isAdmin' => is_admin(),
			'isDebug' => $this->is_debug(),
			'wp_screen' => function_exists("get_current_screen") ? get_current_screen() : null
		]);
		wp_enqueue_script($this->get_name()."-js");
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$this->loader->add_action( 'admin_enqueue_scripts', $this->loader->admin_plugin, 'enqueue_styles' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$this->loader->add_action( 'wp_enqueue_scripts', $this->loader->public_plugin, 'enqueue_styles' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get plugin directory uri
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_uri(){
		static $uri;
		if($uri) return $uri; //We want to save some queries
		$uri = get_bloginfo("url")."/wp-content/plugins/".$this->plugin_name."/";
		return $uri;
	}

	/**
	 * Get plugin directory
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_dir(){
		return $this->plugin_dir;
	}

	/**
	 * Get plugin full path to directory
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_path(){
		return $this->plugin_path;
	}

	/**
	 * Get plugin path relative to WP_PLUGIN_DIR
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_relative_dir(){
		return $this->plugin_relative_dir;
	}

	/**
	 * Checks if the plugin is in debug mode. The debug mode is activated by WP_DEBUG constant.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_debug(){
		return $this->debug_mode;
	}
}
