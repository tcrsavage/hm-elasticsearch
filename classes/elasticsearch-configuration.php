<?php

namespace HMES;

class Configuration {

	/**
	 * Set the default elasticsearch host address to be used by the elasticsearch API wrapper
	 *
	 * @param $host
	 */
	static function set_default_host( $host ) {

		self::set_option( 'server_host', $host );
	}

	/**
	 * Get the default elasticsearch host address to be used by the elasticsearch API wrapper
	 *
	 * @return mixed|void
	 */
	static function get_default_host() {

		return self::get_option( 'server_host', '' );
	}

	/**
	 * Set the default elasticsearch port address to be used by the elasticsearch API wrapper
	 *
	 * @param $port
	 */
	static function set_default_port( $port ) {

		self::set_option( 'server_port', $port );
	}

	/**
	 * Get the default elasticsearch port address to be used by the elasticsearch API wrapper
	 *
	 * @return mixed|void
	 */
	static function get_default_port() {

		return self::get_option( 'server_port', '' );
	}

	/**
	 * Set the default elasticsearch protocol address to be used by the elasticsearch API wrapper
	 *
	 * @return mixed|void
	 */
	static function get_default_protocol() {

		return self::get_option( 'server_protocol', 'http' );
	}

	/**
	 * Get the default elasticsearch host protocol to be used by the elasticsearch API wrapper
	 *
	 * @param $protocol
	 */
	static function set_default_protocol( $protocol ) {

		self::set_option( 'server_protocol', $protocol );
	}

	static function get_default_index_name() {

		return apply_filters( 'hmes_default_index_name', 'hmes' );
	}

	static function get_default_timeout() {

		return apply_filters( 'hmes_default_index_timeout', 10 );
	}

	/**
	 * Set the protocols supported by the elasticsearch API wrapper
	 *
	 * @return array
	 */
	static function get_supported_protocols() {

		return array( 'http' => 'HTTP', 'https' => 'HTTPS' );
	}

	/**
	 * Set whether or not elasticsearch indexing is enabled
	 *
	 * @param $bool
	 */
	static function set_is_enabled( $bool ) {

		$is_enabled = ( $bool ) ? '1' : '0';

		self::set_option( 'is_enabled', $is_enabled );

	}

	/**
	 * Get whether or not elasticsearch indexing is enabled
	 *
	 * @return mixed|void
	 */
	static function get_is_enabled() {

		return ( self::get_option( 'is_enabled', '0' ) );
	}

	/**
	 * Set elasticsearch option
	 *
	 * @param $name
	 * @param $value
	 */
	static function set_option( $name, $value ) {

		update_option( 'hmes_' . $name, $value );
	}

	/**
	 * Get elasticsearch option
	 *
	 * @param $name
	 * @param bool $default
	 * @return mixed|void
	 */
	static function get_option( $name, $default = false ) {

		return get_option( 'hmes_' . $name, $default );
	}

}