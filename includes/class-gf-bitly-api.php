<?php

defined( 'ABSPATH' ) || die();

/**
 * Handles all interactions with the Bitly API.
 *
 * @since 2.8
 */
class GF_Bitly_Api {
	/**
	 * URL of the Bitly API.
	 */
	const URL = 'https://api-ssl.bitly.com';

	/**
	 * Current version of the API.
	 */
	const VERSION = 'v4';

	/**
	 * AddOn class.
	 *
	 * @var GFTwilio
	 */
	private $addon;

	/**
	 * API access token.
	 *
	 * @since 2.8
	 * @var string
	 */
	private $token;

	/**
	 * GF_Bitly_Api constructor.
	 *
	 * @since 2.8
	 *
	 * @param GFTwilio $addon The addon class.
	 * @param string   $token Authorization token.
	 */
	public function __construct( $addon, $token ) {
		$this->addon = $addon;
		$this->token = $token;
	}

	/**
	 * Make a request to the Bitly API.
	 *
	 * @since  2.8
	 *
	 * @param string $path          Request path.
	 * @param string $method        Request method.
	 * @param array  $options       Request options.
	 *
	 * @return array|WP_Error
	 */
	public function make_request( $path, $method, $options = array() ) {
		$this->addon->log_debug( __METHOD__ . "(): Making request to {$path}." );

		$args = array_filter( $this->get_request_args( $method, $options ) );

		if ( ! isset( $args['headers'] ) ) {
			return new WP_Error( 'authorization_required', __( 'Authorization headers required.', 'gravityformstwilio' ) );
		}

		return wp_remote_request(
			$this->get_request_url( $path, $method, $options ),
			$args
		);
	}

	/**
	 * Determine whether the request is a type which requires argument encoding.
	 *
	 * @since 2.8
	 *
	 * @param string $method The request method.
	 *
	 * @return bool
	 */
	private function needs_request_body( $method ) {
		return in_array( strtoupper( $method ), array( 'POST', 'PUT' ), true );
	}

	/**
	 * Get the fully-constructed request URL for an endpoint.
	 *
	 * @since 2.8
	 *
	 * @param string $endpoint The endpoint path for the API request.
	 * @param string $method   The request method.
	 * @param array  $options  Optional set of values to include with the request.
	 *
	 * @return string
	 */
	private function get_request_url( $endpoint, $method, $options = array() ) {
		$base_url = self::URL . '/' . self::VERSION . '/' . $endpoint;

		if ( $this->needs_request_body( $method ) ) {
			return $base_url;
		}

		return empty( $options ) ? $base_url : add_query_arg( $options, $base_url );
	}

	/**
	 * Get the arguments to include with a call to wp_remote_request.
	 *
	 * @since 2.8
	 *
	 * @param string $method  The request method.
	 * @param array  $options Optional set of request options.
	 *
	 * @return array
	 */
	private function get_request_args( $method, $options = array() ) {
		if ( ! $this->needs_request_body( $method ) ) {
			return array(
				'method'  => $method,
				'headers' => $this->get_authorization_headers(),
			);
		}

		$body = wp_json_encode( $options );

		return array(
			'body'    => $body,
			'method'  => $method,
			'headers' => $this->get_authorization_headers( $body ),
		);
	}

	/**
	 * Make the request to the `shorten` endpoint.
	 *
	 * @since 2.8
	 *
	 * @param string $url The URL to shorten.
	 *
	 * @return array|WP_Error The result of the WP-HTTP request.
	 */
	public function do_shorten_request( $url ) {
		return $this->make_request( 'shorten', 'POST', array( 'long_url' => $url ) );
	}

	/**
	 * Make the request to the `user` endpoint.
	 *
	 * @since 2.8
	 *
	 * @return array|WP_Error
	 */
	public function do_user_request() {
		return $this->make_request( 'user', 'GET' );
	}

	/**
	 * Make a call to the `shorten` endpoint and return the shortened URL.
	 *
	 * @since 2.8
	 *
	 * @param string $url The URL to shorten.
	 */
	public function get_shortened_url( $url ) {
		$response = $this->do_shorten_request( $url );

		if ( ! $this->is_successful_response( $response, array( 200, 201 ) ) ) {
			return $url;
		}

		// Decode response.
		$shortened_url = $this->addon->maybe_decode_json( wp_remote_retrieve_body( $response ) );

		return isset( $shortened_url['link'] ) ? $shortened_url['link'] : $url;
	}

	/**
	 * Validates the provided authentication credentials by making a request to the user endpoint.
	 *
	 * @since 2.8
	 *
	 * @return bool
	 */
	public function validate_credentials() {
		if ( ! $this->token ) {
			return false;
		}

		$response = $this->do_user_request();

		if ( is_wp_error( $response ) ) {
			$this->addon->log_error( __METHOD__ . "(): {$response->get_error_message()}" );
			return false;
		}

		return $this->is_successful_response( $response );
	}

	/**
	 * Confirm whether a response from the Bitly API was successful.
	 *
	 * @since 2.8
	 *
	 * @param array $response The response array from a WP-HTTP request.
	 * @param array $successful_codes Optional array of successful codes if those beyond 200 should be evaluated.
	 *
	 * @return bool
	 */
	public function is_successful_response( $response, $successful_codes = array( 200 ) ) {
		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		return in_array( $response_code, $successful_codes, true );
	}

	/**
	 * Get the headers to use to authorize against the Bitly API.
	 *
	 * @since 2.8
	 *
	 * @param string $access_token
	 *
	 * @return array
	 */
	private function get_authorization_headers( $payload = '' ) {
		// If access token is not set, return.
		if ( rgblank( $this->token ) ) {
			return array();
		}

		$headers = array(
			'Authorization' => "Bearer {$this->token}",
			'Content-Type'  => 'application/json',
		);

		if ( ! empty( $payload ) && is_string( $payload ) ) {
			$headers['Content-Length'] = strlen( $payload );
		}

		return $headers;
	}
}
