<?php
/**
 * Plugin Name: Privento
 * Plugin URI:  https://github.com/graffic-webdesign/privento
 * Update URI:  https://github.com/graffic-webdesign/privento
 * Description: GDPR cookie consent: consent banner, Google Consent Mode v2, script blocking, consent log and a generated cookie policy. Follows the site language.
 * Version:     1.7.2
 * Author:      Graffic
 * Author URI:  https://graffic.dk
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: privento
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP:      8.0
 *
 * @package Privento
 */

defined( 'ABSPATH' ) || exit;

// Constants
define( 'PRIVENTO_VERSION',   '1.7.2' );
define( 'PRIVENTO_FILE',      __FILE__ );
define( 'PRIVENTO_DIR',       plugin_dir_path( __FILE__ ) );
define( 'PRIVENTO_URL',       plugin_dir_url( __FILE__ ) );
define( 'PRIVENTO_BASENAME',  plugin_basename( __FILE__ ) );
define( 'PRIVENTO_LOG_TABLE', 'privento_consent_log' );

/**
 * Autoloader — PSR-4 style for the Privento namespace.
 */
spl_autoload_register( function ( string $class ): void {
    $prefix = 'Privento\\';
    if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
        return;
    }
    $relative = str_replace( '\\', DIRECTORY_SEPARATOR, substr( $class, strlen( $prefix ) ) );
    $file      = PRIVENTO_DIR . 'includes/' . $relative . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

/**
 * Returns the single plugin instance.
 */
function privento(): \Privento\Core\Plugin {
    return \Privento\Core\Plugin::instance();
}

// Boot on init, not plugins_loaded: translations are not available earlier
// (WordPress 6.7+ warns when they are used before then), and the settings
// resolve the banner texts through them.
add_action( 'init', 'privento', 0 );

// Activation / deactivation hooks
register_activation_hook(   __FILE__, [ \Privento\Core\Installer::class, 'activate'   ] );
register_deactivation_hook( __FILE__, [ \Privento\Core\Installer::class, 'deactivate' ] );
