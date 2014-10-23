<?php

namespace HMES\Types;
use HMES\Wrapper;
use HMES\Logger;

abstract class Base {

	public $name            = '';
	public $client          = '';
	public $wrapper         = '';
	public $items_per_page  = 100;
	public $index_hooks     = array();
	public $delete_hooks    = array();
	public $mappable_hooks  = array();
	public $queued_actions  = array();

	/**
	 * Index callback, to be called when an item is added or edited in the database
	 *
	 * @param $item
	 * @param array $args
	 * @return mixed
	 */
	public abstract function index_callback( $item, $args = array() );

	/**
	 * Delete callback - to be fired when an item is deleted from the database
	 *
	 * @param $item
	 * @param array $args
	 * @return mixed
	 */
	public abstract function delete_callback( $item, $args = array()  );

	/**
	 * Parse an item for indexing (should support being supplied either an ID, or an item object/array
	 *
	 * @param $item
	 * @return mixed
	 */
	public abstract function parse_item_for_index( $item, $args = array() );

	/**
	 * Get items of specific type to index (used when initialising index)
	 *
	 * @param $page
	 * @param $per_page
	 * @return mixed
	 */
	public abstract function get_items( $page, $per_page );


	/*
	 * Get an integer count of the number of items which can potentially be indexed in the database
	 *
	 * Should serve to return a count which matches the same number of items which can be obtained from use of the get_items method
	 *
	 * @return int
	 */
	public abstract function get_items_count();

	/**
	 * @return mixed
	 */
	public function get_mapping() {

		return false;
	}

	/**
	 * Get the ElasticSearch Client Wrapper with default index and type pre set
	 *
	 * @return \ElasticSearch\Client
	 */
	public function get_client() {

		if ( ! $this->client ) {

			$this->client = $this->get_wrapper()->get_client();
		}

		return $this->client;
	}

	/**
	 * Get the Wrapper, initialised with default index and type pre set
	 *
	 * @return Wrapper|string
	 */
	public function get_wrapper() {

		if ( ! $this->wrapper ) {

			$this->wrapper = Wrapper::get_instance( array( 'type' => $this->name ) );
		}

		return $this->wrapper;
	}

	/**
	 * Add an item to the index
	 *
	 * @param int|object $item
	 */
	public function index_item( $item ) {

		$parsed = $this->parse_item_for_index( $item );

		if ( ! $parsed ) {
			return;
		}

		$this->get_client()->index( $parsed, $parsed['ID'] );
	}

	/**
	 * Delete an item from the index with specified document ID
	 *
	 * @param $item
	 */
	public function delete_item( $item_id ) {

		if ( ! $item_id ) {
			return;
		}

		$this->get_client()->delete( $item_id );
	}

	/**
	 * Search for documents of a type in the index
	 *
	 * @param $query
	 * @param array $options
	 * @return array
	 */
	public function search( $query, $options = array() ) {

		return $this->get_client()->search( $query, $options );
	}

	/**
	 * Bulk index items, this function should be passed an array of ids or objects
	 *
	 * @param array $items[int|object]
	 * @param array $args ['bulk']
	 */
	public function index_items( $items, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'bulk'    => false
		) );

		if ( $args['bulk'] ) {

			$this->get_client()->begin();
		}

		foreach ( $items as $item ) {

			$this->index_item( $item );
		}

		if ( $args['bulk'] ) {

			$this->get_client()->commit();
		}

	}

	/**
	 * Bulk delete items. this function should be passed an array of ids (index document id)
	 *
	 * @param array $items
	 * @param array $args ['bulk']
	 */
	public function delete_items( $items, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'bulk'    => false
		) );

		if ( $args['bulk'] ) {

			$this->get_client()->begin();
		}

		foreach ( $items as $item ) {

			$this->delete_item( $item );
		}

		if ( $args['bulk'] ) {

			$this->get_client()->commit();
		}
	}

	/**
	 * Index all the items for the specified type
	 */
	function index_all() {

		$has_items = true;
		$page = 1;

		$this->set_is_doing_full_index( true );
		$this->delete_all_indexed_items();

		while ( $has_items ) {

			global $wp_object_cache;

			//clear object cache local cache to avoid memory overflow
			if ( ! empty( $wp_object_cache->cache ) ) {
				$wp_object_cache->cache = array();
			}

			$items = $this->get_items( $page, $this->items_per_page );

			if ( $items ) {
				$this->index_items( $items, array( 'bulk' => true ) );
			} else {
				$has_items = false;
			}

			$page++;
		}

		$this->set_is_doing_full_index( false );
	}

	/**
	 * Queue an action for execution on php shutdown - e.g. index post item after 'save_post' hook has been fired
	 *
	 * @param $action
	 * @param $identifier
	 * @param array $args
	 */
	function queue_action( $action, $identifier, $args = array() ) {

		//keep actions in order of when they were last set
		if ( isset( $this->queued_actions[$identifier][$action] ) ) {
			unset( $this->queued_actions[$identifier][$action]  );
		}

		$this->queued_actions[(string)$identifier][$action] = $args;
	}

	/**
	 * Get all actions that have been queued, e.g. index items/delete items
	 *
	 * @return array
	 */
	function get_queued_actions() {

		$saved  = get_option( 'hmes_queued_actions_' . $this->name, array() );
		$all    = array_replace_recursive( $saved, $this->queued_actions );

		return $all;
	}

	/**
	 * Clear all queued actions, is executed after 'execute_queued_actions' completes successfully
	 */
	function clear_queued_actions() {

		$this->queued_actions = array();
		delete_option(  'hmes_queued_actions_' . $this->name );
	}

	/**
	 * Save queued sync actions to the database, is called when 'execute_queued_actions' fails to connect to the ES server correctly
	 */
	function save_actions( $actions ) {

		if ( count( $actions ) > 1000 ) {

			\HMES\Logger::save_log( array(
				'timestamp'      => time(),
				'type'           => 'warning',
				'index'          => $this->get_wrapper()->args['index'],
				'document_type'  => $this->get_wrapper()->args['type'],
				'caller'         => 'save_queued_actions',
				'args'           => '-',
				'message'        => 'Saved actions buffer overflow. Too many actions have been saved for later syncing. (' . count( $actions ) . ' items)'
			) );

			$actions = array_slice( $actions, -1000, 1000, true );
		}

		update_option( 'hmes_queued_actions_' . $this->name, $actions );
	}

	/**
	 * Find all queued actions and execute them, save the actions for later if the ES server is not available
	 */
	function execute_queued_actions() {

		//Clear actions so other threads don't pick them up
		$queued_actions = $this->get_queued_actions();
		$this->clear_queued_actions();

		if ( ! $queued_actions ) {

			return;
		}

		//If we failed to execute actions in the last 5minutes, don't bother trying to connect again, just save queued actions and return
		if ( $this->get_last_execute_failed_attempt() > strtotime( '-5 minutes' ) ) {

			$this->save_actions( $queued_actions );

			return;

		///If we can't get a connection at the moment, save the queued actions for processing later
		} else if ( ! $this->get_wrapper()->is_connection_available() || ! $this->get_wrapper()->is_index_created() ) {

			$this->save_actions( $queued_actions );

			$this->set_last_execute_failed_attempt( time() );

			Logger::save_log( array(
				'timestamp'      => time(),
				'message'        => 'Failed to execute syncing actions for ' . count( $this->get_queued_actions() ) . ' items. Saving for reattempt in 5 mins',
				'data'           => array( 'document_type' => $this->name, 'queued_actions' => $this->get_queued_actions() )
			) );

		//else execute the actions now
		} else {

			//Begin a bulk transaction
			$this->get_wrapper()->get_client()->begin();

			foreach ( $queued_actions as $identifier => $object ) {
				foreach ( $object as $action => $args ) {
					$this->$action( $identifier, $args );
				}
			}

			//Finish the bulk transaction
			$this->get_wrapper()->get_client()->commit();
		}
	}

	/**
	 * Get the last timestamp at which 'execute_queued_actions' failed to complete due to a server issue
	 *
	 * @return int
	 */
	function get_last_execute_failed_attempt() {

		return get_option( 'hmes_' . $this->name . '_last_failed_execute_actions_attempt', 0 );
	}

	/**
	 * Set the last timestamp at which 'execute_queued_actions' failed to complete due to a server issue
	 */
	function set_last_execute_failed_attempt() {

		update_option( 'hmes_' . $this->name . '_last_failed_execute_actions_attempt', time() );
	}

	function set_is_doing_full_index( $bool ) {

		if ( $bool ) {
			update_option( 'hmes_' . $this->name . '_is_doing_full_index', time() );
		} else {

			delete_option( 'hmes_' . $this->name . '_is_doing_full_index' );
		}

	}

	function get_is_doing_full_index() {

		$val = get_option( 'hmes_' . $this->name . '_is_doing_full_index', 0 );

		return strtotime( '-30 minutes', time() ) < $val;
	}

	/*
	 *
	 */
	function get_status() {

		$response = array();

		$count = $this->get_client()->request( '_count' );

		if ( empty( $count['error'] ) ) {
			$response['indexed_count'] = $count['count'];
		} else {
			$response['error'] = $count['error'];
			$response['indexed_count'] = 0;
		}

		$response['database_count'] = $this->get_items_count();

		$response['is_doing_full_index'] = $this->get_is_doing_full_index();

		return $response;

	}

	public function delete_all_indexed_items() {

		$wrapper = $this->get_wrapper();

		$this->get_client()->request( array( '/', $wrapper->args['index'], $this->name ), 'DELETE' );
	}
}