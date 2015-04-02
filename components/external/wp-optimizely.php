<?php

require __DIR__ . '/optimizely-php/optimizely.php';

class WP_Optimizely extends Optimizely {
	/**
	 * Override the curl-based function to use baseline WordPress
	 */
	protected function request( $options ) {
		if ( ! $this->api_token ) {
			return FALSE;
		}//end if

		$url = $this->api_url . $options['function'];

		$args = array(
			'method' => $options['method'],
			'user-agent' => $this->useragent,
			'headers' => array(
				'Token' => $this->api_token,
				'Content-Type' => 'application/json',
			),
			'sslverify' => $this->ssl_verifypeer,
		);

		if ( 'POST' == $options['method']
		  || 'PUT'  == $options['method'] ) {
			$args['body'] = json_encode( $options['data'] );
		}//end if

		$res = wp_remote_request( $url, $args );

		if ( is_wp_error( $res ) ) {
			return FALSE;
		}//end if

		// the following variables are primarily for debugging purposes
		$this->request_http_code = $res['response']['code'];
		$this->request_info = $res;
		$this->request_url = $url;
		$this->request_response = $res['body'];

		$return = json_decode( $res['body'] );
		return $return ?: $res['body'];
	}//end request
}// end WP_Optimizely
