<?php
/**
 * Consent Logger — stores anonymised consent records for compliance documentation.
 *
 * @package Privento\Consent
 */

namespace Privento\Consent;

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

class Logger {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . PRIVENTO_LOG_TABLE;
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    public function record( string $consent_id, array $consented, array $rejected ): int|false {
        global $wpdb;

        $expiry = gmdate( 'Y-m-d H:i:s', time() + ( Settings::get_consent_expiry_days() * DAY_IN_SECONDS ) );

        return $wpdb->insert(
            $this->table,
            [
                'consent_id'   => $consent_id,
                'ip_hash'      => $this->hash_ip(),
                'user_agent'   => $this->get_user_agent(),
                'consented'    => implode( ',', $consented ),
                'rejected'     => implode( ',', $rejected ),
                'version'      => \Privento\Consent\Manager::COOKIE_VERSION,
                'consent_date' => current_time( 'mysql', true ),
                'expiry_date'  => $expiry,
                'country_code' => $this->detect_country(),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );
    }

    // -------------------------------------------------------------------------
    // Retention
    // -------------------------------------------------------------------------

    /** WP-Cron hook that runs the daily cleanup. */
    public const CLEANUP_HOOK = 'privento_purge_logs';

    /** The periods offered in Settings, in months. 0 keeps records forever. */
    public const RETENTION_CHOICES = [ 0, 12, 24, 36, 60 ];

    /** Hooks the cleanup and makes sure it is scheduled. */
    public static function register_cleanup(): void {
        add_action( self::CLEANUP_HOOK, [ self::class, 'run_cleanup' ] );
        if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
            wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
        }
    }

    /** Daily: delete what is older than the period chosen in Settings, if any. */
    public static function run_cleanup(): void {
        $months = (int) Settings::get( 'log_retention_months', 0 );
        if ( $months > 0 ) {
            ( new self() )->purge_older_than( $months );
        }
    }

    /**
     * Deletes records older than $months months. Returns how many went.
     *
     * Never less than twelve months, whatever is asked: a consent can be valid
     * for a year and has to stay provable for as long. Works in batches so a
     * large log does not lock the table, and stops after twenty of them — the
     * rest goes on the next daily run.
     */
    public function purge_older_than( int $months ): int {
        global $wpdb;

        if ( $months < 12 ) {
            return 0;
        }

        // Records are written in UTC.
        $cutoff  = gmdate( 'Y-m-d H:i:s', strtotime( "-{$months} months" ) );
        $deleted = 0;

        for ( $batch = 0; $batch < 20; $batch++ ) {
            $rows = $wpdb->query(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "DELETE FROM {$this->table} WHERE consent_date < %s LIMIT 5000",
                    $cutoff
                )
            );
            if ( ! $rows ) {
                break; // Nothing left, or the query failed.
            }
            $deleted += (int) $rows;
            if ( $rows < 5000 ) {
                break;
            }
        }

        return $deleted;
    }

    // -------------------------------------------------------------------------
    // Read / Export
    // -------------------------------------------------------------------------

    public function get_logs( int $limit = 100, int $offset = 0 ): array {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} ORDER BY consent_date DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }

    public function get_total(): int {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
    }

    public function export_csv(): void {
        global $wpdb;

        $columns = [ 'id', 'consent_id', 'ip_hash', 'user_agent', 'consented', 'rejected', 'version', 'consent_date', 'expiry_date', 'country_code' ];
        $select  = implode( ', ', $columns );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=privento-consent-log-' . gmdate( 'Y-m-d' ) . '.csv' );

        $out = fopen( 'php://output', 'w' );
        // The escape argument is spelled out: leaving it to the default is
        // deprecated from PHP 8.4, and the notice would land inside the file.
        fputcsv( $out, $columns, ',', '"', '\\' );

        // Newest first, a thousand rows at a time, so a large log never has to
        // fit in memory. Ids rise with time, so paging on id keeps the order.
        $before = PHP_INT_MAX;
        do {
            $rows = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT {$select} FROM {$this->table} WHERE id < %d ORDER BY id DESC LIMIT 1000",
                    $before
                ),
                ARRAY_A
            );
            foreach ( (array) $rows as $row ) {
                fputcsv( $out, array_map( [ $this, 'escape_csv_field' ], $row ), ',', '"', '\\' );
                $before = (int) $row['id'];
            }
        } while ( $rows && 1000 === count( $rows ) );

        fclose( $out );
        exit;
    }

    /**
     * Prevent CSV formula injection: user_agent is visitor-controlled, and a
     * leading =, +, - or @ would execute as a formula in spreadsheet apps.
     */
    private function escape_csv_field( mixed $value ): string {
        $value = (string) $value;
        if ( '' !== $value && in_array( $value[0], [ '=', '+', '-', '@' ], true ) ) {
            $value = "'" . $value;
        }
        return $value;
    }

    /**
     * @param int $days   Window size in days; 0 means everything ever recorded.
     * @param int $offset Days to step the window back, so ( 30, 30 ) is the
     *                    thirty days before the last thirty.
     */
    public function get_stats( int $days = 0, int $offset = 0 ): array {
        global $wpdb;

        $where = $this->window_sql( $days, $offset );

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} {$where}" );

        $accept_all = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} {$where}"
            . ( $where ? ' AND' : ' WHERE' )
            . " FIND_IN_SET('necessary', consented)
                AND FIND_IN_SET('statistics', consented)
                AND FIND_IN_SET('marketing', consented)"
        );

        $reject_all = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} {$where}"
            . ( $where ? ' AND' : ' WHERE' )
            . " consented = 'necessary'"
        );
        // phpcs:enable

        return [
            'total'        => $total,
            'accept_all'   => $accept_all,
            'reject_all'   => $reject_all,
            'custom'       => $total - $accept_all - $reject_all,
            'accept_rate'  => $total > 0 ? round( $accept_all / $total * 100, 1 ) : 0,
        ];
    }

    /**
     * Consents per day, oldest first, with empty days filled in so a chart can
     * read the array straight through.
     *
     * @return array<string, int> Keyed by Y-m-d.
     */
    public function get_daily_counts( int $days = 14 ): array {
        global $wpdb;

        $days = max( 1, $days );
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT DATE(consent_date) AS d, COUNT(*) AS c
                 FROM {$this->table}
                 WHERE consent_date >= %s
                 GROUP BY DATE(consent_date)",
                $this->days_ago( $days - 1, 0, true )
            ),
            ARRAY_A
        );

        $counts = [];
        foreach ( (array) $rows as $row ) {
            $counts[ $row['d'] ] = (int) $row['c'];
        }

        $series = [];
        for ( $i = $days - 1; $i >= 0; $i-- ) {
            $day            = gmdate( 'Y-m-d', time() - ( $i * DAY_IN_SECONDS ) );
            $series[ $day ] = $counts[ $day ] ?? 0;
        }

        return $series;
    }

    /**
     * Share of records that granted each category, 0-100.
     *
     * @return array<string, float>
     */
    public function get_category_acceptance( int $days = 0 ): array {
        global $wpdb;

        $where = $this->window_sql( $days );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} {$where}" );

        $out = [];
        foreach ( array_keys( Settings::get_categories() ) as $category ) {
            if ( ! $total ) {
                $out[ $category ] = 0.0;
                continue;
            }
            $granted = (int) $wpdb->get_var(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT COUNT(*) FROM {$this->table} {$where}"
                    . ( $where ? ' AND' : ' WHERE' )
                    . ' FIND_IN_SET(%s, consented)',
                    $category
                )
            );
            $out[ $category ] = round( $granted / $total * 100, 1 );
        }

        return $out;
    }

    /** Builds the date filter shared by the windowed queries. */
    private function window_sql( int $days, int $offset = 0 ): string {
        if ( $days <= 0 ) {
            return '';
        }

        $sql = "WHERE consent_date >= '" . esc_sql( $this->days_ago( $days + $offset ) ) . "'";
        if ( $offset > 0 ) {
            $sql .= " AND consent_date < '" . esc_sql( $this->days_ago( $offset ) ) . "'";
        }

        return $sql;
    }

    /** Records are written with current_time( 'mysql', true ), so compare in UTC. */
    private function days_ago( int $days, int $extra = 0, bool $midnight = false ): string {
        $stamp = time() - ( ( $days + $extra ) * DAY_IN_SECONDS );
        return $midnight ? gmdate( 'Y-m-d 00:00:00', $stamp ) : gmdate( 'Y-m-d H:i:s', $stamp );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function hash_ip(): string {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
        return hash( 'sha256', $ip . wp_salt( 'auth' ) );
    }

    private function get_user_agent(): string {
        return substr(
            sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
            0,
            255
        );
    }

    private function detect_country(): string {
        // Cloudflare's header — which any client can send to a site that is not
        // behind Cloudflare. Store it only when it has the shape of a country
        // code; a longer value would not fit the column and lose the whole record.
        $country = (string) wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '' );
        return preg_match( '/\A[A-Za-z]{2}\z/', $country ) ? strtolower( $country ) : '';
    }
}
