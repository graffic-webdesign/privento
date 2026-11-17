<?php
/**
 * Script blocker — Frontend::block_scripts_callback().
 */

$frontend = new \Privento\Public\Frontend();

$out = $frontend->block_scripts_callback( '<script src="https://www.googletagmanager.com/gtag/js?id=G-1"></script>' );
t_assert(
    str_contains( $out, 'data-src="https://www.googletagmanager.com/gtag/js?id=G-1"' )
    && str_contains( $out, 'data-privento-category="statistics"' )
    && ! str_contains( $out, ' src=' ),
    'blocks_known_script'
);

$lazy = '<iframe data-src="https://www.youtube.com/embed/x"></iframe>';
t_assert( $lazy === $frontend->block_scripts_callback( $lazy ), 'leaves_lazy_loaded_embed_untouched' );

$out = $frontend->block_scripts_callback( '<iframe class="a" src="https://www.youtube.com/embed/x" width="1"></iframe>' );
t_assert(
    str_contains( $out, 'data-privento-category="marketing"' )
    && str_contains( $out, 'class="a"' )
    && str_contains( $out, 'width="1"' ),
    'blocks_embed_and_keeps_other_attributes'
);

$own = '<script src="/wp-includes/js/jquery.js"></script>';
t_assert( $own === $frontend->block_scripts_callback( $own ), 'leaves_unknown_script_untouched' );

// The policy link is part of the banner text — the closing sentence of the
// paragraph, not a line of its own squeezed in above the buttons — and its
// wording is a text setting like the others.
t_reset();
$GLOBALS['wp_options']['privento_settings'] = [ 'policy_page_id' => 5, 'policy_link_text' => 'Se cookiepolitikken' ];
ob_start();
( new \Privento\Public\Frontend() )->render_banner();
$html = ob_get_clean();
t_assert(
    1 === preg_match( '~<p class="privento-banner__text">.*<a [^>]*class="privento-policy-link"[^>]*>\s*Se cookiepolitikken\s*</a>\s*</p>~s', $html ),
    'policy_link_sits_inside_the_banner_text'
);
t_assert( 2 === substr_count( $html, 'Se cookiepolitikken' ), 'policy_link_text_is_used_in_banner_and_panel' );

t_reset();
$GLOBALS['wp_translations'] = [ 'Read our cookie policy' => 'Læs vores cookiepolitik' ];
t_assert( 'Læs vores cookiepolitik' === \Privento\Core\Settings::get( 'policy_link_text' ), 'policy_link_text_follows_the_language_when_empty' );

// The link can be switched off; then neither the banner nor the settings
// window shows it.
t_reset();
$GLOBALS['wp_options']['privento_settings'] = [ 'policy_page_id' => 5, 'policy_link_enabled' => '0' ];
ob_start();
( new \Privento\Public\Frontend() )->render_banner();
$html = ob_get_clean();
t_assert( ! str_contains( $html, 'policy-link' ), 'policy_link_can_be_switched_off' );

// On by default, so existing sites keep their link.
t_reset();
$GLOBALS['wp_options']['privento_settings'] = [ 'policy_page_id' => 5 ];
ob_start();
( new \Privento\Public\Frontend() )->render_banner();
t_assert( 2 === substr_count( ob_get_clean(), 'policy-link"' ), 'policy_link_is_on_by_default' );

t_assert( [ 'policy_link_enabled' => '0' ] === t_call_private( new \Privento\Admin\Ajax(), 'sanitize_settings', [ 'policy_link_enabled' => '0' ] ), 'policy_link_switch_is_saved' );

// Geo-targeting is gone: it never hid the banner (the flag reached the script
// as "" instead of false), and server-side country detection cannot work behind
// a page cache. A site that had it switched on behaves exactly as before.
t_reset();
$GLOBALS['wp_options']['privento_settings'] = [ 'geo_targeting' => '1' ];
$config = t_call_private( new \Privento\Public\Frontend(), 'get_js_config' );
t_assert( ! array_key_exists( 'geoTargeting', $config ) && ! array_key_exists( 'geoAllowed', $config ), 'geo_targeting_is_not_sent_to_the_script' );
t_assert( [] === t_call_private( new \Privento\Admin\Ajax(), 'sanitize_settings', [ 'geo_targeting' => '1' ] ), 'geo_targeting_is_no_longer_a_setting' );
