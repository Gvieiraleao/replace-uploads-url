<?php
/*
 * Plugin Name: Replace Uploads URL
 * Plugin URI:  https://github.com/victorfreitas/replace-uploads-url
 * Description: Replace uploads site URL on localhost to production
 * Author:      Victor Freitas
 * Version:     0.1.1
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit(0);
}

if ( ! defined( 'WP_PRODUCTION_URL' ) ) {
    return;
}

final class Replace_Uploads_Url {

    private static $instance = null;

    public $production_url;

    private function __construct() {
    	$this->set_production_url();

        add_action( 'wp_get_attachment_url', array( $this, 'image_url' ) );
        add_action( 'wp_calculate_image_srcset', array( $this, 'image_srcset' ), 10, 5 );
    }

    public function set_production_url() {
    	$this->production_url = untrailingslashit( WP_PRODUCTION_URL );
    }

    public function indexof( $value, $search ) {
        return ( false !== strpos( $value, $search ) );
    }

    public function remove_home_url( $url ) {
        return preg_replace( '/https?:\/\/(localhost)?.+?\//', '', $url );
    }

    public function get_uri( $url ) {
        $uri = $this->remove_home_url( $url );

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

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self;
        }
    }
}
add_action( 'plugins_loaded', array( 'Replace_Uploads_Url', 'instance' ), 0 );
