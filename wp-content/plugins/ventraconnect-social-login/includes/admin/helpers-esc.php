<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! function_exists( 'ventraconnect_sl_attr_string' ) ) {
    function ventraconnect_sl_attr_string( $attrs, array $allowed_keys = array() ): string {
        if ( empty( $attrs ) ) { return ''; }
        if ( is_array( $attrs ) ) {
            $out = '';
            foreach ( $attrs as $k => $v ) {
                if ( $v === null || $v === false ) { continue; }
                if ( $allowed_keys && ! in_array( $k, $allowed_keys, true ) ) { continue; }
                if ( $v === true ) { $v = $k; }
                $out .= ' ' . esc_attr( $k ) . '="' . esc_attr( (string) $v ) . '"';
            }
            return $out;
        }
        $s = (string) $attrs;
        $s = str_replace(array('<','>'), '', $s);
        if ( $allowed_keys ) {
            $pairs = array();
            if ( preg_match_all('/([\w:-]+)\s*=\s*"(.*?)"/', $s, $m, PREG_SET_ORDER) ) {
                foreach ( $m as $hit ) {
                    if ( in_array( $hit[1], $allowed_keys, true ) ) {
                        $pairs[ $hit[1] ] = $hit[2];
                    }
                }
            }
            return $pairs ? ventraconnect_sl_attr_string( $pairs, $allowed_keys ) : '';
        }
        return ' ' . wp_kses_data( $s );
    }
}

if ( ! function_exists( 'ventraconnect_sl_text' ) ) {
    function ventraconnect_sl_text( $value, string $dest = 'text' ): string {
        switch ( $dest ) {
            case 'attr': return esc_attr( (string) $value );
            case 'url':  return esc_url( (string) $value );
            case 'html': return wp_kses_post( (string) $value );
            default:     return esc_html( (string) $value );
        }
    }
}
