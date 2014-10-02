<?php

namespace HMES;

class Logger {

	/**
	 * Get the max logs count
	 *
	 * @return int
	 */
	static function get_max_logs() {

		return \HMES\Configuration::get_default_max_logs();

	}

	/**
	 * Log a failed request to elasticsearch
	 *
	 * @param int $page
	 * @param int $per_page
	 * @return array
	 */
	public static function log_failed_request( $url, $method, $payload, $response ) {

		//Elastic search response error messages are long - explode off semicolon to get the short error title
		if ( ! empty( $response['error'] ) ) {
			$exploded = explode( ';', $response['error'] );
			$message  = reset( $exploded );
		} else {
			$message = '-';
		}

		self::save_log( array(
			'type'      => 'error',
			'message'   => $message,
			'data'      => array( 'url' => $url, 'method' => $method, 'payload' => $payload, 'response' => $response ),
		) );
	}

	/**
	 * Get a paginated array of log entries (descending on date created)
	 *
	 * @param int $page
	 * @param int $per_page
	 * @return array
	 */
	public static function get_paginated_logs( $page = 1, $per_page = 50 ) {

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
	public static function get_logs() {

		$logs = get_option( 'hmes_logger_logs', array() );

		//Log overflow, need to clear it
		if ( ! is_array( $logs ) ) {

			self::set_logs( array() );

			self::save_log( array(
				'message'   => 'There was a log overflow issue, logs have been automatically cleared',
				'data'      => '-',
			) );

			$logs = get_option( 'hmes_logger_logs', array() );
		}

		return $logs;
	}

	/**
	 * Set log entries
	 *
	 * @param $logs
	 */
	public static function set_logs( $logs ) {

		delete_option( 'hmes_logger_logs' );
		add_option( 'hmes_logger_logs', $logs, '', 'no' );
	}

	/**
	 * Count the number of log entries
	 *
	 * @return int
	 */
	public static function count_logs() {

		return count( self::get_logs( 'logs', array() ) );
	}

	/**
	 * Save a log entry
	 *
	 * @param $item
	 */
	public static function save_log( $item ) {

		$item = wp_parse_args( $item, array(
			'type'      => 'notice',
			'timestamp' => time(),
			'message'   => '-',
			'data'      => '-',
		) );

		$saved = self::get_logs();

		if ( empty( $saved ) ) {

			$saved = array( 1 => $item );
		} else {

			$saved[] = $item;
		}

		if ( count( $saved ) > self::get_max_logs() ) {
			$saved = array_slice( $saved, -self::get_max_logs(), self::get_max_logs(), true );
		}

		self::set_logs( $saved );
	}
}