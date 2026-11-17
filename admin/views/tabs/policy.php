<?php
/**
 * Admin tab: Cookie Policy
 *
 * @package Privento
 */

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

$s              = Settings::all();
$policy_page_id = (int) ( $s['policy_page_id'] ?? 0 );
$page           = $policy_page_id ? get_post( $policy_page_id ) : null;
$page_ok        = $page && 'publish' === $page->post_status;
$intro          = $s['policy_intro_text'] ?? '';
$last_generated = $s['policy_last_generated'] ?? '';
?>
<div class="prv-page">
    <div class="prv-page__header">
        <div>
            <h1 class="prv-page__title"><?php esc_html_e( 'Cookie Policy', 'privento' ); ?></h1>
            <p class="prv-page__subtitle"><?php esc_html_e( 'Auto-generate and manage your GDPR-compliant Cookie Policy page', 'privento' ); ?></p>
        </div>
    </div>

    <div class="prv-grid prv-grid--2">

        <!-- Status & Generator -->
        <div class="prv-panel">
            <div class="prv-panel__header">
                <h2 class="prv-panel__title"><?php esc_html_e( 'Policy Page Status', 'privento' ); ?></h2>
            </div>
            <div class="prv-panel__body">

                <div class="prv-status-block <?php echo $page_ok ? 'prv-status-block--ok' : 'prv-status-block--warn'; ?>">
                    <span class="prv-status-block__icon dashicons <?php echo $page_ok ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <div class="prv-status-block__text">
                        <?php if ( $page_ok ) : ?>
                            <strong><?php esc_html_e( 'Cookie Policy is live', 'privento' ); ?></strong><br>
                            <a href="<?php echo esc_url( get_permalink( $policy_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'View page', 'privento' ); ?> ↗</a>
                            &nbsp;·&nbsp;
                            <a href="<?php echo esc_url( (string) get_edit_post_link( $policy_page_id ) ); ?>" target="_blank"><?php esc_html_e( 'Edit in WordPress', 'privento' ); ?></a>
                            <?php if ( $last_generated ) : ?>
                            <br><small><?php printf( esc_html__( 'Last generated: %s', 'privento' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $last_generated ) ) ) ); ?></small>
                            <?php endif; ?>
                        <?php else : ?>
                            <strong><?php esc_html_e( 'No Cookie Policy page yet', 'privento' ); ?></strong><br>
                            <span style="opacity:.7"><?php esc_html_e( 'Click "Generate" to create one automatically.', 'privento' ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="margin-top:16px;">
                    <button type="button" id="privento-generate-policy" class="prv-btn prv-btn--primary" style="width:100%">
                        <span class="dashicons dashicons-update" style="margin-right:6px;line-height:1.4"></span>
                        <?php echo $page_ok ? esc_html__( 'Regenerate Policy Page', 'privento' ) : esc_html__( 'Generate Policy Page', 'privento' ); ?>
                    </button>
                    <p class="prv-hint" style="margin-top:8px"><?php esc_html_e( 'This will scan your site for cookies and create a policy page with the [privento_cookie_policy] shortcode.', 'privento' ); ?></p>
                </div>

                <div class="prv-field" style="margin-top:16px">
                    <label class="prv-label"><?php esc_html_e( 'Shortcode', 'privento' ); ?></label>
                    <div class="privento-shortcode-box" style="display:flex;gap:8px;align-items:center">
                        <code style="flex:1;padding:8px 12px;background:#f3f4f6;border-radius:6px;font-family:monospace;font-size:13px">[privento_cookie_policy]</code>
                        <button type="button" class="prv-btn prv-btn--ghost privento-copy-shortcode" data-copy="[privento_cookie_policy]">
                            <?php esc_html_e( 'Copy', 'privento' ); ?>
                        </button>
                    </div>
                    <p class="prv-hint"><?php esc_html_e( 'Or place this shortcode manually on any page.', 'privento' ); ?></p>
                </div>

            </div>
        </div>

        <!-- Page Settings -->
        <div class="prv-panel">
            <div class="prv-panel__header">
                <h2 class="prv-panel__title"><?php esc_html_e( 'Policy Page Settings', 'privento' ); ?></h2>
            </div>
            <div class="prv-panel__body">
                <form id="prv-policy-form" class="prv-form">

                    <div class="prv-field">
                        <label class="prv-label" for="policy_page_id"><?php esc_html_e( 'Assigned Policy Page', 'privento' ); ?></label>
                        <?php
                        wp_dropdown_pages( array(
                            'name'             => 'policy_page_id',
                            'id'               => 'policy_page_id',
                            'selected'         => $policy_page_id,
                            'show_option_none' => __( '-- Select a page --', 'privento' ),
                            'class'            => 'prv-select',
                        ) );
                        ?>
                        <p class="prv-hint"><?php esc_html_e( 'The page where your cookie policy shortcode is placed.', 'privento' ); ?></p>
                    </div>

                    <div class="prv-field prv-field--inline">
                        <label class="prv-label"><?php esc_html_e( 'Show "Last updated" date', 'privento' ); ?></label>
                        <label class="prv-toggle-field">
                            <input type="checkbox" name="policy_show_date" value="1" <?php checked( $s['policy_show_date'] ?? '1', '1' ); ?>>
                            <span class="prv-toggle-track"></span>
                        </label>
                    </div>

                    <div class="prv-field prv-field--inline">
                        <label class="prv-label"><?php esc_html_e( 'Include revoke consent button', 'privento' ); ?></label>
                        <label class="prv-toggle-field">
                            <input type="checkbox" name="policy_show_revoke" value="1" <?php checked( $s['policy_show_revoke'] ?? '1', '1' ); ?>>
                            <span class="prv-toggle-track"></span>
                        </label>
                    </div>

                    <div style="margin-top:16px">
                        <button type="button" class="prv-btn prv-btn--primary" id="prv-save-policy">
                            <?php esc_html_e( 'Save Settings', 'privento' ); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Intro Text — full width -->
        <div class="prv-panel" style="grid-column: 1 / -1">
            <div class="prv-panel__header">
                <h2 class="prv-panel__title"><?php esc_html_e( 'Intro Text', 'privento' ); ?></h2>
            </div>
            <div class="prv-panel__body">
                <p class="prv-hint"><?php esc_html_e( 'This text appears at the top of your cookie policy page, before the cookie table.', 'privento' ); ?></p>
                <?php
                wp_editor( $intro, 'privento_policy_intro', array(
                    'textarea_name' => 'policy_intro_text',
                    'textarea_rows' => 6,
                    'media_buttons' => false,
                    'teeny'         => true,
                ) );
                ?>
                <div style="margin-top:12px">
                    <button type="button" class="prv-btn prv-btn--primary" id="prv-save-policy-intro">
                        <?php esc_html_e( 'Save Intro Text', 'privento' ); ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
.prv-status-block {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 14px 16px;
    border-radius: 8px;
    border: 1px solid;
}
.prv-status-block--ok   { background: #f0fdf4; border-color: #86efac; color: #14532d; }
.prv-status-block--warn { background: #fffbeb; border-color: #fcd34d; color: #78350f; }
.prv-status-block__icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }
.prv-status-block--ok  .prv-status-block__icon { color: #16a34a; }
.prv-status-block--warn .prv-status-block__icon { color: #d97706; }
.prv-status-block__text { line-height: 1.5; }
.prv-status-block a { color: inherit; font-weight: 600; }
</style>
