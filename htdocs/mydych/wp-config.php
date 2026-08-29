<?php
//Begin Really Simple Security session cookie settings
@ini_set('session.cookie_httponly', true);
@ini_set('session.cookie_secure', true);
@ini_set('session.use_only_cookies', true);
//END Really Simple Security cookie settings
//Begin Really Simple Security key
define('RSSSL_KEY', 'AYsaJOVyMG4nAxjfx5F8HlIUhh017ptD8zk5wAKkhhBOBpGVzDYHd9enoYXIhu1n');
//END Really Simple Security key

/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'mydych' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'U0}vdZRsv1$BSJk0N-;.cL+Dv?8pN%t4W@LDxKkqh*I-0H5}iAMm{!M;/q{kZ|VC' );
define( 'SECURE_AUTH_KEY',  'teGR!lH/UU:QSYCLlH>WgoWjla88iS}O!4b(q@>dMz3KQ1x!9Yv?N=#zPFZ6qeOR' );
define( 'LOGGED_IN_KEY',    'L%On5(5 *Pi6#q-SvP#5A|N/[PN|:-{]136oIEsApvLSy:j*:0:q~m+I^cq+!Zqk' );
define( 'NONCE_KEY',        '%n(>Fe1yJi;3ZZEW2`wuVk0d5l`UE3Su>.hal/{WU,UWI6}H|M(n8<m1dD}J*e[O' );
define( 'AUTH_SALT',        'D5Y^5A:AJ.-tVR,sk([pQ%h1vC$(3gO)E>kr#}lsnS(Yb~lr37^jYWk#55K(uqai' );
define( 'SECURE_AUTH_SALT', 'q00Jw> +lKRMhuG?CGjCmJ>!VT0j_Avi4cdkP3LBGH+x7k,3RCQ9R;6=}o(fKGv@' );
define( 'LOGGED_IN_SALT',   'i&fm9ocb@*R1]Z^^l7.1 $bv&z(o`B%sB3ZHq7*R-v:j*2<u=3 #*.+`Sz2=cyKG' );
define( 'NONCE_SALT',       ' 3+,D.czrBq$5)WeUz[SawcAV^ULR_b~nE85LmX4Bd!:<3TB(E0WZ$-[$%L2^O(^' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
