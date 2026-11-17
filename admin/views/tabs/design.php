<?php
/**
 * Design tab — visual customization.
 *
 * @package Privento
 */

defined( 'ABSPATH' ) || exit;

use Privento\Core\Settings;
use Privento\Core\Installer;
use Privento\Core\ThemeColors;

$s = Settings::all();

$defaults = Installer::default_settings()['privento_settings'];

$color_defaults = array_filter(
	$defaults,
	static fn( $k ) => 0 === strpos( $k, 'color_' ),
	ARRAY_FILTER_USE_KEY
);

/*
 * Colour groups rendered by the colour studio. Each field is
 * [ setting key, label, whether the literal value "transparent" is allowed ].
 */
$color_groups = [
	[
		'title'  => __( 'Banner', 'privento' ),
		'hint'   => __( 'The consent box itself and its text.', 'privento' ),
		'fields' => [
			[ 'color_bg', __( 'Background', 'privento' ), true ],
			[ 'color_text', __( 'Text', 'privento' ), false ],
			[ 'color_link', __( 'Policy Link', 'privento' ), false ],
		],
	],
	[
		'title'  => __( 'Accept Button', 'privento' ),
		'hint'   => __( 'The primary action.', 'privento' ),
		'fields' => [
			[ 'color_btn_accept_bg', __( 'Background', 'privento' ), true ],
			[ 'color_btn_accept_txt', __( 'Text', 'privento' ), false ],
			[ 'color_accept_hover', __( 'Hover', 'privento' ), false ],
			[ 'color_accept_hover_txt', __( 'Hover Text', 'privento' ), false ],
		],
	],
	[
		'title'  => __( 'Reject Button', 'privento' ),
		'hint'   => __( 'Must be just as visible as accept.', 'privento' ),
		'fields' => [
			[ 'color_btn_reject_bg', __( 'Background', 'privento' ), true ],
			[ 'color_btn_reject_txt', __( 'Text', 'privento' ), false ],
			[ 'color_reject_hover', __( 'Hover', 'privento' ), false ],
			[ 'color_reject_hover_txt', __( 'Hover Text', 'privento' ), false ],
		],
	],
	[
		'title'  => __( 'Settings Button', 'privento' ),
		'hint'   => __( 'Opens the detailed choices.', 'privento' ),
		'fields' => [
			[ 'color_btn_settings_bg', __( 'Background', 'privento' ), true ],
			[ 'color_btn_settings_txt', __( 'Text', 'privento' ), false ],
			[ 'color_settings_hover', __( 'Hover', 'privento' ), false ],
			[ 'color_settings_hover_txt', __( 'Hover Text', 'privento' ), false ],
		],
	],
	[
		'title'  => __( 'Save Button', 'privento' ),
		'hint'   => __( 'Stores the choice made inside the panel.', 'privento' ),
		'fields' => [
			[ 'color_btn_save_bg', __( 'Background', 'privento' ), true ],
			[ 'color_btn_save_txt', __( 'Text', 'privento' ), false ],
			[ 'color_save_hover', __( 'Hover', 'privento' ), false ],
			[ 'color_save_hover_txt', __( 'Hover Text', 'privento' ), false ],
		],
	],
	[
		'title'  => __( 'Floating Settings Button', 'privento' ),
		'hint'   => __( 'The round button that reopens the banner.', 'privento' ),
		'fields' => [
			[ 'color_floating_bg', __( 'Background', 'privento' ), true ],
			[ 'color_floating_txt', __( 'Icon Color', 'privento' ), false ],
			[ 'color_floating_hover', __( 'Hover', 'privento' ), false ],
		],
	],
	[
		'title'  => __( 'Shortcode Link', 'privento' ),
		'hint'   => __( 'The [privento_revoke] link you place yourself.', 'privento' ),
		'fields' => [
			[ 'color_revoke_link', __( 'Link', 'privento' ), false ],
			[ 'color_revoke_hover', __( 'Hover', 'privento' ), false ],
		],
	],
];

$color_presets = [
	[
		'name'   => __( 'Default', 'privento' ),
		'colors' => $color_defaults,
	],
	[
		'name'   => __( 'Dark', 'privento' ),
		'colors' => [
			'color_bg'               => '#1a1a18',
			'color_text'             => '#fafaf7',
			'color_link'             => '#8ca383',
			'color_btn_accept_bg'    => '#8ca383',
			'color_btn_accept_txt'   => '#1a1a18',
			'color_accept_hover'     => '#6e8265',
			'color_accept_hover_txt' => '#1a1a18',
			'color_btn_reject_bg'    => '#3c3c37',
			'color_btn_reject_txt'   => '#fafaf7',
			'color_reject_hover'     => '#6b6b66',
			'color_reject_hover_txt' => '#fafaf7',
			'color_btn_settings_bg'  => 'transparent',
			'color_btn_settings_txt' => '#e0ddd8',
			'color_settings_hover'   => '#8ca383',
			'color_settings_hover_txt' => '#1a1a18',
			'color_btn_save_bg'      => '#3c3c37',
			'color_btn_save_txt'     => '#fafaf7',
			'color_save_hover'       => '#6b6b66',
			'color_save_hover_txt'   => '#fafaf7',
			'color_floating_bg'      => '#1a1a18',
			'color_floating_txt'     => '#9e9e99',
			'color_floating_hover'   => '#8ca383',
			'color_revoke_link'      => '#8ca383',
			'color_revoke_hover'     => '#6e8265',
		],
	],
	[
		'name'   => __( 'High Contrast', 'privento' ),
		'colors' => [
			'color_bg'               => '#ffffff',
			'color_text'             => '#1a1a18',
			'color_link'             => '#14523a',
			'color_btn_accept_bg'    => '#1a1a18',
			'color_btn_accept_txt'   => '#ffffff',
			'color_accept_hover'     => '#3c3c37',
			'color_accept_hover_txt' => '#ffffff',
			'color_btn_reject_bg'    => '#ffffff',
			'color_btn_reject_txt'   => '#1a1a18',
			'color_reject_hover'     => '#e0ddd8',
			'color_reject_hover_txt' => '#1a1a18',
			'color_btn_settings_bg'  => '#ffffff',
			'color_btn_settings_txt' => '#1a1a18',
			'color_settings_hover'   => '#e0ddd8',
			'color_settings_hover_txt' => '#1a1a18',
			'color_btn_save_bg'      => '#1a1a18',
			'color_btn_save_txt'     => '#ffffff',
			'color_save_hover'       => '#3c3c37',
			'color_save_hover_txt'   => '#ffffff',
			'color_floating_bg'      => '#ffffff',
			'color_floating_txt'     => '#1a1a18',
			'color_floating_hover'   => '#14523a',
			'color_revoke_link'      => '#14523a',
			'color_revoke_hover'     => '#1a1a18',
		],
	],
];

/*
 * The site's own palette — Elementor's global colours, or the theme's. Feeds both
 * the "From <source>" preset and the extra swatch row in every picker.
 */
$theme_palette = ThemeColors::get();
$theme_mapped  = ThemeColors::map_to_settings( $theme_palette['colors'], $color_defaults );
$theme_label   = $theme_palette['source']
	/* translators: %s: palette source, e.g. "Elementor" or the theme name. */
	? sprintf( __( 'From %s', 'privento' ), $theme_palette['source'] )
	: '';

if ( $theme_mapped ) {
	$color_presets[] = [
		'name'   => $theme_label,
		'colors' => $theme_mapped,
	];
}

/**
 * One colour field: swatch trigger plus the hidden input the form serializer reads.
 */
function prv_cs_field( array $field, array $s, array $defaults ): void {
	[ $key, $label, $allow_transparent ] = $field;

	$val    = $s[ $key ] ?? ( $defaults[ $key ] ?? '#ffffff' );
	$is_hex = (bool) preg_match( '/^#[0-9a-fA-F]{3,8}$/', $val );
	?>
	<div class="prv-cs-field" data-key="<?php echo esc_attr( $key ); ?>" data-transparent="<?php echo $allow_transparent ? '1' : '0'; ?>">
		<span class="prv-cs-field__label"><?php echo esc_html( $label ); ?></span>
		<button type="button" class="prv-cs-trigger" aria-haspopup="dialog" aria-expanded="false">
			<span class="prv-cs-chip<?php echo $is_hex ? '' : ' is-transparent'; ?>"<?php echo $is_hex ? ' style="background:' . esc_attr( $val ) . '"' : ''; ?>></span>
			<span class="prv-cs-hex"><?php echo esc_html( $val ); ?></span>
		</button>
		<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $val ); ?>">
	</div>
	<?php
}
?>
<div class="prv-page">
	<div class="prv-page__header">
		<div>
			<h1 class="prv-page__title"><?php esc_html_e( 'Banner Design', 'privento' ); ?></h1>
			<p class="prv-page__subtitle"><?php esc_html_e( 'Customize the look and feel of your cookie consent banner', 'privento' ); ?></p>
		</div>
		<button type="button" class="prv-btn prv-btn--primary" id="prv-save-design">
			<?php esc_html_e( 'Save Changes', 'privento' ); ?>
		</button>
	</div>

	<form id="prv-design-form" class="prv-form">

		<!-- Row 1: Placement + Typography side by side -->
		<div class="prv-grid prv-grid--2" style="margin-bottom:0">

			<!-- Placement & Layout -->
			<div class="prv-panel">
				<div class="prv-panel__header">
					<h2 class="prv-panel__title"><?php esc_html_e( 'Placement & Layout', 'privento' ); ?></h2>
				</div>
				<div class="prv-panel__body">

					<div class="prv-field">
						<label class="prv-label"><?php esc_html_e( 'Banner Position', 'privento' ); ?></label>
						<div class="prv-pos-grid">
							<?php
							$current   = $s['banner_position'] ?? 'bottom_bar';
							$positions = [
								'bottom_bar'  => [
									'label' => __( 'Bottom Bar', 'privento' ),
									'svg'   => '<rect x="2" y="14" width="20" height="6" rx="1" fill="currentColor"/><rect x="2" y="2" width="20" height="10" rx="1" fill="currentColor" opacity=".15"/>',
								],
								'modal'       => [
									'label' => __( 'Center Modal', 'privento' ),
									'svg'   => '<rect x="2" y="2" width="20" height="20" rx="1" fill="currentColor" opacity=".15"/><rect x="5" y="5" width="14" height="14" rx="1" fill="currentColor"/>',
								],
								'corner'      => [
									'label' => __( 'Corner Right', 'privento' ),
									'svg'   => '<rect x="2" y="2" width="20" height="20" rx="1" fill="currentColor" opacity=".15"/><rect x="13" y="13" width="8" height="8" rx="1" fill="currentColor"/>',
								],
								'corner_left' => [
									'label' => __( 'Corner Left', 'privento' ),
									'svg'   => '<rect x="2" y="2" width="20" height="20" rx="1" fill="currentColor" opacity=".15"/><rect x="3" y="13" width="8" height="8" rx="1" fill="currentColor"/>',
								],
								'floating'    => [
									'label' => __( 'Top Banner', 'privento' ),
									'svg'   => '<rect x="2" y="2" width="20" height="10" rx="1" fill="currentColor" opacity=".15"/><rect x="2" y="2" width="20" height="6" rx="1" fill="currentColor"/>',
								],
							];
							foreach ( $positions as $val => $pos ) :
								?>
							<label class="prv-pos-option<?php echo $current === $val ? ' is-active' : ''; ?>">
								<input type="radio" name="banner_position" value="<?php echo esc_attr( $val ); ?>" <?php checked( $current, $val ); ?>>
								<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
									<?php echo $pos['svg']; // phpcs:ignore ?>
								</svg>
								<span><?php echo esc_html( $pos['label'] ); ?></span>
							</label>
							<?php endforeach; ?>
						</div>
					</div>

					<div class="prv-field">
						<label class="prv-label"><?php esc_html_e( 'Border Radius (px)', 'privento' ); ?></label>
						<div class="prv-slider-row">
							<input type="range" name="border_radius" min="0" max="32"
									value="<?php echo esc_attr( $s['border_radius'] ?? 10 ); ?>"
									class="prv-slider" id="prv-border-radius">
							<span class="prv-slider__value" id="prv-radius-val"><?php echo esc_attr( $s['border_radius'] ?? 10 ); ?>px</span>
						</div>
					</div>

					<div class="prv-field prv-field--inline">
						<label class="prv-label"><?php esc_html_e( 'Drop Shadow', 'privento' ); ?></label>
						<label class="prv-toggle-field">
							<input type="checkbox" name="shadow" value="1" <?php checked( $s['shadow'] ?? '1', '1' ); ?>>
							<span class="prv-toggle-track"></span>
						</label>
					</div>

				</div>
			</div>

			<!-- Typography + Floating btn -->
			<div style="display:flex;flex-direction:column;gap:20px">

				<div class="prv-panel">
					<div class="prv-panel__header">
						<h2 class="prv-panel__title"><?php esc_html_e( 'Typography', 'privento' ); ?></h2>
					</div>
					<div class="prv-panel__body">
						<div class="prv-field">
							<label class="prv-label"><?php esc_html_e( 'Font Family', 'privento' ); ?></label>
							<select name="font_family" class="prv-select">
								<?php
								foreach ( [
									'inherit'                   => __( 'Inherit from theme', 'privento' ),
									'system-ui'                 => 'System UI',
									'Inter, sans-serif'         => 'Inter',
									'-apple-system, sans-serif' => 'Apple System',
									'Georgia, serif'            => 'Georgia',
								] as $fval => $fname ) :
									?>
								<option value="<?php echo esc_attr( $fval ); ?>" <?php selected( $s['font_family'] ?? 'inherit', $fval ); ?>><?php echo esc_html( $fname ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="prv-field">
							<label class="prv-label"><?php esc_html_e( 'Base Font Size', 'privento' ); ?></label>
							<div class="prv-slider-row">
								<input type="range" name="font_size_base" min="11" max="18"
										value="<?php echo esc_attr( $s['font_size_base'] ?? 14 ); ?>"
										class="prv-slider" id="prv-font-size">
								<span class="prv-slider__value" id="prv-font-size-val"><?php echo esc_attr( $s['font_size_base'] ?? 14 ); ?>px</span>
							</div>
						</div>
					</div>
				</div>

				<div class="prv-panel">
					<div class="prv-panel__header">
						<h2 class="prv-panel__title"><?php esc_html_e( 'Floating Settings Button', 'privento' ); ?></h2>
					</div>
					<div class="prv-panel__body">
						<div class="prv-field prv-field--inline">
							<label class="prv-label"><?php esc_html_e( 'Show Button', 'privento' ); ?></label>
							<label class="prv-toggle-field">
								<input type="checkbox" name="floating_btn_enabled" value="1" <?php checked( $s['floating_btn_enabled'] ?? '1', '1' ); ?>>
								<span class="prv-toggle-track"></span>
							</label>
						</div>
						<div class="prv-field">
							<label class="prv-label"><?php esc_html_e( 'Position', 'privento' ); ?></label>
							<select name="floating_btn_position" class="prv-select">
								<option value="bottom_left"  <?php selected( $s['floating_btn_position'] ?? 'bottom_left', 'bottom_left' ); ?>><?php esc_html_e( 'Bottom Left', 'privento' ); ?></option>
								<option value="bottom_right" <?php selected( $s['floating_btn_position'] ?? 'bottom_left', 'bottom_right' ); ?>><?php esc_html_e( 'Bottom Right', 'privento' ); ?></option>
							</select>
						</div>
						<p class="prv-hint"><?php esc_html_e( 'Its colors live in the Colors card below.', 'privento' ); ?></p>
						<div class="prv-field" style="margin-top:16px">
							<label class="prv-label"><?php esc_html_e( 'Shortcode', 'privento' ); ?></label>
							<div class="privento-shortcode-box" style="display:flex;gap:8px;align-items:center">
								<code style="flex:1;padding:8px 12px;background:#f3f4f6;border-radius:6px;font-family:monospace;font-size:13px">[privento_revoke]</code>
								<button type="button" class="prv-btn prv-btn--ghost privento-copy-shortcode" data-copy="[privento_revoke]">
									<?php esc_html_e( 'Copy', 'privento' ); ?>
								</button>
							</div>
							<p class="prv-hint"><?php esc_html_e( 'Place this shortcode in your footer or a menu to open the cookie settings — useful if you turn the floating button off. Optional attributes: label and class, e.g. [privento_revoke label="Cookie settings" class="my-link"].', 'privento' ); ?></p>
						</div>
					</div>
				</div>

			</div>
		</div>

		<!-- Row 2: Colour studio -->
		<div class="prv-cs" id="prv-color-studio"
			data-defaults="<?php echo esc_attr( wp_json_encode( $color_defaults ) ); ?>"
			data-theme-colors="<?php echo esc_attr( wp_json_encode( $theme_palette['colors'] ) ); ?>"
			data-theme-label="<?php echo esc_attr( $theme_label ); ?>">

			<div class="prv-cs__head">
				<div class="prv-cs__heading">
					<h2 class="prv-cs__title"><?php esc_html_e( 'Colors', 'privento' ); ?></h2>
					<p class="prv-cs__subtitle"><?php esc_html_e( 'Controls how the consent banner looks across the site.', 'privento' ); ?></p>
				</div>
				<div class="prv-cs__presets">
					<?php foreach ( $color_presets as $preset ) : ?>
						<button type="button" class="prv-cs__preset"
								data-colors="<?php echo esc_attr( wp_json_encode( $preset['colors'] ) ); ?>">
							<span class="prv-cs__preset-dots">
								<?php
								foreach ( [ 'color_bg', 'color_btn_accept_bg', 'color_text' ] as $dot_key ) :
									$dot = $preset['colors'][ $dot_key ] ?? '#ffffff';
									?>
									<span class="prv-cs__preset-dot" style="background:<?php echo esc_attr( $dot ); ?>"></span>
								<?php endforeach; ?>
							</span>
							<?php echo esc_html( $preset['name'] ); ?>
						</button>
					<?php endforeach; ?>
					<?php if ( ! $theme_mapped ) : ?>
						<button type="button" class="prv-cs__preset" disabled
								title="<?php esc_attr_e( 'No Elementor or theme palette was found on this site.', 'privento' ); ?>">
							<?php esc_html_e( 'From theme', 'privento' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="prv-cs__body">

				<div class="prv-cs__groups">
					<?php foreach ( $color_groups as $group ) : ?>
						<div class="prv-cs__group">
							<div class="prv-cs__group-meta">
								<span class="prv-cs__group-title"><?php echo esc_html( $group['title'] ); ?></span>
								<span class="prv-cs__group-hint"><?php echo esc_html( $group['hint'] ); ?></span>
							</div>
							<div class="prv-cs__group-fields">
								<?php foreach ( $group['fields'] as $field ) : ?>
									<?php prv_cs_field( $field, $s, $color_defaults ); ?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>

				<aside class="prv-cs__aside">
					<div class="prv-cs__aside-head">
						<span class="prv-cs__eyebrow"><?php esc_html_e( 'Preview', 'privento' ); ?></span>
						<div class="prv-cs__views">
							<button type="button" class="prv-cs__view is-active" data-view="desktop"><?php esc_html_e( 'Desktop', 'privento' ); ?></button>
							<button type="button" class="prv-cs__view" data-view="mobile"><?php esc_html_e( 'Mobile', 'privento' ); ?></button>
						</div>
					</div>

					<div class="prv-cs__preview" id="prv-cs-preview">
						<div class="prv-cs__preview-inner">
							<strong class="prv-cs__pv-title"><?php echo esc_html( $s['headline'] ); ?></strong>
							<p class="prv-cs__pv-body">
								<?php
								printf(
									/* translators: %s: linked "cookie policy" wording. */
									esc_html__( 'We use cookies for statistics and marketing. Read more in our %s.', 'privento' ),
									'<span class="prv-cs__pv-link">' . esc_html__( 'cookie policy', 'privento' ) . '</span>'
								);
								?>
							</p>
							<div class="prv-cs__pv-actions">
								<span class="prv-cs__pv-btn prv-cs__pv-btn--accept"><?php echo esc_html( $s['btn_accept_all'] ); ?></span>
								<span class="prv-cs__pv-btn prv-cs__pv-btn--reject"><?php echo esc_html( $s['btn_reject_all'] ); ?></span>
								<span class="prv-cs__pv-btn prv-cs__pv-btn--settings"><?php echo esc_html( $s['btn_settings'] ); ?></span>
							</div>
						</div>
					</div>

					<p class="prv-cs__pv-note"><?php esc_html_e( 'Hover the buttons to check their hover colors.', 'privento' ); ?></p>

					<div class="prv-cs__checks">
						<div class="prv-cs__check" data-fg="color_text" data-bg="color_bg">
							<span class="prv-cs__check-dot"></span>
							<span class="prv-cs__check-label"><?php esc_html_e( 'Banner text', 'privento' ); ?></span>
							<span class="prv-cs__check-ratio"></span>
						</div>
						<div class="prv-cs__check" data-fg="color_btn_accept_txt" data-bg="color_btn_accept_bg">
							<span class="prv-cs__check-dot"></span>
							<span class="prv-cs__check-label"><?php esc_html_e( 'Accept button', 'privento' ); ?></span>
							<span class="prv-cs__check-ratio"></span>
						</div>
						<div class="prv-cs__check" data-fg="color_btn_reject_txt" data-bg="color_btn_reject_bg">
							<span class="prv-cs__check-dot"></span>
							<span class="prv-cs__check-label"><?php esc_html_e( 'Reject button', 'privento' ); ?></span>
							<span class="prv-cs__check-ratio"></span>
						</div>
						<span class="prv-cs__checks-note"><?php esc_html_e( 'Contrast is measured against WCAG 2.1 AA (4.5:1 for text).', 'privento' ); ?></span>
					</div>
				</aside>

			</div>

			<div class="prv-cs__foot">
				<button type="button" class="prv-cs__reset"><?php esc_html_e( 'Reset to default colors', 'privento' ); ?></button>
				<div class="prv-cs__foot-actions">
					<button type="button" class="prv-cs__cancel"><?php esc_html_e( 'Cancel', 'privento' ); ?></button>
					<button type="button" class="prv-cs__save"><?php esc_html_e( 'Save Changes', 'privento' ); ?></button>
				</div>
			</div>

		</div>

	</form>
</div>

<style>
/* ── Position picker ──────────────────────────────────── */
.prv-pos-grid {
	display: grid;
	grid-template-columns: repeat(5, 1fr);
	gap: 8px;
	margin-top: 6px;
}
.prv-pos-option {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 6px;
	padding: 10px 6px;
	border: 2px solid #e5e7eb;
	border-radius: 8px;
	cursor: pointer;
	transition: border-color .15s, background .15s;
	font-size: 12px;
	font-weight: 500;
	color: #6b7280;
	background: #fff;
	text-align: center;
}
.prv-pos-option input { display: none; }
.prv-pos-option svg {
	width: 36px; height: 36px;
	color: #9ca3af;
	transition: color .15s;
}
.prv-pos-option:hover { border-color: #93c5fd; background: #eff6ff; color: #2179b6; }
.prv-pos-option:hover svg { color: #2179b6; }
.prv-pos-option.is-active {
	border-color: #2179b6;
	background: #eff6ff;
	color: #2179b6;
}
.prv-pos-option.is-active svg { color: #2179b6; }
</style>
