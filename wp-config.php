<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'leadly' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'p@ssw0rd12' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'wli)I1|]X0N{Jy]bH.ORKB]4okaNR.gS,^~h;1k5V}W6}. v %N#A2Qp[m)5B]LR');
define('SECURE_AUTH_KEY',  'P~HkL Z-+v#gPJYGQrbhP0AshM6|iBYg<-KA/-6nAhgskmL yqB@TC8jAoG}ytVv');
define('LOGGED_IN_KEY',    '/J0Q^e<=tM=bX,h-L>Nc/j1hr[+^vcl{8]Tj^YH0s=3S)2ssyb#- o.~)aC[D`e~');
define('NONCE_KEY',        'oP#ln|z/UH-9N|X<2d+q{qgm36Sx~Y&}4`q&%MRw*NJ#2+8_7L&2,/nO0#RkE#p|');
define('AUTH_SALT',        '-fFO3H+2}3a,T4Rt!Yoi8eLSE03#|9q[c36,`^v4N.&u$?v8s 1S@C Q|].<rX8=');
define('SECURE_AUTH_SALT', 'f Y+*wRs#BIrDVBu7<Fmt|JG]%7,7x^9Ytl1W;sD1hmxp{>X`$ezipO7[_oybu4W');
define('LOGGED_IN_SALT',   'u!ueI4xTl5`Wko~.6Jxny  YzICV[vSHML} FlRm1,8L+:ShSFWUxVL33;o3h7!J');
define('NONCE_SALT',       '8ve$&D6Xuz$OA73nQz>542lDuE;L4~NIWQ+-lW|Dti%Exf73PUb&l9f!+|`wHD$i');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
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
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
