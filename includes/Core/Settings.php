<?php
/**
 * Settings helper — central, cached option access.
 *
 * @package Privento\Core
 */

namespace Privento\Core;

defined( 'ABSPATH' ) || exit;

class Settings {

    /**
     * Visitor-facing texts. Stored empty, they follow the site language; a
     * value the site owner typed is shown as typed.
     */
    public const TEXT_KEYS = [
        'headline', 'body_text', 'policy_link_text',
        'btn_accept_all', 'btn_reject_all', 'btn_settings', 'btn_save',
        'cat_necessary_name', 'cat_necessary_desc',
        'cat_preferences_name', 'cat_preferences_desc',
        'cat_statistics_name', 'cat_statistics_desc',
        'cat_marketing_name', 'cat_marketing_desc',
    ];

    private static ?array $cache = null;

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public static function get( string $key, mixed $fallback = null ): mixed {
        $all = self::all();
        return $all[ $key ] ?? $fallback;
    }

    public static function all(): array {
        if ( null === self::$cache ) {
            $stored      = get_option( 'privento_settings', [] );
            $defaults    = \Privento\Core\Installer::default_settings()['privento_settings'];
            $all         = array_merge( $defaults, (array) $stored );
            $texts       = self::default_texts();
            foreach ( self::TEXT_KEYS as $key ) {
                if ( '' === (string) $all[ $key ] ) {
                    $all[ $key ] = $texts[ $key ];
                }
            }
            self::$cache = $all;
        }
        return self::$cache;
    }

    /** The value as saved — '' when the text follows the site language. */
    public static function stored( string $key ): string {
        $stored = (array) get_option( 'privento_settings', [] );
        return (string) ( $stored[ $key ] ?? '' );
    }

    /**
     * The texts in the current language.
     *
     * @return array<string, string> Keyed by TEXT_KEYS.
     */
    public static function default_texts(): array {
        return [
            'headline'             => __( 'We use cookies', 'privento' ),
            'body_text'            => __( 'We use cookies to improve your experience on the website, show relevant content and analyse our traffic. Choose your preferences below.', 'privento' ),
            'policy_link_text'     => __( 'Read our cookie policy', 'privento' ),
            'btn_accept_all'       => __( 'Accept all', 'privento' ),
            'btn_reject_all'       => __( 'Reject all', 'privento' ),
            'btn_settings'         => __( 'Cookie settings', 'privento' ),
            'btn_save'             => __( 'Save settings', 'privento' ),
            'cat_necessary_name'   => __( 'Necessary', 'privento' ),
            'cat_necessary_desc'   => __( 'These cookies are required for the website to work and cannot be switched off.', 'privento' ),
            'cat_preferences_name' => __( 'Preferences', 'privento' ),
            'cat_preferences_desc' => __( 'These cookies remember your settings and personal choices.', 'privento' ),
            'cat_statistics_name'  => __( 'Statistics', 'privento' ),
            'cat_statistics_desc'  => __( 'These cookies help us understand how visitors use the website.', 'privento' ),
            'cat_marketing_name'   => __( 'Marketing', 'privento' ),
            'cat_marketing_desc'   => __( 'These cookies are used to show personalised ads.', 'privento' ),
        ];
    }

    /** Drops the per-request cache, e.g. after the locale switches. */
    public static function flush(): void {
        self::$cache = null;
    }

    public static function update( array $data ): bool {
        self::$cache = null; // Bust cache
        return update_option( 'privento_settings', $data );
    }

    public static function merge_update( array $partial ): bool {
        // Merge into what is stored, not into all(): that holds the resolved
        // texts, and writing those back would freeze the banner in the language
        // of whoever happened to save.
        $merged      = array_merge( (array) get_option( 'privento_settings', [] ), $partial );
        self::$cache = null;
        return update_option( 'privento_settings', $merged );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public static function get_categories(): array {
        $s = self::all();
        return [
            'necessary'   => [
                'name'     => $s['cat_necessary_name'],
                'desc'     => $s['cat_necessary_desc'],
                'required' => true,
            ],
            'preferences' => [
                'name'     => $s['cat_preferences_name'],
                'desc'     => $s['cat_preferences_desc'],
                'required' => false,
            ],
            'statistics'  => [
                'name'     => $s['cat_statistics_name'],
                'desc'     => $s['cat_statistics_desc'],
                'required' => false,
            ],
            'marketing'   => [
                'name'     => $s['cat_marketing_name'],
                'desc'     => $s['cat_marketing_desc'],
                'required' => false,
            ],
        ];
    }

    public static function get_consent_expiry_days(): int {
        return (int) self::get( 'consent_duration_days', 180 );
    }

    public static function is_gcm_enabled(): bool {
        return '1' === self::get( 'gcm_enabled', '0' );
    }

    public static function gtm_id(): string {
        return sanitize_text_field( self::get( 'gtm_id', '' ) );
    }

    public static function ga4_id(): string {
        return sanitize_text_field( self::get( 'ga4_id', '' ) );
    }

    public static function meta_pixel_id(): string {
        return sanitize_text_field( self::get( 'meta_pixel_id', '' ) );
    }
}
