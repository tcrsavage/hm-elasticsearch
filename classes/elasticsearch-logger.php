<?php

class HMES_Logger {

	static $max_logs = 500;

	/**
	 * Process a response from elasticsearch and create a log entry if there is an erroneous response
	 *
	 * @param $function
	 * @param HMES_ElasticSearch_Wrapper $class
	 * @param $payload
	 * @param $response
	 */
	static function process_response( $function, HMES_ElasticSearch_Wrapper $class, $payload, $response ) {

		if ( ! is_array( $response ) || empty( $response['error'] ) ) {
			return;
		}

		$log_entry = array(
			'timestamp'      => time(),
			'type'           => 'error',
			'index'          => $class->args['index'],
			'document_type'  => ! empty( $class->args['type'] ) ? $class->args['type'] : '-',
			'caller'         => $function,
			'args'           => $payload,
			'message'        => $response
		);

		self::save_log( $log_entry );

	}

	/**
	 * Process an exception from the elasticsearch wrapper and create a log entry
	 *
	 * @param $function
	 * @param HMES_ElasticSearch_Wrapper $class
	 * @param $payload
	 * @param Exception $e
	 */
	static function process_exception( $function, HMES_ElasticSearch_Wrapper $class, $payload, \Exception $e ) {

		$log_entry = array(
			'timestamp'       => time(),
			'type'            => 'error',
			'index'           => $class->args['index'],
			'document_type'   => ! empty( $class->args['type'] ) ? $class->args['type'] : '-',
			'caller'          => $function,
			'args'            => $payload,
			'message'         => $e->getMessage()
		);

		self::save_log( $log_entry );
	}

	/**
	 * Get a paginated array of log entries (descending on date created)
	 *
	 * @param int $page
	 * @param int $per_page
	 * @return array
	 */
	static function get_paginated_logs( $page = 1, $per_page = 50 ) {

		if ( ! $page || $page < 0 ) {
			$page = 1;
		}

		if ( ! $per_page || $per_page < 0 ) {
			$per_page = 50;
		}

		$saved = self::get_logs();
		$saved = array_reverse( $saved, true );
		$logs  = array_slice( $saved, ( ( $page - 1 ) * $per_page ), ( $per_page * $page ), true );

		return $logs;
	}

	/**
	 * Get all log entries
	 *
	 * @return mixed|void
	 */
	static function get_logs() {

		return get_option( 'hmes_logger_logs', array() );
	}

	/**
	 * Set log entries
	 *
	 * @param $logs
	 */
	static function set_logs( $logs ) {

		delete_option( 'hmes_logger_logs' );
		add_option( 'hmes_logger_logs', $logs, '', 'no' );
	}

	/**
	 * Count the number of log entries
	 *
	 * @return int
	 */
	static function count_logs() {

		return count( self::get_logs( 'logs', array() ) );
	}

	/**
	 * Save a log entry
	 *
	 * @param $item
	 */
	static function save_log( $item ) {

		$saved = self::get_logs();

		if ( empty( $saved ) ) {

			$saved = array( 1 => $item );
		} else {

			$saved[] = $item;
		}

		if ( count( $saved ) > self::$max_logs ) {
			$saved = array_slice( $saved, -self::$max_logs, self::$max_logs, true );
		}

		self::set_logs( $saved );
	}
}