<?php
/**
 * Consent Logs tab.
 *
 * @package Privento
 */

defined( 'ABSPATH' ) || exit;

use Privento\Consent\Logger;

$logger = new Logger();
$stats  = $logger->get_stats();
$logs   = $logger->get_logs( 50, 0 );
$total  = $logger->get_total();
?>
<div class="prv-page">
    <div class="prv-page__header">
        <div>
            <h1 class="prv-page__title"><?php esc_html_e( 'Consent Logs', 'privento' ); ?></h1>
            <p class="prv-page__subtitle">
                <?php
                /* translators: %d = number of records */
                printf( esc_html__( '%d consent records stored. IP addresses are hashed for GDPR compliance.', 'privento' ), $total );
                ?>
            </p>
        </div>
        <button type="button" class="prv-btn prv-btn--outline" id="prv-export-logs">
            <?php esc_html_e( 'Export CSV', 'privento' ); ?>
        </button>
    </div>

    <!-- Stats row -->
    <div class="prv-cards prv-cards--4">
        <div class="prv-card prv-card--mini">
            <span class="prv-card__value"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
            <span class="prv-card__label"><?php esc_html_e( 'Total', 'privento' ); ?></span>
        </div>
        <div class="prv-card prv-card--mini">
            <span class="prv-card__value prv-card__value--green"><?php echo esc_html( $stats['accept_rate'] ); ?>%</span>
            <span class="prv-card__label"><?php esc_html_e( 'Accept Rate', 'privento' ); ?></span>
        </div>
        <div class="prv-card prv-card--mini">
            <span class="prv-card__value"><?php echo esc_html( number_format_i18n( $stats['accept_all'] ) ); ?></span>
            <span class="prv-card__label"><?php esc_html_e( 'Accepted All', 'privento' ); ?></span>
        </div>
        <div class="prv-card prv-card--mini">
            <span class="prv-card__value"><?php echo esc_html( number_format_i18n( $stats['reject_all'] ) ); ?></span>
            <span class="prv-card__label"><?php esc_html_e( 'Rejected All', 'privento' ); ?></span>
        </div>
    </div>

    <!-- Table -->
    <div class="prv-panel">
        <div class="prv-panel__body prv-panel__body--flush">
            <?php if ( empty( $logs ) ) : ?>
                <div class="prv-empty-state">
                    <p><?php esc_html_e( 'No consent records yet. Records appear here as visitors interact with your cookie banner.', 'privento' ); ?></p>
                </div>
            <?php else : ?>
            <div class="prv-table-wrap">
                <table class="prv-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Date', 'privento' ); ?></th>
                            <th><?php esc_html_e( 'Consent ID', 'privento' ); ?></th>
                            <th><?php esc_html_e( 'Consented', 'privento' ); ?></th>
                            <th><?php esc_html_e( 'Rejected', 'privento' ); ?></th>
                            <th><?php esc_html_e( 'Country', 'privento' ); ?></th>
                            <th><?php esc_html_e( 'Expires', 'privento' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs as $log ) : ?>
                        <tr>
                            <td class="prv-td--mono">
                                <?php echo esc_html( wp_date( 'd M Y H:i', strtotime( $log['consent_date'] ) ) ); ?>
                            </td>
                            <td class="prv-td--mono prv-td--small">
                                <code><?php echo esc_html( substr( $log['consent_id'], 0, 8 ) . '…' ); ?></code>
                            </td>
                            <td>
                                <?php
                                $cats = array_filter( explode( ',', $log['consented'] ) );
                                foreach ( $cats as $c ) :
                                ?>
                                <span class="prv-badge prv-badge--cat prv-badge--<?php echo esc_attr( trim( $c ) ); ?>">
                                    <?php echo esc_html( trim( $c ) ); ?>
                                </span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php
                                $rejs = array_filter( explode( ',', $log['rejected'] ) );
                                foreach ( $rejs as $r ) :
                                ?>
                                <span class="prv-badge prv-badge--grey">
                                    <?php echo esc_html( trim( $r ) ); ?>
                                </span>
                                <?php endforeach; ?>
                                <?php if ( empty( $rejs ) ) echo '—'; ?>
                            </td>
                            <td><?php echo esc_html( $log['country_code'] ?: '—' ); ?></td>
                            <td class="prv-td--small">
                                <?php echo esc_html( wp_date( 'd M Y', strtotime( $log['expiry_date'] ) ) ); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ( $total > 50 ) : ?>
            <div class="prv-table-footer">
                <p class="prv-hint">
                    <?php
                    /* translators: %d = total records */
                    printf( esc_html__( 'Showing 50 of %d records. Export CSV for full dataset.', 'privento' ), $total );
                    ?>
                </p>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
