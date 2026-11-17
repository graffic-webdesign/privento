<?php
/**
 * Log retention: consent records older than the chosen period are deleted
 * once a day. Off unless the site owner turns it on.
 */

use Privento\Consent\Logger;

$cutoff_of = static function ( string $sql ): int {
    preg_match( "/consent_date < '([^']+)'/", $sql, $m );
    return strtotime( ( $m[1] ?? '1970-01-01 00:00:00' ) . ' UTC' );
};

// Keeping forever is the default: nothing is ever deleted.
t_reset();
$GLOBALS['wp_cron'] = [];
$GLOBALS['wpdb']    = t_wpdb( [ 0 ] );
t_assert( 0 === \Privento\Core\Settings::get( 'log_retention_months' ), 'retention_is_off_by_default' );
Logger::run_cleanup();
t_assert( [] === $GLOBALS['wpdb']->queries, 'cleanup_does_nothing_when_keeping_forever' );

// Two years: one delete, for rows older than two years, in the log table only.
t_reset();
$GLOBALS['wp_options']['privento_settings'] = [ 'log_retention_months' => 24 ];
$GLOBALS['wpdb']                            = t_wpdb( [ 7 ] );
Logger::run_cleanup();
$q = $GLOBALS['wpdb']->queries;
t_assert( 1 === count( $q ) && 0 === strpos( $q[0], 'DELETE FROM wp_privento_consent_log WHERE consent_date < ' ), 'cleanup_deletes_from_the_log_table_by_date' );
$expected = strtotime( '-24 months' );
t_assert( abs( $cutoff_of( $q[0] ) - $expected ) < 2 * DAY_IN_SECONDS, 'cutoff_is_the_chosen_number_of_months_ago' );

// Large logs are cleared in batches, so no single query holds the table long.
$GLOBALS['wpdb'] = t_wpdb( [ 5000, 5000, 120 ] );
$deleted         = ( new Logger() )->purge_older_than( 12 );
t_assert( 10120 === $deleted && 3 === count( $GLOBALS['wpdb']->queries ), 'purge_runs_in_batches_until_done' );
t_assert( str_contains( $GLOBALS['wpdb']->queries[0], 'LIMIT 5000' ), 'each_batch_is_limited' );

// …and one run never goes on forever; the rest waits for tomorrow.
$GLOBALS['wpdb'] = t_wpdb( [ 5000 ] );
( new Logger() )->purge_older_than( 12 );
t_assert( 20 === count( $GLOBALS['wpdb']->queries ), 'one_run_stops_after_twenty_batches' );

// Whatever ends up in the option, records younger than a year are never deleted:
// a consent can be valid for 365 days and must stay provable that long.
foreach ( [ 0, 1, 11, -5 ] as $months ) {
    $GLOBALS['wpdb'] = t_wpdb( [ 5000 ] );
    t_assert( 0 === ( new Logger() )->purge_older_than( $months ) && [] === $GLOBALS['wpdb']->queries, 'purge_refuses_' . $months . '_months' );
}

// A failing query (false) ends the run quietly.
$GLOBALS['wpdb'] = t_wpdb( [ false ] );
t_assert( 0 === ( new Logger() )->purge_older_than( 12 ) && 1 === count( $GLOBALS['wpdb']->queries ), 'purge_stops_on_database_error' );

// The setting accepts only the offered periods.
$save = static fn( $value ) => t_call_private( new \Privento\Admin\Ajax(), 'sanitize_settings', [ 'log_retention_months' => $value ] )['log_retention_months'];
t_assert( 24 === $save( '24' ) && 60 === $save( 60 ) && 0 === $save( '0' ), 'offered_periods_are_saved' );
t_assert( 0 === $save( '7' ) && 0 === $save( 'abc' ) && 0 === $save( '-12' ), 'anything_else_means_keep_forever' );

// The daily job is registered, scheduled once, and removed on deactivation.
t_reset();
$GLOBALS['wp_cron'] = [];
Logger::register_cleanup();
Logger::register_cleanup();
t_assert( 'daily' === ( $GLOBALS['wp_cron']['privento_purge_logs'] ?? '' ), 'cleanup_is_scheduled_daily' );
t_assert( ! empty( $GLOBALS['wp_filters']['privento_purge_logs'] ), 'cleanup_handler_is_hooked' );
function flush_rewrite_rules() {}
\Privento\Core\Installer::deactivate();
t_assert( ! isset( $GLOBALS['wp_cron']['privento_purge_logs'] ), 'deactivation_unschedules_the_cleanup' );
