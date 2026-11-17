<?php
/**
 * Hardening found in the 2026-11 security check.
 */

use Privento\Core\Updater;

$release = static fn( string $url ): array => [
    'tag_name' => 'v9.9.9',
    'assets'   => [ [ 'name' => 'privento.zip', 'browser_download_url' => $url ] ],
];

// The updater installs a zip on every site. Accept it only from this
// repository's own releases, whatever the API response says.
t_assert( null !== Updater::parse_release( $release( 'https://github.com/graffic-webdesign/privento/releases/download/v9.9.9/privento.zip' ) ), 'package_from_own_releases_is_accepted' );
t_assert( null === Updater::parse_release( $release( 'https://evil.example/privento.zip' ) ), 'package_from_another_host_is_refused' );
t_assert( null === Updater::parse_release( $release( 'https://github.com/someone-else/privento/releases/download/v9.9.9/privento.zip' ) ), 'package_from_another_repository_is_refused' );
t_assert( null === Updater::parse_release( $release( 'http://github.com/graffic-webdesign/privento/releases/download/v9.9.9/privento.zip' ) ), 'package_over_plain_http_is_refused' );

// Consent logging must work on installs whose wp-config.php has no AUTH_SALT
// constant, and behind a proxy where many visitors share one address.
t_reset();
$_SERVER['REMOTE_ADDR'] = '203.0.113.7';
$manager                = new \Privento\Consent\Manager();
$refused                = 0;
for ( $i = 0; $i < 60; $i++ ) {
    $refused += t_call_private( $manager, 'is_rate_limited' ) ? 1 : 0;
}
t_assert( 0 === $refused, 'sixty_consents_a_minute_from_one_address_are_accepted' );
t_assert( true === t_call_private( $manager, 'is_rate_limited' ), 'the_sixty_first_is_refused' );
t_assert( ! defined( 'AUTH_SALT' ), 'ran_without_the_auth_salt_constant' );

$GLOBALS['wpdb'] = new class { public $prefix = 'wp_'; };
$logger          = new \Privento\Consent\Logger();
t_assert( 64 === strlen( t_call_private( $logger, 'hash_ip' ) ), 'ip_hash_works_without_the_auth_salt_constant' );

// The country header is only meaningful as two letters; anything else is
// dropped rather than stored (and rather than making the insert fail).
foreach ( [ 'DK' => 'dk', 'dk' => 'dk', 'XX' => 'xx', 'DENMARK' => '', 'd1' => '', "D\nK" => '', '' => '' ] as $header => $expected ) {
    $_SERVER['HTTP_CF_IPCOUNTRY'] = $header;
    t_assert( $expected === t_call_private( $logger, 'detect_country' ), 'country_header_' . json_encode( (string) $header ) );
}
unset( $_SERVER['HTTP_CF_IPCOUNTRY'], $_SERVER['REMOTE_ADDR'] );

// Tracking IDs end up inside script tags and URLs. Keep only what an ID can
// contain; never blank an unusual but valid one.
$clean = t_call_private( new \Privento\Admin\Ajax(), 'sanitize_settings', [
    'gtm_id'        => ' GTM-ABC123 ',
    'ga4_id'        => "G-XYZ');alert(1);//",
    'meta_pixel_id' => '1234567890"><script>',
] );
t_assert( 'GTM-ABC123' === $clean['gtm_id'], 'gtm_id_is_kept' );
t_assert( 'G-XYZalert1' === $clean['ga4_id'], 'ga4_id_loses_everything_but_id_characters' );
t_assert( '1234567890' === $clean['meta_pixel_id'], 'pixel_id_loses_everything_but_id_characters' );
