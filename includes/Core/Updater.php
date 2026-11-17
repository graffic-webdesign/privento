<?php
/**
 * Updates from GitHub releases.
 *
 * The plugin header carries "Update URI: https://github.com/…". For such
 * plugins WordPress skips WordPress.org and asks the update_plugins_{hostname}
 * filter instead; this class answers it from the repository's latest release.
 * Everything after that — the notice, one-click update, auto-updates — is
 * WordPress' own.
 *
 * @package Privento\Core
 */

namespace Privento\Core;

defined( 'ABSPATH' ) || exit;

class Updater {

    /** GitHub "owner/name". A fork only needs to change this and the Update URI header. */
    public const REPO = 'graffic-webdesign/privento';

    public const TRANSIENT = 'privento_update';

    /** The release asset WordPress installs; its top-level folder is "privento/". */
    private const ASSET = 'privento.zip';

    public function __construct() {
        add_filter( 'update_plugins_github.com', [ $this, 'check' ], 10, 4 );
        add_filter( 'plugins_api', [ $this, 'details' ], 10, 3 );
        // Before WordPress' own plugin check on the Updates screen (priority 10).
        add_action( 'load-update-core.php', [ self::class, 'recheck' ], 9 );
    }

    /**
     * Tells WordPress about the latest release. WordPress compares the version
     * with the installed one itself.
     *
     * @param array|false $update      Update data from an earlier filter.
     * @param array       $plugin_data Plugin headers.
     * @param string      $plugin_file Plugin basename.
     * @param string[]    $locales     Installed locales.
     * @return array|false
     */
    public function check( $update, $plugin_data, $plugin_file, $locales ) {
        if ( PRIVENTO_BASENAME !== $plugin_file ) {
            return $update; // Another plugin hosted on github.com.
        }

        $release = $this->latest();
        if ( ! $release ) {
            return false;
        }

        $update = [
            'id'           => 'https://github.com/' . self::REPO,
            'slug'         => 'privento',
            'version'      => $release['version'],
            'url'          => $release['url'],
            'package'      => $release['package'],
            'requires_php' => ( $release['requires_php'] ?? '' ) ?: '8.0',
        ];

        // Without this WordPress prints "Compatibility: Unknown" beside the update.
        $tested = self::tested_for_this_site( $release['tested'] ?? '' );
        if ( $tested ) {
            $update['tested'] = $tested;
        }

        return $update;
    }

    /**
     * "Tested up to: 7.1" means every 7.1.x, but WordPress compares the value
     * with its full version number, where 7.1 is older than 7.1.4. Spell the
     * branch out as the running version, the way WordPress.org does.
     */
    private static function tested_for_this_site( string $tested ): string {
        $running = (string) get_bloginfo( 'version' );
        if ( $tested && 0 === strpos( $running, $tested . '.' ) ) {
            return $running;
        }
        return $tested;
    }

    /**
     * "Check again" on the Updates screen.
     *
     * WordPress re-checks core there, but not plugins: its plugin check ran when
     * the screen opened and is skipped for the next minute, so this class is
     * never asked and a release published since stays invisible. Forget the
     * cached release and mark the plugin check as due, so the check that runs
     * right after this asks again — WordPress.org and GitHub alike.
     */
    public static function recheck(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( empty( $_GET['force-check'] ) || ! current_user_can( 'update_plugins' ) ) {
            return;
        }

        delete_site_transient( self::TRANSIENT );

        $updates = get_site_transient( 'update_plugins' );
        if ( is_object( $updates ) ) {
            $updates->last_checked = 0;
            set_site_transient( 'update_plugins', $updates );
        }
    }

    /**
     * Fills the "View details" popup, which would otherwise look the slug up
     * on WordPress.org.
     *
     * @param false|object|array $result
     * @param string             $action
     * @param object             $args
     * @return false|object|array
     */
    public function details( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || 'privento' !== ( $args->slug ?? '' ) ) {
            return $result;
        }

        $release = $this->latest();
        if ( ! $release ) {
            return $result;
        }

        return (object) [
            'name'          => 'Privento',
            'slug'          => 'privento',
            'version'       => $release['version'],
            'author'        => '<a href="https://graffic.dk">Graffic</a>',
            'homepage'      => 'https://github.com/' . self::REPO,
            'requires'      => ( $release['requires'] ?? '' ) ?: '6.0',
            'requires_php'  => ( $release['requires_php'] ?? '' ) ?: '8.0',
            'tested'        => self::tested_for_this_site( $release['tested'] ?? '' ),
            'download_link' => $release['package'],
            'sections'      => [
                // Release notes are written on GitHub — plain text, never markup.
                'changelog' => wpautop( esc_html( $release['notes'] ) ),
            ],
        ];
    }

    /**
     * Reduces a GitHub release to what the updater needs, or null when it is
     * not something to install: a draft, a pre-release, an API error body, or
     * a release without the built zip (GitHub's automatic source archive has
     * the wrong folder name and includes development files).
     *
     * @return array{version: string, package: string, url: string, notes: string, tested: string, requires: string, requires_php: string}|null
     */
    public static function parse_release( array $release ): ?array {
        if ( empty( $release['tag_name'] ) || ! empty( $release['draft'] ) || ! empty( $release['prerelease'] ) ) {
            return null;
        }

        // The release workflow ends the notes with a hidden marker copied from
        // readme.txt: <!-- privento: tested=7.1 requires=6.0 requires_php=8.0 -->
        $notes = (string) ( $release['body'] ?? '' );
        $meta  = [ 'tested' => '', 'requires' => '', 'requires_php' => '' ];
        if ( preg_match( '/<!--\s*privento:(.*?)-->/s', $notes, $marker ) ) {
            preg_match_all( '/(tested|requires_php|requires)=([0-9.]+)/', $marker[1], $pairs, PREG_SET_ORDER );
            foreach ( $pairs as $pair ) {
                $meta[ $pair[1] ] = $pair[2];
            }
            $notes = trim( str_replace( $marker[0], '', $notes ) );
        }

        foreach ( (array) ( $release['assets'] ?? [] ) as $asset ) {
            if ( self::ASSET === ( $asset['name'] ?? '' ) && ! empty( $asset['browser_download_url'] ) ) {
                return $meta + [
                    'version' => ltrim( (string) $release['tag_name'], 'v' ),
                    'package' => (string) $asset['browser_download_url'],
                    'url'     => (string) ( $release['html_url'] ?? 'https://github.com/' . self::REPO ),
                    'notes'   => $notes,
                ];
            }
        }

        return null;
    }

    /**
     * The latest release, cached for an hour — a failed lookup too, so an
     * unreachable or rate-limiting GitHub is not asked again on every admin
     * page load. No longer than that: WordPress already decides how often it
     * checks (twice a day from cron, hourly on the Plugins screen), and a longer
     * cache here only delays a release on top of that.
     */
    private function latest(): ?array {
        $cached = get_site_transient( self::TRANSIENT );
        if ( is_array( $cached ) ) {
            return $cached ?: null;
        }

        $response = wp_remote_get(
            'https://api.github.com/repos/' . self::REPO . '/releases/latest',
            [
                'timeout' => 8,
                'headers' => [ 'Accept' => 'application/vnd.github+json' ],
            ]
        );

        $release = null;
        if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
            $body    = json_decode( wp_remote_retrieve_body( $response ), true );
            $release = is_array( $body ) ? self::parse_release( $body ) : null;
        }

        set_site_transient( self::TRANSIENT, $release ?? [], HOUR_IN_SECONDS );

        return $release;
    }
}
