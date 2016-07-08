<?php

namespace stephenharris\WPTestLibrary;

use \Composer\Semver\Comparator;
use \WP_CLI\Utils;
use \WP_CLI;

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Download, install and configure the WordPress test library
 *
 * ## EXAMPLES
 *
 *     $ wp test-library download --library-path=/tmp/wordpress-test-lib
 *     Creating directory '/tmp/wordpress-test-lib'.
 *     Downloading WordPress Test Library 4.5.3 ...
 *     Success: WordPress Test Library downloaded.
 *
 * @package wp-cli
 */
class Command extends \WP_CLI_Command {


	/**
	 * Download core WordPress files.
	 *
	 * ## OPTIONS
	 *
	 * [--library-path=<library-path>]
	 * : Specify the path in which to install the test library.
	 *
	 * [--path=<path>]
	 * : Specify the location of the WordPress install.
	 *
	 * [--locale=<locale>]
	 * : Select which language you want to download.
	 *
	 * [--version=<version>]
	 * : Select which version you want to download.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp test-library download --library-path=/tmp/wordpress-test-lib
	 *     Creating directory '/tmp/wordpress-test-lib'.
	 *     Downloading WordPress Test Library 4.5.3 ...
	 *     Success: WordPress Test Library downloaded.
	 *
	 * @when before_wp_load
	 */
	public function download( $args, $assoc_args ) {

		$assoc_args = array_merge( array(
			'version'     => 'latest',
			'library-path' => '',
			'path'        => ABSPATH,
		), $assoc_args );

		$version      = $assoc_args['version'];
		$library_path = $assoc_args['library-path'];

		if ( ! is_dir( $library_path ) ) {
			if ( ! is_writable( dirname( $library_path ) ) ) {
				WP_CLI::error( sprintf( "Insufficient permission to create directory '%s'.", $library_path ) );
			}

			WP_CLI::log( sprintf( "Creating directory '%s'.", $library_path ) );
			$mkdir = \WP_CLI\Utils\is_windows() ? 'mkdir %s' : 'mkdir -p %s';
			WP_CLI::launch( Utils\esc_cmd( $mkdir, $library_path ) );
		}

		if ( ! is_writable( $library_path ) ) {
			WP_CLI::error( sprintf( "'%s' is not writable by current user.", $library_path ) );
		}

		if ( 'latest' == $version ) {
			$version = $this->get_latest_version();
		}
		$download_url = $this->get_download_url($version);

		WP_CLI::log( sprintf( 'Downloading WordPress Test Library %s ...', $version ) );

		$cmd = "svn co --quiet $download_url %s";

		WP_CLI::launch( Utils\esc_cmd( $cmd, $library_path ) );
		WP_CLI::success( 'WordPress Test Library downloaded.' );
	}

	/**
	 * Generate a wp-tests-config.php file.
	 *
	 * ## OPTIONS
	 *
	 * --dbname=<dbname>
	 * : Set the database name.
	 *
	 * --dbuser=<dbuser>
	 * : Set the database user.
	 *
	 * [--dbpass=<dbpass>]
	 * : Set the database user password.
	 *
	 * [--dbhost=<dbhost>]
	 * : Set the database host. Default: 'localhost'
	 *
	 * [--dbprefix=<dbprefix>]
	 * : Set the database table prefix. Default: 'wptests_'
	 *
	 * [--dbcharset=<dbcharset>]
	 * : Set the database charset. Default: 'utf8'
	 *
	 * [--dbcollate=<dbcollate>]
	 * : Set the database collation. Default: ''
	 *
	 * [--testdomain=<testdomain>]
	 * : Set the database collation. Default: 'example.org'
	 *
	 * [--testemail=<testemail>]
	 * : Set the database collation. Default: 'admin@example.org'
	 *
	 * [--testtitle=<testtitle>]
	 * : Set the database collation. Default: 'Test Blog'
	 *
	 * [--phpbinary=<phpbinary>]
	 * : Set the database collation. Default: 'php'
	 *
	 * [--locale=<locale>]
	 * : Set the WPLANG constant. Defaults to $wp_local_package variable.
	 *
	 * [--skip-check]
	 * : If set, the database connection is not checked.
	 *
	 * [--force]
	 * : Over-ride the exsiting wp-tests-config.php if it exists.
	 *
	 * ## EXAMPLES
	 *
	 *     # Basic wp-tests-config.php file
	 *     $ wp test-library config --dbname=wordpress_test --dbuser=wp --dbpass=wp
	 *     Success: Generated 'wp-tests-config.php' file.
	 *
	 *     # When database details match that of the core install
	 *     $ wp test-library config --dbname=wp --dbuser=wp --dbpass=wp --dbprefix=wp_
	 *     Warning: Test database and table prefix matches that of the WordPress install.
	 *     Warning: Running the test will DROP ALL TABLES in the wp with prefix wp_.
	 *     Success: Generated 'wp-tests-config.php' file.
	 *
	 */
	public function config( $_, $assoc_args ) {

		$wp_config = Utils\locate_wp_config();
		$wp_tests_config = dirname( $wp_config ) . DIRECTORY_SEPARATOR . 'wp-tests-config.php';

		if ( file_exists( $wp_tests_config ) && ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'force' ) ) {
			WP_CLI::error( "The 'wp-tests-config.php' file already exists." );
		}

		$versions_path = ABSPATH . 'wp-includes/version.php';
		include $versions_path;

		$defaults = array(
			'dbhost'     => 'localhost',
			'dbpass'     => '',
			'dbcharset'  => 'utf8',
			'dbcollate'  => '',
			'dbprefix'   => 'wptests_',
			'testdomain' => 'example.org',
			'testemail'  => 'admin@example.org',
			'testtitle'  => 'Test Blog',
			'phpbinary'  => 'php',
			'path'       => ABSPATH,
		);
		$assoc_args = array_merge( $defaults, $assoc_args );

		//Warn the user if the database name and prefix match the core install
		global $wpdb;
		if ( DB_NAME == $assoc_args['dbname'] && $wpdb->prefix == $assoc_args['dbprefix'] ) {
			WP_CLI::warning( 'Test database and table prefix matches that of the WordPress install.' );
			WP_CLI::warning( sprintf(
				'Running the test will DROP ALL TABLES in the %s with prefix %s.',
				DB_NAME,
				$wpdb->prefix
			) );
		}

		if ( preg_match( '|[^a-z0-9_]|i', $assoc_args['dbprefix'] ) ) {
			WP_CLI::error( '--dbprefix can only contain numbers, letters, and underscores.' );
		}

		// Check DB connection
		if ( ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-check' ) ) {
			Utils\run_mysql_command( 'mysql --no-defaults', array(
				'execute' => ';',
				'host' => $assoc_args['dbhost'],
				'user' => $assoc_args['dbuser'],
				'pass' => $assoc_args['dbpass'],
			) );
		}

		$assoc_args['add-wplang'] = (bool) \WP_CLI\Utils\wp_version_compare( '4.0', '<' );

		$template_name = dirname( __FILE__ ) . '/templates/wp-tests-config.mustache';
		$template      = file_get_contents( $template_name );
		$m = new \Mustache_Engine( array(
			'escape' => function ( $val ) { return $val; }
		) );
		$out = $m->render( $template, $assoc_args );

		$bytes_written = file_put_contents( ABSPATH . 'wp-tests-config.php', $out );
		if ( ! $bytes_written ) {
			WP_CLI::error( "Could not create 'wp-tests-config.php' file." );
		} else {
			WP_CLI::success( "Generated 'wp-tests-config.php' file." );
		}
	}

	/**
	 * Gets the download url of the test library for the specified WordPress version
	 *
	 * @param $version WordPress version being tested against (e.g. 4.5.3, 4.4, nightly)
	 * @return string
	 */
	private function get_download_url( $version )
	{
		$tag     = false;
		$version = strtolower( $version );

		if ( preg_match( '#[0-9]+\.[0-9]+(\.[0-9]+)?#', $version ) ) {
			$tag = "tags/$version";
		} elseif ( 'nightly' == $version || 'trunk' == $version ) {
			$tag = "trunk";
		}

		if ( ! $tag ) {
			WP_CLI::error( "Failed to find $version" );
			return;
		}

		return "https://develop.svn.wordpress.org/{$tag}/tests/phpunit/includes/";
	}

	/**
	 * Uses wordpress.org's API to determine the latest version.
	 *
	 * @return bool|string False on failure. Otherwise a version string (no fixed format).
	 */
	private function get_latest_version() {
		$response = wp_remote_get( 'http://api.wordpress.org/core/version-check/1.7' );
		$body     = wp_remote_retrieve_body( $response );
		$code     = (int) wp_remote_retrieve_response_code( $response );
		$version  = false;

		if ( 200 !== $code ) {
			return $version;
		}

		$json = json_decode( $body, true );
		if ( isset( $json['offers'] ) && isset( $json['offers'][0] ) ) {
			if ( isset( $json['offers'][0]['version'] ) ) {
				$version = $json['offers'][0]['version'];
			}
		}

		$version = preg_replace('/[^.0-9]/', '', $version );

		return $version;
	}

}
WP_CLI::add_command( 'test-library', '\stephenharris\WPTestLibrary\Command' );
