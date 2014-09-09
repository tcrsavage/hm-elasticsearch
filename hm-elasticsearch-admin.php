<?php

namespace HMES;

add_action( 'admin_menu', 'hm_es_admin_screen' );

/**
 * Add a submenu page to settings for HMES
 */
function admin_screen() {

	$hook = add_submenu_page( 'options-general.php', 'ElasticSearch Settings', 'ElasticSearch Settings', 'manage_options', 'elastic-search-settings', function() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>ElasticSearch Settings</h2>

			<form method="post">
				<?php wp_nonce_field( 'hm_es_settings', 'hm_es_settings' ); ?>

				<table class="form-table">
					<tbody>
					<tr valign="top">
						<th scope="row"><label for="hm_es_host">Elastic Search Host</label></th>
						<td><input name="hm_es_host" type="text" id="hm_es_host" value="<?php echo Configuration::get_default_host(); ?>" placeholder="10.1.1.5" class="regular-text"></td>
					</tr>

					<tr valign="top">
						<th scope="row"><label for="hm_es_port">Elastic Search Port</label></th>
						<td><input name="hm_es_port" type="text" id="hm_es_port" value="<?php echo Configuration::get_default_port(); ?>" placeholder="9200" class="regular-text"></td>
					</tr>

					<tr valign="top">
						<th scope="row"><label for="hm_es_protocol">Elastic Search Protocol</label></th>
						<td>
							<select	id="hm_es_protocol" name="hm_es_protocol">
								<?php foreach ( Configuration::get_supported_protocols() as $protocol => $label ) : ?>
									<option value="<?php echo $protocol; ?>" <?php selected( $protocol, Configuration::get_default_protocol() ); ?>><?php echo $label; ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row"><label for="hm_es_is_enabled">Enable Elastic Search Indexing</label></th>
						<td>
							<input type="hidden" name="hm_es_is_enabled" value="0" />
							<input name="hm_es_is_enabled" type="checkbox" id="hm_es_is_enabled" <?php checked( Configuration::get_is_enabled() ); ?> value="1">
						</td>
					</tr>

					<?php if ( Logger::count_logs() ) : ?>

						<tr valign="top">
							<th scope="row"><label for="hm_es_clear_logs">Clear Logs</label></th>
							<td>
								<input type="hidden" name="hm_es_clear_logs" value="0" />
								<input name="hm_es_clear_logs" type="checkbox" id="hm_es_clear_logs" value="1">
							</td>
						</tr>

					<?php endif; ?>

					<tr valign="top">
						<th scope="row"><label for="hm_es_reindex">Reindex Everything</label></th>
						<td>
							<input type="hidden" name="hm_es_reindex" value="0" />
							<input name="hm_es_reindex" type="checkbox" id="hm_es_reindex" value="1">
						</td>
					</tr>

					<tr valign="top">
						<?php $status = Wrapper::get_instance()->is_connection_available( array( 'log' => false ) ); ?>
						<th scope="row"><label for="">Status</label></th>
						<td><span style="color: <?php echo ( $status ) ? 'green' : 'red'; ?>"><?php echo ( $status ) ? 'OK' : 'Connection failed'; ?></span></td>
					</tr>

					</tbody>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"></p>
			</form>
		</div>

		<?php if ( Logger::count_logs() ) : ?>

			<?php $page = ( ! empty( $_GET['log_page'] ) ) ? intval( $_GET['log_page'] ) : 1; ?>

			<h2>Logs</h2>

			<div class="wrap">
				<table class="widefat hmes-log-table">
					<thead>
					<tr>
						<th>ID</th>
						<th>Type</th>
						<th>Date</th>
						<th>Index</th>
						<th>Doc Type</th>
						<th>Caller</th>
						<th>Args</th>
						<th>Message</th>
						<th>Expand</th>
					</tr>
					</thead>
					<tbody>
					<?php foreach ( Logger::get_paginated_logs( $page, 20 ) as $entry_number => $log_item ) : ?>
						<tr>
							<td class="td-id"><div><pre><?php echo $entry_number; ?></pre></div></td>
							<td class="td-type"><div><pre><?php echo $log_item['type']; ?></pre></div></td>
							<td class="td-date"><div><pre><?php echo date( 'Y-m-d H:i:s', $log_item['timestamp'] ); ?></pre></div></td>
							<td class="td-index"><div><pre><?php echo $log_item['index']; ?></pre></div></td>
							<td class="td-document-type"><div><pre><?php echo $log_item['document_type']; ?></pre></div></td>
							<td class="td-caller"><div><pre><?php echo $log_item['caller']; ?></pre></div></td>
							<td class="td-args"><div><pre><?php print_r( $log_item['args'] )?></pre></div></td>
							<td class="td-message"><div><pre><?php print_r( $log_item['message'] )?></pre></div></td>
							<td class="expand"><div class="cell">+</div></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( ( $log_count = Logger::count_logs() ) > 20 ) : ?>
					<div class="hmes-log-table-pagination">
						<span>Page</span>
						<?php for ( $i = 1; $i < ( ( $log_count + 20 ) / 20 ); $i++ ) : ?>
							<a href="<?php echo add_query_arg( 'log_page', $i ); ?>"><?php echo $i; ?></a>
						<?php endfor; ?>
					</div>
				<?php endif; ?>

			</div>

		<?php endif; ?>

	<?php
	} );

	add_action( 'load-'. $hook, 'hm_es_init_elastic_search_index', 9 );
	add_action( 'load-'. $hook, 'hm_es_process_admin_screen_form_submission' );
	add_action( 'load-'. $hook, 'hm_es_enqueue_admin_assets' );
}

/**
 * Capture form submissions from the HMES settings page
 */
function hm_es_process_admin_screen_form_submission() {

	if ( ! isset( $_POST['submit'] ) || ! wp_verify_nonce( $_POST['hm_es_settings'], 'hm_es_settings' ) )
		return;

	if ( isset( $_POST['hm_es_host'] ) )
		Configuration::set_default_host( str_replace( 'http://', '', sanitize_text_field( $_POST['hm_es_host'] ) ) );

	if ( isset( $_POST['hm_es_port'] ) )
		Configuration::set_default_port( sanitize_text_field( $_POST['hm_es_port'] ) );

	if ( isset( $_POST['hm_es_protocol'] ) && array_key_exists( sanitize_text_field( $_POST['hm_es_protocol'] ), Configuration::get_supported_protocols() ) ) {
		Configuration::set_default_protocol( sanitize_text_field( $_POST['hm_es_protocol'] ) );
	}

	if ( isset( $_POST['hm_es_is_enabled'] ) ) {

		Configuration::set_is_enabled( (bool) sanitize_text_field( $_POST['hm_es_is_enabled'] ) );
	}

	if ( ! empty( $_POST['hm_es_clear_logs'] ) ) {
		Logger::set_logs( array() );
	}

	if ( ! empty( $_POST['hm_es_reindex'] ) ) {

		if ( Wrapper::get_instance()->is_connection_available() && Wrapper::get_instance()->is_index_created() ) {

			hm_es_delete_elastic_search_index();
			hm_es_init_elastic_search_index();

			foreach ( Type_Manager::get_types() as $type ) {
				$type->index_all();
			}
		}
	}

	wp_redirect( add_query_arg( 'updated', '1' ) );

	exit;
}

/**
 * Enqueue scripts and styles for the HMES settings page
 */
function enqueue_admin_assets()  {

	wp_enqueue_script( 'hmes-admin-scripts', plugin_dir_url( __FILE__ ) . 'assets/admin-scripts.js', array( 'jquery' ), false, true );
	wp_enqueue_style( 'hmes-admin-scripts', plugin_dir_url( __FILE__ ) . 'assets/admin-styles.css' );

};
