<?php

namespace stephenharris\WPTestLibrary;

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
	 * [--force]
	 * : Over-ride the exsiting wp-tests-config.php if it exists.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp test-library download --library-path=/tmp/wordpress-test-lib
	 *     Creating directory '/tmp/wordpress-test-lib'.
	 *     Downloading WordPress Test Library 4.5.3 ...
	 *     Success: WordPress Test Library downloaded.
	 *
	 */
	public function download( $args, $assoc_args ) {

		$assoc_args = array_merge( array(
			'version'      => 'latest',
			'library-path' => '',
			'path'         => ABSPATH,
		), $assoc_args );

		$version      = $assoc_args['version'];
		$library_path = $assoc_args['library-path'];

		$build_file = rtrim( $assoc_args['library-path'], '/' ) . '/build.xml';
		$test_library_present = file_exists( $build_file );
		
		if ( ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'force' ) && $test_library_present ) {
			WP_CLI::error( sprintf( "The WordPress Test Library files seem to already be present here. at %s", $build_file ) );
		}

		$this->create_directory_if_not_exists( $library_path );

		if ( ! is_writable( $library_path ) ) {
			WP_CLI::error( sprintf( "'%s' is not writable by current user.", $library_path ) );
		}

		if ( 'latest' == $version ) {
			$version = $this->get_latest_version();
		}
		$download_url = $this->get_download_url($version);

		WP_CLI::log( sprintf( 'Downloading WordPress Test Library %s ...', $version ) );

		//Checkout test library SVN repo to temporary location and copy to desired location.
		$tempdir = \WP_CLI\Utils\get_temp_dir() . uniqid('wp_');
		$mkdir = \WP_CLI\Utils\is_windows() ? 'mkdir %s' : 'mkdir -p %s';
		WP_CLI::launch( Utils\esc_cmd( $mkdir, $tempdir ) );
		WP_CLI::launch( Utils\esc_cmd( "svn co --quiet $download_url %s", $tempdir ) );
		
		self::_copy_overwrite_files( $tempdir, $library_path );
		self::_rmdir( $tempdir );

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
	 * [--library-path=<library-path>]
	 * : Specify the path in which to store the tests file (i.e. location of the test library)
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
	 * : Set the test domain. Default: 'example.org'
	 *
	 * [--testemail=<testemail>]
	 * : Set the test admin e-mail. Default: 'admin@example.org'
	 *
	 * [--testtitle=<testtitle>]
	 * : Set the test site title. Default: 'Test Blog'
	 *
	 * [--phpbinary=<phpbinary>]
	 * : Set the PHP binary. Default: 'php'
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

		$wp_tests_config = $assoc_args['library-path'] . DIRECTORY_SEPARATOR . 'wp-tests-config.php';
		if ( file_exists( $wp_tests_config ) && ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'force' ) ) {
			WP_CLI::error( "The 'wp-tests-config.php' file already exists." );
		}

		$defaults = array(
			'dbhost'       => 'localhost',
			'dbpass'       => '',
			'dbcharset'    => 'utf8',
			'dbcollate'    => '',
			'dbprefix'     => 'wptests_',
			'testdomain'   => 'example.org',
			'testemail'    => 'admin@example.org',
			'testtitle'    => 'Test Blog',
			'phpbinary'    => 'php',
			'path'         => ABSPATH,
			'library-path' => '',
		);
		$assoc_args = array_merge( $defaults, $assoc_args );

		if ( preg_match( '|[^a-z0-9_]|i', $assoc_args['dbprefix'] ) ) {
			WP_CLI::error( '--dbprefix can only contain numbers, letters, and underscores.' );
		}

		$this->warn_if_db_tables_match_site( $assoc_args['dbname'], $assoc_args['dbprefix'] );

		if ( ! \WP_CLI\Utils\get_flag_value( $assoc_args, 'skip-check' ) ) {
			$this->check_db_connection( $assoc_args['dbhost'], $assoc_args['dbuser'], $assoc_args['dbpass'] );
		}

		$this->create_config( $assoc_args, $assoc_args['library-path'] . '/wp-tests-config.php' );
	}

	private function warn_if_db_tables_match_site( $dnbame, $dbprefix ) {
		global $wpdb;
		if ( DB_NAME == $dnbame && $wpdb->prefix == $dbprefix ) {
			WP_CLI::warning( 'Test database and table prefix matches that of the WordPress install.' );
			WP_CLI::warning( sprintf(
				'Running the test will DROP ALL TABLES in the %s with prefix %s.',
				DB_NAME,
				$wpdb->prefix
			) );
		}
	}

	private function check_db_connection( $dbhost, $dbuser, $dbpass ) {
		Utils\run_mysql_command( 'mysql --no-defaults', array(
			'execute' => ';',
			'host' => $dbhost,
			'user' => $dbuser,
			'pass' => $dbpass,
		) );
	}

	private function create_config( $parameters, $location ) {
		$template_name = dirname( __FILE__ ) . '/templates/wp-tests-config.mustache';
		$template      = file_get_contents( $template_name );
		$m = new \Mustache_Engine( array(
			'escape' => function ( $val ) { return $val; }
		) );
		$out = $m->render( $template, $parameters );

		$bytes_written = file_put_contents( $location, $out );
		if ( ! $bytes_written ) {
			WP_CLI::error( sprintf( "Could not create '%s' file.", basename( $location ) ) );
		} else {
			WP_CLI::success(  sprintf( "Generated '%s' file.", basename( $location ) ) );
		}
	}

	private function create_directory_if_not_exists( $directory ) {
		if ( ! is_dir( $directory ) ) {
			if ( ! is_writable( dirname( $directory ) ) ) {
				WP_CLI::error( sprintf( "Insufficient permission to create directory '%s'.", $directory ) );
			}

			WP_CLI::log( sprintf( "Creating directory '%s'.", $directory ) );
			$mkdir = \WP_CLI\Utils\is_windows() ? 'mkdir %s' : 'mkdir -p %s';
			WP_CLI::launch( Utils\esc_cmd( $mkdir, $directory ) );
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

		return "https://develop.svn.wordpress.org/{$tag}/tests/phpunit/";
	}

	private static function _copy_overwrite_files( $source, $dest ) {
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $source, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::SELF_FIRST);
		$error = 0;
		foreach ( $iterator as $item ) {
			$dest_path = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
			if ( $item->isDir() ) {
				if ( !is_dir( $dest_path ) ) {
					mkdir( $dest_path );
				}
			} else {
				if ( file_exists( $dest_path ) && is_writable( $dest_path ) ) {
					copy( $item, $dest_path );
				} elseif ( ! file_exists( $dest_path ) ) {
					copy( $item, $dest_path );
				} else {
					$error = 1;
					WP_CLI::warning( "Unable to copy '" . $iterator->getSubPathName() . "' to current directory." );
				}
			}
		}
		if ( $error ) {
			WP_CLI::error( 'There was an error downloading all WordPress files.' );
		}
	}
	
	private static function _rmdir( $dir ) {
		$files = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $files as $fileinfo ) {
			$todo = $fileinfo->isDir() ? 'rmdir' : 'unlink';
			$todo( $fileinfo->getRealPath() );
		}
		rmdir( $dir );
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
