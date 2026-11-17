<?php
/**
 * Test bootstrap — WordPress function stubs, so the plugin's classes can be
 * exercised without a WordPress install. Run with: php tests/run.php
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'PRIVENTO_VERSION', '9.9.9' );
define( 'PRIVENTO_DIR', dirname( __DIR__ ) . '/' );
define( 'PRIVENTO_URL', 'https://example.test/wp-content/plugins/privento/' );
define( 'PRIVENTO_BASENAME', 'privento/privento.php' );
define( 'PRIVENTO_LOG_TABLE', 'privento_consent_log' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MINUTE_IN_SECONDS', 60 );

spl_autoload_register( function ( string $class ): void {
    if ( 0 === strncmp( 'Privento\\', $class, 9 ) ) {
        require_once PRIVENTO_DIR . 'includes/' . str_replace( '\\', '/', substr( $class, 9 ) ) . '.php';
    }
} );

// ---------------------------------------------------------------------------
// Test helpers
// ---------------------------------------------------------------------------

$GLOBALS['t_failed'] = 0;

function t_assert( bool $cond, string $name ): void {
    echo ( $cond ? '  ok   ' : '  FAIL ' ) . $name . "\n";
    if ( ! $cond ) {
        $GLOBALS['t_failed']++;
    }
}

/** Fresh WordPress state between tests. */
function t_reset(): void {
    $GLOBALS['wp_options']      = [];
    $GLOBALS['wp_transients']   = [];
    $GLOBALS['wp_translations'] = [];
    $GLOBALS['wp_filters']      = [];
    $GLOBALS['wp_http']         = null; // callable( string $url ): array|WP_Error
    $GLOBALS['wp_http_calls']   = 0;
    $GLOBALS['wp_locale']       = 'da_DK';
    $_GET                       = [];
    \Privento\Core\Settings::flush();
}

class WP_Error {
    public function __construct( public string $code = '', public string $message = '' ) {}
}

// ---------------------------------------------------------------------------
// WordPress stubs
// ---------------------------------------------------------------------------

function add_action( $hook, $cb, $prio = 10, $args = 1 ) { $GLOBALS['wp_filters'][ $hook ][] = $cb; return true; }
function add_filter( $hook, $cb, $prio = 10, $args = 1 ) { $GLOBALS['wp_filters'][ $hook ][] = $cb; return true; }
function add_shortcode( $tag, $cb ) {}
function is_admin() { return false; }

function __( $text, $domain = 'default' ) { return $GLOBALS['wp_translations'][ $text ] ?? $text; }
function esc_html__( $text, $domain = 'default' ) { return esc_html( __( $text, $domain ) ); }
function esc_attr__( $text, $domain = 'default' ) { return esc_attr( __( $text, $domain ) ); }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }

function get_option( $key, $default = false ) { return $GLOBALS['wp_options'][ $key ] ?? $default; }
function update_option( $key, $value ) { $GLOBALS['wp_options'][ $key ] = $value; return true; }
function add_option( $key, $value ) { $GLOBALS['wp_options'][ $key ] ??= $value; return true; }
function delete_option( $key ) { unset( $GLOBALS['wp_options'][ $key ] ); return true; }

function get_site_transient( $key ) { return $GLOBALS['wp_transients'][ $key ]['value'] ?? false; }
function set_site_transient( $key, $value, $ttl = 0 ) { $GLOBALS['wp_transients'][ $key ] = [ 'value' => $value, 'ttl' => $ttl ]; return true; }

function sanitize_text_field( $s ) { return trim( strip_tags( (string) $s ) ); }
function sanitize_key( $s ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $s ) ); }
function sanitize_hex_color( $s ) { return preg_match( '/^#([0-9a-f]{3}){1,2}$/i', (string) $s ) ? $s : null; }
function wp_kses_post( $s ) { return (string) $s; }
function absint( $n ) { return abs( (int) $n ); }
function wp_unslash( $v ) { return $v; }

function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function wp_remote_get( $url, $args = [] ) { $GLOBALS['wp_http_calls']++; return ( $GLOBALS['wp_http'] )( $url, $args ); }
function wp_remote_retrieve_response_code( $r ) { return is_array( $r ) ? ( $r['response']['code'] ?? 0 ) : 0; }
function wp_remote_retrieve_body( $r ) { return is_array( $r ) ? ( $r['body'] ?? '' ) : ''; }

// Locale switching: $GLOBALS['wp_site_translations'] holds the site language's
// catalogue; wp_translations is whatever the current user sees.
function get_locale() { return $GLOBALS['wp_locale'] ?? 'da_DK'; }
function switch_to_locale( $locale ) {
    $GLOBALS['wp_locale_stack'][] = $GLOBALS['wp_translations'];
    $GLOBALS['wp_translations']   = $GLOBALS['wp_site_translations'] ?? $GLOBALS['wp_translations'];
    return true;
}
function restore_previous_locale() {
    $GLOBALS['wp_translations'] = array_pop( $GLOBALS['wp_locale_stack'] );
    return true;
}
function wpautop( $t ) { return "<p>" . $t . "</p>"; }
function delete_site_transient( $key ) { unset( $GLOBALS["wp_transients"][ $key ] ); return true; }
function load_plugin_textdomain( $domain, $deprecated = false, $path = '' ) { return true; }
function current_user_can( $cap ) { return true; }
function dbDelta( $sql ) {}
function get_permalink( $id ) { return 'https://example.test/policy/'; }
function esc_url( $url ) { return $url; }
function esc_html_e( $text, $domain = 'default' ) { echo esc_html( __( $text, $domain ) ); }
function esc_attr_e( $text, $domain = 'default' ) { echo esc_attr( __( $text, $domain ) ); }
function get_bloginfo( $key ) { return $GLOBALS['wp_version'] ?? '6.7'; }

/** Calls a private method. A bound closure works on every PHP version; ReflectionMethod needs setAccessible() before 8.1. */
function t_call_private( object $object, string $method, ...$args ) {
    return ( fn() => $this->$method( ...$args ) )->call( $object );
}
function rest_url( $path = '' ) { return 'https://example.test/wp-json/' . $path; }
function esc_url_raw( $url ) { return $url; }
function wp_salt( $scheme = 'auth' ) { return 'test-salt-' . $scheme; }
function get_transient( $key ) { return $GLOBALS['wp_transients'][ $key ]['value'] ?? false; }
function set_transient( $key, $value, $ttl = 0 ) { $GLOBALS['wp_transients'][ $key ] = [ 'value' => $value, 'ttl' => $ttl ]; return true; }
function apply_filters( $hook, $value, ...$args ) { foreach ( $GLOBALS['wp_filters'][ $hook ] ?? [] as $cb ) { $value = $cb( $value, ...$args ); } return $value; }

// Cron: $GLOBALS['wp_cron'] maps hook => recurrence.
function wp_next_scheduled( $hook ) { return isset( $GLOBALS['wp_cron'][ $hook ] ) ? 1 : false; }
function wp_schedule_event( $time, $recurrence, $hook ) { $GLOBALS['wp_cron'][ $hook ] = $recurrence; return true; }
function wp_clear_scheduled_hook( $hook ) { unset( $GLOBALS['wp_cron'][ $hook ] ); return 1; }

/** A $wpdb that records queries and answers query() from a scripted list of affected-row counts. */
function t_wpdb( array $affected = [] ) {
    return new class( $affected ) {
        public $prefix  = 'wp_';
        public $queries = [];
        private $affected;
        public function __construct( array $affected ) { $this->affected = $affected; }
        public function prepare( $sql, ...$args ) {
            return vsprintf( str_replace( '%s', "'%s'", $sql ), $args );
        }
        public function query( $sql ) {
            $this->queries[] = $sql;
            return count( $this->affected ) > 1 ? array_shift( $this->affected ) : ( $this->affected[0] ?? 0 );
        }
    };
}
