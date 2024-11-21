<?php
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
define( 'DB_NAME', 'emi' );

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
define( 'AUTH_KEY',         '~`4irvYGph=HTV0)!7&SvCFJ&bW/m#KOes#*9]IVHYTU)k9bAn{%:HFDK^JKg_Kz' );
define( 'SECURE_AUTH_KEY',  'j=rc{D*uFfWJ&=+lZZW1p1@U&q5Jj<4>fv-5Z/pg%QqtHftg-{c~<#x?@VF#==aW' );
define( 'LOGGED_IN_KEY',    'A1vFJx;cW:5I;!SR(x)(J<o8K}3A`RYZF.v.?mK]57%ML]a435vDeKD(kP2-<u-w' );
define( 'NONCE_KEY',        'v-ee5+5^eBWT:-[iPKVws5[HIGo#X0|}}y7UMd]~L,s+v}h0|aPm,s#vu>!qgt/?' );
define( 'AUTH_SALT',        'D(id$s4CDwv;<|N;{^_Ip9PU%fBJt=nJ[2$[8x~?>#SEH3~{p/MU(Bq5/Kk^of[o' );
define( 'SECURE_AUTH_SALT', 'd3*:&V3xfbZ&j0&S9ELqCJeo1&Q=[k_Z8P#t+U$t$VxwnA-eB{1G)=L=9hv$+[Z{' );
define( 'LOGGED_IN_SALT',   '/}<Z},#K2XC[e%<n4K*y4s+x>}T#JWAnx#5{DM|e_{kz;P7}3y:[=R,dqe4`$t*l' );
define( 'NONCE_SALT',       '^l[Eb(6!Cyg|o_(hllj=FT(76eIR)LDc7Ui?;ephjppkTQ(cz!o=2?,Xp;Q? eR3' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'emi_';

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
