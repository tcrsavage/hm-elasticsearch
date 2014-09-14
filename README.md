hm-elasticsearch
================

WordPress elasticsearch integration

This plugin adds automatic indexing capabilities and elastic search client wrapping to your wordpress site. The plugin is designed for developers who whish to conduct heavily customised elasticsearch queries on their WordPress data

- Automaitcally indexes and syncs posts, users, comments and terms along with applicable meta into your elasticsearch server
- Adds convenient wrappers to your document types with low level search API
- Logs bad requests and errors into a dedicated database stored log

###Setup

1. Set up your elasticsearch server (https://gist.github.com/wingdspur/2026107)
2. Download and install the plugin in your plugins/mu-plugins directory, then activate
3. Navigate to the main HM Elasticsearch settings page in wp-admin and add your elasticsearch server's address+port+protocol
4. Check that 'Status' reads 'OK' after submitting your settings. If not, check your settings and try again
5. Navigate to the 'indexing' screen and reindex all your document types to apply an initial sync to your existing data
6. Navigate to the logs page and check for any errors, then check your php error logs to ensure there are no issues with your setup

###Running queries

```
$type = \HMES\Type_Manager::get_type( 'post' );

//Get published posts which were authored by author ID=1 queried by post_title, post_content contains 'My', 'test', 'post'

$r = $type->search( array(
	"query" => array(
		'bool' => array(
			'must' => array(
				array(
					'term' => array(
						'post_type' => 'post',
					),
				),
				array(
					'term' => array(
						'post_status' => 'publish',
					),
				),
				array(
					'term' => array(
						'post_author' => '1'
					)
				),
				array(
					'multi_match' => array(
						'query'  => 'My test post',
						'fields' => array( 'post_title^5', 'post_content' )
					)
				),
			)
		)
	)
) );
```
