<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wordpress' );

/** Database username */
define( 'DB_USER', 'user' );

/** Database password */
define( 'DB_PASSWORD', 'wp_password' );

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
define( 'AUTH_KEY',         '}Mfq7!qKMF@sC]e)oo#vRY[u+pLI~en^JBmhK;I|lo=3;<o!0x(}R~=;!8Z9]pj_' );
define( 'SECURE_AUTH_KEY',  '`-TVCNBJ@:,m%.+qe,$U EPm8d?C%qPt}Xh5qHk{aS6uhZbSwX6`A;qHJ49;]<X&' );
define( 'LOGGED_IN_KEY',    'R5:{]Lj7z;%L&H-q3t-*AuS%=@=F *%F2-`(!ob][7A`8QRF{T#o`FiSQh@Z?B`t' );
define( 'NONCE_KEY',        '7Hu%me@rhK_m6b4{`EB^Arqj[u0;4%Iv/ft7z.ro~VXK>p7dr`rW*Qu3Kn,e?J_=' );
define( 'AUTH_SALT',        'pA$Js|WKAU1c$;fn/n x14QIYDqg{bN.o-%a&nD)(Erl9J@oJm^N<$pBZvp.7!G)' );
define( 'SECURE_AUTH_SALT', '#tB#x>Cc7P)XG58p03t>=cm2! j$-9xHN@SH8i>dq`mhU161/}IbPVQ*Ku<=QjhI' );
define( 'LOGGED_IN_SALT',   'T(.?W3g}twScnGQ/s;1KAVmtC]3]<q$>8:OtYKsNp,Z=c3I`>BoGg+,6a6?R=6xG' );
define( 'NONCE_SALT',       '(6~g_7}JFF)1H0Wh .u2,G*C!AvmU!;f?VH:m/@#/5 bd?#eZ[bCbU>uY)-b;wTK' );

/**#@-*/

/**
 * WordPress database table prefix.
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
define('FS_METHOD', 'direct');
define( 'WP_DEBUG_LOG', false );

/* Add any custom values between this line and the "stop editing" line. */

define( 'AS3CF_SETTINGS', serialize( array(
    'provider' => 'do',
    'access-key-id' => 'ZBIEVPXC6TXRP65HLVSD',
    'secret-access-key' => 'QJE25wzJ7juS3faCgSCKSfZI78OkIa+oXEirC+ny2DE',
) ) );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
