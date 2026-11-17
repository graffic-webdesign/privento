<?php
/**
 * Admin AJAX handler — processes all admin-side AJAX requests.
 *
 * @package Privento\Admin
 */

namespace Privento\Admin;

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

class Ajax {

    public function __construct() {
        $actions = [
            'privento_save_settings'       => 'save_settings',
            'privento_export_logs'         => 'export_logs',
            'privento_generate_policy'     => 'generate_policy',
        ];

        foreach ( $actions as $action => $method ) {
            add_action( 'wp_ajax_' . $action, [ $this, $method ] );
        }
    }

    // -------------------------------------------------------------------------
    // Handlers
    // -------------------------------------------------------------------------

    public function save_settings(): void {
        $this->verify_nonce();

        // JS sends settings as JSON string; fall back to raw array for safety
        $settings_raw = $_POST['settings'] ?? '';
        if ( is_string( $settings_raw ) && ! empty( $settings_raw ) ) {
            $raw = json_decode( wp_unslash( $settings_raw ), true );
            if ( ! is_array( $raw ) ) {
                $raw = [];
            }
        } else {
            $raw = (array) $settings_raw;
        }

        // Sanitize
        $clean = $this->sanitize_settings( $raw );

        // Merge so saving one tab does not wipe other tabs settings
        Settings::merge_update( $clean );

        wp_send_json_success( [ 'message' => __( 'Settings saved successfully.', 'privento' ) ] );
    }

    public function export_logs(): void {
        $this->verify_nonce();
        ( new \Privento\Consent\Logger() )->export_csv();
    }

    public function generate_policy(): void {
        $this->verify_nonce();

        // Scan cookies
        $scanner = new \Privento\Scanner\CookieScanner();
        $cookies = $scanner->scan();
        update_option( 'privento_scanned_cookies', $cookies );
        update_option( 'privento_last_scan', current_time( 'mysql' ) );

        // Create or update the Cookie Policy page
        $settings       = Settings::all();
        $policy_page_id = (int) ( $settings['policy_page_id'] ?? 0 );
        $page_content   = '[privento_cookie_policy]';

        if ( $policy_page_id && get_post( $policy_page_id ) ) {
            // Update existing page
            wp_update_post( [
                'ID'           => $policy_page_id,
                'post_content' => $page_content,
                'post_status'  => 'publish',
            ] );
        } else {
            // Create a new page
            $new_id = wp_insert_post( [
                'post_title'   => \Privento\Core\Installer::policy_page_title(),
                'post_content' => $page_content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ] );
            if ( $new_id && ! is_wp_error( $new_id ) ) {
                $policy_page_id = $new_id;
                Settings::merge_update( [ 'policy_page_id' => $new_id ] );
            }
        }

        Settings::merge_update( [ 'policy_last_generated' => current_time( 'mysql' ) ] );

        wp_send_json_success( [
            'message'  => __( 'Cookie Policy page created/updated successfully.', 'privento' ),
            'count'    => count( $cookies ),
            'page_id'  => $policy_page_id,
            'page_url' => $policy_page_id ? get_permalink( $policy_page_id ) : '',
        ] );
    }

    // -------------------------------------------------------------------------
    // Sanitize settings
    // -------------------------------------------------------------------------

    private function sanitize_settings( array $raw ): array {
        $clean = [];

        $text_fields = [
            'banner_position', 'floating_btn_position',
            'headline', 'policy_link_text',
            'btn_accept_all', 'btn_reject_all', 'btn_settings', 'btn_save',
            'cat_necessary_name', 'cat_necessary_desc',
            'cat_preferences_name', 'cat_preferences_desc',
            'cat_statistics_name', 'cat_statistics_desc',
            'cat_marketing_name', 'cat_marketing_desc',
            'gtm_id', 'ga4_id', 'meta_pixel_id',
        ];

        // Fields that allow basic HTML (rendered with wp_kses_post on output)
        $html_fields = [ 'body_text', 'policy_intro_text' ];

        $color_fields = [
            'color_bg', 'color_text',
            'color_btn_accept_bg', 'color_btn_accept_txt',
            'color_btn_reject_bg', 'color_btn_reject_txt',
            'color_btn_settings_bg', 'color_btn_settings_txt',
            'color_btn_save_bg', 'color_btn_save_txt',
            'color_accept_hover', 'color_reject_hover',
            'color_settings_hover', 'color_save_hover',
            'color_accept_hover_txt', 'color_reject_hover_txt',
            'color_settings_hover_txt', 'color_save_hover_txt',
            'color_floating_bg', 'color_floating_txt', 'color_floating_hover',
            'color_link',
            'color_revoke_link', 'color_revoke_hover',
        ];

        $int_fields = [
            'font_size_base', 'border_radius', 'consent_duration_days', 'policy_page_id',
        ];

        $bool_fields = [
            'shadow', 'gcm_enabled', 'gcm_default_denied',
            'geo_targeting', 'floating_btn_enabled',
            'wcag_compliant', 'focus_ring',
            'policy_show_date', 'policy_show_revoke', 'policy_link_enabled',
        ];

        // IMPORTANT: Only sanitize and return keys that are actually present in $raw.
        // This prevents saving one tab from blanking out fields that belong to other tabs.
        foreach ( $text_fields as $f ) {
            if ( array_key_exists( $f, $raw ) ) {
                $clean[ $f ] = sanitize_text_field( $raw[ $f ] );
            }
        }

        foreach ( $html_fields as $f ) {
            if ( array_key_exists( $f, $raw ) ) {
                $clean[ $f ] = wp_kses_post( $raw[ $f ] );
            }
        }

        foreach ( $color_fields as $f ) {
            if ( array_key_exists( $f, $raw ) ) {
                $val = sanitize_hex_color( $raw[ $f ] );
                if ( ! $val && in_array( $raw[ $f ], [ 'transparent', 'inherit' ], true ) ) {
                    $val = $raw[ $f ];
                }
                $clean[ $f ] = $val ?: '#ffffff';
            }
        }

        foreach ( $int_fields as $f ) {
            if ( array_key_exists( $f, $raw ) ) {
                $clean[ $f ] = absint( $raw[ $f ] );
            }
        }

        foreach ( $bool_fields as $f ) {
            if ( array_key_exists( $f, $raw ) ) {
                $clean[ $f ] = $raw[ $f ] ? '1' : '0';
            }
        }

        // font_family is echoed into an inline <style> block, so restrict it to
        // the Design tab's fixed options — free text would allow CSS injection.
        if ( array_key_exists( 'font_family', $raw ) ) {
            $allowed_fonts = [
                'inherit',
                'system-ui',
                'Inter, sans-serif',
                '-apple-system, sans-serif',
                'Georgia, serif',
            ];
            $clean['font_family'] = in_array( $raw['font_family'], $allowed_fonts, true )
                ? $raw['font_family']
                : 'inherit';
        }

        return $clean;
    }

    // -------------------------------------------------------------------------
    // Security
    // -------------------------------------------------------------------------

    private function verify_nonce(): void {
        if (
            ! isset( $_REQUEST['nonce'] )
            || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'privento_admin' )
        ) {
            wp_send_json_error( __( 'Security check failed.', 'privento' ), 403 );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Insufficient permissions.', 'privento' ), 403 );
        }
    }
}
