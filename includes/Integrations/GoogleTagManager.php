<?php
/**
 * Google Tag Manager integration — loads GTM only after consent or in GCM mode.
 *
 * @package Privento\Integrations
 */

namespace Privento\Integrations;

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

class GoogleTagManager {

    public function __construct() {
        $gtm_id = Settings::gtm_id();
        if ( empty( $gtm_id ) ) {
            return;
        }

        add_action( 'wp_head',   [ $this, 'inject_head_snippet' ], 2 );
        add_action( 'wp_body_open', [ $this, 'inject_noscript' ],  1 );
    }

    public function inject_head_snippet(): void {
        $gtm_id = Settings::gtm_id();
        if ( empty( $gtm_id ) ) {
            return;
        }
        // With Consent Mode enabled, GTM may load immediately — the default-denied
        // signals gate the tags. Without it, block the loader until Statistics consent.
        $blocked_attrs = Settings::is_gcm_enabled()
            ? ''
            : ' type="text/plain" data-privento-blocked="1" data-privento-category="statistics"';
        ?>
        <!-- Privento: Google Tag Manager -->
        <script<?php echo $blocked_attrs; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
        (function(w,d,s,l,i){
            w[l]=w[l]||[];
            w[l].push({'gtm.start': new Date().getTime(), event:'gtm.js'});
            var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
            j.async=true;
            j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
            f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','<?php echo esc_js( $gtm_id ); ?>');
        </script>
        <!-- End Privento: Google Tag Manager -->
        <?php
    }

    public function inject_noscript(): void {
        $gtm_id = Settings::gtm_id();
        if ( empty( $gtm_id ) ) {
            return;
        }
        ?>
        <!-- Privento: Google Tag Manager (noscript) -->
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
                    height="0" width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
        <!-- End Privento: Google Tag Manager (noscript) -->
        <?php
    }
}
