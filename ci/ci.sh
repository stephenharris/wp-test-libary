#!/bin/bash

# Exit if anything fails AND echo each command before executing
# http://www.peterbe.com/plog/set-ex
set -e

if [ $# -lt 3 ]; then
	echo "usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
	exit 1
fi

# Set up constant
DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

WORDPRESS_SITE_DIR='/tmp/wordpress';

# Create WordPress root directory
mkdir -p $WORDPRESS_SITE_DIR

# Download WordPress
vendor/bin/wp core download --force --version=$WP_VERSION --path=$WORDPRESS_SITE_DIR --allow-root

# Create configs
rm -f ${WORDPRESS_SITE_DIR}/wp-config.php
vendor/bin/wp core config --path=$WORDPRESS_SITE_DIR --dbname=wp_cli_test_library_test --dbuser=$DB_USER --dbpass=$DB_PASS --dbhost=$DB_HOST --allow-root

# Create Database
vendor/bin/wp db reset --path=$WORDPRESS_SITE_DIR --allow-root --yes

# We only run the install command so that we can run further wp-cli commands
vendor/bin/wp core install --path=$WORDPRESS_SITE_DIR --url="wp.dev" --title="wp.dev" --admin_user="admin" --admin_password="password" --admin_email="admin@wp.dev" --skip-email --allow-root

mysql --user=$DB_USER --password=$DB_PASS -e "GRANT ALL PRIVILEGES  ON ${DB_NAME}.* TO 'testuser'@'localhost' IDENTIFIED BY 'testpass' WITH GRANT OPTION;";