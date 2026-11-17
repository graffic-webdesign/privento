<?php
/**
 * Admin — registers menus, assets, and orchestrates admin UI.
 *
 * @package Privento\Admin
 */

namespace Privento\Admin;

defined( 'ABSPATH' ) || exit;

class Admin {

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'plugin_action_links_' . PRIVENTO_BASENAME, [ $this, 'plugin_action_links' ] );
    }

    // -------------------------------------------------------------------------
    // Menus
    // -------------------------------------------------------------------------

    public function register_menus(): void {
        add_submenu_page(
            'options-general.php',
            __( 'Privento', 'privento' ),
            __( 'Privento', 'privento' ),
            'manage_options',
            'privento',
            [ $this, 'render_main_page' ]
        );

        // The plugin has its own tab navigation, so the remaining pages are
        // registered — otherwise WordPress rejects them — and then hidden from
        // the Settings menu again.
        $subpages = [
            'privento-design'  => __( 'Design', 'privento' ),
            'privento-texts'   => __( 'Texts', 'privento' ),
            'privento-cookies' => __( 'Cookies & Scanner', 'privento' ),
            'privento-integrations' => __( 'Integrations', 'privento' ),
            'privento-policy'  => __( 'Cookie Policy', 'privento' ),
            'privento-logs'    => __( 'Consent Logs', 'privento' ),
            'privento-settings'=> __( 'Settings', 'privento' ),
        ];

        foreach ( $subpages as $slug => $label ) {
            add_submenu_page(
                'options-general.php',
                $label . ' — Privento',
                $label,
                'manage_options',
                $slug,
                [ $this, 'render_main_page' ]
            );
            remove_submenu_page( 'options-general.php', $slug );
        }
    }

    // -------------------------------------------------------------------------
    // Page renderer (tab router)
    // -------------------------------------------------------------------------

    public function render_main_page(): void {
        $page = sanitize_key( $_GET['page'] ?? 'privento' );

        $tab_map = [
            'privento'              => 'dashboard',
            'privento-design'       => 'design',
            'privento-texts'        => 'texts',
            'privento-cookies'      => 'cookies',
            'privento-integrations' => 'integrations',
            'privento-policy'       => 'policy',
            'privento-logs'         => 'logs',
            'privento-settings'     => 'settings',
        ];

        $tab  = $tab_map[ $page ] ?? 'dashboard';
        $file = PRIVENTO_DIR . 'admin/views/tabs/' . $tab . '.php';

        include PRIVENTO_DIR . 'admin/views/layout.php';
    }

    // -------------------------------------------------------------------------
    // Assets
    // -------------------------------------------------------------------------

    public function enqueue_assets( string $hook ): void {
        if ( false === strpos( $hook, 'privento' ) ) {
            return;
        }

        wp_enqueue_style(
            'privento-admin',
            PRIVENTO_URL . 'admin/css/privento-admin.css',
            [],
            PRIVENTO_VERSION
        );

        wp_enqueue_script(
            'privento-admin',
            PRIVENTO_URL . 'admin/js/privento-admin.js',
            [ 'jquery' ],
            PRIVENTO_VERSION,
            true
        );

        wp_localize_script( 'privento-admin', 'PriventoAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'privento_admin' ),
            'i18n'    => [
                'saving'        => __( 'Saving…', 'privento' ),
                'saved'         => __( 'Settings saved!', 'privento' ),
                'scanning'      => __( 'Scanning cookies…', 'privento' ),
                'scanComplete'  => __( 'Scan complete.', 'privento' ),
                'error'         => __( 'Something went wrong. Please try again.', 'privento' ),
                'confirmExport' => __( 'Export all consent logs as CSV?', 'privento' ),
            ],
        ] );
    }

    // -------------------------------------------------------------------------
    // Plugin action links
    // -------------------------------------------------------------------------

    public function plugin_action_links( array $links ): array {
        $custom = [
            '<a href="' . esc_url( menu_page_url( 'privento', false ) ) . '">' . __( 'Dashboard', 'privento' ) . '</a>',
            '<a href="' . esc_url( menu_page_url( 'privento-design', false ) ) . '">' . __( 'Design', 'privento' ) . '</a>',
        ];
        return array_merge( $custom, $links );
    }

}
