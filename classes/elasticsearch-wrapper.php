<?php

namespace HMES;

class Wrapper {

	public $connection           = false;
	public $client               = false;

	public $server_address       = '';
	public $server_port          = '';

	public function __construct( $args = array() ) {

		$this->args = wp_parse_args( $args, array(
			'host'      => Configuration::get_default_host(),
			'port'      => Configuration::get_default_port(),
			'protocol'  => Configuration::get_default_protocol(),
			'index'     => Configuration::get_default_index_name(),
			'timeout'   => Configuration::get_default_timeout(),
		) );
	}

	/**
	 * Get a class instance
	 *
	 * @param array $args
	 * @return Wrapper
	 */
	public static function get_instance( $args = array() ) {

		return new self ( $args );
	}

	public function disable_logging() {
		$this->get_connection()->disable_logging();
	}

	public function enable_logging() {

		$this->get_connection()->enable_logging();
	}

	/**
	 * Get an elasticsearch Transport HTTP wrapper
	 *
	 * @return bool|Transports\WP_HTTP
	 */
	public function get_connection() {

		return $this->get_client()->getTransport();
	}

	/**
	 * Get an elasticsearch Client wrapper
	 *
	 * @return bool|Client_Abstraction
	 */
	public function get_client() {

		if ( ! $this->client ) {
			$this->client = Client_Abstraction::connection( $this->args );
		}

		return $this->client;
	}

	/**
	 * Get status of the elasticsearch index
	 *
	 * @return array
	 */
	public function get_status() {

		$r = $this->get_connection()->request( array( '_status' ) );

		return $r;
	}

	/**
	 * Check if a connection to the elasticsearch server is available
	 *
	 * @return bool
	 */
	public function is_connection_available( $args = array() ) {

		if ( ! $this->args['host'] || ! $this->args['port'] ) {
			return false;
		}

		$c = $this->get_connection();
		$c->setIndex( '' );
		$r = $c->request( array( '_status' ), 'GET', array() );
		$c->setIndex( $this->args['index'] );

		return ( empty( $r['error'] ) ) ? true : false;
	}

	/**
	 * Check if the default elasticsearch index is created
	 *
	 * @return bool
	 */
	public function is_index_created( $args = array() ) {

		if ( ! $this->args['host'] || ! $this->args['port'] || ! $this->args['index'] ) {
			return false;
		}

		$r = $this->get_status();

		return ( empty( $r['error'] ) || strpos( $r['error'], 'IndexMissingException' ) === false ) ? true : false;
	}

	/**
	 * Create the elasticsearch index
	 *
	 * @param array $args
	 * @return array
	 */
	public function create_index( $args = array() ) {

		$r = $this->get_connection()->request( '', 'PUT', $args );

		return $r;
	}

	/**
	 * Delete the elasticsearch index
	 *
	 * @param array $args
	 * @return array|bool
	 */
	public function delete_index( $args = array() ) {

		$r = $this->get_connection()->request( '', 'DELETE', $args );

		return $r;
	}
}