<?php
/**
 * Integrations tab — GTM, GA4, GCM v2, Meta Pixel.
 *
 * @package Privento
 */

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

$s = Settings::all();
?>
<div class="prv-page">
    <div class="prv-page__header">
        <div>
            <h1 class="prv-page__title"><?php esc_html_e( 'Integrations', 'privento' ); ?></h1>
            <p class="prv-page__subtitle"><?php esc_html_e( 'Connect analytics, tag managers, and advertising platforms', 'privento' ); ?></p>
        </div>
        <button type="button" class="prv-btn prv-btn--primary" id="prv-save-integrations">
            <?php esc_html_e( 'Save Changes', 'privento' ); ?>
        </button>
    </div>

    <form id="prv-integrations-form" class="prv-form">

        <!-- Google Consent Mode v2 -->
        <div class="prv-panel prv-panel--featured">
            <div class="prv-panel__header">
                <h2 class="prv-panel__title">
                    <?php esc_html_e( 'Google Consent Mode v2', 'privento' ); ?>
                </h2>
                <span class="prv-badge prv-badge--required"><?php esc_html_e( 'Recommended', 'privento' ); ?></span>
            </div>
            <div class="prv-panel__body">
                <p class="prv-panel__desc">
                    <?php esc_html_e( 'Google Consent Mode v2 is required for every site that uses Google advertising or Analytics products and must comply with EU rules. It passes the consent choices of your visitors directly to Google.', 'privento' ); ?>
                </p>
                <div class="prv-field prv-field--inline">
                    <label class="prv-label prv-label--strong"><?php esc_html_e( 'Enable Google Consent Mode v2', 'privento' ); ?></label>
                    <label class="prv-toggle-field">
                        <input type="checkbox" name="gcm_enabled" value="1" <?php checked( $s['gcm_enabled'] ?? '0', '1' ); ?>>
                        <span class="prv-toggle-track"></span>
                    </label>
                </div>
                <div class="prv-field prv-field--inline">
                    <label class="prv-label"><?php esc_html_e( 'Default all signals to "Denied" before consent', 'privento' ); ?></label>
                    <label class="prv-toggle-field">
                        <input type="checkbox" name="gcm_default_denied" value="1" <?php checked( $s['gcm_default_denied'] ?? '1', '1' ); ?>>
                        <span class="prv-toggle-track"></span>
                    </label>
                </div>
                <div class="prv-gcm-signals">
                    <div class="prv-signal prv-signal--info">
                        <span class="prv-signal__key">analytics_storage</span>
                        <span class="prv-signal__maps"><?php esc_html_e( '→ Statistics consent', 'privento' ); ?></span>
                    </div>
                    <div class="prv-signal prv-signal--info">
                        <span class="prv-signal__key">ad_storage</span>
                        <span class="prv-signal__maps"><?php esc_html_e( '→ Marketing consent', 'privento' ); ?></span>
                    </div>
                    <div class="prv-signal prv-signal--info">
                        <span class="prv-signal__key">ad_user_data</span>
                        <span class="prv-signal__maps"><?php esc_html_e( '→ Marketing consent', 'privento' ); ?></span>
                    </div>
                    <div class="prv-signal prv-signal--info">
                        <span class="prv-signal__key">ad_personalization</span>
                        <span class="prv-signal__maps"><?php esc_html_e( '→ Marketing consent', 'privento' ); ?></span>
                    </div>
                    <div class="prv-signal prv-signal--info">
                        <span class="prv-signal__key">personalization_storage</span>
                        <span class="prv-signal__maps"><?php esc_html_e( '→ Preferences consent', 'privento' ); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="prv-grid prv-grid--2">

            <!-- GTM -->
            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Google Tag Manager', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <p class="prv-panel__desc"><?php esc_html_e( 'GTM is loaded after Consent Mode defaults are set. Fire tags via GTM triggers based on consent signals.', 'privento' ); ?></p>
                    <div class="prv-field">
                        <label class="prv-label" for="prv-gtm-id"><?php esc_html_e( 'GTM Container ID', 'privento' ); ?></label>
                        <input type="text" name="gtm_id" id="prv-gtm-id"
                               value="<?php echo esc_attr( $s['gtm_id'] ?? '' ); ?>"
                               placeholder="GTM-XXXXXXX"
                               class="prv-input">
                        <p class="prv-hint"><?php esc_html_e( 'Leave blank to disable GTM injection.', 'privento' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- GA4 -->
            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Google Analytics 4', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <p class="prv-panel__desc"><?php esc_html_e( 'GA4 is only loaded when Statistics consent is granted. Leave blank if you\'re loading GA via GTM.', 'privento' ); ?></p>
                    <div class="prv-field">
                        <label class="prv-label" for="prv-ga4-id"><?php esc_html_e( 'GA4 Measurement ID', 'privento' ); ?></label>
                        <input type="text" name="ga4_id" id="prv-ga4-id"
                               value="<?php echo esc_attr( $s['ga4_id'] ?? '' ); ?>"
                               placeholder="G-XXXXXXXXXX"
                               class="prv-input">
                    </div>
                </div>
            </div>

            <!-- Meta Pixel -->
            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Meta (Facebook) Pixel', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <p class="prv-panel__desc"><?php esc_html_e( 'Pixel is blocked until Marketing consent is given. Automatically activated when user accepts marketing cookies.', 'privento' ); ?></p>
                    <div class="prv-field">
                        <label class="prv-label" for="prv-pixel-id"><?php esc_html_e( 'Pixel ID', 'privento' ); ?></label>
                        <input type="text" name="meta_pixel_id" id="prv-pixel-id"
                               value="<?php echo esc_attr( $s['meta_pixel_id'] ?? '' ); ?>"
                               placeholder="000000000000000"
                               class="prv-input">
                    </div>
                </div>
            </div>

            <!-- Auto-blocked services -->
            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Auto-Blocked Services', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <p class="prv-panel__desc"><?php esc_html_e( 'These services are automatically blocked until the appropriate consent is given:', 'privento' ); ?></p>
                    <div class="prv-service-list">
                        <?php
                        $services = [
                            'marketing'   => [ 'YouTube embeds', 'Vimeo embeds', 'Facebook embeds', 'DoubleClick/Ads', 'Hotjar', 'Microsoft Clarity' ],
                            'statistics'  => [ 'Google Analytics', 'Google Tag Manager' ],
                            'preferences' => [ 'Google Maps embeds', 'Google Fonts', 'reCAPTCHA' ],
                        ];
                        foreach ( $services as $cat => $items ) :
                        ?>
                        <div class="prv-service-group">
                            <span class="prv-badge prv-badge--cat prv-badge--<?php echo esc_attr( $cat ); ?>">
                                <?php echo esc_html( ucfirst( $cat ) ); ?>
                            </span>
                            <ul class="prv-service-items">
                                <?php foreach ( $items as $item ) : ?>
                                <li><?php echo esc_html( $item ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
