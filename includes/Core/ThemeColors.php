<?php
/**
 * Reads the site's own colour palette (Elementor kit, block theme, or classic
 * theme palette) and maps it onto Privento's banner colours.
 *
 * @package Privento\Core
 */

namespace Privento\Core;

defined( 'ABSPATH' ) || exit;

class ThemeColors {

    /** Popovers get unusable past a couple of rows. */
    private const MAX_COLORS = 24;

    // -------------------------------------------------------------------------
    // Reading
    // -------------------------------------------------------------------------

    /**
     * @return array{source: string, colors: array<int, array{slug: string, name: string, color: string}>}
     */
    public static function get(): array {
        $found = self::from_elementor();
        if ( ! $found[1] ) {
            $found = self::from_global_settings();
        }
        if ( ! $found[1] ) {
            $found = self::from_editor_palette();
        }
        if ( ! $found[1] ) {
            return [ 'source' => '', 'colors' => [] ];
        }

        return [
            'source' => $found[0],
            'colors' => array_slice( self::dedupe( $found[1] ), 0, self::MAX_COLORS ),
        ];
    }

    /**
     * Elementor keeps its global colours in the active "kit" — a WordPress post.
     * The kit document exposes them; if that API is unavailable we read the same
     * values straight out of the kit's post meta.
     */
    private static function from_elementor(): array {
        if ( ! class_exists( '\Elementor\Plugin' ) ) {
            return [ '', [] ];
        }

        $colors = [];

        $manager = \Elementor\Plugin::$instance->kits_manager ?? null;
        if ( $manager && method_exists( $manager, 'get_active_kit' ) ) {
            $kit = $manager->get_active_kit();
            if ( $kit && method_exists( $kit, 'get_settings' ) ) {
                foreach ( [ 'system_colors', 'custom_colors' ] as $group ) {
                    $colors = array_merge( $colors, self::normalise_elementor( $kit->get_settings( $group ) ) );
                }
            }
        }

        if ( ! $colors ) {
            $kit_id   = (int) get_option( 'elementor_active_kit' );
            $settings = $kit_id ? get_post_meta( $kit_id, '_elementor_page_settings', true ) : [];
            if ( is_array( $settings ) ) {
                foreach ( [ 'system_colors', 'custom_colors' ] as $group ) {
                    $colors = array_merge( $colors, self::normalise_elementor( $settings[ $group ] ?? [] ) );
                }
            }
        }

        return [ 'Elementor', $colors ];
    }

    /**
     * Block themes (theme.json). WordPress also folds a classic theme's
     * add_theme_support( 'editor-color-palette' ) into this, so it covers both.
     * The 'default' origin is WordPress's own generic set — not the site's, so
     * it is skipped.
     */
    private static function from_global_settings(): array {
        if ( ! function_exists( 'wp_get_global_settings' ) ) {
            return [ '', [] ];
        }

        $palette = wp_get_global_settings( [ 'color', 'palette' ] );
        $colors  = [];

        if ( is_array( $palette ) ) {
            // Classic shape is a flat list; block themes key by origin.
            $groups = isset( $palette['theme'] ) || isset( $palette['custom'] ) || isset( $palette['default'] )
                ? [ $palette['theme'] ?? [], $palette['custom'] ?? [] ]
                : [ $palette ];

            foreach ( $groups as $group ) {
                $colors = array_merge( $colors, self::normalise_wp( $group ) );
            }
        }

        return [ self::theme_name(), $colors ];
    }

    /** Last resort: a classic theme that declared a palette WordPress did not surface. */
    private static function from_editor_palette(): array {
        $palette = get_theme_support( 'editor-color-palette' );
        $palette = is_array( $palette ) ? ( $palette[0] ?? [] ) : [];

        return [ self::theme_name(), self::normalise_wp( $palette ) ];
    }

    private static function theme_name(): string {
        $name = wp_get_theme()->get( 'Name' );
        return $name ? (string) $name : __( 'theme', 'privento' );
    }

    /** Elementor entries look like [ '_id' => 'primary', 'title' => '…', 'color' => '#6ec1e4' ]. */
    private static function normalise_elementor( $raw ): array {
        $out = [];
        foreach ( (array) $raw as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $hex = sanitize_hex_color( $item['color'] ?? '' );
            if ( ! $hex ) {
                continue;
            }
            $slug   = sanitize_key( $item['_id'] ?? '' );
            $out[]  = [
                'slug'  => $slug,
                'name'  => sanitize_text_field( $item['title'] ?? '' ) ?: $slug,
                'color' => strtolower( $hex ),
            ];
        }
        return $out;
    }

    /** WordPress entries look like [ 'slug' => 'primary', 'name' => '…', 'color' => '#6ec1e4' ]. */
    private static function normalise_wp( $raw ): array {
        $out = [];
        foreach ( (array) $raw as $item ) {
            if ( ! is_array( $item ) ) {
                continue;
            }
            $hex = sanitize_hex_color( $item['color'] ?? '' );
            if ( ! $hex ) {
                continue; // Skips gradients and var()/currentColor entries.
            }
            $slug  = sanitize_key( $item['slug'] ?? '' );
            $out[] = [
                'slug'  => $slug,
                'name'  => sanitize_text_field( $item['name'] ?? '' ) ?: $slug,
                'color' => strtolower( $hex ),
            ];
        }
        return $out;
    }

    private static function dedupe( array $colors ): array {
        $seen = [];
        $out  = [];
        foreach ( $colors as $c ) {
            if ( isset( $seen[ $c['color'] ] ) ) {
                continue;
            }
            $seen[ $c['color'] ] = true;
            $out[]               = $c;
        }
        return $out;
    }

    // -------------------------------------------------------------------------
    // Mapping onto Privento's colour settings
    // -------------------------------------------------------------------------

    /**
     * Four brand colours cannot fill twenty banner roles, so only the background,
     * text and primary colours are taken from the palette; every button surface is
     * derived from those. Returns [] when there is nothing to map.
     *
     * @param array $colors   Palette from get().
     * @param array $defaults Privento's own colour defaults, used when the palette
     *                        has no usable background/text pair.
     */
    public static function map_to_settings( array $colors, array $defaults ): array {
        if ( ! $colors ) {
            return [];
        }

        $shades = array_column( $colors, 'color' );
        usort( $shades, static fn( $a, $b ) => self::luminance( $a ) <=> self::luminance( $b ) );

        $darkest  = $shades[0];
        $lightest = $shades[ count( $shades ) - 1 ];

        $primary = self::pick( $colors, [ 'primary', 'accent', 'accent-1', 'link', 'brand' ] ) ?: $shades[0];
        $bg      = self::pick( $colors, [ 'background', 'base', 'white' ] ) ?: $lightest;
        $text    = self::pick( $colors, [ 'text', 'contrast', 'foreground', 'dark' ] ) ?: $darkest;

        // A palette of mid-tones gives no readable pair; keep Privento's own.
        if ( self::contrast( $text, $bg ) < 4.5 ) {
            $bg   = $defaults['color_bg'] ?? '#ffffff';
            $text = $defaults['color_text'] ?? '#1a1a18';
        }

        $link       = self::ensure_contrast( $primary, $bg, 4.5 );
        $accept_bg  = $primary;
        $accept_txt = self::readable_on( $accept_bg );
        $accept_hov = self::shade( $accept_bg );
        $neutral    = self::mix( $bg, $text, 0.08 );
        $neutral_hv = self::mix( $bg, $text, 0.16 );
        $muted      = self::ensure_contrast( self::mix( $bg, $text, 0.55 ), $bg, 4.5 );

        return [
            'color_bg'                 => $bg,
            'color_text'               => $text,
            'color_link'               => $link,

            'color_btn_accept_bg'      => $accept_bg,
            'color_btn_accept_txt'     => $accept_txt,
            'color_accept_hover'       => $accept_hov,
            'color_accept_hover_txt'   => self::readable_on( $accept_hov ),

            'color_btn_reject_bg'      => $neutral,
            'color_btn_reject_txt'     => $text,
            'color_reject_hover'       => $neutral_hv,
            'color_reject_hover_txt'   => $text,

            'color_btn_settings_bg'    => 'transparent',
            'color_btn_settings_txt'   => $muted,
            'color_settings_hover'     => self::mix( $bg, $text, 0.10 ),
            'color_settings_hover_txt' => $text,

            'color_btn_save_bg'        => $neutral,
            'color_btn_save_txt'       => $text,
            'color_save_hover'         => $neutral_hv,
            'color_save_hover_txt'     => $text,

            'color_floating_bg'        => $bg,
            'color_floating_txt'       => $muted,
            'color_floating_hover'     => $link,

            'color_revoke_link'        => $link,
            // A link hover must deepen against the page, not follow the colour's
            // own lightness the way a button surface does.
            'color_revoke_hover'       => self::ensure_contrast( self::deepen( $link, $bg ), $bg, 4.5 ),
        ];
    }

    // -------------------------------------------------------------------------
    // Colour maths
    // -------------------------------------------------------------------------

    private static function pick( array $colors, array $slugs ): string {
        foreach ( $slugs as $slug ) {
            foreach ( $colors as $c ) {
                if ( $c['slug'] === $slug ) {
                    return $c['color'];
                }
            }
        }
        return '';
    }

    private static function rgb( string $hex ): array {
        $hex = ltrim( $hex, '#' );
        if ( 3 === strlen( $hex ) ) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return [
            (int) hexdec( substr( $hex, 0, 2 ) ),
            (int) hexdec( substr( $hex, 2, 2 ) ),
            (int) hexdec( substr( $hex, 4, 2 ) ),
        ];
    }

    private static function hex( array $rgb ): string {
        return sprintf(
            '#%02x%02x%02x',
            max( 0, min( 255, (int) round( $rgb[0] ) ) ),
            max( 0, min( 255, (int) round( $rgb[1] ) ) ),
            max( 0, min( 255, (int) round( $rgb[2] ) ) )
        );
    }

    private static function luminance( string $hex ): float {
        $ch = array_map(
            static function ( $c ) {
                $c /= 255;
                return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
            },
            self::rgb( $hex )
        );
        return 0.2126 * $ch[0] + 0.7152 * $ch[1] + 0.0722 * $ch[2];
    }

    private static function contrast( string $a, string $b ): float {
        $l1 = self::luminance( $a );
        $l2 = self::luminance( $b );
        return ( max( $l1, $l2 ) + 0.05 ) / ( min( $l1, $l2 ) + 0.05 );
    }

    /** $weight is the share of $b in the result. */
    private static function mix( string $a, string $b, float $weight ): string {
        $ra = self::rgb( $a );
        $rb = self::rgb( $b );
        return self::hex( [
            $ra[0] + ( $rb[0] - $ra[0] ) * $weight,
            $ra[1] + ( $rb[1] - $ra[1] ) * $weight,
            $ra[2] + ( $rb[2] - $ra[2] ) * $weight,
        ] );
    }

    /** Nudges a colour away from its own lightness, so hover stays visible either way. */
    private static function shade( string $hex, float $amount = 0.16 ): string {
        return self::mix( $hex, self::luminance( $hex ) > 0.45 ? '#000000' : '#ffffff', $amount );
    }

    /** Moves a colour away from $bg, so a hover deepens instead of fading out. */
    private static function deepen( string $hex, string $bg, float $amount = 0.22 ): string {
        return self::mix( $hex, self::luminance( $bg ) > 0.45 ? '#000000' : '#ffffff', $amount );
    }

    private static function readable_on( string $bg ): string {
        return self::contrast( '#ffffff', $bg ) >= self::contrast( '#1a1a18', $bg ) ? '#ffffff' : '#1a1a18';
    }

    /** Darkens or lightens $fg until it clears $min against $bg, or gives up. */
    private static function ensure_contrast( string $fg, string $bg, float $min ): string {
        $target = self::luminance( $bg ) > 0.45 ? '#000000' : '#ffffff';
        $out    = $fg;
        for ( $i = 0; $i < 10 && self::contrast( $out, $bg ) < $min; $i++ ) {
            $out = self::mix( $out, $target, 0.12 );
        }
        return $out;
    }
}
