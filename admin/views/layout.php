<?php
/**
 * Admin layout — wraps all Privento admin pages.
 *
 * @package Privento
 * @var string $tab  Current tab key
 * @var string $file Path to the tab file to include
 */

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

$current_page = sanitize_key( $_GET['page'] ?? 'privento' );

$nav_items = [
    'privento'               => [ 'icon' => '⊹', 'label' => __( 'Dashboard',        'privento' ) ],
    'privento-design'        => [ 'icon' => '◑', 'label' => __( 'Design',            'privento' ) ],
    'privento-texts'         => [ 'icon' => '✎', 'label' => __( 'Texts',           'privento' ) ],
    'privento-cookies'       => [ 'icon' => '◉', 'label' => __( 'Cookie Scanner',    'privento' ) ],
    'privento-integrations'  => [ 'icon' => '⟡', 'label' => __( 'Integrations',      'privento' ) ],
    'privento-policy'        => [ 'icon' => '☰', 'label' => __( 'Cookie Policy',     'privento' ) ],
    'privento-logs'          => [ 'icon' => '▤', 'label' => __( 'Consent Logs',      'privento' ) ],
    'privento-settings'      => [ 'icon' => '⚙', 'label' => __( 'Settings',          'privento' ) ],
];
?>
<div class="prv-admin">

    <!-- Sidebar -->
    <aside class="prv-sidebar">
        <div class="prv-sidebar__brand">
            <svg class="prv-sidebar__logo" viewBox="0 0 24 24" fill="none">
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" fill="#FE5B00" opacity=".3"/>
                <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z" fill="#FE5B00"/>
                <path d="M9 12l2 2 4-4" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="prv-sidebar__name">Privento</span>
            <span class="prv-sidebar__version"><?php echo esc_html( PRIVENTO_VERSION ); ?></span>
        </div>

        <nav class="prv-sidebar__nav" aria-label="<?php esc_attr_e( 'Privento navigation', 'privento' ); ?>">
            <?php foreach ( $nav_items as $slug => $item ) :
                $is_active = ( $current_page === $slug );
            ?>
            <a href="<?php echo esc_url( menu_page_url( $slug, false ) ); ?>"
               class="prv-nav-item <?php echo $is_active ? 'prv-nav-item--active' : ''; ?>"
               aria-current="<?php echo $is_active ? 'page' : 'false'; ?>">
                <span class="prv-nav-item__icon" aria-hidden="true"><?php echo $item['icon']; // phpcs:ignore ?></span>
                <span class="prv-nav-item__label"><?php echo esc_html( $item['label'] ); ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="prv-sidebar__footer">
            <a class="prv-sidebar__link" href="https://graffic.dk" target="_blank" rel="noopener">
                <?php
                /* translators: %s = graffic.dk */
                printf( esc_html__( 'Made by %s', 'privento' ), 'graffic.dk' );
                ?>
            </a>
        </div>

    </aside>

    <!-- Main content -->
    <main class="prv-main" id="prv-main">
        <?php
        if ( file_exists( $file ) ) {
            include $file;
        } else {
            include PRIVENTO_DIR . 'admin/views/tabs/dashboard.php';
        }
        ?>
    </main>

</div><!-- .prv-admin -->
