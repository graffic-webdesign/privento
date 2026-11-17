<?php
/**
 * Google Consent Mode v2 integration.
 * Injects gtag consent defaults in <head> before any GTM/GA loads.
 *
 * @package Privento\Integrations
 */

namespace Privento\Integrations;

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

class GoogleConsentMode {

    public function __construct() {
        if ( ! Settings::is_gcm_enabled() ) {
            return;
        }
        // Must fire early — before GTM
        add_action( 'wp_head', [ $this, 'inject_default_consent' ], 1 );
    }

    public function inject_default_consent(): void {
        $default_denied = Settings::get( 'gcm_default_denied', '1' ) === '1' ? 'denied' : 'granted';
        ?>
        <script>
        /* Privento – Google Consent Mode v2 Defaults */
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}

        gtag('consent', 'default', {
            'ad_storage':              '<?php echo esc_js( $default_denied ); ?>',
            'ad_user_data':            '<?php echo esc_js( $default_denied ); ?>',
            'ad_personalization':      '<?php echo esc_js( $default_denied ); ?>',
            'analytics_storage':       '<?php echo esc_js( $default_denied ); ?>',
            'functionality_storage':   'granted',
            'personalization_storage': '<?php echo esc_js( $default_denied ); ?>',
            'security_storage':        'granted',
            'wait_for_update':         500
        });

        gtag('set', 'ads_data_redaction', true);
        gtag('set', 'url_passthrough', true);
        </script>
        <?php
    }
}
