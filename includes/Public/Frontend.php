<?php
/**
 * Frontend — enqueues assets, renders banner, handles script blocking.
 *
 * @package Privento\Public
 */

namespace Privento\Public;

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

class Frontend {

    public function __construct() {
        add_action( 'wp_enqueue_scripts',  [ $this, 'enqueue' ] );
        add_action( 'wp_footer',           [ $this, 'render_banner' ], 1 );
        add_action( 'wp_footer',           [ $this, 'render_floating_btn' ], 2 );

        // Block third-party scripts in <head> via output buffering
        add_action( 'template_redirect',   [ $this, 'maybe_start_buffer' ] );
        add_action( 'shutdown',            [ $this, 'maybe_end_buffer' ] );
    }

    // -------------------------------------------------------------------------
    // Assets
    // -------------------------------------------------------------------------

    public function enqueue(): void {
        // Minified builds (bin/minify.sh) unless WordPress is in script-debug mode.
        $min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'privento-public',
            PRIVENTO_URL . 'public/css/privento-public' . $min . '.css',
            [],
            PRIVENTO_VERSION
        );

        wp_enqueue_script(
            'privento-public',
            PRIVENTO_URL . 'public/js/privento-public' . $min . '.js',
            [],
            PRIVENTO_VERSION,
            // The strategy argument needs WordPress 6.3; older versions take the bool.
            version_compare( get_bloginfo( 'version' ), '6.3', '>=' )
                ? [ 'in_footer' => true, 'strategy' => 'defer' ]
                : true
        );

        wp_localize_script( 'privento-public', 'PriventoConfig', $this->get_js_config() );
    }

    private function get_js_config(): array {
        $s = Settings::all();
        return [
            'restUrl'          => esc_url_raw( rest_url( 'privento/v1' ) ),
            'cookieName'       => \Privento\Consent\Manager::COOKIE_NAME,
            'cookieVersion'    => \Privento\Consent\Manager::COOKIE_VERSION,
            'expiryDays'       => Settings::get_consent_expiry_days(),
            'categories'       => Settings::get_categories(),
            'gcmEnabled'       => Settings::is_gcm_enabled(),
            'gcmDefaultDenied' => '1' === ( $s['gcm_default_denied'] ?? '1' ),
            'gtmId'            => Settings::gtm_id(),
            'ga4Id'            => Settings::ga4_id(),
            'metaPixelId'      => Settings::meta_pixel_id(),
            'wcag'             => '1' === ( $s['wcag_compliant'] ?? '0' ),
            'focusRing'        => '1' === ( $s['focus_ring'] ?? '0' ),
            'floatingBtn'      => [
                'enabled'  => '1' === ( $s['floating_btn_enabled'] ?? '1' ),
                'position' => $s['floating_btn_position'] ?? 'bottom_left',
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Banner HTML
    // -------------------------------------------------------------------------

    public function render_banner(): void {
        $s        = Settings::all();
        $position = $s['banner_position'] ?? 'bottom_bar';
        $css      = $this->build_css_vars( $s );
        $pid      = (int) ( $s['policy_page_id'] ?? 0 );
        $policy   = '1' === $s['policy_link_enabled'] && $pid ? get_permalink( $pid ) : '';
        ?>
        <style id="privento-vars"><?php echo $css; // phpcs:ignore ?></style>
        <?php if ( '1' === ( $s['wcag_compliant'] ?? '0' ) || '1' === ( $s['focus_ring'] ?? '0' ) ) :
            $ring_size  = '1' === ( $s['focus_ring'] ?? '0' ) ? '6px' : '4px';
            $ring_style = '1' === ( $s['focus_ring'] ?? '0' ) ? 'focus' : 'focus-visible';
        ?>
        <style id="privento-wcag">
            /* Privento WCAG — injected inline to override any theme outline:none */
            #privento-banner .privento-btn:<?php echo $ring_style; ?>,
            #privento-banner .privento-btn:focus-visible,
            .privento-panel-footer .privento-btn:<?php echo $ring_style; ?>,
            .privento-panel-footer .privento-btn:focus-visible {
                outline: 3px solid #2179b6 !important;
                outline-offset: 3px !important;
                box-shadow: 0 0 0 <?php echo $ring_size; ?> rgba(33,121,182,.3) !important;
            }
            #privento-banner .privento-toggle__input:<?php echo $ring_style; ?> + .privento-toggle__label,
            #privento-banner .privento-toggle__input:focus-visible + .privento-toggle__label {
                outline: 3px solid #2179b6 !important;
                outline-offset: 2px !important;
            }
            #privento-banner .privento-category__header:focus-visible {
                outline: 2px solid #2179b6 !important;
                outline-offset: -2px !important;
            }
        </style>
        <?php endif; ?>
        <div id="privento-overlay" aria-hidden="true"></div>

        <div id="privento-banner"
             class="privento-banner privento-banner--<?php echo esc_attr( $position ); ?>"
             role="dialog"
             aria-modal="true"
             tabindex="-1"
             aria-label="<?php esc_attr_e( 'Cookie consent', 'privento' ); ?>"
             aria-hidden="true">

            <div class="privento-banner__inner">

                <!-- Left: title, text, policy link -->
                <div class="privento-banner__content">
                    <h2 class="privento-banner__title">
                        <?php echo esc_html( $s['headline'] ); ?>
                    </h2>
                    <?php // The policy link closes the paragraph, so it reads as part of the text in every layout. ?>
                    <p class="privento-banner__text">
                        <?php echo wp_kses_post( $s['body_text'] ?? '' ); ?>
                        <?php if ( $policy ) : ?>
                        <a href="<?php echo esc_url( $policy ); ?>"
                           class="privento-policy-link"
                           target="_blank"
                           rel="noopener noreferrer"><?php echo esc_html( $s['policy_link_text'] ); ?></a>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Right: stacked buttons -->
                <div class="privento-banner__actions">
                    <button type="button" class="privento-btn privento-btn--accept" id="privento-accept-all">
                        <?php echo esc_html( $s['btn_accept_all'] ); ?>
                    </button>
                    <button type="button" class="privento-btn privento-btn--reject" id="privento-reject-all">
                        <?php echo esc_html( $s['btn_reject_all'] ); ?>
                    </button>
                    <button type="button" class="privento-btn privento-btn--settings" id="privento-open-settings">
                        <?php echo esc_html( $s['btn_settings'] ); ?>
                    </button>
                </div>

                <!-- Full-width: settings panel with its own footer -->
                <div class="privento-banner__details">
                    <div class="privento-settings-panel" id="privento-settings-panel">
                        <h2 class="privento-panel-title">
                            <?php echo esc_html( $s['btn_settings'] ); ?>
                        </h2>
                        <div class="privento-panel-scroll">
                            <?php $this->render_category_toggles(); ?>
                        </div>
                        <div class="privento-panel-footer" id="privento-panel-footer">
                            <button type="button" class="privento-btn privento-btn--accept" id="privento-panel-accept">
                                <?php echo esc_html( $s['btn_accept_all'] ); ?>
                            </button>
                            <button type="button" class="privento-btn privento-btn--reject" id="privento-panel-reject">
                                <?php echo esc_html( $s['btn_reject_all'] ); ?>
                            </button>
                            <button type="button" class="privento-btn privento-btn--save" id="privento-save-prefs">
                                <?php echo esc_html( $s['btn_save'] ); ?>
                            </button>
                            <?php if ( $policy ) : ?>
                            <a href="<?php echo esc_url( $policy ); ?>"
                               class="privento-panel-policy-link" target="_blank" rel="noopener">
                                <?php echo esc_html( $s['policy_link_text'] ); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div><!-- .privento-banner__inner -->
        </div><!-- .privento-banner -->
        <?php
    }


    private function render_category_toggles(): void {
        $cats    = Settings::get_categories();
        $scanner = new \Privento\Scanner\CookieScanner();
        $by_cat  = $scanner->get_cookies_by_category();

        foreach ( $cats as $key => $cat ) :
            $id      = 'privento-cat-' . esc_attr( $key );
            $cookies = $by_cat[ $key ] ?? [];
        ?>
        <div class="privento-category" id="privento-cat-wrap-<?php echo esc_attr( $key ); ?>">

            <!-- Header row: chevron + name + toggle/badge -->
            <div class="privento-category__header"
                 role="button" tabindex="0" aria-expanded="false">

                <span class="privento-category__chevron" aria-hidden="true">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 5l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>

                <span class="privento-category__name"><?php echo esc_html( $cat['name'] ); ?></span>

                <?php if ( $cat['required'] ) : ?>
                    <span class="privento-badge privento-badge--always"><?php esc_html_e( 'Always active', 'privento' ); ?></span>
                <?php else : ?>
                    <div class="privento-toggle">
                        <input type="checkbox"
                               id="<?php echo esc_attr( $id ); ?>"
                               value="<?php echo esc_attr( $key ); ?>"
                               autocomplete="off"
                               class="privento-toggle__input">
                        <label for="<?php echo esc_attr( $id ); ?>" class="privento-toggle__label">
                            <span class="screen-reader-text"><?php echo esc_html( $cat['name'] ); ?></span>
                        </label>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Expandable body: description + cookie list -->
            <div class="privento-category__body">
                <?php if ( ! empty( $cat['desc'] ) ) : ?>
                <p class="privento-category__desc"><?php echo esc_html( $cat['desc'] ); ?></p>
                <?php endif; ?>

                <?php if ( ! empty( $cookies ) ) : ?>
                <div class="privento-cookie-list">
                    <?php foreach ( $cookies as $cookie ) : ?>
                    <div class="privento-cookie-item">
                        <div class="privento-cookie-row">
                            <span class="privento-cookie-label"><?php esc_html_e( 'Cookie', 'privento' ); ?></span>
                            <span class="privento-cookie-value"><?php echo esc_html( $cookie['name'] ); ?></span>
                        </div>
                        <div class="privento-cookie-row">
                            <span class="privento-cookie-label"><?php esc_html_e( 'Duration', 'privento' ); ?></span>
                            <span class="privento-cookie-value"><?php echo esc_html( $cookie['duration'] ?? '–' ); ?></span>
                        </div>
                        <div class="privento-cookie-row">
                            <span class="privento-cookie-label"><?php esc_html_e( 'Description', 'privento' ); ?></span>
                            <span class="privento-cookie-value"><?php echo esc_html( $cookie['description'] ?? '' ); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
        <?php
        endforeach;
    }

    public function render_floating_btn(): void {
        $s = Settings::all();
        if ( empty( $s['floating_btn_enabled'] ) || '1' !== $s['floating_btn_enabled'] ) {
            return;
        }
        $pos = $s['floating_btn_position'] ?? 'bottom_left';
        ?>
        <button type="button"
                id="privento-floating-btn"
                class="privento-floating-btn privento-floating-btn--<?php echo esc_attr( $pos ); ?>"
                aria-label="<?php esc_attr_e( 'Manage cookie preferences', 'privento' ); ?>"
                title="<?php esc_attr_e( 'Cookie Settings', 'privento' ); ?>">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" fill="currentColor"/>
                <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <?php
    }

    // -------------------------------------------------------------------------
    // CSS variables
    // -------------------------------------------------------------------------

    private function build_css_vars( array $s ): string {
        $radius  = (int) ( $s['border_radius'] ?? 10 );
        $shadow  = '1' === ( $s['shadow'] ?? '1' )
            ? '0 -4px 40px rgba(0,0,0,.12)'
            : 'none';
        $font_px = (int) ( $s['font_size_base'] ?? 14 );

        $vars = sprintf(
            ':root{' .
            '--prv-bg:%s;--prv-text:%s;--prv-subtext:%s;' .
            '--prv-accept-bg:%s;--prv-accept-txt:%s;--prv-accept-hover:%s;--prv-accept-hover-txt:%s;' .
            '--prv-reject-bg:%s;--prv-reject-txt:%s;--prv-reject-hover:%s;--prv-reject-hover-txt:%s;' .
            '--prv-settings-bg:%s;--prv-settings-txt:%s;--prv-settings-bdr:#e0dfd8;--prv-settings-hover:%s;--prv-settings-hover-txt:%s;' .
            '--prv-save-bg:%s;--prv-save-txt:%s;--prv-save-hover:%s;--prv-save-hover-txt:%s;' .
            '--prv-floating-bg:%s;--prv-floating-txt:%s;--prv-floating-hover:%s;' .
            '--prv-link:%s;' .
            '--prv-revoke-link:%s;--prv-revoke-hover:%s;' .
            '--prv-radius:%spx;--prv-shadow:%s;' .
            '--prv-font:%s;--prv-size:%spx;' .
            '}',
            esc_attr( $s['color_bg']               ?? '#ffffff' ),
            esc_attr( $s['color_text']             ?? '#1a1a18' ),
            '#6b6b66',
            esc_attr( $s['color_btn_accept_bg']    ?? '#1a6b4a' ),
            esc_attr( $s['color_btn_accept_txt']   ?? '#ffffff' ),
            esc_attr( $s['color_accept_hover']     ?? '#1b5f8f' ),
            esc_attr( $s['color_accept_hover_txt'] ?? '#ffffff' ),
            esc_attr( $s['color_btn_reject_bg']    ?? '#f0ede8' ),
            esc_attr( $s['color_btn_reject_txt']   ?? '#1a1a18' ),
            esc_attr( $s['color_reject_hover']     ?? '#e0ddd8' ),
            esc_attr( $s['color_reject_hover_txt'] ?? '#1a1a18' ),
            esc_attr( $s['color_btn_settings_bg']  ?? 'transparent' ),
            esc_attr( $s['color_btn_settings_txt'] ?? '#6b6b66' ),
            esc_attr( $s['color_settings_hover']   ?? '#1a6b4a' ),
            esc_attr( $s['color_settings_hover_txt'] ?? '#ffffff' ),
            esc_attr( $s['color_btn_save_bg']      ?? '#f0ede8' ),
            esc_attr( $s['color_btn_save_txt']     ?? '#1a1a18' ),
            esc_attr( $s['color_save_hover']       ?? '#e0ddd8' ),
            esc_attr( $s['color_save_hover_txt']   ?? '#1a1a18' ),
            esc_attr( $s['color_floating_bg']    ?? '#ffffff' ),
            esc_attr( $s['color_floating_txt']   ?? '#9e9e99' ),
            esc_attr( $s['color_floating_hover'] ?? '#1a6b4a' ),
            esc_attr( $s['color_link']           ?? '#1a6b4a' ),
            esc_attr( $s['color_revoke_link']    ?? '#1a6b4a' ),
            esc_attr( $s['color_revoke_hover']   ?? '#14523a' ),
            $radius,
            $shadow,
            esc_attr( $s['font_family']            ?? 'inherit' ),
            $font_px
        );

        return $vars;
    }


    // -------------------------------------------------------------------------
    // Script blocking via output buffer
    // -------------------------------------------------------------------------

    public function maybe_start_buffer(): void {
        if ( is_admin() ) {
            return;
        }
        ob_start( [ $this, 'block_scripts_callback' ] );
    }

    public function maybe_end_buffer(): void {
        if ( is_admin() || ! ob_get_level() ) {
            return;
        }
        ob_end_flush();
    }

    /**
     * Replace src attributes on blocked scripts with data-src so they don't load.
     * The JS layer re-enables them after consent is granted.
     */
    public function block_scripts_callback( string $html ): string {
        $blocked_patterns = $this->get_blocked_patterns();

        $alternation = implode( '|', array_map(
            static fn( string $p ): string => preg_quote( $p, '/' ),
            array_keys( $blocked_patterns )
        ) );

        // One pass per tag. The lookbehind keeps lazy-loaders' data-src="…" out:
        // those embeds are not loading anything yet.
        foreach ( [ 'script', 'iframe' ] as $tag ) {
            $result = preg_replace_callback(
                '/<' . $tag . '([^>]*)((?<![\w-])src=["\'])([^"\']*(?:' . $alternation . ')[^"\']*)["\']([^>]*)>/i',
                function ( array $m ) use ( $blocked_patterns, $tag ): string {
                    $category = 'marketing';
                    foreach ( $blocked_patterns as $pattern => $cat ) {
                        if ( false !== stripos( $m[3], $pattern ) ) {
                            $category = $cat;
                            break;
                        }
                    }
                    return sprintf(
                        '<%s%sdata-src="%s" data-privento-category="%s" data-privento-blocked="1"%s>',
                        $tag,
                        $m[1],
                        esc_attr( $m[3] ),
                        esc_attr( $category ),
                        $m[4]
                    );
                },
                $html
            );

            // PCRE returns null on backtrack-limit failures (very large pages);
            // keep the unmodified HTML rather than emitting a blank page.
            if ( null !== $result ) {
                $html = $result;
            }
        }

        return $html;
    }

    private function get_blocked_patterns(): array {
        return [
            // Statistics
            'google-analytics.com'   => 'statistics',
            'googletagmanager.com'   => 'statistics',
            'analytics.google.com'   => 'statistics',
            // Marketing
            'connect.facebook.net'   => 'marketing',
            'doubleclick.net'        => 'marketing',
            'ads.google.com'         => 'marketing',
            'hotjar.com'             => 'marketing',
            'clarity.ms'             => 'marketing',
            // Media (marketing/preferences)
            'youtube.com/embed'      => 'marketing',
            'youtube-nocookie.com'   => 'marketing',
            'vimeo.com/video'        => 'marketing',
            'maps.googleapis.com'    => 'preferences',
            'google.com/maps'        => 'preferences',
            // Preferences
            'recaptcha.net'          => 'preferences',
        ];
    }
}
