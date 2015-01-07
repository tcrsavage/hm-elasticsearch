<?php

namespace HMES\Types;

class Post extends Base {

	public $name             = 'post';
	public $index_hooks      = array( 'edit_post', 'save_post', 'publish_post' );
	public $delete_hooks     = array( 'delete_post' );
	public $mappable_hooks   = array(
		'added_post_meta'        => 'update_post_meta_callback',
		'updated_post_meta'      => 'update_post_meta_callback',
		'deleted_post_meta'      => 'update_post_meta_callback',
		'set_object_terms'       => 'set_object_terms_callback',
		'transition_post_status' => 'transition_post_status_callback',
	);

	/**
	 * Called when post meta is added/deleted/edited - triggers re-indexing of the applicable post
	 *
	 * @param $meta_id
	 * @param $post_id
	 */
	public function update_post_meta_callback( $meta_id, $post_id ) {

		$this->index_callback( $post_id );
	}

	/**
	 * Called when a post has it's terms modified - triggers re-indexing of applicable post
	 *
	 * @param $post_id
	 */
	public function set_object_terms_callback( $post_id ) {

		$this->index_callback( $post_id );
	}

	/**
	 * Hook in on transition post status and check if the new status is one we can index, if so - index, if not, delete
	 *
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 */
	public function transition_post_status_callback( $new_status, $old_status, $post ) {

		$post = (array) $post;

		//Make sure it's ok to index this post - if it can't be searched by wp_query, skip it
		if ( ! in_array( $new_status, get_post_types( array( 'exclude_from_search' => false ) ) ) ) {

			$this->delete_callback( $post['ID'] );

		} else {

			$this->index_callback( $post['ID'] );
		}
	}

	/**
	 * Queue the indexing of an item - called when a post is modified or added to the database
	 *
	 * @param $post_id
	 * @param array $args
	 */
	public function index_callback( $post_id, $args = array()  ) {

		$post = (array) get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		//Make sure it's ok to index this post - if it can't be searched by wp_query, skip it
		if ( ! in_array( $post['post_type'], get_post_types( array( 'exclude_from_search' => false ) ) ) ) {

			return;
		}

		$this->add_action( 'index_item', $post_id );
	}

	/**
	 * Queue the deletion of an item - called when a post is deleted from the database
	 *
	 * @param $post_id
	 * @param array $args
	 */
	public function delete_callback( $post_id, $args = array()  ) {

		$this->add_action( 'delete_item', $post_id );
	}

	/**
	 * Parse an item for indexing, accepts post ID or post object
	 *
	 * @param $item
	 * @param array $args
	 * @return array|bool
	 */
	public function parse_item_for_index( $item, $args = array() ) {

		//get a valid post object as array (populate if only id is supplied)
		if ( is_numeric( $item ) ) {
			$item = (array) get_post( $item );
		} else {
			$item = (array) $item;
		}

		if ( empty( $item['ID'] ) ) {
			return false;
		}

		$item['meta'] = get_metadata( 'post', (int) $item['ID'], '', true );

		foreach ( $item['meta'] as $meta_key => $meta_array ) {

			$item['meta'][$meta_key] = reset( $meta_array );
		}

		$item['post_date_timestamp'] = strtotime( $item['post_date'] );
		$item['post_modified_timestamp'] = strtotime( $item['post_modified'] );

		$item['taxonomies'] = array();

		foreach ( get_taxonomies() as $tax ) {

			$terms = get_the_terms( $item['ID'], $tax );

			if ( $terms && is_array( $terms ) ) {
				$item['taxonomies'][$tax] = array_map( function( $term ) {
					return $term->term_id;
				}, array_values( $terms ) );
			}
		};

		$item = apply_filters( 'hmes_parsed_item_for_index_' . $this->name, $item );

		return $item;
	}

	/**
	 * Get paginated comments for use by index_all base class method
	 *
	 * @param $page
	 * @param $per_page
	 * @return array
	 */
	public function get_items( $page, $per_page ) {

		global $wpdb;

		$in_search_post_types = get_post_types( array('exclude_from_search' => false ) );

		$posts = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->posts WHERE post_type IN ('" . join("', '", $in_search_post_types ) . "') ORDER BY ID ASC LIMIT %d, %d", ( $page > 0 ) ? $per_page * ( $page -1 ) : 0, $per_page ) );

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

		//wp query only queries for posts which are set to exclude_from_search->false, so honor that here
		$in_search_post_types = get_post_types( array('exclude_from_search' => false ) );

		$r = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type IN ('" . join("', '", $in_search_post_types ) . "')" );

		return (int) $r;
	}

}