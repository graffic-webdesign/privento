<?php
/**
 * Texts & Languages tab.
 *
 * @package Privento
 */

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;

$defaults = Settings::default_texts();
?>
<div class="prv-page">
    <div class="prv-page__header">
        <div>
            <h1 class="prv-page__title"><?php esc_html_e( 'Texts & Languages', 'privento' ); ?></h1>
            <p class="prv-page__subtitle"><?php esc_html_e( 'Customize all visible text in the cookie banner. Leave a field empty to use the translation for the site language.', 'privento' ); ?></p>
        </div>
        <button type="button" class="prv-btn prv-btn--primary" id="prv-save-texts">
            <?php esc_html_e( 'Save Changes', 'privento' ); ?>
        </button>
    </div>

    <form id="prv-texts-form" class="prv-form">

        <div class="prv-grid prv-grid--2">

            <!-- Banner texts -->
            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Banner Content', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <div class="prv-field">
                        <label class="prv-label" for="headline"><?php esc_html_e( 'Headline', 'privento' ); ?></label>
                        <input type="text" name="headline" id="headline"
                               value="<?php echo esc_attr( Settings::stored( 'headline' ) ); ?>"
                               placeholder="<?php echo esc_attr( $defaults['headline'] ); ?>"
                               class="prv-input">
                    </div>
                    <div class="prv-field">
                        <label class="prv-label" for="body_text"><?php esc_html_e( 'Body Text', 'privento' ); ?></label>
                        <textarea name="body_text" id="body_text" rows="4" class="prv-textarea" placeholder="<?php echo esc_attr( $defaults['body_text'] ); ?>"><?php echo esc_textarea( Settings::stored( 'body_text' ) ); ?></textarea>
                        <p class="prv-hint"><?php esc_html_e( 'Basic HTML allowed: <a>, <strong>, <em>', 'privento' ); ?></p>
                    </div>
                    <div class="prv-field prv-field--inline">
                        <label class="prv-label" for="policy_link_enabled"><?php esc_html_e( 'Show link to the cookie policy', 'privento' ); ?></label>
                        <label class="prv-toggle-field">
                            <input type="checkbox" name="policy_link_enabled" id="policy_link_enabled" value="1" <?php checked( Settings::get( 'policy_link_enabled' ), '1' ); ?>>
                            <span class="prv-toggle-track"></span>
                        </label>
                    </div>
                    <div class="prv-field">
                        <label class="prv-label" for="policy_link_text"><?php esc_html_e( 'Cookie policy link', 'privento' ); ?></label>
                        <input type="text" name="policy_link_text" id="policy_link_text"
                               value="<?php echo esc_attr( Settings::stored( 'policy_link_text' ) ); ?>"
                               placeholder="<?php echo esc_attr( $defaults['policy_link_text'] ); ?>"
                               class="prv-input">
                        <p class="prv-hint"><?php esc_html_e( 'Shown after the body text and in the settings window, linking to the Cookie Policy page.', 'privento' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title"><?php esc_html_e( 'Button Labels', 'privento' ); ?></h2>
                </div>
                <div class="prv-panel__body">
                    <?php
                    $buttons = [
                        'btn_accept_all' => __( 'Accept All Button', 'privento' ),
                        'btn_reject_all' => __( 'Reject All Button', 'privento' ),
                        'btn_settings'   => __( 'Manage Preferences Button', 'privento' ),
                        'btn_save'       => __( 'Save Preferences Button', 'privento' ),
                    ];
                    foreach ( $buttons as $key => $label ) :
                    ?>
                    <div class="prv-field">
                        <label class="prv-label" for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>"
                               value="<?php echo esc_attr( Settings::stored( $key ) ); ?>"
                               placeholder="<?php echo esc_attr( $defaults[ $key ] ); ?>"
                               class="prv-input">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Categories -->
            <?php
            $categories = [
                'necessary'   => __( 'Necessary', 'privento' ),
                'preferences' => __( 'Preferences', 'privento' ),
                'statistics'  => __( 'Statistics', 'privento' ),
                'marketing'   => __( 'Marketing', 'privento' ),
            ];
            foreach ( $categories as $cat => $cat_label ) :
                $name_key = 'cat_' . $cat . '_name';
                $desc_key = 'cat_' . $cat . '_desc';
            ?>
            <div class="prv-panel">
                <div class="prv-panel__header">
                    <h2 class="prv-panel__title">
                        <span class="prv-badge prv-badge--cat prv-badge--<?php echo esc_attr( $cat ); ?>"><?php echo esc_html( $cat_label ); ?></span>
                        <?php esc_html_e( 'Category', 'privento' ); ?>
                    </h2>
                </div>
                <div class="prv-panel__body">
                    <div class="prv-field">
                        <label class="prv-label"><?php esc_html_e( 'Name', 'privento' ); ?></label>
                        <input type="text" name="<?php echo esc_attr( $name_key ); ?>"
                               value="<?php echo esc_attr( Settings::stored( $name_key ) ); ?>"
                               placeholder="<?php echo esc_attr( $defaults[ $name_key ] ); ?>"
                               class="prv-input">
                    </div>
                    <div class="prv-field">
                        <label class="prv-label"><?php esc_html_e( 'Description', 'privento' ); ?></label>
                        <textarea name="<?php echo esc_attr( $desc_key ); ?>" rows="3" class="prv-textarea" placeholder="<?php echo esc_attr( $defaults[ $desc_key ] ); ?>"><?php echo esc_textarea( Settings::stored( $desc_key ) ); ?></textarea>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
    </form>
</div>
