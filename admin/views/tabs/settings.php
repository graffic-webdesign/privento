<?php
/**
 * Settings tab — general and consent settings.
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
            <h1 class="prv-page__title"><?php esc_html_e( 'Settings', 'privento' ); ?></h1>
            <p class="prv-page__subtitle"><?php esc_html_e( 'General plugin settings and consent management options', 'privento' ); ?></p>
        </div>
        <button type="button" class="prv-btn prv-btn--primary" id="prv-save-settings">
            <?php esc_html_e( 'Save Changes', 'privento' ); ?>
        </button>
    </div>

    <form id="prv-settings-form" class="prv-form">

        <div class="prv-grid prv-grid--2">

            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Consent Duration', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <div class="prv-field">
                        <label class="prv-label"><?php esc_html_e( 'Consent Valid For (days)', 'privento' ); ?></label>
                        <select name="consent_duration_days" class="prv-select">
                            <?php
                            foreach ( [ 30 => '30', 60 => '60', 90 => '90', 180 => '180', 365 => '365' ] as $days => $label ) :
                                $sel = selected( (int) ( $s['consent_duration_days'] ?? 180 ), $days, false );
                            ?>
                            <option value="<?php echo esc_attr( $days ); ?>" <?php echo $sel; // phpcs:ignore ?>>
                                <?php
                                /* translators: %d = number of days */
                                printf( esc_html__( '%d days', 'privento' ), $days );
                                ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="prv-hint"><?php esc_html_e( 'EDPB recommends a maximum of 6 months (180 days).', 'privento' ); ?></p>
                    </div>
                </div>
            </div>

            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Geo-Targeting', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <div class="prv-field prv-field--inline">
                        <label class="prv-label"><?php esc_html_e( 'Show banner to EU visitors only', 'privento' ); ?></label>
                        <label class="prv-toggle-field">
                            <input type="checkbox" name="geo_targeting" value="1" <?php checked( $s['geo_targeting'] ?? '0', '1' ); ?>>
                            <span class="prv-toggle-track"></span>
                        </label>
                    </div>
                    <p class="prv-hint"><?php esc_html_e( 'When enabled, the banner is only shown to visitors from EU/EEA countries. Uses country codes from CDN/proxy headers (e.g. Cloudflare); without such a header the banner is shown. Showing it globally is also fine under GDPR.', 'privento' ); ?></p>
                </div>
            </div>

            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Accessibility (WCAG)', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <div class="prv-field prv-field--inline">
                        <label class="prv-label"><?php esc_html_e( 'WCAG 2.1 AA compliant mode', 'privento' ); ?></label>
                        <label class="prv-toggle-field">
                            <input type="checkbox" name="wcag_compliant" value="1" <?php checked( $s['wcag_compliant'] ?? '1', '1' ); ?>>
                            <span class="prv-toggle-track"></span>
                        </label>
                    </div>
                    <div class="prv-field prv-field--inline">
                        <label class="prv-label"><?php esc_html_e( 'Enhanced focus rings', 'privento' ); ?></label>
                        <label class="prv-toggle-field">
                            <input type="checkbox" name="focus_ring" value="1" <?php checked( $s['focus_ring'] ?? '1', '1' ); ?>>
                            <span class="prv-toggle-track"></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Cookie Policy Page', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <div class="prv-field">
                        <label class="prv-label"><?php esc_html_e( 'Cookie Policy Page', 'privento' ); ?></label>
                        <?php
                        wp_dropdown_pages( [
                            'name'              => 'policy_page_id',
                            'show_option_none'  => __( '— Select page —', 'privento' ),
                            'option_none_value' => '0',
                            'selected'          => (int) ( $s['policy_page_id'] ?? 0 ),
                            'class'             => 'prv-select',
                        ] );
                        ?>
                        <p class="prv-hint">
                            <?php esc_html_e( 'This page should contain the shortcode: ', 'privento' ); ?>
                            <code>[privento_cookie_policy]</code>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
