<?php

/*
Plugin Name: RBS Remote Dashboard Welcome Control
Plugin URL: https://roadbearstudios.com/rbs-centralized-custom-dashboard
Description: Create a custom Remote Dashboard Welcome page for your users on your own website. 
Version: 1.0.4
Text Domain: rbs-remote-dashboard-welcome-control
Domain Path: /languages/
Author: Roadbear Studios - Rene Diekstra
Author URI: https://roadbearstudios.com
License: GPLv2
*/

//Only load this file as a plugin
if ( ! function_exists( 'add_action' ) ) {
	exit;
}

if ( !defined( 'DASHBOARD_PAGE_TITLE' ) ) {
	define( 'DASHBOARD_PAGE_TITLE', 'Remote Dashboard Welcome Control' );
}

function rrdwc_add_custom_query_var( $vars ){
	$vars[] = "reqhost";
	return $vars;
}

add_filter( 'query_vars', 'rrdwc_add_custom_query_var' );

function rrdwc_append_error_message( $content ) {
	$reqhost = sanitize_text_field(get_query_var( 'reqhost' ));
	$plugin_cfg_url = admin_url()."options-general.php?page=remote_dashboard_welcome_control";
	
	$domain_not_allowed_title = __('Domain not allowed' , 'rbs-remote-dashboard-welcome-control' );
	$domain_not_allowed = sprintf(__('The domain %s is not allowed to use the Control iframe' , 'rbs-remote-dashboard-welcome-control' ), $reqhost);
	
	$add_to_host = sprintf(__('Go to the configuration page of the plugin and add \'%s\' to the allowed hosts.' , 'rbs-remote-dashboard-welcome-control' ), $reqhost);
	$go_to_configuration_page = __('Go to configuration page' , 'rbs-remote-dashboard-welcome-control' );
	
	$content .= <<<EOF
	<div id="dashboardError" class="rrdwc-modal-overlay">
	<div class="rrdwc-modal">
	<h2 class="title">$domain_not_allowed_title</h2>	
	<p>$domain_not_allowed</p>
	<p>$add_to_host</p>
	<p><a href="$plugin_cfg_url" target="_blank" class="button">$go_to_configuration_page</a></p>
	</div></div>
EOF;
//$content = print_r(get_option( 'rrdwc_settings' ), true);

	return $content;
}

function rrdwc_enqueue_files() {
	if (is_page ( DASHBOARD_PAGE_TITLE )) {
		wp_register_style('rrdwc', plugins_url('css/rrdwc.css',__FILE__ ));
		wp_enqueue_style('rrdwc');
		
		$options = get_option( 'rrdwc_settings' );
		
		$reqhost = sanitize_text_field(get_query_var( 'reqhost' ));
		
		if (rrdwc_request_host_is_allowed ($reqhost)) {
			wp_enqueue_script ( 'rrdwc_functions', plugin_dir_url ( __FILE__ ) . 'js/rrdwc_functions.js', array (
					'jquery' 
			), null, true );
			wp_localize_script ( 'rrdwc_functions', 'rrdwcObject', array (
					'reqHost' => $reqhost,
					'linksInNewWindow' => $options['rrdwc_links_in_new_window']
			) );
		}
		else if($reqhost !== '') {
			add_filter('the_content', 'rrdwc_append_error_message');
		}
	}
}

add_action( 'wp_enqueue_scripts', 'rrdwc_enqueue_files' );

function rrdwc_request_host_is_allowed($reqhost) {
	$options = get_option( 'rrdwc_settings' );
	
	foreach(preg_split("/\\r\\n|\\r|\\n/", $options['rrdwc_allowed_hosts']) as $allowed_host) {
		//Allow wildcard, not recommended
		if($allowed_host === '*') {
			return 1;
		}
		else if($reqhost === $allowed_host) {
			return 1;
		}
	}
	return 0;
}

/* Let wordpress know about our own template */

function rrdwc_page_template($page_template) {

	if ( is_page( DASHBOARD_PAGE_TITLE ) ) {
        	$page_template = dirname( __FILE__ ) . '/template/rbs-remote-dashboard-welcome-control-template.php';
    }
    return $page_template;
}


add_filter( 'page_template', 'rrdwc_page_template' );

function rrdwc_add_admin_menu(  ) {
	add_submenu_page( 'options-general.php', 'Remote Dashboard Welcome Control', 'Remote Dashboard Welcome Control', 'manage_options', 'remote_dashboard_welcome_control', 'rrdwc_options_page' );
}

add_action( 'admin_menu', 'rrdwc_add_admin_menu' );

function rrdwc_settings_init(  ) {

	register_setting( 'pluginPage', 'rrdwc_settings' );

	add_settings_section(
			'rrdwc_pluginPage_section',
			__('Remote Dashboard Welcome Control Settings' , 'rbs-remote-dashboard-welcome-control'),
			'rrdwc_settings_section_callback',
			'pluginPage'
			);

	add_settings_field(
			'rrdwc_links_links_in_new_window',
			__('Open all links on dashboard page in a new window' , 'rbs-remote-dashboard-welcome-control' ),
			'rrdwc_links_in_new_window_render',
			'pluginPage',
			'rrdwc_pluginPage_section'
			);
	
	add_settings_field(
			'rrdwc_allowed_hosts',
			__('Hosts which are allowed to include the remote dashboard page as an iframe' , 'rbs-remote-dashboard-welcome-control' ),
			'rrdwc_allowed_hosts_render',
			'pluginPage',
			'rrdwc_pluginPage_section'
	);
}

add_action( 'admin_init', 'rrdwc_settings_init' );

function rrdwc_allowed_hosts_render() {

	$options = get_option( 'rrdwc_settings' );
	echo "<textarea name='rrdwc_settings[rrdwc_allowed_hosts]' rows='7' cols='50' type='textarea'>{$options['rrdwc_allowed_hosts']}</textarea>";
}

function rrdwc_links_in_new_window_render() {

	$options = get_option( 'rrdwc_settings' );
?>
	<input type="checkbox" name="rrdwc_settings[rrdwc_links_in_new_window]" value="1"<?php checked( isset( $options['rrdwc_links_in_new_window'] ) ); ?> />
<?php
}


function rrdwc_options_page(  ) { 

	?>
	<div class="wrap">
	<h1><?php __( 'Remote Dashboard Welcome Control Settings', 'rbs-remote-dashboard-welcome-control' );?></h1>
	<form action='options.php' method='post'>
		<?php
		settings_fields( 'pluginPage' );
		do_settings_sections( 'pluginPage' );
		submit_button();
		?>

	</form>
	</div>
	<?php

}

function rrdwc_settings_section_callback( $arg ) {
	_e( 'Enter the urls of the websites that will include the content of the Remote Dashboard Welcome Control page, separated by newlines.' , 'rbs-remote-dashboard-welcome-control' );	
}


/* Upon plugin activation create the centralized dashboard page */
function rrdwc_on_plugin_activation() {
    $dashpage = get_page_by_title(DASHBOARD_PAGE_TITLE);

    //Create the dashboard page, if it doesn't already exist
    if(!isset($dashpage)) { 
        $page_id = wp_insert_post(array('post_type' => 'page', 'post_title' => DASHBOARD_PAGE_TITLE));
    }
}

register_activation_hook( __FILE__, 'rrdwc_on_plugin_activation' );

function rrdwc_add_action_plugin($actions, $plugin_file) {
	static $plugin;

	if (! isset ( $plugin ))
		$plugin = plugin_basename ( __FILE__ );
		if ($plugin == $plugin_file) {

			$settings = array (
					'settings' => '<a href="options-general.php?page=remote_dashboard_welcome_control">' . __( 'Settings', 'rbs-remote-dashboard-welcome-control' ) . '</a>'
			);
			
			$site_link = array (
					'support' => '<a href="https://roadbearstudios.com/plugins/rbs-remote-dashboard-welcome" target="_blank">' . __( 'Support', 'rbs-remote-dashboard-welcome-control' ) . '</a>'
			);
			
			$actions = array_merge ( $settings, $actions );
			$actions = array_merge ( $site_link, $actions );
		}

		return $actions;
}

add_filter ( 'plugin_action_links', 'rrdwc_add_action_plugin', 10, 5 );

add_action( 'init', 'rrdwc_load_textdomain' );

/**
 * Load plugin textdomain.
 */
function rrdwc_load_textdomain() {
	load_plugin_textdomain( 'rbs-remote-dashboard-welcome-control', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
