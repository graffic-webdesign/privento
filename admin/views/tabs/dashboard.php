<?php
/**
 * Dashboard tab — compliance overview.
 *
 * @package Privento
 */

defined( 'ABSPATH' ) || exit;

use Privento\Consent\Logger;
use Privento\Consent\Manager;
use Privento\Core\Settings;
use Privento\Scanner\CookieScanner;

$logger  = new Logger();
$scanner = new CookieScanner();
$s       = Settings::all();

$window    = 30;
$stats     = $logger->get_stats( $window );
$previous  = $logger->get_stats( $window, $window );
$trend     = $logger->get_daily_counts( 14 );
$cat_rates = $logger->get_category_acceptance( $window );

$cookies    = $scanner->get_cached_cookies();
$by_category = $scanner->get_cookies_by_category();
$last_scan   = $scanner->get_last_scan();

$last_scan_label = $last_scan
    ? wp_date( get_option( 'date_format' ), strtotime( $last_scan ) )
    : __( 'Never', 'privento' );

$policy_page = ! empty( $s['policy_page_id'] ) ? get_post( (int) $s['policy_page_id'] ) : null;

$positions = [
    'bottom_bar'  => __( 'Bottom Bar', 'privento' ),
    'modal'       => __( 'Center Modal', 'privento' ),
    'corner'      => __( 'Corner Right', 'privento' ),
    'corner_left' => __( 'Corner Left', 'privento' ),
    'floating'    => __( 'Top Banner', 'privento' ),
];

/*
 * Two ways to satisfy the revoke requirement: the floating button, or a
 * [privento_revoke] link placed anywhere. The shortcode reports its own
 * renders, so a link in a footer, menu or page builder counts too.
 */
$floating_on = '1' === ( $s['floating_btn_enabled'] ?? '1' );
// Only look for the link when the button is off — otherwise the answer cannot change.
$revoke_link = ! $floating_on && Manager::revoke_link_in_use();

if ( $floating_on ) {
    $revoke_meta = __( 'Floating button', 'privento' );
} elseif ( $revoke_link ) {
    $revoke_meta = __( 'Shortcode link', 'privento' );
} else {
    $revoke_meta = __( 'Off', 'privento' );
}

/*
 * The compliance requirements. The first unmet one is promoted into the callout
 * above the list, so `why`, `cta` and `url` only matter for the ones that can fail.
 */
$requirements = [
    [
        'label' => __( 'Banner is active', 'privento' ),
        'meta'  => $positions[ $s['banner_position'] ?? 'bottom_bar' ] ?? '',
        'ok'    => true,
    ],
    [
        'label' => __( 'Google Consent Mode v2 enabled', 'privento' ),
        'meta'  => Settings::gtm_id() ?: ( Settings::ga4_id() ?: __( 'No tag ID', 'privento' ) ),
        'ok'    => Settings::is_gcm_enabled(),
        'why'   => __( 'Without it, Google\'s tags keep their default behaviour no matter what the visitor chose.', 'privento' ),
        'cta'   => __( 'Set up', 'privento' ),
        'url'   => menu_page_url( 'privento-integrations', false ),
    ],
    [
        'label' => __( 'Cookie Policy page exists', 'privento' ),
        'meta'  => $policy_page ? '/' . $policy_page->post_name : __( 'Not created', 'privento' ),
        'ok'    => (bool) $policy_page,
        'why'   => __( 'Visitors must be able to read what each cookie is for before they decide.', 'privento' ),
        'cta'   => __( 'Create', 'privento' ),
        'url'   => menu_page_url( 'privento-policy', false ),
    ],
    [
        'label' => __( 'Cookie scan completed', 'privento' ),
        'meta'  => $last_scan_label,
        'ok'    => (bool) $last_scan,
        'why'   => __( 'The policy can only list the cookies a scan has actually found.', 'privento' ),
        'cta'   => __( 'Scan now', 'privento' ),
        'url'   => menu_page_url( 'privento-cookies', false ),
    ],
    [
        'label' => __( 'Consent logs recording', 'privento' ),
        /* translators: %s: number of stored consent records. */
        'meta'  => sprintf( __( '%s records', 'privento' ), number_format_i18n( $logger->get_total() ) ),
        'ok'    => $logger->get_total() > 0,
        'why'   => __( 'A log is your documentation that consent was given, and when.', 'privento' ),
        'cta'   => __( 'Open logs', 'privento' ),
        'url'   => menu_page_url( 'privento-logs', false ),
    ],
    [
        'label' => __( 'Revoke option available', 'privento' ),
        'meta'  => $revoke_meta,
        'ok'    => $floating_on || $revoke_link,
        'why'   => __( 'Visitors must be able to withdraw consent as easily as they gave it — either through the floating button or a [privento_revoke] link you place yourself.', 'privento' ),
        'cta'   => __( 'Turn on', 'privento' ),
        'url'   => menu_page_url( 'privento-design', false ),
    ],
];

$met     = count( array_filter( array_column( $requirements, 'ok' ) ) );
$total_r = count( $requirements );
$score   = (int) round( $met / $total_r * 100 );

$open = array_values( array_filter( $requirements, static fn( $r ) => ! $r['ok'] ) );
$next = $open[0] ?? null;

$trend_max = max( 1, max( $trend ) );
$trend_days = array_keys( $trend );

$delta = null;
if ( $previous['total'] > 0 && $stats['total'] > 0 ) {
    $delta = round( $stats['accept_rate'] - $previous['accept_rate'], 1 );
}

$category_colors = [
    'necessary'   => '#0f5c43',
    'preferences' => '#3f8f6e',
    'statistics'  => '#5fa286',
    'marketing'   => '#7fb39b',
];

$share = static fn( int $part ) => $stats['total'] > 0 ? round( $part / $stats['total'] * 100, 1 ) : 0;
?>
<div class="prv-dash">

    <header class="prv-dash__head">
        <div>
            <span class="prv-dash__eyebrow">GDPR / ePrivacy</span>
            <h1 class="prv-dash__title"><?php esc_html_e( 'Overview', 'privento' ); ?></h1>
            <p class="prv-dash__lede">
                <?php
                /* translators: %d: number of days the figures cover. */
                printf( esc_html__( 'Your compliance status for the last %d days — and what is still missing.', 'privento' ), (int) $window );
                ?>
            </p>
        </div>
        <div class="prv-dash__head-actions">
            <a class="prv-dash__btn" href="<?php echo esc_url( menu_page_url( 'privento-logs', false ) ); ?>">
                <?php esc_html_e( 'Export log', 'privento' ); ?>
            </a>
            <a class="prv-dash__btn prv-dash__btn--solid" href="<?php echo esc_url( home_url() ); ?>" target="_blank" rel="noopener">
                <?php esc_html_e( 'Preview banner', 'privento' ); ?>
            </a>
        </div>
    </header>

    <section class="prv-dash__row">

        <!-- Compliance score -->
        <div class="prv-dash__card prv-dash__card--dark">
            <div class="prv-dash__score-head">
                <div>
                    <span class="prv-dash__eyebrow prv-dash__eyebrow--dim"><?php esc_html_e( 'Compliance score', 'privento' ); ?></span>
                    <div class="prv-dash__score">
                        <span class="prv-dash__score-num"><?php echo esc_html( number_format_i18n( $score ) ); ?></span>
                        <span class="prv-dash__score-den">/100</span>
                    </div>
                    <p class="prv-dash__score-note">
                        <?php
                        if ( $next ) {
                            printf(
                                /* translators: 1: requirements met, 2: total requirements. */
                                esc_html__( '%1$d of %2$d requirements are met. The open one is holding the score down.', 'privento' ),
                                (int) $met,
                                (int) $total_r
                            );
                        } else {
                            esc_html_e( 'Every requirement is met.', 'privento' );
                        }
                        ?>
                    </p>
                </div>
                <span class="prv-dash__pill <?php echo Settings::is_gcm_enabled() ? '' : 'is-off'; ?>">
                    <span class="prv-dash__pill-dot"></span>
                    <?php echo Settings::is_gcm_enabled()
                        ? esc_html__( 'Consent Mode v2 active', 'privento' )
                        : esc_html__( 'Consent Mode v2 off', 'privento' ); ?>
                </span>
            </div>

            <div class="prv-dash__bar" role="img"
                aria-label="<?php echo esc_attr( sprintf( /* translators: 1: met, 2: total. */ __( '%1$d of %2$d requirements met', 'privento' ), $met, $total_r ) ); ?>">
                <?php if ( $met ) : ?>
                    <span class="prv-dash__bar-fill" style="flex:<?php echo (int) $met; ?>"></span>
                <?php endif; ?>
                <?php if ( $total_r - $met ) : ?>
                    <span class="prv-dash__bar-rest" style="flex:<?php echo (int) ( $total_r - $met ); ?>"></span>
                <?php endif; ?>
            </div>

            <div class="prv-dash__tiles">
                <div class="prv-dash__tile">
                    <span class="prv-dash__tile-value"><?php echo esc_html( number_format_i18n( $stats['accept_rate'], 1 ) ); ?>%</span>
                    <span class="prv-dash__tile-label"><?php esc_html_e( 'Accept rate', 'privento' ); ?></span>
                    <span class="prv-dash__tile-meta<?php echo ( null !== $delta && $delta > 0 ) ? ' is-up' : ''; ?>">
                        <?php
                        if ( null === $delta ) {
                            esc_html_e( 'No earlier period', 'privento' );
                        } else {
                            printf(
                                '%s %s pp',
                                $delta > 0 ? '▲' : ( $delta < 0 ? '▼' : '—' ),
                                esc_html( number_format_i18n( abs( $delta ), 1 ) )
                            );
                        }
                        ?>
                    </span>
                </div>
                <div class="prv-dash__tile">
                    <span class="prv-dash__tile-value"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
                    <span class="prv-dash__tile-label"><?php esc_html_e( 'Total consents', 'privento' ); ?></span>
                    <span class="prv-dash__tile-meta">
                        <?php
                        /* translators: %d: number of days. */
                        printf( esc_html__( '%d days', 'privento' ), (int) $window );
                        ?>
                    </span>
                </div>
                <div class="prv-dash__tile">
                    <span class="prv-dash__tile-value"><?php echo esc_html( number_format_i18n( count( $cookies ) ) ); ?></span>
                    <span class="prv-dash__tile-label"><?php esc_html_e( 'Cookies detected', 'privento' ); ?></span>
                    <span class="prv-dash__tile-meta"><?php echo esc_html( $last_scan_label ); ?></span>
                </div>
            </div>
        </div>

        <!-- Consents per day -->
        <div class="prv-dash__card">
            <div class="prv-dash__card-head">
                <h2 class="prv-dash__card-title"><?php esc_html_e( 'Consents per day', 'privento' ); ?></h2>
                <span class="prv-dash__eyebrow"><?php esc_html_e( 'Last 14 days', 'privento' ); ?></span>
            </div>

            <?php if ( array_sum( $trend ) > 0 ) : ?>
                <div class="prv-dash__chart">
                    <?php
                    $i = 0;
                    foreach ( $trend as $day => $count ) :
                        $height = round( $count / $trend_max * 100 );
                        $recent = $i >= count( $trend ) - 3;
                        ++$i;
                        ?>
                        <span class="prv-dash__bar-col">
                            <span class="prv-dash__bar-bit<?php echo $recent ? ' is-recent' : ''; ?>"
                                style="height:<?php echo esc_attr( $height ); ?>%"
                                title="<?php echo esc_attr( $day . ' — ' . number_format_i18n( $count ) ); ?>"></span>
                        </span>
                    <?php endforeach; ?>
                </div>
                <div class="prv-dash__axis">
                    <span><?php echo esc_html( wp_date( 'd/m', strtotime( $trend_days[0] ) ) ); ?></span>
                    <span><?php echo esc_html( wp_date( 'd/m', strtotime( $trend_days[ (int) floor( count( $trend_days ) / 2 ) ] ) ) ); ?></span>
                    <span><?php echo esc_html( wp_date( 'd/m', strtotime( end( $trend_days ) ) ) ); ?></span>
                </div>
            <?php else : ?>
                <p class="prv-dash__empty"><?php esc_html_e( 'No consents recorded in this period yet.', 'privento' ); ?></p>
            <?php endif; ?>

            <div class="prv-dash__substats">
                <div>
                    <span class="prv-dash__substat-value"><?php echo esc_html( number_format_i18n( $share( $stats['accept_all'] ), 1 ) ); ?>%</span>
                    <span class="prv-dash__substat-label"><?php esc_html_e( 'Accepted all', 'privento' ); ?></span>
                </div>
                <div>
                    <span class="prv-dash__substat-value"><?php echo esc_html( number_format_i18n( $share( $stats['custom'] ), 1 ) ); ?>%</span>
                    <span class="prv-dash__substat-label"><?php esc_html_e( 'Partial consent', 'privento' ); ?></span>
                </div>
                <div>
                    <span class="prv-dash__substat-value"><?php echo esc_html( number_format_i18n( $share( $stats['reject_all'] ), 1 ) ); ?>%</span>
                    <span class="prv-dash__substat-label"><?php esc_html_e( 'Rejected', 'privento' ); ?></span>
                </div>
            </div>
        </div>
    </section>

    <section class="prv-dash__row prv-dash__row--top">

        <!-- Requirements -->
        <div class="prv-dash__card prv-dash__card--flush">
            <div class="prv-dash__card-head prv-dash__card-head--pad">
                <h2 class="prv-dash__card-title"><?php esc_html_e( 'Compliance requirements', 'privento' ); ?></h2>
                <span class="prv-dash__eyebrow">
                    <?php
                    /* translators: 1: requirements met, 2: total requirements. */
                    printf( esc_html__( '%1$d / %2$d met', 'privento' ), (int) $met, (int) $total_r );
                    ?>
                </span>
            </div>

            <div class="prv-dash__callout<?php echo $next ? '' : ' is-clear'; ?>">
                <div class="prv-dash__callout-body">
                    <span class="prv-dash__eyebrow prv-dash__eyebrow--accent">
                        <?php echo $next
                            ? esc_html__( 'Needs action', 'privento' )
                            : esc_html__( 'All clear', 'privento' ); ?>
                    </span>
                    <div class="prv-dash__callout-title">
                        <?php echo esc_html( $next ? $next['label'] : __( 'Nothing is waiting on you', 'privento' ) ); ?>
                    </div>
                    <p class="prv-dash__callout-note">
                        <?php echo esc_html( $next
                            ? ( $next['why'] ?? '' )
                            : __( 'Every requirement below is met. Re-scan after you add or remove tracking.', 'privento' ) ); ?>
                    </p>
                </div>
                <?php if ( $next && ! empty( $next['url'] ) ) : ?>
                    <a class="prv-dash__callout-btn" href="<?php echo esc_url( $next['url'] ); ?>">
                        <?php echo esc_html( $next['cta'] ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <div class="prv-dash__reqs">
                <?php foreach ( $requirements as $index => $req ) : ?>
                    <div class="prv-dash__req<?php echo $req['ok'] ? '' : ' is-open'; ?>">
                        <span class="prv-dash__req-num"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
                        <span class="prv-dash__req-label"><?php echo esc_html( $req['label'] ); ?></span>
                        <span class="prv-dash__req-meta"><?php echo esc_html( $req['meta'] ); ?></span>
                        <span class="prv-dash__req-mark" aria-hidden="true"><?php echo $req['ok'] ? '&#10003;' : '!'; ?></span>
                        <span class="screen-reader-text">
                            <?php echo $req['ok']
                                ? esc_html__( 'Met', 'privento' )
                                : esc_html__( 'Not met', 'privento' ); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="prv-dash__stack">

            <!-- Cookie categories -->
            <div class="prv-dash__card">
                <h2 class="prv-dash__card-title"><?php esc_html_e( 'Cookie categories', 'privento' ); ?></h2>
                <div class="prv-dash__cats">
                    <?php foreach ( Settings::get_categories() as $key => $category ) :
                        $pct = $cat_rates[ $key ] ?? 0;
                        ?>
                        <div class="prv-dash__cat">
                            <span class="prv-dash__cat-name"><?php echo esc_html( $category['name'] ); ?></span>
                            <span class="prv-dash__cat-count"><?php echo esc_html( number_format_i18n( count( $by_category[ $key ] ?? [] ) ) ); ?></span>
                            <span class="prv-dash__cat-track">
                                <span class="prv-dash__cat-fill"
                                    style="width:<?php echo esc_attr( $pct ); ?>%;background:<?php echo esc_attr( $category_colors[ $key ] ?? '#0f5c43' ); ?>"></span>
                            </span>
                            <span class="prv-dash__cat-pct"><?php echo esc_html( number_format_i18n( $pct ) ); ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="prv-dash__cats-note">
                    <?php esc_html_e( 'Count is cookies found by the scanner; the bar is how often visitors granted that category.', 'privento' ); ?>
                </p>
            </div>

            <!-- Quick actions -->
            <div class="prv-dash__card">
                <h2 class="prv-dash__card-title"><?php esc_html_e( 'Quick actions', 'privento' ); ?></h2>
                <div class="prv-dash__actions">
                    <?php
                    $actions = [
                        [
                            'title' => __( 'Customize banner', 'privento' ),
                            'sub'   => __( 'Colors, fonts, placement', 'privento' ),
                            'url'   => menu_page_url( 'privento-design', false ),
                        ],
                        [
                            'title' => __( 'Scan cookies again', 'privento' ),
                            /* translators: %s: date of the last scan. */
                            'sub'   => sprintf( __( 'Last scan: %s', 'privento' ), $last_scan_label ),
                            'url'   => menu_page_url( 'privento-cookies', false ),
                        ],
                        [
                            'title' => __( 'Export consent log', 'privento' ),
                            'sub'   => __( 'CSV for compliance audits', 'privento' ),
                            'url'   => menu_page_url( 'privento-logs', false ),
                        ],
                        [
                            'title' => __( 'Configure integrations', 'privento' ),
                            'sub'   => __( 'GTM, GA4, Meta Pixel', 'privento' ),
                            'url'   => menu_page_url( 'privento-integrations', false ),
                        ],
                    ];
                    foreach ( $actions as $action ) :
                        ?>
                        <a class="prv-dash__action" href="<?php echo esc_url( $action['url'] ); ?>">
                            <span class="prv-dash__action-body">
                                <span class="prv-dash__action-title"><?php echo esc_html( $action['title'] ); ?></span>
                                <span class="prv-dash__action-sub"><?php echo esc_html( $action['sub'] ); ?></span>
                            </span>
                            <span class="prv-dash__action-arrow" aria-hidden="true">&rarr;</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</div>
