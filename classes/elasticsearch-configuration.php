<?php

namespace HMES;

class Configuration {

	/**
	 * Set the default elasticsearch host address to be used by the elasticsearch API wrapper
	 *
	 * @param $host
	 */
	public static function set_default_host( $host ) {

		self::set_option( 'server_host', $host );
	}

	/**
	 * Get the default elasticsearch host address to be used by the elasticsearch API wrapper
	 *
	 * @return mixed|void
	 */
	public static function get_default_host() {

		$current = defined( 'HMES_HOST' ) ? HMES_HOST : self::get_option( 'server_host', '' );

		return apply_filters( 'hmes_default_host', $current );
	}

	/**
	 * Set the default elasticsearch port address to be used by the elasticsearch API wrapper
	 *
	 * @param $port
	 */
	public static function set_default_port( $port ) {

		self::set_option( 'server_port', $port );
	}

	/**
	 * Get the default elasticsearch port address to be used by the elasticsearch API wrapper
	 *
	 * @return mixed|void
	 */
	public static function get_default_port() {

		$current = defined( 'HMES_PORT' ) ? HMES_PORT : self::get_option( 'server_port', '' );

		return apply_filters( 'hmes_default_port', $current );
	}


	/**
	 * Get the default elasticsearch host protocol to be used by the elasticsearch API wrapper
	 *
	 * @param $protocol
	 */
	public static function set_default_protocol( $protocol ) {

		self::set_option( 'server_protocol', $protocol );
	}


	/**
	 * Set the default elasticsearch protocol address to be used by the elasticsearch API wrapper
	 *
	 * @return mixed|void
	 */
	public static function get_default_protocol() {

		return apply_filters( 'hmes_default_protocol', self::get_option( 'server_protocol', 'http' ) );
	}

	/**
	 * Gets the index name to be used
	 *
	 * @return string
	 */
	public static function get_default_index_name() {

		$current = defined( 'HMES_INDEX_NAME' ) ? HMES_INDEX_NAME : 'hmes';

		return apply_filters( 'hmes_default_index_name', $current );
	}

	/**
	 * Get the default elasticsearch connection timeout
	 *
	 * @return int
	 */
	public static function get_default_timeout() {

		return apply_filters( 'hmes_default_index_timeout', 10 );
	}

	/**
	 * Get the default maximum log count
	 *
	 * @return int
	 */
	public static function get_default_max_logs() {

		return apply_filters( 'hmes_defauilt_max_logs', 50 );
	}

	/**
	 * Set the protocols supported by the elasticsearch API wrapper
	 *
	 * @return array
	 */
	public static function get_supported_protocols() {

		$protocols = array_keys( Client_Abstraction::getTransports() );

		$protocols_with_name = array();

		foreach ( $protocols as $protocol ) {

			$protocols_with_name[$protocol] = strtoupper( $protocol );
		}

		return apply_filters( 'hmes_supported_protocols', $protocols_with_name );
	}

	/**
	 * Set whether or not elasticsearch indexing is enabled
	 *
	 * @param $bool
	 */
	public static function set_is_indexing_enabled( $bool ) {

		$is_enabled = ( $bool ) ? '1' : '0';

		self::set_option( 'is_enabled', $is_enabled );
	}

	/**
	 * Get whether or not elasticsearch indexing is enabled
	 *
	 * @return mixed|void
	 */
	public static function get_is_indexing_enabled() {

		$current = defined( 'HMES_IS_INDEXING_ENABLED' ) ? HMES_IS_INDEXING_ENABLED : self::get_option( 'is_enabled', '0' );

		return (bool) apply_filters( 'hmes_is_indexing_enabled', $current );
	}

	/**
	 * Set elasticsearch option
	 *
	 * @param $name
	 * @param $value
	 */
	public static function set_option( $name, $value ) {

		update_option( 'hmes_' . $name, $value );
	}

	/**
	 * Get elasticsearch option
	 *
	 * @param $name
	 * @param bool $default
	 * @return mixed|void
	 */
	public static function get_option( $name, $default = false ) {

		return get_option( 'hmes_' . $name, $default );
	}

}