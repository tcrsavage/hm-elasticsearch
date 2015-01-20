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

	/**
	 * Get item ids of specific type to index (used when adding pending items to the index)
	 *
	 * @param $page
	 * @param $per_page
	 * @return mixed
	 */
	public abstract function get_items_ids( $page, $per_page );

	/*
	 * Get an integer count of the number of items which can potentially be indexed in the database
	 *
	 * Should serve to return a count which matches the same number of items which can be obtained from use of the get_items method
	 *
	 * @return int
	 */
	/**
	 * @return mixed
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

	function index_pending() {

		$this->set_is_doing_full_index( true );

		$has_items = true;
		$page = 1;
		$total_items = $this->get_items_count();

		while ( $has_items ) {

			global $wp_object_cache;

			//clear object cache local cache to avoid memory overflow
			if ( ! empty( $wp_object_cache->cache ) ) {
				$wp_object_cache->cache = array();
			}

			$items = $this->get_items_ids( $page, $this->items_per_page );

			$r = $this->search( array(
				'fields' => [],
				'query' => array(
					'ids' => array( 'values' => $items )
				),
				'size'   => $this->items_per_page,
				'from'   =>  $this->items_per_page * ( $page - 1 ),
			) );

			if ( ! empty( $r['hits']['total'] ) ) {
				$hits_count = $r['hits']['total'];
			} else {
				$hits_count = 0;
			}

			$cur_count =  $this->get_client()->request( '_count' );
			$cur_count = ! empty( $cur_count['count'] ) ? $cur_count['count'] : 0;

			if ( $hits_count < count( $items ) ) {
				$this->index_items( $this->get_items( $page, $this->items_per_page ), array( 'bulk' => true ) );
			}

			if ( ! $items || ( $cur_count >= $total_items ) ) {
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
	function add_action( $action, $identifier, $args = array() ) {

		//keep actions in order of when they were last set
		if ( isset( $this->queued_actions[$identifier][$action] ) ) {
			unset( $this->queued_actions[$identifier][$action]  );
		}

		$this->queued_actions[(string)$identifier][$action] = $args;
	}

	/**
	 * Get all indexing actions queued by the current thread
	 *
	 * @return array
	 */
	function get_actions() {

		return $this->queued_actions;
	}

	/**
	 * Aquire a save lock to update the global actions queue with those set in the current thread
	 *
	 * @return bool
	 */
	function acquire_lock( $action ) {

		$attempts = 0;

		//Wait until other threads have finished saving their queued items (failsafe)
		while ( ! wp_cache_add( 'hmes_queued_actions_lock_' . $this->name . '_' . $action, '1', '', 60 ) && $attempts < 10 ) {
			$attempts++;
			time_nanosleep( 0, 500000000 );
		}

		return $attempts < 10 ? true : false;
	}

	/**
	 * Clear the save lock after global actions have been updated
	 */
	function clear_lock( $action ) {

		wp_cache_delete( 'hmes_queued_actions_lock_' . $this->name . '_' . $action );
	}

	/**
	 * Get all actions that have been queued, e.g. index items/delete items
	 *
	 */
	function save_actions() {

		//no actions to save
		if ( ! $this->queued_actions ) {
			return;
		}

		if ( ! $this->acquire_lock( 'save_actions' ) ) {
			return;
		}

		$saved  = $this->get_saved_actions();
		$all    = array_replace_recursive( $saved, $this->queued_actions );

		if ( count( $all ) > 10000 ) {

			\HMES\Logger::save_log( array(
				'timestamp'      => time(),
				'type'           => 'warning',
				'index'          => $this->get_wrapper()->args['index'],
				'document_type'  => $this->get_wrapper()->args['type'],
				'caller'         => 'save_queued_actions',
				'args'           => '-',
				'message'        => 'Saved actions buffer overflow. Too many actions have been saved for later syncing. (' . count( $all ) . ' items)'
			) );

			$all = array_slice( $all, -10000, 10000, true );
		}

		update_option( 'hmes_queued_actions_' . $this->name, $all );

		$this->clear_lock( 'save_actions' );
	}

	/**
	 * Get the array of global indexing actions which should be performed
	 *
	 * @return array
	 */
	function get_saved_actions() {

		return get_option( 'hmes_queued_actions_' . $this->name, array() );
	}

	/**
	 * Clear the saved indexing actions
	 *
	 */
	function clear_saved_actions() {

		delete_option( 'hmes_queued_actions_' . $this->name );
	}

	/**
	 * Get the hook name for the queued actions execution cron
	 *
	 * @return string
	 */
	function get_execute_cron_hook() {

		return 'hmes_execute_queued_actions_cron_' . $this->name;
	}

	/**
	 * Find all queued actions and execute them, save the actions for later if the ES server is not available
	 */
	function execute_queued_actions() {

		if ( ! $this->acquire_lock( 'execute_queued_actions' ) ) {
			return;
		}

		$actions = $this->get_saved_actions();
		$this->clear_saved_actions();

		if ( ! $actions ) {

			$this->clear_lock( 'execute_queued_actions' );
			return;
		}

		///If we can't get a connection at the moment, save the queued actions for processing later
		if ( ! $this->get_wrapper()->is_connection_available() || ! $this->get_wrapper()->is_index_created() ) {

			Logger::save_log( array(
				'timestamp'      => time(),
				'message'        => 'Failed to execute syncing actions for ' . $actions . ' items.',
				'data'           => array( 'document_type' => $this->name, 'queued_actions' => $actions )
			) );

			$this->queued_actions = $actions;
			$this->save_actions();

		//else execute the actions now
		} else {

			//Begin a bulk transaction
			$this->get_wrapper()->get_client()->begin();

			foreach ( $actions as $identifier => $object ) {
				foreach ( $object as $action => $args ) {
					$this->$action( $identifier, $args );
				}
			}

			//Finish the bulk transaction
			$this->get_wrapper()->get_client()->commit();
		}

		$this->clear_lock( 'execute_queued_actions' );
	}

	/**
	 * Set a flag when we are performing a full index
	 *
	 * @param $bool
	 */
	function set_is_doing_full_index( $bool ) {

		if ( $bool ) {
			update_option( 'hmes_' . $this->name . '_is_doing_full_index', time() );
		} else {

			delete_option( 'hmes_' . $this->name . '_is_doing_full_index' );
		}

	}

	/**
	 * Check if we are performing a full index
	 *
	 * @return bool
	 */
	function get_is_doing_full_index() {

		$val = get_option( 'hmes_' . $this->name . '_is_doing_full_index', 0 );

		return strtotime( '-30 minutes', time() ) < $val;
	}

	/**
	 * Get the status of the index
	 *
	 * @return array
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

	/**
	 * Delete all items from the index
	 */
	public function delete_all_indexed_items() {

		$wrapper = $this->get_wrapper();

		$this->get_client()->request( array( '/', $wrapper->args['index'], $this->name ), 'DELETE' );
	}
}