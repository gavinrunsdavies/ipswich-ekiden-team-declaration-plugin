<?php
/*
Plugin Name: Ipswich Ekiden Team Declaration API
Plugin URI: https://github.com/gavinrunsdavies/ipswich-ekiden-team-declaration-plugin
Description: Provides REST API endpoints for Ipswich Ekiden team declaration and registration.
Version: 0.1.0
Author: Gavin Davies
Author URI: https://github.com/gavinrunsdavies/
Text Domain: ipswich-ekiden-team-declaration-api
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

register_activation_hook( __FILE__, array( 'Program', 'activate' ) );

$go = new Program();

class Program {
	const DB_VERSION = '1.0';

	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_clubs = $wpdb->prefix . 'ietd_clubs';
		$table_teams = $wpdb->prefix . 'ietd_teams';
		$table_runners = $wpdb->prefix . 'ietd_runners';
		$table_team_runners = $wpdb->prefix . 'ietd_team_runners';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_clubs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			PRIMARY KEY  (id)
		) {$charset_collate};";
		dbDelta( $sql );

		$sql = "CREATE TABLE {$table_teams} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			club_id bigint(20) unsigned NOT NULL,
			captain_id bigint(20) unsigned NOT NULL,
			is_junior_team tinyint(1) NOT NULL DEFAULT 0,
			number int(11) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY club_id (club_id),
			KEY captain_id (captain_id)
		) {$charset_collate};";
		dbDelta( $sql );

		$sql = "CREATE TABLE {$table_runners} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			age_category varchar(50) DEFAULT NULL,
			gender varchar(50) DEFAULT NULL,
			date_of_birth date DEFAULT NULL,
			medical_info text DEFAULT NULL,
			PRIMARY KEY  (id)
		) {$charset_collate};";
		dbDelta( $sql );

		$sql = "CREATE TABLE {$table_team_runners} (
			team_id bigint(20) unsigned NOT NULL,
			runner_id bigint(20) unsigned NOT NULL,
			leg tinyint(2) unsigned NOT NULL,
			PRIMARY KEY  (team_id, runner_id, leg),
			KEY runner_id (runner_id)
		) {$charset_collate};";
		dbDelta( $sql );

		add_option( 'ipswich_ekiden_team_declaration_db_version', self::DB_VERSION );
	}

	public function __construct() {
		require_once plugin_dir_path( __FILE__ ) . 'api/plugin.php';
	}
}
?>