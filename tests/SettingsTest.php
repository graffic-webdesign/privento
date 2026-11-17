<?php
/**
 * Banner texts follow the site language unless the site owner edited them.
 */

use Privento\Core\Installer;
use Privento\Core\Settings;

// An empty stored text falls back to the translation.
t_reset();
$GLOBALS['wp_translations'] = [ 'Accept all' => 'Accepter alle' ];
t_assert( 'Accepter alle' === Settings::get( 'btn_accept_all' ), 'empty_text_uses_translation' );
t_assert( 'Reject all' === Settings::get( 'btn_reject_all' ), 'empty_text_without_translation_uses_english' );
t_assert( 'Necessary' === Settings::get_categories()['necessary']['name'], 'categories_are_resolved' );

// A text the owner typed wins over the translation.
t_reset();
$GLOBALS['wp_translations']                 = [ 'Accept all' => 'Accepter alle' ];
$GLOBALS['wp_options']['privento_settings'] = [ 'btn_accept_all' => 'OK' ];
t_assert( 'OK' === Settings::get( 'btn_accept_all' ), 'edited_text_wins' );
t_assert( 'OK' === Settings::stored( 'btn_accept_all' ) && '' === Settings::stored( 'headline' ), 'stored_returns_raw_value' );

// Upgrade: a stored text identical to what the site language would show anyway
// is blanked, so it follows the language from now on.
t_reset();
$GLOBALS['wp_translations']                   = [ 'We use cookies' => 'Vi bruger cookies' ];
$GLOBALS['wp_options']['privento_db_version'] = '1.6.2';
$GLOBALS['wp_options']['privento_settings']   = [ 'headline' => 'Vi bruger cookies', 'btn_save' => 'Gem mit valg' ];
Installer::maybe_upgrade();
t_assert( '' === $GLOBALS['wp_options']['privento_settings']['headline'], 'migration_blanks_text_equal_to_locale_default' );
t_assert( 'Vi bruger cookies' === Settings::get( 'headline' ), 'migration_keeps_what_the_visitor_sees' );
t_assert( 'Gem mit valg' === Settings::get( 'btn_save' ), 'migration_keeps_edited_text' );

// Upgrade on a site whose language has no such translation: blanking would
// change the banner, so the stored text stays.
t_reset();
$GLOBALS['wp_options']['privento_db_version'] = '1.6.2';
$GLOBALS['wp_options']['privento_settings']   = [ 'headline' => 'Vi bruger cookies' ];
Installer::maybe_upgrade();
t_assert( 'Vi bruger cookies' === $GLOBALS['wp_options']['privento_settings']['headline'], 'migration_keeps_text_that_differs_from_locale_default' );

// The migration runs once per version.
$GLOBALS['wp_options']['privento_settings'] = 'untouched';
Installer::maybe_upgrade();
t_assert( 'untouched' === $GLOBALS['wp_options']['privento_settings'], 'migration_runs_once' );

// LEGACY_DA covers every text key — the translation check in bin/i18n.py relies on it.
t_assert(
    [] === array_diff( Settings::TEXT_KEYS, array_keys( Installer::LEGACY_DA ) ),
    'legacy_danish_defaults_cover_every_text_key'
);

// The first request after an update may come from an administrator whose
// profile language differs from the site's. The site language decides.
t_reset();
$GLOBALS['wp_site_translations']              = [ 'We use cookies' => 'Vi bruger cookies' ];
$GLOBALS['wp_options']['privento_db_version'] = '1.6.2';
$GLOBALS['wp_options']['privento_settings']   = [ 'headline' => 'Vi bruger cookies' ];
Installer::maybe_upgrade();
t_assert( '' === $GLOBALS['wp_options']['privento_settings']['headline'], 'migration_uses_site_language_not_admin_language' );
t_assert( [] === $GLOBALS['wp_translations'], 'migration_restores_the_admin_language' );
unset( $GLOBALS['wp_site_translations'] );

// Clearing a field in the Texts tab must store '' (= follow the language),
// not drop the key and leave the old text in place.
$clean = t_call_private( new \Privento\Admin\Ajax(), 'sanitize_settings', [ 'headline' => '', 'btn_save' => ' Gem ' ] );
t_assert( [ 'headline' => '', 'btn_save' => 'Gem' ] === $clean, 'saving_empty_text_stores_empty' );

// Saving another tab must not write the resolved texts back into the option —
// that would freeze the banner in the saving administrator's language.
t_reset();
$GLOBALS['wp_translations']                 = [ 'We use cookies' => 'Vi bruger cookies' ];
$GLOBALS['wp_options']['privento_settings'] = [ 'btn_save' => 'Gem mit valg' ];
Settings::get( 'headline' ); // Warm the cache, as any admin page does.
Settings::merge_update( [ 'color_bg' => '#000000' ] );
t_assert(
    [ 'btn_save' => 'Gem mit valg', 'color_bg' => '#000000' ] === $GLOBALS['wp_options']['privento_settings'],
    'saving_another_tab_stores_only_what_was_sent'
);
t_assert( '#000000' === Settings::get( 'color_bg' ) && 'Vi bruger cookies' === Settings::get( 'headline' ), 'saved_value_is_readable_afterwards' );

// A site running in another language than Danish, still showing the Danish
// defaults 1.6.x stored: everything else the visitor sees was hard-coded Danish
// too, and is now translated. Pin such a site to Danish so nothing changes.
t_reset();
$GLOBALS['wp_locale']                         = 'en_US';
$GLOBALS['wp_options']['privento_db_version'] = '1.6.2';
$GLOBALS['wp_options']['privento_settings']   = [ 'headline' => 'Vi bruger cookies', 'btn_accept_all' => 'Accepter alle', 'btn_save' => 'Gem mit valg' ];
Installer::maybe_upgrade();
$after = $GLOBALS['wp_options']['privento_settings'];
t_assert( 'da_DK' === ( $after['forced_locale'] ?? '' ), 'danish_defaults_on_foreign_locale_pin_the_banner_to_danish' );
t_assert( '' === $after['headline'] && '' === $after['btn_accept_all'] && 'Gem mit valg' === $after['btn_save'], 'pinned_site_follows_danish_and_keeps_edits' );

// No Danish defaults stored → nothing to pin.
t_reset();
$GLOBALS['wp_locale']                         = 'en_US';
$GLOBALS['wp_options']['privento_db_version'] = '1.6.2';
$GLOBALS['wp_options']['privento_settings']   = [ 'headline' => 'Hello' ];
Installer::maybe_upgrade();
t_assert( ! isset( $GLOBALS['wp_options']['privento_settings']['forced_locale'] ), 'custom_texts_on_foreign_locale_are_not_pinned' );

// Activating over replaced files must not mark the upgrade as done.
t_reset();
$GLOBALS['wp_options']['privento_db_version'] = '1.6.2';
$GLOBALS['wpdb'] = new class { public $prefix = 'wp_'; public function get_charset_collate() { return ''; } };
Installer::create_tables();
t_assert( '1.6.2' === $GLOBALS['wp_options']['privento_db_version'], 'activation_does_not_skip_the_upgrade' );

// The policy page is titled in the site language, whoever creates it.
t_reset();
$GLOBALS['wp_site_translations'] = [ 'Cookie Policy' => 'Cookiepolitik' ];
t_assert( 'Cookiepolitik' === Installer::policy_page_title(), 'policy_page_title_uses_site_language' );
unset( $GLOBALS['wp_site_translations'] );
