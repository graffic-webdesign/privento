<?php
/**
 * Cookie catalogue — descriptions follow the language at render time.
 */

use Privento\Scanner\CookieScanner;

$scanner = new CookieScanner();
$ga4_en  = 'Used by Google Analytics 4 to persist session state.';

// A stored scan carries the catalogue key; the text comes from the catalogue.
t_reset();
$GLOBALS['wp_translations'] = [ $ga4_en => 'Bruges af Google Analytics 4 til at fastholde sessionstilstand.' ];
$out = $scanner->describe( [ 'name' => '_ga_ABC', 'key' => '_ga_', 'category' => 'statistics', 'description' => 'gammel' ] );
t_assert( 'Bruges af Google Analytics 4 til at fastholde sessionstilstand.' === $out['description'], 'describe_uses_key' );
t_assert( '_ga_ABC' === $out['name'], 'describe_keeps_the_cookie_name' );

// Scans stored by 1.6.x have no key: match on the name, longest prefix first
// ("_ga_ABC" is a GA4 cookie, not the plain "_ga" one).
t_reset();
$out = $scanner->describe( [ 'name' => '_ga_ABC', 'category' => 'statistics', 'description' => 'gammel' ] );
t_assert( $ga4_en === $out['description'], 'describe_matches_legacy_scan_by_name' );

// A cookie the catalogue does not know is passed through.
$unknown = [ 'name' => 'foo', 'category' => 'necessary', 'description' => 'mine' ];
t_assert( $unknown === $scanner->describe( $unknown ), 'describe_leaves_unknown_cookie' );

// Scanning: only real prefixes match by prefix. "frontend_lang" is not
// Facebook's "fr" cookie.
t_reset();
$_COOKIE = [ 'frontend_lang' => '1', '_ga_ABC' => '1', 'fr' => '1' ];
$found   = array_column( $scanner->scan(), 'key', 'name' );
t_assert( [ '_ga_ABC' => '_ga_', 'fr' => 'fr' ] === $found, 'scan_matches_prefixes_only_where_meant' );
$_COOKIE = [];

// The stored list is described on the way out.
t_reset();
$GLOBALS['wp_options']['privento_scanned_cookies'] = [ [ 'name' => '_ga_ABC', 'category' => 'statistics', 'description' => 'gammel' ] ];
t_assert( $ga4_en === $scanner->get_cookies_by_category()['statistics'][0]['description'], 'stored_scan_is_described_on_read' );
