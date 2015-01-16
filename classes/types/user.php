<?php

namespace HMES\Types;

class User extends Base {

	public $name             = 'user';
	public $index_hooks      = array( 'user_register', 'profile_update' );
	public $delete_hooks     = array( 'deleted_user' );
	public $mappable_hooks   = array(
		'added_user_meta'   => 'update_user_meta_callback',
		'updated_user_meta' => 'update_user_meta_callback',
		'deleted_user_meta' => 'update_user_meta_callback'
	);

	/**
	 * Called when user meta is added/deleted/updated
	 *
	 * @param $meta_id
	 * @param $user_id
	 */
	public function update_user_meta_callback( $meta_id, $user_id ) {

		$this->index_callback( $user_id );
	}

	/**
	 * Queue the indexing of an item - called when a user is modified or added to the database
	 *
	 * @param $item
	 * @param array $args
	 */
	public function index_callback( $item, $args = array()  ) {

		$user = get_userdata( $item );

		if ( ! $user ) {
			return;
		}

		$this->add_action( 'index_item', $item );
	}

	/**
	 * Queue the deletion of an item - called when a user is deleted from the database
	 *
	 * @param $user_id
	 * @param array $args
	 */
	public function delete_callback( $user_id, $args = array()  ) {

		$this->add_action( 'delete_item', $user_id );
	}

	/**
	 * arse an item for indexing, accepts user ID or user object
	 *
	 * @param $item
	 * @param array $args
	 * @return array|bool
	 */
	public function parse_item_for_index( $item, $args = array() ) {

		//get a valid user object as array (populate if only id is supplied)
		if ( is_numeric( $item ) ) {
			$item = (array) get_userdata( $item );
		} else {
			$item = (array) $item;
		}

		if ( empty( $item['ID'] ) ) {
			return false;
		}

		$item['meta'] = get_metadata( 'user', (int) $item['ID'], '', true );

		foreach ( $item['meta'] as $meta_key => $meta_array ) {
			$item['meta'][$meta_key] = reset( $meta_array );
		}

		$item = apply_filters( 'hmes_parsed_item_for_index_' . $this->name, $item );

		return $item;
	}

	/**
	 * Get paginated users for use by index_all base class method
	 *
	 * @param $page
	 * @param $per_page
	 * @return array
	 */
	public function get_items( $page, $per_page ) {

		$posts = get_users( array(
			'offset' => ( $page > 0 ) ? $per_page * ( $page -1 ) : 0,
			'number' => $per_page,
			'blog_id' => null
		) );

		return $posts;
	}

	/**
	 * Get paginated users ids for use by index_pending base class method
	 *
	 * @param $page
	 * @param $per_page
	 * @return array
	 */
	public function get_items_ids( $page, $per_page ) {

		$posts = get_users( array(
			'offset' => ( $page > 0 ) ? $per_page * ( $page -1 ) : 0,
			'number' => $per_page,
			'blog_id' => null,
			'fields'  => 'ID'
		) );

		return $posts;
	}

	/*
	 * Get an integer count of the number of items which can potentially be indexed in the database
	 *
	 * Should serve to return a count which matches the same number of items which can be obtained from use of the get_items method
	 *
	 * @return int
	 */
	function get_items_count() {

		global $wpdb;

		$r = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->users" );

		return (int) $r;
	}

}