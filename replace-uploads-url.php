<?php
/*
 * Plugin Name: Replace Uploads URL
 * Plugin URI:  https://github.com/victorfreitas/replace-uploads-url
 * Description: Replace uploads site URL on localhost to production
 * Author:      Victor Freitas
 * Version:     0.0.1
 */
if ( ! function_exists( 'add_action' ) ) {
	exit(0);
}

class Replace_Uploads_Url {

	private static $instance = null;

	public function __construct() {
		if ( ! defined( 'WP_PRODUCTION_URL' ) ) {
		 	return;
		}

		add_action( 'wp_get_attachment_url', array( &$this, 'attachment_url' ) );
		add_action( 'wp_calculate_image_srcset', array( &$this, 'image_srcset' ), 10, 5 );
	}

	protected function _get_uri( $url ) {
		$regex = ( false === strpos( $url, 'localhost' ) ) ? '' : '.+?\/';
		$uri   = preg_replace( "/(https?:\/\/.+?\/{$regex})/", '', $url );

		if ( file_exists( ABSPATH . $uri ) )
			return false;

		return $uri;
	}

	public function attachment_url( $url ) {
		if ( ! $uri = $this->_get_uri( $url ) )
			return $url;

		return sprintf( '%s/%s', WP_PRODUCTION_URL, $uri );
	}

	public function image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		foreach ( $sources as $key => $source ) {
			if ( ! $uri = $this->_get_uri( $source['url'] ) )
				continue;

			$source['url']   = sprintf( '%s/%s', WP_PRODUCTION_URL, $uri );
			$sources[ $key ] = $source;
		}

		return $sources;
	}

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
}
add_action( 'plugins_loaded', array( 'Replace_Uploads_Url', 'get_instance' ) );
