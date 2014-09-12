<?php

namespace HMES;

class Client_Abstraction extends \ElasticSearch\Client {

	protected $transport_ref;

	static function getTransports() {
		return array(
			//'http'  => 'ElasticSearch\\Transport\\HTTP',
			'http' => '\\HMES\\Transports\\WP_HTTP',
			'https' => '\\HMES\\Transports\\WP_HTTP',
		);
	}

	/**
	 * Get a client instance
	 * Defaults to opening a http transport connection to 127.0.0.1:9200
	 *
	 * @param string|array $config Allow overriding only the configuration bits you desire
	 *   - _transport_
	 *   - _host_
	 *   - _port_
	 *   - _index_
	 *   - _type_
	 * @throws \Exception
	 * @return \ElasticSearch\Client
	 */
	public static function connection( $config = array() ) {

		if (! $config && ($url = getenv('ELASTICSEARCH_URL') ) ) {
			$config = $url;
		}

		if (is_string( $config ) ) {
			$config = self::parseDsn($config);
		}

		$config += self::$_defaults;

		$protocol = $config['protocol'];

		$protocols = static::getTransports();

		if ( ! array_key_exists( $protocol, $protocols ) ) {
			throw new \Exception( "Tried to use unknown protocol: $protocol" );
		}

		$class = $protocols[$protocol];

		if ( null !== $config['timeout'] && ! is_numeric( $config['timeout'] ) ) {
			throw new \Exception("HTTP timeout should have a numeric value when specified.");
		}

		$server = is_array( $config['servers'] ) ? $config['servers'][0] : $config['servers'];

		list( $host, $port ) = explode( ':', $server);

		$transport = new $class( $host, $port, $config['timeout'] );

		$client = new self( $transport, $config['index'], $config['type']);

		//hackery to allow access to the transport
		$client->transport_ref = $transport;

		$client->config( $config );

		return $client;
	}

	public function getTransport() {
		return $this->transport_ref;
	}

}