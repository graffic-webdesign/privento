<?php
/**
 * Updates from GitHub releases.
 */

use Privento\Core\Updater;

$release = [
    'tag_name'   => 'v1.7.1',
    'draft'      => false,
    'prerelease' => false,
    'html_url'   => 'https://github.com/graffic-webdesign/privento/releases/tag/v1.7.1',
    'body'       => 'Fixes.',
    'assets'     => [
        [ 'name' => 'source.tar.gz', 'browser_download_url' => 'https://example.test/source.tar.gz' ],
        [ 'name' => 'privento.zip', 'browser_download_url' => 'https://example.test/privento.zip' ],
    ],
];

$http_ok = static fn() => [ 'response' => [ 'code' => 200 ], 'body' => json_encode( $GLOBALS['t_release'] ) ];
$GLOBALS['t_release'] = $release;

// parse_release()
$parsed = Updater::parse_release( $release );
t_assert( '1.7.1' === $parsed['version'] && 'https://example.test/privento.zip' === $parsed['package'], 'parse_release_ok' );
t_assert( null === Updater::parse_release( [ 'assets' => [] ] + $release ), 'parse_release_without_zip' );
t_assert( null === Updater::parse_release( [ 'prerelease' => true ] + $release ), 'parse_release_prerelease' );
t_assert( null === Updater::parse_release( [ 'draft' => true ] + $release ), 'parse_release_draft' );
t_assert( null === Updater::parse_release( [ 'message' => 'API rate limit exceeded' ] ), 'parse_release_error_body' );

// check(): other plugins' update data passes through untouched, without a request.
t_reset();
$GLOBALS['wp_http'] = $http_ok;
$updater            = new Updater();
t_assert( 'theirs' === $updater->check( 'theirs', [], 'other/other.php', [] ) && 0 === $GLOBALS['wp_http_calls'], 'check_ignores_other_plugins' );

// check(): maps the release onto what WordPress expects.
$update = $updater->check( false, [], PRIVENTO_BASENAME, [] );
t_assert(
    is_array( $update )
    && '1.7.1' === $update['version']
    && 'https://example.test/privento.zip' === $update['package']
    && 'privento' === $update['slug']
    && 'https://github.com/graffic-webdesign/privento' === $update['id'],
    'check_maps_release'
);

// check(): the answer is cached for twelve hours.
$updater->check( false, [], PRIVENTO_BASENAME, [] );
t_assert( 1 === $GLOBALS['wp_http_calls'] && 12 * HOUR_IN_SECONDS === $GLOBALS['wp_transients']['privento_update']['ttl'], 'check_caches_the_release' );

// check(): GitHub unreachable → no update, no retry storm (one request per hour).
t_reset();
$GLOBALS['wp_http'] = static fn() => new WP_Error( 'http_request_failed', 'timeout' );
$updater            = new Updater();
t_assert( false === $updater->check( false, [], PRIVENTO_BASENAME, [] ), 'check_returns_false_when_api_fails' );
$updater->check( false, [], PRIVENTO_BASENAME, [] );
t_assert( 1 === $GLOBALS['wp_http_calls'] && HOUR_IN_SECONDS === $GLOBALS['wp_transients']['privento_update']['ttl'], 'check_backs_off_after_failure' );

// check(): rate-limited (403 with a JSON error body) is a failure too.
t_reset();
$GLOBALS['wp_http'] = static fn() => [ 'response' => [ 'code' => 403 ], 'body' => '{"message":"API rate limit exceeded"}' ];
t_assert( false === ( new Updater() )->check( false, [], PRIVENTO_BASENAME, [] ), 'check_returns_false_when_rate_limited' );

// details(): only answers for this plugin's "View details" popup.
t_reset();
$GLOBALS['wp_http'] = $http_ok;
$updater            = new Updater();
t_assert( 'x' === $updater->details( 'x', 'plugin_information', (object) [ 'slug' => 'akismet' ] ), 'details_ignores_other_plugins' );
$info = $updater->details( false, 'plugin_information', (object) [ 'slug' => 'privento' ] );
t_assert(
    is_object( $info ) && '1.7.1' === $info->version && str_contains( $info->sections['changelog'], 'Fixes.' )
    && 'https://example.test/privento.zip' === $info->download_link,
    'details_describes_the_release'
);

// details(): release notes are GitHub-user content — never trusted as HTML.
$GLOBALS['t_release']['body'] = '<script>alert(1)</script>';
t_reset();
$GLOBALS['wp_http'] = $http_ok;
$info               = ( new Updater() )->details( false, 'plugin_information', (object) [ 'slug' => 'privento' ] );
t_assert( ! str_contains( $info->sections['changelog'], '<script>' ), 'details_escapes_release_notes' );

// "Check again" on the Updates screen (update-core.php?force-check=1) must
// look at GitHub again, or a release published a minute ago stays invisible.
t_reset();
$GLOBALS['wp_http']   = $http_ok;
$GLOBALS['t_release'] = $release;
$updater              = new Updater();
$updater->check( false, [], PRIVENTO_BASENAME, [] );
$_GET['force-check'] = '1';
$updater->check( false, [], PRIVENTO_BASENAME, [] );
t_assert( 2 === $GLOBALS['wp_http_calls'], 'check_again_refetches_the_release' );
$_GET = [];
