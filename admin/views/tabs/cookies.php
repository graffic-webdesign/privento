<?php
/**
 * Cookie Scanner tab.
 *
 * @package Privento
 */

defined( 'ABSPATH' ) || exit;

use Privento\Scanner\CookieScanner;

$scanner   = new CookieScanner();
$cookies   = $scanner->get_cookies_by_category();
$last_scan = $scanner->get_last_scan();
$total     = array_sum( array_map( 'count', $cookies ) );
?>
<div class="prv-page">
    <div class="prv-page__header">
        <div>
            <h1 class="prv-page__title"><?php esc_html_e( 'Cookie Scanner', 'privento' ); ?></h1>
            <p class="prv-page__subtitle">
                <?php if ( $last_scan ) :
                    /* translators: %s = date */
                    printf( esc_html__( 'Last scan: %s — %d cookies detected.', 'privento' ), esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $last_scan ) ) ), $total );
                else :
                    esc_html_e( 'No scan performed yet. Run a scan to detect cookies on your site.', 'privento' );
                endif; ?>
            </p>
        </div>
        <div style="display:flex;gap:8px">
            <button type="button" class="prv-btn prv-btn--primary" id="prv-run-scan">
                <?php esc_html_e( 'Scan Now', 'privento' ); ?>
            </button>
            <button type="button" class="prv-btn prv-btn--outline" id="privento-generate-policy">
                <?php esc_html_e( 'Update Cookie Policy', 'privento' ); ?>
            </button>
        </div>
    </div>

    <div id="prv-scan-results">
    <?php if ( $total === 0 ) : ?>
        <div class="prv-panel">
            <div class="prv-panel__body">
                <div class="prv-empty-state">
                    <svg viewBox="0 0 40 40" fill="none" style="width:48px;height:48px;opacity:.3;margin-bottom:12px">
                        <circle cx="20" cy="20" r="18" stroke="currentColor" stroke-width="2"/>
                        <path d="M13 20l4 4 10-10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <p><?php esc_html_e( 'Click "Scan Now" to detect cookies on your website.', 'privento' ); ?></p>
                </div>
            </div>
        </div>
    <?php else : ?>
        <?php
        $category_labels = [
            'necessary'   => [ 'label' => __( 'Necessary', 'privento' ),   'class' => 'prv-badge--necessary' ],
            'preferences' => [ 'label' => __( 'Preferences', 'privento' ), 'class' => 'prv-badge--preferences' ],
            'statistics'  => [ 'label' => __( 'Statistics', 'privento' ),  'class' => 'prv-badge--statistics' ],
            'marketing'   => [ 'label' => __( 'Marketing', 'privento' ),   'class' => 'prv-badge--marketing' ],
        ];
        foreach ( $category_labels as $cat => $meta ) :
            if ( empty( $cookies[ $cat ] ) ) continue;
        ?>
        <div class="prv-panel">
            <div class="prv-panel__header">
                <h2 class="prv-panel__title">
                    <span class="prv-badge prv-badge--cat <?php echo esc_attr( $meta['class'] ); ?>"><?php echo esc_html( $meta['label'] ); ?></span>
                    <span class="prv-count-badge"><?php echo count( $cookies[ $cat ] ); ?></span>
                </h2>
            </div>
            <div class="prv-panel__body prv-panel__body--flush">
                <table class="prv-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Cookie Name', 'privento' ); ?></th>
                            <th><?php esc_html_e( 'Service', 'privento' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'privento' ); ?></th>
                            <th><?php esc_html_e( 'Source', 'privento' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $cookies[ $cat ] as $cookie ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $cookie['name'] ); ?></code></td>
                            <td><?php echo esc_html( $cookie['service'] ?? '' ); ?></td>
                            <td><?php echo esc_html( $cookie['description'] ?? '' ); ?></td>
                            <td class="prv-td--small prv-td--muted"><?php echo esc_html( $cookie['source'] ?? '' ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div><!-- #prv-scan-results -->
</div>
