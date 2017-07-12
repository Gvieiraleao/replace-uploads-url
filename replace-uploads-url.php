<?php
/*
 * Plugin Name: Replace Uploads URL
 * Plugin URI:  https://github.com/victorfreitas/replace-uploads-url
 * Description: Replace uploads site URL on localhost to production
 * Author:      Victor Freitas, Mesaque Silva
 * Version:     0.2
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit(0);
}

final class Replace_Uploads_Url {

    private static $instance = null;

    public $production_url;

    public $option_name = 'ruu_production_url';

    public $field_section = 'ruu_field_section';

    public $title = 'Replace Uploads URL';

    private function __construct() {
    	$this->set_production_url();

        add_action( 'admin_init',  array( $this, 'register_option' ) );

        if ( $this->production_url ) :
            $this->init_actions();
        endif;
    }

    public function init_actions() {
        add_action( 'wp_get_attachment_url', array( $this, 'image_url' ) );
        add_action( 'wp_calculate_image_srcset', array( $this, 'image_srcset' ), 10, 5 );
    }

    public function set_production_url() {
    	$this->production_url = untrailingslashit( $this->get_option() );
    }

    public function indexof( $value, $search ) {
        return ( false !== strpos( $value, $search ) );
    }

    public function get_relative_uri( $url ) {
        return preg_replace( '/https?:\/\/(localhost)?.+?\//', '', $url );
    }

    public function get_uri( $url ) {
        $uri = $this->get_relative_uri( $url );

        if ( file_exists( ABSPATH . $uri ) ) {
            return false;
        }

        return $uri;
    }

    public function image_url( $url ) {
        if ( ! $uri = $this->get_uri( $url ) ) {
            return $url;
        }

        return sprintf( '%s/%s', $this->production_url, $uri );
    }

    public function image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
        foreach ( $sources as $key => $source ) :
            if ( ! $uri = $this->get_uri( $source['url'] ) ) {
                continue;
            }

            $source['url']   = sprintf( '%s/%s', $this->production_url, $uri );
            $sources[ $key ] = $source;
        endforeach;

        return $sources;
    }

    public function register_option() {
        register_setting(
            'general',
            $this->option_name,
            'esc_url'
        );

        add_settings_section(
            $this->field_section,
            $this->title,
            '__return_false',
            'general'
        );

        add_settings_field(
            $this->option_name,
            'Production URL',
            array( $this, 'render_field' ),
            'general',
            $this->field_section
        );
    }

    public function render_field() {
        printf(
            '<input type="url"
                    name="%s"
                    class="regular-text"
                    value="%s"
                    placeholder="Enter your production URL">',
            $this->option_name,
            $this->get_option()
        );
    }

    public function get_option() {
        return esc_url( get_option( $this->option_name ) );
    }

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self;
        }
    }
}
add_action( 'plugins_loaded', array( 'Replace_Uploads_Url', 'instance' ), 0 );
