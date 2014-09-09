<?php

namespace HMES;

class Wrapper {

	var $connection           = false;
	var $client               = false;

	var $server_address       = '';
	var $server_port          = '';

	function __construct( $args = array() ) {

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
	static function get_instance( $args = array() ) {

		return new self ( $args );
	}

	/**
	 * Begin a bulk transaction - any actions performed between begin and commit will be sent as a bulk request
	 *
	 * Commit must be called after begin to ensure actions are performed
	 */
	function begin() {
		$this->get_client()->begin();
	}

	/**
	 * Commit a bulk transactions - any actions performed between begin and commit will be sent as a bulk request
	 *
	 * Begin must be called before commit to ensure actions are performed in a bulk request
	 *
	 */
	function commit() {
		$this->get_client()->commit();
	}

	/**
	 * Delete an item from the index
	 *
	 * @param bool $id
	 * @param array $options
	 * @return array|bool
	 */
	function delete( $id = false, $options = array() ) {

		try {

			$r = $this->get_client()->delete( $id, $options );

		} catch( \Exception $e ) {

			Logger::process_exception( 'delete', $this, array( 'id' => $id, 'options' => $options ), $e );
			return false;
		}

		Logger::process_response( 'delete', $this, array( 'id' => $id, 'options' => $options ), $r );

		return $r;
	}

	/**
	 * Delete an item or items by query from the index
	 *
	 * @param $query
	 * @param array $options
	 * @return array|bool
	 */
	function delete_by_query( $query, $options = array() ) {

		try {

			$r = $this->get_client()->deleteByQuery( $query, $options );

		} catch( \Exception $e ) {

			Logger::process_exception( 'delete_by_query', $this, array( 'query' => $query, 'options' => $options ), $e );
			return false;
		}

		Logger::process_response( 'delete_by_query', $this, array( 'query' => $query, 'options' => $options ), $r );

		return $r;
	}

	/**
	 * Index an item
	 *
	 * @param $document
	 * @param bool $id
	 * @param array $options
	 * @return array|bool
	 */
	function index( $document, $id = false, $options = array() ) {

		try {

			$r = $this->get_client()->index( $document, $id, $options );

		} catch( \Exception $e ) {

			Logger::process_exception( 'index', $this, array( 'document' => $document, 'id' => $id, 'options' => $options ), $e );
			return false;
		}

		Logger::process_response( 'index', $this, array( 'document' => $document, 'id' => $id, 'options' => $options ), $r );

		return $r;
	}

	/**
	 * Set the mapping for the current document type
	 *
	 * @param $mapping
	 * @param array $options
	 * @return array|bool
	 */
	function map( $mapping, $options = array() ) {

		try {

			$r = $this->get_client()->map( $mapping, $options );

		} catch( \Exception $e ) {

			Logger::process_exception( 'map', $this, array( 'mapping' => $mapping, 'options' => $options ), $e );
			return false;
		}

		Logger::process_response( 'map', $this, array( 'mapping' => $mapping, 'options' => $options ), $r );

		return $r;
	}

	/**
	 * Perform refresh of current indexes
	 *
	 * @return array|bool
	 */
	function refresh() {

		try {

			$r = $this->get_client()->refresh();

		} catch( \Exception $e ) {

			Logger::process_exception( 'refresh', $this, array(), $e );
			return false;
		}

		Logger::process_response( 'refresh', $this, array(), $r );

		return $r;
	}

	/**
	 * Perform a request on the index (and type if type is set)
	 *
	 * @param $path
	 * @param string $method
	 * @param bool $payload
	 * @param bool $verbose
	 * @return array|bool
	 */
	function request( $path, $method = 'GET', $payload = false, $verbose = false ) {

		try {

			$r = $this->get_client()->request( $path, $method, $payload, $verbose );

		} catch( \Exception $e ) {

			Logger::process_exception( 'request', $this, array( 'path' => $path, 'method' => $method, 'payload' => $payload ), $e );
			return false;
		}

		Logger::process_response( 'request', $this, array(  'path' => $path, 'method' => $method, 'payload' => $payload ), $r );

		return $r;
	}

	/**
	 * Search for a document in the index
	 *
	 * @param $query
	 * @param array $options
	 * @return array|bool
	 */
	function search( $query, $options = array() ) {

		try {

			$r = $this->get_client()->search( $query, $options );

		} catch( \Exception $e ) {

			Logger::process_exception( 'search', $this, array( 'query' => $query, 'options' => $options ), $e );
			return false;
		}

		Logger::process_response( 'search', $this, array( 'query' => $query, 'options' => $options ), $r );

		return $r;
	}

	/**
	 * Get an elasticsearch Transport HTTP wrapper
	 *
	 * @return bool|\ElasticSearch\Transport\HTTP
	 */
	function get_connection() {

		if ( ! $this->connection ) {
			$this->connection = new \ElasticSearch\Transport\HTTP( $this->args['host'], $this->args['port'], $this->args['timeout'] );
		}

		$this->connection->setIndex( $this->args['index'] );

		return $this->connection;
	}

	/**
	 * Get an elasticsearch Client wrapper
	 *
	 * @return bool|\ElasticSearch\Client
	 */
	function get_client() {

		if ( ! $this->client ) {
			$this->client = \ElasticSearch\Client::connection( $this->args );
		}

		return $this->client;
	}

	/**
	 * Get status of the elasticsearch index
	 *
	 * @return array
	 */
	function get_status() {

		try {

			$r = $this->get_connection()->request( array( '_status' ) );

		} catch ( \Exception $e ) {

			Logger::process_exception( 'get_status', $this, array( 'path' => array( '_status' ) ), $e );

			return false;
		}

		return $r;
	}

	/**
	 * Check if a connection to the elasticsearch server is available
	 *
	 * @return bool
	 */
	function is_connection_available( $args = array() ) {

		$args = wp_parse_args( $args, array(

			'log' => true
		) );

		if ( ! $this->args['host'] || ! $this->args['port'] ) {
			return false;
		}

		try {

			$c = $this->get_connection();
			$c->setIndex( '' );
			$r = $c->request( array( '_status' ) );

		} catch ( \Exception $e ) {

			if ( $args['log'] ) {
				Logger::process_exception( 'is_connection_available', $this, array( 'path' => array( '_status' ) ), $e );
			}

			return false;
		}

		return ( empty( $r['error'] ) ) ? true : false;
	}

	/**
	 * Check if the default elasticsearch index is created
	 *
	 * @return bool
	 */
	function is_index_created() {

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
	function create_index( $args = array() ) {

		try {

			$r = $this->get_connection()->request( '', 'PUT', $args );

		} catch ( \Exception $e ) {

			Logger::process_exception( 'create_index', $this, array( 'args' => $args ), $e );

			return false;
		}

		return $r;
	}

	/**
	 * Delete the elasticsearch index
	 *
	 * @param array $args
	 * @return array|bool
	 */
	function delete_index( $args = array() ) {

		try {

			$r = $this->get_connection()->request( '', 'DELETE', $args );

		} catch ( \Exception $e ) {

			Logger::process_exception( 'delete_index', $this, array( 'args' => $args ), $e );

			return false;
		}

		return $r;
	}
}