Feature: Install test library

  Background:
    Given I have a vanilla WordPress installation
      | name          | email                   | username | password |
      | BDD WordPress | test.user@wordpress.dev | admin    | test     |
    And an empty directory "/tmp/wp-test-library"

  Scenario: Download the test library
    When I run `wp --path=/tmp/wordpress test-library download --library-path=/tmp/wp-test-library`
    Then STDOUT should contain:
    """
    Downloading WordPress Test Library
    """
    And STDOUT should contain:
    """
    Success: WordPress Test Library downloaded.
    """
    And the file "/tmp/wp-test-library/build.xml" should contain
    """
    <project name="WordPress Unit Tests" default="build"
    """

    When I try `wp --path=/tmp/wordpress test-library download --library-path=/tmp/wp-test-library`
    Then the return code should be 1
    And STDERR should contain:
    """
    The WordPress Test Library files seem to already be present here.
    """

  Scenario: Download a specific version
    When I run `wp --path=/tmp/wordpress test-library download --library-path=/tmp/wp-test-library --version=4.6.1`
    Then STDOUT should contain:
    """
    Downloading WordPress Test Library 4.6.1
    """
    And STDOUT should contain:
    """
    Success: WordPress Test Library downloaded.
    """

  Scenario: Download the trunk version
    When I run `wp --path=/tmp/wordpress test-library download --library-path=/tmp/wp-test-library --version=trunk`
    Then STDOUT should contain:
    """
    Downloading WordPress Test Library trunk
    """
    And STDOUT should contain:
    """
    Success: WordPress Test Library downloaded.
    """

  Scenario: Download the nightly version (alias for trunk)
    When I run `wp --path=/tmp/wordpress test-library download --library-path=/tmp/wp-test-library --version=nightly`
    Then STDOUT should contain:
    """
    Downloading WordPress Test Library nightly
    """
    And STDOUT should contain:
    """
    Success: WordPress Test Library downloaded.
    """
