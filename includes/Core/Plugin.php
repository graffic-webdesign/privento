<?php
/**
 * Core Plugin bootstrap.
 *
 * @package Privento\Core
 */

namespace Privento\Core;

defined( 'ABSPATH' ) || exit;

final class Plugin {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function __construct() {}

    private function init(): void {
        $this->load_textdomain();
        // Resolved texts are cached per request; a locale switch invalidates them.
        add_action( 'change_locale', [ Settings::class, 'flush' ] );
        if ( Installer::maybe_upgrade() ) {
            $this->load_textdomain(); // The upgrade may have pinned a language.
        }
        $this->load_modules();
    }

    private function load_textdomain(): void {
        // A site pinned to one language by the 1.7.0 upgrade (see
        // Installer::blank_default_texts()) shows visitors that language whatever
        // the WordPress locale. The admin screens keep following the user.
        $forced = Settings::stored( 'forced_locale' );
        $mofile = PRIVENTO_DIR . 'languages/privento-' . $forced . '.mo';
        if ( $forced && ! is_admin() && preg_match( '/^[A-Za-z_]+$/', $forced ) && is_readable( $mofile ) ) {
            unload_textdomain( 'privento' );
            load_textdomain( 'privento', $mofile, $forced );
            return;
        }

        load_plugin_textdomain(
            'privento',
            false,
            dirname( PRIVENTO_BASENAME ) . '/languages'
        );
    }

    private function load_modules(): void {
        // Always-on modules. The updater too: WordPress checks for updates
        // from cron, where is_admin() is false.
        new Updater();
        new \Privento\Consent\Manager();
        \Privento\Consent\Logger::register_cleanup();
        new \Privento\Public\Frontend();

        // Admin-only modules
        if ( is_admin() ) {
            new \Privento\Admin\Admin();
            new \Privento\Admin\Ajax();
        }

        // Integrations
        new \Privento\Integrations\GoogleConsentMode();
        new \Privento\Integrations\GoogleTagManager();
        new \Privento\Integrations\MetaPixel();
        new \Privento\Scanner\CookieScanner();
    }
}
