<?php
/**
 * @package WP Confession Box
 * @version 1.0.1
 */
/*
Plugin Name: WP Confession Box
Description: Allow users to do confession without showing their identity to others. 
Author: Ankur Vishwakarma
Version: 1.0
Author URI: ankurvishwakarma54@yahoo.com 
*/
if (!class_exists('WP_ConfessionBox')) {
class WP_ConfessionBox {

var $wpcb_db_version;
var $wpcb_dirpath;
var $wpcb_urlpath;

function __construct(){
$this->wpcb_version='1.0.1';
$this->wpcb_urlpath=plugin_dir_url( __FILE__ );
$this->wpcb_dirpath=plugin_dir_path(__FILE__);
register_activation_hook( __FILE__, array($this,'wpcb_install'));
add_action( 'wp_enqueue_scripts', array($this,'load_front_js_css_files' ));
$this->includes();
}

//Include files
function includes(){
	require_once $this->wpcb_dirpath.'controller/wpcb_controller.php';
}

// Load JS
function load_front_js_css_files(){

	
	wp_register_style( 'wpcb-bootstrap-css', $this->wpcb_urlpath . 'assets/css/wpcb_bootstrap.css' ); //
	wp_register_script( 'wpcb-tether-js', $this->wpcb_urlpath . 'assets/js/tether.min.js' );
	wp_register_script( 'wpcb-validator-js', $this->wpcb_urlpath . 'assets/js/validator.min.js' );
	wp_register_script( 'wpcb-bootstrap-js', $this->wpcb_urlpath . 'assets/js/bootstrap.min.js' );
	//wp_enqueue_script('jquery');
    wp_enqueue_style( 'wp-confessionbox-css', $this->wpcb_urlpath . 'assets/css/wpcb_confessionbox.css'  );
	wp_enqueue_script( 'wp-confessionbox-js', $this->wpcb_urlpath .  'assets/js/wp-confession-box.js' ,array('jquery'));
	wp_localize_script( 'wp-confessionbox-js', 'wpcb_ajax',array( 'ajaxurl' => admin_url( 'admin-ajax.php' )) );
	
}

function wpcb_install() {
	global $wpdb;

	$wpcb_manager = $wpdb->prefix . 'wpcb_manager';
	$wpcb_likes_manager = $wpdb->prefix . 'wpcb_likes_manager';
	$charset_collate = $wpdb->get_charset_collate();

	/*
	--
	-- Table structure for table `wpcb_manager`
	--
	*/
	$sql = "CREATE TABLE IF NOT EXISTS $wpcb_manager (
		  id mediumint(11) NOT NULL AUTO_INCREMENT,
		  author_name varchar(20) NOT NULL,
		  age int(11) NOT NULL,
		  location varchar(20) NOT NULL,
		  title varchar(20) NOT NULL,
		  confession text NOT NULL,
		  category int(11) NOT NULL,
		  approved int(11) NOT NULL,
		  created_at datetime NOT NULL,
		  ip_address varchar(20) NOT NULL,
		  blocked int(11) NOT NULL,
		  PRIMARY KEY (id)
		) $charset_collate;";
	
	/*
	--
	-- Table structure for table `wpcb_likes_manager`
	--
	*/
	$sql2 = "CREATE TABLE IF NOT EXISTS $wpcb_likes_manager (
			id int(11) NOT NULL AUTO_INCREMENT,
			confession_id int(11) NOT NULL,
			ip_address varchar(20) NOT NULL,
			user_id int(11) NOT NULL,
			applied int(11) NOT NULL,
			PRIMARY KEY (`id`)
			) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	dbDelta( $sql2 );

	add_option( 'wpcb_version', $this->wpcb_version );
}

}
global $wp_confession_box;
$wp_confession_box=new WP_ConfessionBox;
}