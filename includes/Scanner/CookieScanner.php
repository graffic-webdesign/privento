<?php
/**
 * Cookie Scanner — detects cookies set on the site and categorises them.
 *
 * @package Privento\Scanner
 */

namespace Privento\Scanner;

defined( 'ABSPATH' ) || exit;

class CookieScanner {

    /**
     * Known cookies → category, service, duration, description.
     *
     * A key ending in "_" or "-" is a prefix ("_ga_" matches "_ga_ABC123");
     * every other key matches that exact cookie name only.
     *
     * A method, not a constant: the texts go through the translation, so they
     * follow the language of the page being rendered.
     */
    public static function known(): array {
        $session = __( 'Session', 'privento' );
        $year    = __( '1 year', 'privento' );
        $years   = __( '2 years', 'privento' );
        $half    = __( '6 months', 'privento' );
        $quarter = __( '3 months', 'privento' );

        return [
            // Necessary
            'wordpress_logged_in_' => [ 'category' => 'necessary',   'service' => 'WordPress',         'duration' => $year,    'description' => __( 'Keeps users logged in to WordPress.', 'privento' ) ],
            'wordpress_sec_'       => [ 'category' => 'necessary',   'service' => 'WordPress',         'duration' => $session, 'description' => __( 'Security cookie for authenticated users.', 'privento' ) ],
            'wordpress_test_cookie'=> [ 'category' => 'necessary',   'service' => 'WordPress',         'duration' => $session, 'description' => __( 'Used to check whether cookies are enabled.', 'privento' ) ],
            'wp-settings-'         => [ 'category' => 'necessary',   'service' => 'WordPress',         'duration' => $year,    'description' => __( 'Stores user interface settings.', 'privento' ) ],
            'PHPSESSID'            => [ 'category' => 'necessary',   'service' => 'PHP',               'duration' => $session, 'description' => __( 'Maintains the user session state.', 'privento' ) ],
            'privento_consent'     => [ 'category' => 'necessary',   'service' => 'Privento',          'duration' => $half,    'description' => __( 'Stores your cookie consent preferences.', 'privento' ) ],
            'woocommerce_cart_hash'=> [ 'category' => 'necessary',   'service' => 'WooCommerce',       'duration' => $session, 'description' => __( 'Keeps track of the contents of your shopping cart.', 'privento' ) ],
            'woocommerce_session_' => [ 'category' => 'necessary',   'service' => 'WooCommerce',       'duration' => __( '2 days', 'privento' ), 'description' => __( 'Manages WooCommerce session data.', 'privento' ) ],
            'elementor'            => [ 'category' => 'necessary',   'service' => 'Elementor',         'duration' => __( 'Never expires', 'privento' ), 'description' => __( 'The WordPress theme of the website uses this cookie to implement or change the appearance of the content in real time.', 'privento' ) ],
            'cookieyes-consent'    => [ 'category' => 'necessary',   'service' => 'CookieYes',         'duration' => $half,    'description' => __( 'CookieYes sets this cookie to remember the consent preferences of users, so that their preferences are respected on subsequent visits.', 'privento' ) ],

            // Preferences
            'wp-wpml_current_language' => [ 'category' => 'preferences', 'service' => 'WPML',     'duration' => __( '1 day', 'privento' ), 'description' => __( 'Stores your selected language.', 'privento' ) ],
            'pll_language'             => [ 'category' => 'preferences', 'service' => 'Polylang', 'duration' => $year, 'description' => __( 'Stores your preferred language.', 'privento' ) ],

            // Statistics
            '_ga'                  => [ 'category' => 'statistics',  'service' => 'Google Analytics',  'duration' => $years,   'description' => __( 'Registers a unique ID used to generate statistical data on how visitors use the website.', 'privento' ) ],
            '_ga_'                 => [ 'category' => 'statistics',  'service' => 'Google Analytics',  'duration' => $years,   'description' => __( 'Used by Google Analytics 4 to persist session state.', 'privento' ) ],
            '_gid'                 => [ 'category' => 'statistics',  'service' => 'Google Analytics',  'duration' => __( '24 hours', 'privento' ), 'description' => __( 'Registers a unique ID for a 24-hour session.', 'privento' ) ],
            '_gat'                 => [ 'category' => 'statistics',  'service' => 'Google Analytics',  'duration' => __( '1 minute', 'privento' ), 'description' => __( 'Used to throttle the request rate to Google Analytics.', 'privento' ) ],
            '_gat_'                => [ 'category' => 'statistics',  'service' => 'Google Analytics',  'duration' => __( '1 minute', 'privento' ), 'description' => __( 'Used to throttle the request rate to Google Analytics.', 'privento' ) ],
            '_gac_'                => [ 'category' => 'statistics',  'service' => 'Google Ads',        'duration' => __( '90 days', 'privento' ), 'description' => __( 'Contains campaign-related information.', 'privento' ) ],
            '_hjSessionUser_'      => [ 'category' => 'statistics',  'service' => 'Hotjar',            'duration' => $year,    'description' => __( 'Used by Hotjar to track the user session.', 'privento' ) ],
            '_hjSession_'          => [ 'category' => 'statistics',  'service' => 'Hotjar',            'duration' => __( '30 minutes', 'privento' ), 'description' => __( 'Hotjar session cookie.', 'privento' ) ],
            'CLID'                 => [ 'category' => 'statistics',  'service' => 'Microsoft Clarity', 'duration' => $year,    'description' => __( 'Stores session data for Microsoft Clarity.', 'privento' ) ],

            // Marketing
            '_fbp'                 => [ 'category' => 'marketing',   'service' => 'Meta (Facebook)',   'duration' => $quarter, 'description' => __( 'Used by Facebook to deliver ads.', 'privento' ) ],
            '_fbc'                 => [ 'category' => 'marketing',   'service' => 'Meta (Facebook)',   'duration' => $quarter, 'description' => __( 'Stores the time of the latest visit from a Facebook ad click.', 'privento' ) ],
            'fr'                   => [ 'category' => 'marketing',   'service' => 'Meta (Facebook)',   'duration' => $quarter, 'description' => __( 'Used by Facebook for targeted advertising.', 'privento' ) ],
            'IDE'                  => [ 'category' => 'marketing',   'service' => 'Google Ads',        'duration' => $year,    'description' => __( 'Google DoubleClick advertising cookie.', 'privento' ) ],
            'test_cookie'          => [ 'category' => 'marketing',   'service' => 'Google Ads',        'duration' => $session, 'description' => __( 'Used by DoubleClick to check cookie support.', 'privento' ) ],
            '__Secure-1PSID'       => [ 'category' => 'marketing',   'service' => 'Google',            'duration' => $years,   'description' => __( 'Used to target advertising.', 'privento' ) ],
            'VISITOR_INFO1_LIVE'   => [ 'category' => 'marketing',   'service' => 'YouTube',           'duration' => $half,    'description' => __( 'YouTube video statistics and advertising.', 'privento' ) ],
            'YSC'                  => [ 'category' => 'marketing',   'service' => 'YouTube',           'duration' => $session, 'description' => __( 'Registers a unique ID to keep statistics on videos.', 'privento' ) ],
            'vuid'                 => [ 'category' => 'marketing',   'service' => 'Vimeo',             'duration' => $years,   'description' => __( 'Vimeo user tracking cookie.', 'privento' ) ],
        ];
    }

    public function __construct() {
        add_action( 'wp_ajax_privento_scan_cookies', [ $this, 'ajax_scan' ] );
    }

    // -------------------------------------------------------------------------
    // AJAX handler
    // -------------------------------------------------------------------------

    public function ajax_scan(): void {
        check_ajax_referer( 'privento_admin', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'privento' ) );
        }

        $cookies = $this->scan();
        update_option( 'privento_scanned_cookies', $cookies );
        update_option( 'privento_last_scan', current_time( 'mysql' ) );

        wp_send_json_success( [
            'cookies' => $cookies,
            'count'   => count( $cookies ),
            'scanned' => current_time( 'mysql' ),
        ] );
    }

    // -------------------------------------------------------------------------
    // Scan
    // -------------------------------------------------------------------------

    public function scan(): array {
        // Later sources win on the same cookie name.
        $keyed = [];
        foreach ( array_merge( $this->scan_active_plugins(), $this->scan_request_cookies() ) as $cookie ) {
            $keyed[ $cookie['name'] ] = $cookie;
        }

        return array_values( $keyed );
    }

    private function scan_active_plugins(): array {
        $cookies = [];
        $known   = self::known();

        $plugin_cookie_map = [
            'google-analytics-for-wordpress/googleanalytics.php'    => [ '_ga', '_gid', '_gat', '_gac_' ],
            'woocommerce/woocommerce.php'                           => [ 'woocommerce_cart_hash', 'woocommerce_session_' ],
            'facebook-for-woocommerce/facebook-for-woocommerce.php' => [ '_fbp', '_fbc' ],
            'wpml-multilingual-cms/sitepress.php'                   => [ 'wp-wpml_current_language' ],
            'polylang/polylang.php'                                 => [ 'pll_language' ],
            'hotjar/hotjar.php'                                     => [ '_hjSessionUser_', '_hjSession_' ],
        ];

        foreach ( (array) get_option( 'active_plugins', [] ) as $plugin ) {
            foreach ( $plugin_cookie_map[ $plugin ] ?? [] as $key ) {
                $cookies[] = array_merge(
                    [ 'name' => $key, 'key' => $key, 'source' => 'plugin:' . $plugin ],
                    $known[ $key ]
                );
            }
        }

        return $cookies;
    }

    /** Cookies the scanning administrator's own browser sent along. */
    private function scan_request_cookies(): array {
        $cookies = [];
        $known   = self::known();

        foreach ( array_keys( $_COOKIE ) as $raw_name ) {
            $name = sanitize_text_field( (string) $raw_name );
            $key  = self::match_key( $name );
            if ( null !== $key ) {
                $cookies[] = array_merge(
                    [ 'name' => $name, 'key' => $key, 'source' => 'browser' ],
                    $known[ $key ]
                );
            }
        }

        return $cookies;
    }

    /** The catalogue key for a cookie name: exact first, then the longest prefix. */
    private static function match_key( string $name ): ?string {
        $known = self::known();
        if ( isset( $known[ $name ] ) ) {
            return $name;
        }

        $best = null;
        foreach ( array_keys( $known ) as $key ) {
            $is_prefix = str_ends_with( $key, '_' ) || str_ends_with( $key, '-' );
            if ( $is_prefix && str_starts_with( $name, $key ) && strlen( $key ) > strlen( (string) $best ) ) {
                $best = $key;
            }
        }

        return $best;
    }

    // -------------------------------------------------------------------------
    // Public helpers
    // -------------------------------------------------------------------------

    /**
     * Refreshes a stored cookie's service, duration and description from the
     * catalogue, so they read in the current language rather than the one the
     * scan happened to run in. Cookies the catalogue does not know pass through.
     */
    public function describe( array $cookie ): array {
        $known = self::known();
        $key   = $cookie['key'] ?? null;
        if ( null === $key || ! isset( $known[ $key ] ) ) {
            $key = self::match_key( (string) ( $cookie['name'] ?? '' ) );
        }
        if ( null === $key ) {
            return $cookie;
        }

        $entry = $known[ $key ];
        unset( $entry['category'] ); // The stored category stands.

        return array_merge( $cookie, $entry );
    }

    public function get_cached_cookies(): array {
        return array_map( [ $this, 'describe' ], (array) get_option( 'privento_scanned_cookies', [] ) );
    }

    public function get_last_scan(): string {
        return (string) get_option( 'privento_last_scan', '' );
    }

    public function get_cookies_by_category(): array {
        $grouped = [ 'necessary' => [], 'preferences' => [], 'statistics' => [], 'marketing' => [] ];
        foreach ( $this->get_cached_cookies() as $cookie ) {
            $cat = $cookie['category'] ?? 'necessary';
            $grouped[ $cat ][] = $cookie;
        }
        return $grouped;
    }
}
