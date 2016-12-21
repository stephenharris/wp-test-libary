Feature: Manage wp-test-config

  Background:
    Given I have a vanilla WordPress installation
      | name          | email                   | username | password |
      | BDD WordPress | test.user@wordpress.dev | admin    | test     |
    And an empty directory "/tmp/wp-test-library"

  Scenario: Create a wp-test-config file
    When I run `wp --path=/tmp/wordpress test-library config --dbname=wordpress_cli_test --dbuser=testuser --dbpass=testpass --library-path=/tmp/wp-test-library`
    Then STDOUT should contain:
    """
    Success: Generated 'wp-tests-config.php
    """
    And the file "/tmp/wp-test-library/wp-tests-config.php" should contain
    """
    define( 'DB_NAME', 'wordpress_cli_test' );
    """
    And the file "/tmp/wp-test-library/wp-tests-config.php" should contain
    """
    define( 'DB_USER', 'testuser' );
    """
    And the file "/tmp/wp-test-library/wp-tests-config.php" should contain
    """
    define( 'DB_PASSWORD', 'testpass' );
    """
    And the file "/tmp/wp-test-library/wp-tests-config.php" should contain
    """
    $table_prefix  = 'wptests_';
    """

    When I try `wp --path=/tmp/wordpress test-library config --dbname=wordpress_cli_test --dbuser=testuser --dbpass=testpass --library-path=/tmp/wp-test-library`
    Then the return code should be 1
    And STDERR should contain:
    """
    Error: The 'wp-tests-config.php' file already exists.
    """

    When I try `wp --path=/tmp/wordpress test-library config --force --dbname=wordpress_cli_test --dbuser=testuser --dbpass=testpass --library-path=/tmp/wp-test-library`
    Then STDOUT should contain:
    """
    Success: Generated 'wp-tests-config.php
    """

  Scenario: Create a wp-test-config file with an incorrect username/password
    When I try `wp --path=/tmp/wordpress test-library config --dbname=wordpress_cli_test --dbuser=testuser --dbpass=nottherightpassword --library-path=/tmp/wp-test-library`
    Then the return code should be 1
    And STDERR should contain:
    """
    Access denied for user 'testuser'@'localhost'
    """

  Scenario: Create a wp-test-config file with an incorrect username/password, but the skip is checked
    When I run `wp --path=/tmp/wordpress test-library config --skip-check --dbname=wordpress_cli_test --dbuser=testuser --dbpass=nottherightpassword --library-path=/tmp/wp-test-library`
    Then STDOUT should contain:
    """
    Success: Generated 'wp-tests-config.php
    """

  Scenario: Create a wp-test-config file where the database credentials match that of the WP install
    When I run `wp --path=/tmp/wordpress test-library config --dbname=wp_cli_test_library_test --dbprefix=wp_ --dbuser=testuser --dbpass=testpass --library-path=/tmp/wp-test-library`
    Then STDOUT should contain:
    """
    Success: Generated 'wp-tests-config.php' file
    """
    And STDERR should contain:
    """
    Warning: Test database and table prefix matches that of the WordPress install.
    """
    And STDERR should contain:
    """
    Warning: Running the test will DROP ALL TABLES in the wp_cli_test_library_test with prefix wp_.
    """

  Scenario: Create a wp-test-config file with an invalid database prefix
    When I try `wp --path=/tmp/wordpress test-library config --dbname=wp_cli_test_library_test --dbprefix=!invalidprefix! --dbuser=testuser --dbpass=testpass --library-path=/tmp/wp-test-library`
    Then the return code should be 1
    And STDERR should contain:
    """
    --dbprefix can only contain numbers, letters, and underscores.
    """
