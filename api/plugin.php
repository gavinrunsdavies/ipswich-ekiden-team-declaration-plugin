<?php
/*
Plugin Name: 
*/

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

require_once plugin_dir_path( __FILE__ ) .'v1/class-ipswich-ekiden-team-declaration-api-controller-v1.php';

// hook into the rest_api_init action so we can start registering routes
$api_controller_V1 = new IpswichEkidenTeamDeclaration\V1\Ipswich_Ekiden_Team_Declaration_API_Controller_V1();
add_action( 'rest_api_init', array( $api_controller_V1, 'rest_api_init') );
add_action( 'plugins_loaded', array( $api_controller_V1, 'plugins_loaded') );
?>