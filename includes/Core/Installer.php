<?php
/**
 * Plugin Installer — handles activation, deactivation, and DB schema.
 *
 * @package Privento\Core
 */

namespace Privento\Core;

defined( 'ABSPATH' ) || exit;

class Installer {

    /**
     * The Danish texts 1.3.8–1.6.x wrote into the settings at activation. The
     * da_DK translation must reproduce them exactly, or upgraded sites would
     * change wording — bin/i18n.py checks that.
     */
    public const LEGACY_DA = [
        'headline'             => 'Vi bruger cookies',
        'body_text'            => 'Vi bruger cookies til at forbedre din oplevelse på hjemmesiden, vise relevant indhold og analysere vores trafik. Vælg dine præferencer nedenfor.',
        'policy_link_text'     => 'Læs vores cookiepolitik',
        'btn_accept_all'       => 'Accepter alle',
        'btn_reject_all'       => 'Afvis alle',
        'btn_settings'         => 'Cookieindstillinger',
        'btn_save'             => 'Gem indstillinger',
        'cat_necessary_name'   => 'Nødvendige',
        'cat_necessary_desc'   => 'Disse cookies er nødvendige for, at hjemmesiden fungerer, og kan ikke slås fra.',
        'cat_preferences_name' => 'Præferencer',
        'cat_preferences_desc' => 'Disse cookies husker dine indstillinger og personlige valg.',
        'cat_statistics_name'  => 'Statistik',
        'cat_statistics_desc'  => 'Disse cookies hjælper os med at forstå, hvordan besøgende bruger hjemmesiden.',
        'cat_marketing_name'   => 'Marketing',
        'cat_marketing_desc'   => 'Disse cookies bruges til at vise personaliserede annoncer.',
    ];

    /**
     * Runs on plugin activation.
     */
    public static function activate(): void {
        self::create_tables();
        self::seed_default_options();
        self::create_cookie_policy_page();
        flush_rewrite_rules();
    }

    /**
     * Runs on plugin deactivation.
     */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    // -------------------------------------------------------------------------
    // Database
    // -------------------------------------------------------------------------

    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table           = $wpdb->prefix . PRIVENTO_LOG_TABLE;

        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id           BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            consent_id   VARCHAR(64)         NOT NULL,
            ip_hash      VARCHAR(64)         NOT NULL,
            user_agent   VARCHAR(255)        DEFAULT '',
            consented    TINYTEXT            NOT NULL,
            rejected     TINYTEXT            NOT NULL,
            version      VARCHAR(20)         NOT NULL,
            consent_date DATETIME            NOT NULL,
            expiry_date  DATETIME            NOT NULL,
            country_code VARCHAR(5)          DEFAULT '',
            PRIMARY KEY  (id),
            KEY consent_id   (consent_id),
            KEY consent_date (consent_date)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // Only on a first install. Activating over replaced files must leave
        // the stored version alone, or maybe_upgrade() would never run.
        add_option( 'privento_db_version', PRIVENTO_VERSION );
    }

    // -------------------------------------------------------------------------
    // Default options
    // -------------------------------------------------------------------------

    private static function seed_default_options(): void {
        $defaults = self::default_settings();
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }

    public static function default_settings(): array {
        return [
            'privento_settings' => [
                // Banner
                'banner_position'      => 'bottom_bar',   // bottom_bar | modal | corner | floating
                'border_radius'        => '10',
                'shadow'               => '1',

                // Colors
                'color_bg'             => '#ffffff',
                'color_text'           => '#1a1a18',
                'color_btn_accept_bg'  => '#1a6b4a',
                'color_btn_accept_txt' => '#ffffff',
                'color_btn_reject_bg'  => '#f0ede8',
                'color_btn_reject_txt' => '#1a1a18',
                'color_btn_settings_bg'  => 'transparent',
                'color_btn_settings_txt' => '#6b6b66',
                'color_btn_save_bg'      => '#f0ede8',
                'color_btn_save_txt'     => '#1a1a18',
                'color_accept_hover'     => '#1b5f8f',
                'color_accept_hover_txt' => '#ffffff',
                'color_reject_hover'     => '#e0ddd8',
                'color_reject_hover_txt' => '#1a1a18',
                'color_settings_hover'   => '#1a6b4a',
                'color_settings_hover_txt' => '#ffffff',
                'color_save_hover'       => '#e0ddd8',
                'color_save_hover_txt'   => '#1a1a18',
                'color_floating_bg'      => '#ffffff',
                'color_floating_txt'     => '#9e9e99',
                'color_floating_hover'   => '#1a6b4a',
                'color_link'             => '#1a6b4a',
                'color_revoke_link'      => '#1a6b4a',
                'color_revoke_hover'     => '#14523a',

                // Typography
                'font_family'          => 'inherit',
                'font_size_base'       => '14',

                // Text — empty means "follow the site language"; see Settings::default_texts().
                'headline'             => '',
                'body_text'            => '',
                'policy_link_text'     => '',
                'btn_accept_all'       => '',
                'btn_reject_all'       => '',
                'btn_settings'         => '',
                'btn_save'             => '',

                // Categories
                'cat_necessary_name'   => '',
                'cat_necessary_desc'   => '',
                'cat_preferences_name' => '',
                'cat_preferences_desc' => '',
                'cat_statistics_name'  => '',
                'cat_statistics_desc'  => '',
                'cat_marketing_name'   => '',
                'cat_marketing_desc'   => '',

                // Consent management
                'consent_duration_days' => 180,

                // Integrations
                'gcm_enabled'           => '0',
                'gcm_default_denied'    => '1',
                'gtm_id'                => '',
                'ga4_id'                => '',
                'meta_pixel_id'         => '',

                // Floating button
                'floating_btn_enabled'  => '1',
                'floating_btn_position' => 'bottom_left',  // bottom_left | bottom_right

                // Accessibility
                'wcag_compliant'        => '1',
                'focus_ring'            => '1',

                // Cookie Policy
                'policy_page_id'        => 0,
                'policy_link_enabled'   => '1',   // link to the policy in the banner and settings window
                'policy_last_generated' => '',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Upgrades
    // -------------------------------------------------------------------------

    /**
     * Runs on every load; does work only when the stored version is behind the
     * plugin version. Updating plugin files does not fire activate().
     *
     * @return bool Whether an upgrade ran.
     */
    public static function maybe_upgrade(): bool {
        if ( get_option( 'privento_db_version' ) === PRIVENTO_VERSION ) {
            return false;
        }

        self::blank_default_texts();
        update_option( 'privento_db_version', PRIVENTO_VERSION );
        return true;
    }

    /**
     * Up to 1.6.x the banner texts were written into the settings at activation,
     * where no translation could reach them. Blank every stored text that equals
     * what the site language shows anyway, so it follows the language from now
     * on. A text the owner edited would change on screen, and is left alone.
     *
     * One case needs more: Danish defaults on a site running in another
     * language. Up to 1.6.x everything else a visitor saw was hard-coded Danish
     * as well, so such a site was Danish throughout. It is pinned to Danish
     * (see Plugin::load_textdomain()) rather than left half translated.
     */
    private static function blank_default_texts(): void {
        $settings = get_option( 'privento_settings', [] );
        if ( ! is_array( $settings ) ) {
            return;
        }

        // "Marketing" reads the same in most languages and proves nothing.
        $danish = array_diff_key( self::LEGACY_DA, [ 'cat_marketing_name' => true ] );
        $stored = array_filter( array_intersect_key( $settings, $danish ), 'is_string' );

        $changed = false;
        if ( 'da_DK' !== get_locale() && array_intersect_assoc( $danish, $stored ) ) {
            $settings['forced_locale'] = 'da_DK';
            $changed                   = true;
            // What da_DK translates the defaults to — bin/i18n.py guarantees it.
            $defaults = self::LEGACY_DA;
        } else {
            $defaults = self::in_site_language( [ Settings::class, 'default_texts' ] );
        }

        foreach ( $defaults as $key => $text ) {
            if ( isset( $settings[ $key ] ) && '' !== $settings[ $key ] && $text === $settings[ $key ] ) {
                $settings[ $key ] = '';
                $changed          = true;
            }
        }

        if ( $changed ) {
            update_option( 'privento_settings', $settings );
            Settings::flush();
        }
    }

    /**
     * Runs $callback with translations in the site's language. The current
     * request may be an administrator's, whose profile language need not be
     * the one visitors see.
     */
    public static function in_site_language( callable $callback ): mixed {
        $switched = switch_to_locale( get_locale() );
        try {
            return $callback();
        } finally {
            if ( $switched ) {
                restore_previous_locale();
            }
        }
    }

    /** Title for a newly created policy page, in the site's language. */
    public static function policy_page_title(): string {
        // Activation runs without the plugin having booted.
        load_plugin_textdomain( 'privento', false, dirname( PRIVENTO_BASENAME ) . '/languages' );

        return self::in_site_language( static fn(): string => __( 'Cookie Policy', 'privento' ) );
    }

    // -------------------------------------------------------------------------
    // Cookie Policy page
    // -------------------------------------------------------------------------

    private static function create_cookie_policy_page(): void {
        $settings = get_option( 'privento_settings', [] );
        if ( ! empty( $settings['policy_page_id'] ) && get_post( $settings['policy_page_id'] ) ) {
            return;
        }

        $page_id = wp_insert_post( [
            'post_title'   => self::policy_page_title(),
            'post_content' => '[privento_cookie_policy]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );

        if ( ! is_wp_error( $page_id ) ) {
            $settings['policy_page_id'] = $page_id;
            update_option( 'privento_settings', $settings );
        }
    }
}
