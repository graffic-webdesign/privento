<?php
/**
 * Consent Manager — reads, validates, and exposes consent state.
 *
 * @package Privento\Consent
 */

namespace Privento\Consent;

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

class Manager {

    public const COOKIE_NAME    = 'privento_consent';
    public const COOKIE_VERSION = '1';

    /** Date the [privento_revoke] link last rendered, as Y-m-d. */
    public const REVOKE_SEEN_OPTION = 'privento_revoke_link_seen';

    /** How long that sighting counts for on the Dashboard. */
    public const REVOKE_SEEN_DAYS = 30;

    /** Caches the content scan so the Dashboard does not re-run it on every view. */
    private const REVOKE_SCAN_TRANSIENT = 'privento_revoke_link_found';

    /**
     * True when visitors have a [privento_revoke] link.
     *
     * Two signals, because neither alone is enough. The shortcode records its own
     * renders, which catches links in theme templates, widgets and menus that no
     * query would find — but only once a visitor has loaded such a page, so it is
     * blind on a fresh install and behind a full-page cache. The content scan
     * covers that gap by looking where the markup is actually stored.
     */
    public static function revoke_link_in_use(): bool {
        return self::revoke_link_seen() || self::revoke_link_in_content();
    }

    private static function revoke_link_seen(): bool {
        $seen = (string) get_option( self::REVOKE_SEEN_OPTION, '' );
        if ( ! $seen ) {
            return false;
        }

        return strtotime( $seen ) > time() - ( self::REVOKE_SEEN_DAYS * DAY_IN_SECONDS );
    }

    private static function revoke_link_in_content(): bool {
        $cached = get_transient( self::REVOKE_SCAN_TRANSIENT );
        if ( false !== $cached ) {
            return '1' === $cached;
        }

        global $wpdb;

        // No closing bracket, so [privento_revoke label="…"] matches too.
        $like = '%' . $wpdb->esc_like( '[privento_revoke' ) . '%';

        $found = (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_status = 'publish' AND post_content LIKE %s
                 LIMIT 1",
                $like
            )
        );

        if ( ! $found ) {
            // Page builders keep their markup in postmeta, not post_content.
            $found = (bool) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_id FROM {$wpdb->postmeta}
                     WHERE meta_key IN ( '_elementor_data', '_elementor_page_settings', 'panels_data' )
                       AND meta_value LIKE %s
                     LIMIT 1",
                    $like
                )
            );
        }

        set_transient( self::REVOKE_SCAN_TRANSIENT, $found ? '1' : '0', HOUR_IN_SECONDS );

        return $found;
    }

    public function __construct() {
        // REST endpoint for saving consent from JS
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

        // Shortcode for revoke link
        add_shortcode( 'privento_revoke', [ $this, 'revoke_shortcode' ] );

        // Cookie policy shortcode
        add_shortcode( 'privento_cookie_policy', [ $this, 'cookie_policy_shortcode' ] );
    }

    // -------------------------------------------------------------------------
    // REST API
    // -------------------------------------------------------------------------

    public function register_rest_routes(): void {
        register_rest_route( 'privento/v1', '/consent', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_consent_submission' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'consented' => [ 'type' => 'array', 'default' => [] ],
                'rejected'  => [ 'type' => 'array', 'default' => [] ],
            ],
        ] );

    }

    public function handle_consent_submission( \WP_REST_Request $request ): \WP_REST_Response {
        if ( $this->is_rate_limited() ) {
            return new \WP_REST_Response( [ 'success' => false ], 429 );
        }

        $valid       = array_keys( Settings::get_categories() );
        $consented   = array_values( array_intersect( array_map( 'sanitize_key', (array) $request->get_param( 'consented' ) ), $valid ) );
        $rejected    = array_values( array_intersect( array_map( 'sanitize_key', (array) $request->get_param( 'rejected' ) ), $valid ) );
        $consent_id  = $this->generate_consent_id();
        $expiry_days = Settings::get_consent_expiry_days();

        // Log to DB
        ( new Logger() )->record( $consent_id, $consented, $rejected );

        return new \WP_REST_Response( [
            'success'    => true,
            'consent_id' => $consent_id,
            'expires_in' => $expiry_days,
        ], 200 );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function generate_consent_id(): string {
        return wp_generate_uuid4();
    }

    /**
     * Allow at most 10 consent submissions per IP per minute to prevent
     * anonymous flooding of the consent log.
     */
    private function is_rate_limited(): bool {
        $ip    = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
        $key   = 'privento_rl_' . hash( 'sha256', $ip . AUTH_SALT );
        $count = (int) get_transient( $key );
        if ( $count >= 10 ) {
            return true;
        }
        set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
        return false;
    }

    public static function parse_cookie(): array {
        if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
            return [];
        }
        $raw = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
        $data = json_decode( base64_decode( $raw ), true );
        return is_array( $data ) ? $data : [];
    }

    public static function has_consent( string $category ): bool {
        $data = self::parse_cookie();
        return in_array( $category, (array) ( $data['consented'] ?? [] ), true );
    }

    // -------------------------------------------------------------------------
    // Shortcodes
    // -------------------------------------------------------------------------

    public function revoke_shortcode( array $atts ): string {
        $atts = shortcode_atts( [
            'label' => __( 'Cookie settings', 'privento' ),
            'class' => '',
        ], $atts );

        $this->remember_revoke_link();

        return sprintf(
            '<a href="#" class="privento-revoke-link %s" role="button" aria-haspopup="dialog">%s</a>',
            esc_attr( $atts['class'] ),
            esc_html( $atts['label'] )
        );
    }

    /**
     * Notes the date the revoke link last rendered on the front end.
     *
     * The Dashboard needs to know whether visitors have a way to withdraw
     * consent. Searching post content for the shortcode would miss page
     * builders, widgets, menus and theme templates, so the shortcode reports
     * itself instead — the stamp is proof the link actually reached a visitor.
     * At most one write per day, and it goes stale on its own once the
     * shortcode is removed.
     */
    private function remember_revoke_link(): void {
        if ( is_admin() ) {
            return; // Editor previews are not evidence of a live link.
        }

        $today = current_time( 'Y-m-d' );
        if ( get_option( self::REVOKE_SEEN_OPTION ) === $today ) {
            return;
        }

        update_option( self::REVOKE_SEEN_OPTION, $today );
    }

    public function cookie_policy_shortcode( array $atts ): string {
        ob_start();
        include PRIVENTO_DIR . 'templates/cookie-policy.php';
        return ob_get_clean();
    }
}
