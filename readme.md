# wp test-library

Provides WP-Cli commands for installing the WordPress test library and
 creating a wp-tests-config.php


## Installation

For now, you can install this package directly from GitHub:

```
wp package install https://github.com/stephenharris/wp-test-library.git
```
 
 
## Example useage

First install WordPress

```
# Download WordPress
mkdir -p /var/www/html/wordpress/
cd /var/www/html/wordpress/
wp core download --force --version=latest

# Create WordPress config
wp core config --dbname=wordpress --dbuser=dbuser --dbpass=dbpass

# Install the database tables
wp db create
wp core install --url="wp.dev" --title="wp.dev" --admin_user="admin" --admin_password="password" --admin_email="admin@wp.dev" --skip-email
```


Then install the test library

```
wp test-library download --library-path=/tmp/wp-test-library

```


Then create a `wp-tests-config.php` in your WordPress install for use with your tests

```
wp test-library config --dbname=wordpress_test --dbuser=dbuser --dbpass=dbpass --library-path=/tmp/wp-test-library

```


You can then run your integration tests by including the test library.

For example, you PHPUnit bootstrap file may look like:


```
//Load the test library...
require_once /tmp/wp-test-library/includes/functions.php';

//Install and activate plug-ins
function _manually_load_plugin() {
    //Include the plugin(s)
	require_once '/path/to/plugin.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

//Include test library bootstrap file
require '/tmp/wp-test-library/includes/bootstrap.php';

```