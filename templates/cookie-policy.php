<?php
/**
 * Cookie Policy template — rendered via [privento_cookie_policy] shortcode.
 *
 * @package Privento
 */

defined( 'ABSPATH' ) || exit;

use Privento\Scanner\CookieScanner;
use Privento\Core\Settings;

$scanner   = new CookieScanner();
$by_cat    = $scanner->get_cookies_by_category();
$s         = Settings::all();
$cats      = Settings::get_categories();
$site_name = get_bloginfo( 'name' );
$site_url  = home_url();

$generated   = ! empty( $s['policy_last_generated'] )
    ? wp_date( 'F Y', strtotime( $s['policy_last_generated'] ) )
    : wp_date( 'F Y' );
$show_revoke = '0' !== ( $s['policy_show_revoke'] ?? '1' );
$intro_text  = $s['policy_intro_text'] ?? '';
$policy_pid  = (int) ( $s['policy_page_id'] ?? 0 );
$cat_number  = 0;

$cat_badge_colors = [
    'necessary'   => '#2b6b3e',
    'statistics'  => '#1a6b8a',
    'marketing'   => '#8b3a3a',
    'preferences' => '#5a4a8a',
];
?>
<div class="prv-policy">

    <!-- Header -->
    <?php if ( '0' !== ( $s['policy_show_date'] ?? '1' ) ) : ?>
    <p class="prv-policy__meta">
        <?php printf(
            esc_html__( 'Last updated: %s · Version 1.0', 'privento' ),
            esc_html( $generated )
        ); ?>
    </p>
    <?php endif; ?>

    <?php if ( ! empty( $intro_text ) ) : ?>
    <div class="prv-policy__intro">
        <?php echo wp_kses_post( $intro_text ); ?>
    </div>
    <?php else : ?>
    <p><?php printf(
        esc_html__( '%s uses cookies and similar technologies to keep the website working, analyse how it is used and improve your experience. This cookie policy describes which cookies we use, what they are used for, and how you manage your preferences.', 'privento' ),
        esc_html( $site_name )
    ); ?></p>
    <?php endif; ?>

    <?php if ( $s['gcm_enabled'] ?? '0' ) : ?>
    <div class="prv-policy__notice">
        <?php printf(
            esc_html__( 'We use Google Consent Mode v2, which ensures that no analytics cookies are set before you have actively accepted them. You can change your choices at any time via the "Cookie settings" button at the bottom of the page.', 'privento' )
        ); ?>
    </div>
    <?php endif; ?>

    <!-- What are cookies -->
    <h2><?php esc_html_e( 'What are cookies?', 'privento' ); ?></h2>
    <p><?php esc_html_e( 'Cookies are small text files stored in your browser when you visit a website. They are used to remember information about your visit, such as your preferences or whether you have accepted cookies, and can make your experience more personal and seamless.', 'privento' ); ?></p>
    <p><?php esc_html_e( 'Cookies do not contain personal information such as your name or email address, and cannot transfer viruses or other software.', 'privento' ); ?></p>

    <!-- Which cookies -->
    <h2><?php esc_html_e( 'Which cookies do we use?', 'privento' ); ?></h2>

    <?php foreach ( $cats as $key => $cat ) :
        $cat_number++;
        $cat_cookies = $by_cat[ $key ] ?? [];
        $badge_color = $cat_badge_colors[ $key ] ?? '#555';
    ?>
    <h3 class="prv-policy__cat-heading">
        <?php echo esc_html( $cat_number . '. ' . $cat['name'] . ' ' ); ?>
        <span class="prv-policy__badge" style="background:<?php echo esc_attr( $badge_color ); ?>">
            <?php echo esc_html( $cat['name'] ); ?>
        </span>
    </h3>
    <p><?php echo esc_html( $cat['desc'] ); ?></p>
    <?php if ( 'necessary' === $key ) : ?>
    <p><small><?php esc_html_e( 'These cookies are strictly necessary for the website to work correctly. They cannot be disabled and do not require your consent under the ePrivacy Directive.', 'privento' ); ?></small></p>
    <?php else : ?>
    <p><small><?php esc_html_e( 'Only set after your consent.', 'privento' ); ?></small></p>
    <?php endif; ?>

    <div class="prv-policy__table-wrap">
        <table class="prv-policy__table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Name', 'privento' ); ?></th>
                    <th><?php esc_html_e( 'Provider', 'privento' ); ?></th>
                    <th><?php esc_html_e( 'Purpose', 'privento' ); ?></th>
                    <th><?php esc_html_e( 'Expiry', 'privento' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $cat_cookies ) ) : ?>
                <?php foreach ( $cat_cookies as $cookie ) : ?>
                <tr>
                    <td><code><?php echo esc_html( $cookie['name'] ); ?></code></td>
                    <td><?php echo esc_html( $cookie['service'] ?? '' ); ?></td>
                    <td><?php echo esc_html( $cookie['description'] ?? '' ); ?></td>
                    <td><?php echo esc_html( $cookie['duration'] ?? '–' ); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php else : ?>
                <tr>
                    <td colspan="4"><em><?php esc_html_e( 'No cookies are currently in use', 'privento' ); ?></em></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

    <!-- Google Consent Mode v2 section -->
    <?php if ( $s['gcm_enabled'] ?? '0' ) : ?>
    <h2><?php esc_html_e( 'Google Consent Mode v2', 'privento' ); ?></h2>
    <p><?php esc_html_e( 'We use Google Consent Mode v2, which lets Google adapt the behaviour of Google tags to your consent. If you reject statistics cookies, Google Analytics uses modelling to estimate aggregate figures without setting cookies or using personally identifying data.', 'privento' ); ?></p>
    <?php endif; ?>

    <!-- How to manage cookies -->
    <h2><?php esc_html_e( 'How to manage your cookies', 'privento' ); ?></h2>
    <p><?php printf(
        /* translators: %d = number of days consent is stored */
        esc_html__( 'You can change your cookie preferences at any time using the button below. Your choice is stored for %d days, after which we ask you to confirm your preferences again.', 'privento' ),
        (int) Settings::get_consent_expiry_days()
    ); ?></p>

    <?php if ( $show_revoke ) : ?>
    <p>
        <a href="#" class="privento-revoke-link prv-policy__open-btn">
            <?php esc_html_e( 'Open cookie settings', 'privento' ); ?>
        </a>
    </p>
    <?php endif; ?>

    <h3><?php esc_html_e( 'Reject or delete cookies in your browser', 'privento' ); ?></h3>
    <p><?php esc_html_e( 'You can also manage cookies directly in your browser. Note that disabling necessary cookies can affect how the website works:', 'privento' ); ?></p>
    <ul>
        <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener">Google Chrome</a></li>
        <li><a href="https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox" target="_blank" rel="noopener">Mozilla Firefox</a></li>
        <li><a href="https://support.apple.com/en-gb/guide/safari/sfri11471/mac" target="_blank" rel="noopener">Apple Safari</a></li>
        <li><a href="https://support.microsoft.com/en-us/windows/delete-and-manage-cookies-168dab11-0753-043d-7c16-ede5947fc64d" target="_blank" rel="noopener">Microsoft Edge</a></li>
    </ul>

    <h3><?php esc_html_e( 'Opt out of Google Analytics', 'privento' ); ?></h3>
    <p><?php echo wp_kses_post( __( 'You can install the <a href="https://tools.google.com/dlpage/gaoptout" target="_blank" rel="noopener">Google Analytics Opt-out Browser Add-on</a> to prevent data from being sent to Google Analytics on all websites.', 'privento' ) ); ?></p>

    <!-- Changes -->
    <h2><?php esc_html_e( 'Changes to this cookie policy', 'privento' ); ?></h2>
    <p><?php esc_html_e( 'We reserve the right to update this cookie policy. Material changes result in a new cookie version, and you will be asked to renew your consent. The date of the latest update is shown at the top of this page.', 'privento' ); ?></p>



</div>

<style>
.prv-policy { max-width: 780px; line-height: 1.7; }
.prv-policy h2 { font-size: 1.35em; margin: 2em 0 .5em; }
.prv-policy h3 { font-size: 1.1em; margin: 1.5em 0 .4em; }
.prv-policy p, .prv-policy ul { margin: 0 0 1em; }
.prv-policy ul { padding-left: 1.4em; }
.prv-policy ul li { margin-bottom: .25em; }
.prv-policy a { color: #1a6b4a; }
.prv-policy a:hover { color: #1b5f8f; }

.prv-policy__meta {
    color: #6b7280; font-size: .85em; margin-bottom: 1.5em;
}
.prv-policy__intro { margin-bottom: 1.5em; }

.prv-policy__notice {
    background: #f0fdf4; border-left: 4px solid #1a6b4a;
    padding: 12px 16px; border-radius: 0 6px 6px 0;
    margin: 1em 0 1.5em; font-size: .92em; color: #14532d;
}

.prv-policy__cat-heading {
    display: flex; align-items: center; gap: 10px;
    font-size: 1.1em; margin: 1.8em 0 .5em;
}
.prv-policy__badge {
    display: inline-block; padding: 2px 10px;
    border-radius: 999px; font-size: .72em; font-weight: 700;
    color: #fff; letter-spacing: .03em; vertical-align: middle;
    text-transform: uppercase; flex-shrink: 0;
}

.prv-policy__table-wrap { overflow-x: auto; margin: .75em 0 1.5em; border-radius: 8px; border: 1px solid #e5e7eb; }
.prv-policy__table { width: 100%; border-collapse: collapse; font-size: .9em; }
.prv-policy__table th,
.prv-policy__table td { padding: 10px 14px; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
.prv-policy__table tr:last-child td { border-bottom: none; }
.prv-policy__table thead th { background: #f9fafb; font-weight: 700; color: #374151; }
.prv-policy__table code { font-size: .85em; background: #f3f4f6; padding: 1px 5px; border-radius: 3px; }

.prv-policy__open-btn {
    display: inline-block;
    background: #1a6b4a; color: #fff !important;
    padding: 10px 20px; border-radius: 8px;
    text-decoration: none !important; font-weight: 600;
    margin: .5em 0 1em;
}
.prv-policy__open-btn:hover { background: #1b5f8f !important; color: #fff !important; }
</style>
