<?php
/**
 * Meta Pixel — blocked until marketing consent is given; JS handles activation.
 *
 * @package Privento\Integrations
 */

namespace Privento\Integrations;

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

class MetaPixel {

    public function __construct() {
        $pixel_id = Settings::meta_pixel_id();
        if ( empty( $pixel_id ) ) {
            return;
        }
        // We output the pixel as a blocked script via data-privento-* attributes.
        // The JS engine re-activates it once marketing consent is granted.
        add_action( 'wp_head', [ $this, 'inject_pixel' ], 5 );
    }

    public function inject_pixel(): void {
        $pixel_id = Settings::meta_pixel_id();
        if ( empty( $pixel_id ) ) {
            return;
        }
        ?>
        <!-- Privento: Meta Pixel (blocked until marketing consent) -->
        <script type="text/plain" data-privento-blocked="1" data-privento-category="marketing" data-privento-inline="1">
        /* eslint-disable */
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window, document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?php echo esc_js( $pixel_id ); ?>');
        fbq('track', 'PageView');
        </script>
        <!-- End Privento: Meta Pixel -->
        <?php
    }
}
