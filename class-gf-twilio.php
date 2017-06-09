<?php

GFForms::include_feed_addon_framework();

class GFTwilio extends GFFeedAddOn {

	protected $_version = GF_TWILIO_VERSION;
	protected $_min_gravityforms_version = '1.9.11';
	protected $_slug = 'gravityformstwilio';
	protected $_path = 'gravityformstwilio/twilio.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.gravityforms.com';
	protected $_title = 'Twilio Add-On';
	protected $_short_title = 'Twilio';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_twilio', 'gravityforms_twilio_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_twilio';
	protected $_capabilities_form_settings = 'gravityforms_twilio';
	protected $_capabilities_uninstall = 'gravityforms_twilio_uninstall';
	protected $_enable_rg_autoupgrade = true;

	private static $_instance = null;

	/**
	 * Get an instance of this class.
	 *
	 * @return GFTwilio
	 */
	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFTwilio();
		}

		return self::$_instance;
	}

	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Initiate processing the feed.
	 *
	 * @param array $feed The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 *
	 * @return void
	 */
	public function process_feed( $feed, $entry, $form ) {

		$this->export_feed( $entry, $form, $feed );
	}

	/**
	 * Process the feed, send the message.
	 *
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 * @param array $feed The feed object currently being processed.
	 */
	public function export_feed( $entry, $form, $feed ) {

		$this->send_sms( $this->get_message_from( $feed ), $feed['meta']['toNumber'], $feed['meta']['smsMessage'], $feed['meta']['shortenURL'], $feed['id'], $entry );
	}

	/**
	 * @param string $from The message From number or Alphanumeric Sender ID.
	 * @param string $to The message To number.
	 * @param string $body The message body.
	 * @param bool|false $shorten_urls Should URLs in the body be shortened?
	 * @param int $feed_id The ID of the feed currently being processed.
	 * @param array $entry The entry object currently being processed.
	 */
	private function send_sms( $from, $to, $body, $shorten_urls = false, $feed_id, $entry ) {

		$api      = $this->get_api();
		$messages = $this->prepare_message( $body, $shorten_urls, $feed_id, $entry );

		for ( $i = 0, $count = count( $messages ); $i < $count; $i ++ ) {
			$body = $messages[ $i ];

			//Add ... to all messages except last one
			if ( $i < $count - 1 ) {
				$body .= ' ...';
			}

			//call filter to change the TO phone number
			$to   = apply_filters( 'gform_twilio_set_to_phone_number', $to, $entry, $feed_id );
			$to   = preg_replace( '|[^\d\+]|', '', $to );
			$data = array( 'From' => $from, 'To' => $to, 'Body' => $body );
			$this->log_debug( __METHOD__ . '(): Sending SMS to Twilio => ' . print_r( $data, true ) );
			$response = $api->request( "{$api->base_path}/Messages", 'POST', $data );
			$this->log_debug( __METHOD__ . '(): Response from Twilio for SMS => ' . print_r( $response, true ) );
		}
	}


	// # ADMIN FUNCTIONS -----------------------------------------------------------------------------------------------

	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Send SMS only when a payment is received.', 'gravityformstwilio' )
			)
		);

	}

	// ------- Plugin settings -------

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => esc_html__( 'Twilio Account Information', 'gravityformstwilio' ),
				'description' => sprintf(
					esc_html__( 'Twilio provides a web-service API for businesses to build scalable and reliable communication apps. %1$s Sign up for a Twilio account%2$s to receive SMS messages when a Gravity Form is submitted.', 'gravityformstwilio' ),
					'<a href="http://www.twilio.com" target="_blank">', '</a>'
				),
				'fields'      => array(
					array(
						'name'              => 'accountSid',
						'label'             => esc_html__( 'Account SID', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_credentials' )
					),
					array(
						'name'              => 'authToken',
						'label'             => esc_html__( 'Auth Token', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_credentials' )
					)
				)
			),
			array(
				'title'       => esc_html__( 'Bitly Account Information', 'gravityformstwilio' ),
				'description' => sprintf(
					esc_html__( 'Bitly helps you shorten, track and analyze your links. Enter your Bitly account information below to automatically shorten URLs in your SMS message. If you don\'t have a Bitly account, %1$ssign-up for one here%2$s', 'gravityformstwilio' ),
					'<a href="http://bit.ly" target="_blank">', '</a>.'
				),
				'fields'      => array(
					array(
						'name'              => 'bitlyLogin',
						'label'             => esc_html__( 'Login', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_bitly_credentials' )
					),
					array(
						'name'              => 'bitlyApikey',
						'label'             => esc_html__( 'API Key', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_bitly_credentials' )
					)
				)
			)
		);

	}

	// ------- Feed list page -------

	/**
	 * Prevent feeds being listed or created if the api key isn't valid.
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->is_valid_credentials();
	}

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {
		return array(
			'feedName'   => esc_html__( 'Name', 'gravityformstwilio' ),
			'fromNumber' => esc_html__( 'From', 'gravityformstwilio' ),
			'toNumber'   => esc_html__( 'To Number', 'gravityformstwilio' ),
		);
	}

	/**
	 * Returns the value to be displayed in the From column.
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_fromNumber( $feed ) {
		return $this->get_message_from( $feed );
	}

	public function feed_settings_fields() {

		$account_info = $this->get_account_info();

		if ( $account_info['is_sandbox'] ) {
			$to_number_setting = array(
				'name'    => 'toNumber',
				'label'   => esc_html__( 'To Number', 'gravityformstwilio' ),
				'type'    => 'select',
				'choices' => $this->get_twilio_phone_numbers( 'outgoing_numbers', $account_info ),
				'tooltip' => '<h6>' . esc_html__( 'To Number', 'gravityformstwilio' ) . '</h6>' . esc_html__( 'Phone number to send this message to. For Twilio trial accounts, you can only send SMS messages to validated numbers. To validate a number, log in to your Twilio account and navigate to the \'Numbers\' tab.', 'gravityformstwilio' ),
			);
		} else {
			$to_number_setting = array(
				'name'    => 'toNumber',
				'label'   => esc_html__( 'To Number', 'gravityformstwilio' ),
				'type'    => 'text',
				'tooltip' => '<h6>' . esc_html__( 'To Number', 'gravityformstwilio' ) . '</h6>' . esc_html__( 'Phone number to send this message to.', 'gravityformstwilio' ),
			);
		}

		return array(
			array(
				'title'       => esc_html__( 'Twilio Feed Settings', 'gravityformstwilio' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => esc_html__( 'Name', 'gravityformstwilio' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => '<h6>' . esc_html__( 'Name', 'gravityformstwilio' ) . '</h6>' . esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformstwilio' ),
					),
					array(
						'name'                => 'fromNumber',
						'label'               => esc_html__( 'From', 'gravityformstwilio' ),
						'type'                => 'select_custom',
						'choices'             => $this->get_twilio_phone_numbers( 'incoming_numbers', $account_info ),
						'tooltip'             => '<h6>' . esc_html__( 'From', 'gravityformstwilio' ) . '</h6>' . esc_html__( 'Phone number or Alphanumeric Sender ID that the message will be sent FROM.', 'gravityformstwilio' ),
						'required'            => true,
						'validation_callback' => array( $this, 'validate_from' ),
					),
					$to_number_setting,
					array(
						'name'    => 'smsMessage',
						'label'   => esc_html__( 'Message', 'gravityformstwilio' ),
						'type'    => 'sms_message',
						'tooltip' => '<h6>' . esc_html__( 'Message', 'gravityformstwilio' ) . '</h6>' . esc_html__( 'Write the SMS message you would like to be sent. You can insert fields submitted by the user by selecting them from the \'Insert merge code\' drop down. SMS message are limited to 160 characters. Messages larger than 160 characters will automatically be split into multiple SMS messages.', 'gravityformstwilio' ),
					),
					array(
						'name'           => 'feed_condition',
						'label'          => esc_html__( 'Conditional Logic', 'gravityformstwilio' ),
						'type'           => 'feed_condition',
						'checkbox_label' => esc_html__( 'Enable', 'gravityformstwilio' ),
						'instructions'   => esc_html__( 'Export to Twilio if', 'gravityformstwilio' ),
						'tooltip'        => '<h6>' . esc_html__( 'Conditional Logic', 'gravityformstwilio' ) . '</h6>' . esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Twilio when the condition is met. When disabled, all form submissions will be exported.', 'gravityformstwilio' )

					),
				),
			),
		);
	}

	/**
	 * Define the markup for the sms_message type field.
	 *
	 * @param array $field The field properties.
	 * @param bool|true $echo Should the setting markup be echoed.
	 *
	 * @return string|void
	 */
	public function settings_sms_message( $field, $echo = true ) {

		$field['type']  = 'textarea';
		$field['class'] = 'medium merge-tag-support mt-position-right';

		$html = $this->settings_textarea( $field, false );

		$shortUrlField         = array();
		$shortUrlField['type'] = 'checkbox';
		$shortUrlField['name'] = 'shortenURL_';
		$checkboxes            = array(
			array(
				'label'   => esc_html__( 'Shorten URLs', 'gravityformstwilio' ),
				'name'    => 'shortenURL',
				'tooltip' => '<h6>' . esc_html__( 'Shorten URLs', 'gravityformstwilio' ) . '</h6>' . esc_html__( 'Enable this option to automatically shorten all URLs in your SMS message.', 'gravityformstwilio' ),
			),
		);

		$warning = '';
		if ( ! $this->is_valid_bitly_credentials() ) {
			$checkboxes[0]['disabled'] = 'disabled';
			$warning                   = '<div class="gfield_error" style="width:49%">' .
			                             sprintf( esc_html__( 'Bitly account required. %sEnter your Bitly account information%s to enable this option.', 'gravityformstwilio' ), '<a href="' . esc_url( $this->get_plugin_settings_url() ) . '">', '</a>' ) .
			                             '</div>';

		}

		$shortUrlField['choices'] = $checkboxes;
		$html2                    = $this->settings_checkbox( $shortUrlField, false ) . $warning;


		if ( $echo ) {
			echo $html . $html2;
		}

		return $html . $html2;

	}

	/**
	 * Validate the text input for the message From setting.
	 *
	 * @param array $field The field properties.
	 * @param string $field_setting The field value.
	 */
	public function validate_from( $field, $field_setting ) {

		if ( $field_setting != 'gf_custom' ) {
			return;
		}

		$settings = $this->get_posted_settings();

		$field['name'] .= '_custom';
		$from = rgar( $settings, 'fromNumber_custom' );

		// Alphanumeric Sender ID can't be more than 11 characters
		if ( rgblank( $from ) ) {
			$this->set_field_error( $field );
		} elseif ( strlen( $from ) > 11 ) {
			$this->set_field_error( $field, __( 'The Alphanumeric Sender ID must be no longer than 11 characters.' ), 'gravityformstwilio' );
		} elseif ( ! preg_match( '/^[a-zA-Z0-9 ]+$/', $from ) ) {
			$this->set_field_error( $field, __( 'The Alphanumeric Sender ID only supports upper and lower case letters, the digits 0 through 9, and spaces.' ), 'gravityformstwilio' );
		}

	}


	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * Return the from number or Alphanumeric Sender ID.
	 *
	 * @param array $feed The feed object being processed.
	 *
	 * @return string
	 */
	public function get_message_from( $feed ) {

		return $feed['meta']['fromNumber'] == 'gf_custom' ? rgar( $feed['meta'], 'fromNumber_custom' ) : $feed['meta']['fromNumber'];
	}

	/**
	 * Retrieve the from/to numbers for use on the feed settings page.
	 *
	 * @param string $type The phone number type. Either incoming_numbers or outgoing_numbers.
	 * @param array|null $account_info The Twilio account information, including the phone numbers.
	 *
	 * @return array|void
	 */
	public function get_twilio_phone_numbers( $type, $account_info = null ) {

		if ( ! $account_info ) {
			$account_info = $this->get_account_info();
		}

		if ( ! $account_info ) {
			return;
		}

		$phone_numbers = array();

		foreach ( $account_info[ $type ] as $number ) {

			$phone_numbers[] = array(
				'label' => $number,
				'value' => $number,
			);

		}

		if ( $type == 'incoming_numbers' ) {
			$phone_numbers[] = array(
				'label' => esc_html__( 'Use Alphanumeric Sender ID', 'gravityformstwilio' ),
				'value' => 'gf_custom'
			);
		}

		return $phone_numbers;

	}

	/**
	 * Check if the Bitly credentials are valid by testing if a url can be shortened.
	 *
	 * @return bool
	 */
	public function is_valid_bitly_credentials() {
		$url = $this->shorten_url( 'http://www.google.com' );

		return $url != 'http://www.google.com';
	}

	/**
	 * Shorten the supplied url using Bitly.
	 *
	 * @param string $url The url to shorten.
	 *
	 * @return string
	 */
	public function shorten_url( $url ) {
		$this->log_debug( __METHOD__ . "(): Processing URL => {$url}" );

		$encoded_url = urlencode( $url );
		$settings    = $this->get_plugin_settings();
		$login       = urlencode( rgar( $settings, 'bitlyLogin' ) );
		$apikey      = urlencode( rgar( $settings, 'bitlyApikey' ) );

		$request_url = "http://api.bit.ly/v3/shorten?login={$login}&apiKey={$apikey}&longUrl={$encoded_url}&format=txt";
		$response    = wp_remote_get( $request_url );

		if ( is_wp_error( $response ) || $response['response']['code'] != 200 ) {
			$this->log_debug( __METHOD__ . '(): Unable to shorten URL. Not changing.' );

			return $url;
		}
		$this->log_debug( __METHOD__ . '(): Response from bitly => ' . print_r( $response, true ) );

		$is_valid = substr( trim( $response['body'] ), 0, 4 ) == 'http';

		return $is_valid ? trim( $response['body'] ) : $url;
	}

	/**
	 * Check if the Twilio API credentials are valid.
	 *
	 * @return bool
	 */
	public function is_valid_credentials() {
		$api = $this->get_api();

		$response = $api->request( "{$api->base_path}" );
		$this->log_debug( __METHOD__ . '(): Response from Twilio => ' . print_r( $response, true ) );

		return ! $response->IsError;

	}

	/**
	 * Retrieve the Twilio account information.
	 *
	 * @return array|bool
	 */
	public function get_account_info() {
		require_once( $this->get_base_path() . '/xml.php' );

		$api = $this->get_api();

		$this->log_debug( __METHOD__ . '(): Getting account information from Twilio.' );
		$response = $api->request( "{$api->base_path}" );
		$this->log_debug( __METHOD__ . '(): Response from Twilio => ' . print_r( $response, true ) );
		if ( $response->IsError ) {
			$this->log_error( __METHOD__ . "(): Unable to retrieve account information => {$response->ErrorMessage}" );

			return false;
		}

		$options = array(
			'OutgoingCallerId'    => array( 'unserialize_as_array' => true ),
			'IncomingPhoneNumber' => array( 'unserialize_as_array' => true )
		);
		$xml     = new RGXML( $options );

		$response_object = $xml->unserialize( $response->ResponseText );
		$is_trial        = strtolower( $response_object['Account']['Type'] ) == 'trial';

		$incoming_numbers = array();
		$outgoing_numbers = array();
		if ( $is_trial ) {
			//Getting Sandbox phone number
			$this->log_debug( __METHOD__ . '(): Trial account - getting Sandbox phone number.' );
			$response = $api->request( "{$api->base_path}/Sandbox" );
			$this->log_debug( __METHOD__ . '(): Response from Twilio => ' . print_r( $response, true ) );
			if ( $response->IsError ) {
				$this->log_error( __METHOD__ . "(): Unable to retrieve Sandbox information => {$response->ErrorMessage}" );

				return false;
			}

			$response_object    = $xml->unserialize( $response->ResponseText );
			$incoming_numbers[] = $response_object['TwilioSandbox']['PhoneNumber'];
			$this->log_debug( __METHOD__ . '(): Sandbox PhoneNumber => ' . print_r( $incoming_numbers, true ) );

			//Getting validated outgoing phone numbers
			$response = $api->request( "{$api->base_path}/OutgoingCallerIds" );
			$this->log_debug( __METHOD__ . '(): Sandbox OutgoingCallerIds: ' . print_r( $response, true ) );
			if ( $response->IsError ) {
				$this->log_error( __METHOD__ . "(): Unable to retrieve Sandbox OutgoingCallerIds => {$response->ErrorMessage}" );

				return false;
			}

			$response_object = $xml->unserialize( $response->ResponseText );
			foreach ( $response_object['OutgoingCallerIds'] as $caller_id ) {
				if ( is_array( $caller_id ) && isset( $caller_id['PhoneNumber'] ) ) {
					$outgoing_numbers[] = $caller_id['PhoneNumber'];
				}
			}
			$this->log_debug( __METHOD__ . '(): Sandbox OutgoingCallerIds: ' . print_r( $outgoing_numbers, true ) );
		} else {
			//Getting incoming phone numbers
			$this->log_debug( __METHOD__ . '(): Live account - getting IncomingPhoneNumbers.' );
			$response = $api->request( "{$api->base_path}/IncomingPhoneNumbers" );
			$this->log_debug( __METHOD__ . '(): Response from Twilio: ' . print_r( $response, true ) );
			if ( $response->IsError ) {
				$this->log_error( __METHOD__ . "(): Unable to retrieve IncomingPhoneNumbers => {$response->ErrorMessage}" );

				return false;
			}

			$response_object = $xml->unserialize( $response->ResponseText );
			foreach ( $response_object['IncomingPhoneNumbers'] as $number ) {
				if ( is_array( $number ) && isset( $number['PhoneNumber'] ) ) {
					$incoming_numbers[] = $number['PhoneNumber'];
				}
			}
			$this->log_debug( __METHOD__ . '(): IncomingPhoneNumbers => ' . print_r( $incoming_numbers, true ) );
		}

		return array(
			'is_sandbox'       => $is_trial,
			'incoming_numbers' => $incoming_numbers,
			'outgoing_numbers' => $outgoing_numbers
		);

	}


	/**
	 * Shorten urls from the message body.
	 *
	 * @param array $matches The url to be shortened.
	 *
	 * @return string
	 */
	public function regex_shorten_url( $matches ) {
		//remove the mt delimiter from the string containing the url before passing to bitly
		$url = str_replace( array( '<mt>', '</mt>' ), '', $matches[0] );

		return $this->shorten_url( $url );
	}

	/**
	 * Process the message body replacing merge tags etc.
	 *
	 * @param string $text The message body.
	 * @param bool|false $shorten_urls Should URLs in the body be shortened?
	 * @param int $feed_id The ID of the feed currently being processed.
	 * @param array $entry The entry object currently being processed.
	 *
	 * @return array
	 */
	public function prepare_message( $text, $shorten_urls = false, $feed_id, $entry ) {
		$this->log_debug( __METHOD__ . '(): Processing.' );
		$form = GFFormsModel::get_form_meta( $entry['form_id'] );

		if ( $shorten_urls ) {
			// remove spaces from all merge tags, we need to do this so we can handle urls that have spaces in them
			preg_match_all( '/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi', $text, $matches, PREG_SET_ORDER );
			if ( is_array( $matches ) ) {
				foreach ( $matches as $match ) {
					$new_tag = str_replace( ' ', '', $match[0] );
					$text    = str_replace( $match[0], $new_tag, $text );
				}
			}

			// find urls, process merge tags in url, shorten url
			preg_match_all( '~(https?|ftp):\/\/\S+~', $text, $matches, PREG_SET_ORDER );
			if ( is_array( $matches ) ) {
				foreach ( $matches as $match ) {
					$url  = GFCommon::replace_variables( $match[0], $form, $entry, false, true, false, 'text' );
					$text = str_replace( $match[0], $this->shorten_url( $url ), $text );
				}
			}

			// find any remaining merge tags (field or meta)
			preg_match_all( '/{.+}/m', $text, $matches, PREG_SET_ORDER );
			if ( is_array( $matches ) && ! empty( $matches ) ) {
				// surround merge tags with the mt delimiter
				foreach ( $matches as $match ) {
					$text = str_replace( $match[0], '<mt>' . $match[0] . '</mt>', $text );
				}

				// replace merge tags
				$text = GFCommon::replace_variables( $text, $form, $entry, false, true, false, 'text' );

				// find any urls from the replaced merge tags and pass to regex_shorten_url
				$text = preg_replace_callback( '~<mt>(https?|ftp):\/\/.*<\/mt>~', array(
					$this,
					'regex_shorten_url'
				), $text );

				// remove any remaining mt delimiters
				$text = str_replace( array( '<mt>', '</mt>' ), '', $text );
			}

		} else {
			// replace merge tags
			$text = GFCommon::replace_variables( $text, $form, $entry, false, true, false, 'text' );
		}

		return str_split( $text, 156 );
	}

	/**
	 * Initialize the Twilio API.
	 *
	 * @return TwilioRestClient
	 */
	public function get_api() {
		require_once( $this->get_base_path() . '/api/twilio.php' );

		// Twilio REST API version
		$ApiVersion = '2010-04-01';

		// Set our AccountSid and AuthToken
		$settings = $this->get_plugin_settings();

		// Instantiate a new Twilio Rest Client
		$this->log_debug( __METHOD__ . '(): Getting Twilio client using account Sid: ' . rgar( $settings, 'accountSid' ) . ', auth token: ' . rgar( $settings, 'authToken' ) );
		$client            = new TwilioRestClient( rgar( $settings, 'accountSid' ), rgar( $settings, 'authToken' ) );
		$client->base_path = "{$ApiVersion}/Accounts/" . rgar( $settings, 'accountSid' );

		return $client;
	}


	// # TO FRAMEWORK MIGRATION ----------------------------------------------------------------------------------------

	/**
	 * Initialize the admin specific hooks.
	 */
	public function init_admin() {
		parent::init_admin();

		add_filter( 'gform_addon_navigation', array( $this, 'maybe_create_menu' ) );
	}

	/**
	 * Maybe add the temporary plugin page to the menu.
	 *
	 * @param array $menus
	 *
	 * @return array
	 */
	public function maybe_create_menu( $menus ) {
		$current_user        = wp_get_current_user();
		$dismiss_twilio_menu = get_metadata( 'user', $current_user->ID, 'dismiss_twilio_menu', true );
		if ( $dismiss_twilio_menu != '1' ) {
			$menus[] = array(
				'name'       => $this->_slug,
				'label'      => $this->get_short_title(),
				'callback'   => array( $this, 'temporary_plugin_page' ),
				'permission' => $this->_capabilities_form_settings
			);
		}

		return $menus;
	}

	/**
	 * Initialize the AJAX hooks.
	 */
	public function init_ajax() {
		parent::init_ajax();

		add_action( 'wp_ajax_gf_dismiss_twilio_menu', array( $this, 'ajax_dismiss_menu' ) );

	}

	/**
	 * Update the user meta to indicate they shouldn't see the temporary plugin page again.
	 */
	public function ajax_dismiss_menu() {

		$current_user = wp_get_current_user();
		update_metadata( 'user', $current_user->ID, 'dismiss_twilio_menu', '1' );
	}

	/**
	 * Display a temporary page explaining how feeds are now managed.
	 */
	public function temporary_plugin_page() {
		$current_user = wp_get_current_user();
		?>
		<script type="text/javascript">
			function dismissMenu() {
				jQuery('#gf_spinner').show();
				jQuery.post(ajaxurl, {
						action: "gf_dismiss_twilio_menu"
					},
					function (response) {
						document.location.href = '?page=gf_edit_forms';
						jQuery('#gf_spinner').hide();
					}
				);

			}
		</script>

		<div class="wrap about-wrap">
			<h1><?php _e( 'Twilio Add-On v2.0', 'gravityformstwilio' ) ?></h1>

			<div
				class="about-text"><?php _e( 'Thank you for updating! The new version of the Gravity Forms Twilio Add-On makes changes to how you manage your Twilio integration.', 'gravityformstwilio' ) ?></div>
			<div class="changelog">
				<hr/>
				<div class="feature-section col two-col">
					<div class="col-1">
						<h3><?php _e( 'Manage Twilio Contextually', 'gravityformstwilio' ) ?></h3>

						<p><?php _e( 'Twilio Feeds are now accessed via the Twilio sub-menu within the Form Settings for the Form with which you would like to integrate Twilio.', 'gravityformstwilio' ) ?></p>
					</div>
					<div class="col-2 last-feature">
						<img src="http://gravityforms.s3.amazonaws.com/webimages/AddonNotice/NewTwilio2.png">
					</div>
				</div>

				<hr/>

				<form method="post" id="dismiss_menu_form" style="margin-top: 20px;">
					<input type="checkbox" name="dismiss_twilio_menu" value="1" onclick="dismissMenu();">
					<label><?php _e( 'I understand this change, dismiss this message!', 'gravityformstwilio' ) ?></label>
					<img id="gf_spinner" src="<?php echo GFCommon::get_base_url() . '/images/spinner.gif'?>"
					     alt="<?php _e( 'Please wait...', 'gravityformstwilio' ) ?>" style="display:none;"/>
				</form>

			</div>
		</div>
	<?php
	}

	/**
	 * Checks if a previous version was installed and if the feeds need migrating to the framework structure.
	 *
	 * @param string $previous_version The version number of the previously installed version.
	 */
	public function upgrade( $previous_version ) {
		if ( empty( $previous_version ) ) {
			$previous_version = get_option( 'gf_twilio_version' );
		}
		$previous_is_pre_addon_framework = ! empty( $previous_version ) && version_compare( $previous_version, '2.0.dev1', '<' );

		if ( $previous_is_pre_addon_framework ) {
			$old_feeds = $this->get_old_feeds();

			if ( $old_feeds ) {
				$counter = 1;
				foreach ( $old_feeds as $old_feed ) {
					$feed_name = 'Feed ' . $counter;
					$form_id   = $old_feed['form_id'];
					$is_active = $old_feed['is_active'];

					$new_meta = array(
						'feedName'           => $feed_name,
						'notificationType'   => rgar( $old_feed['meta'], 'type' ),
						'smsMessage'         => rgar( $old_feed['meta'], 'message' ),
						'fromNumber'         => rgar( $old_feed['meta'], 'from' ),
						'toNumber'           => rgar( $old_feed['meta'], 'to' ),
						'shortenURL'         => rgar( $old_feed['meta'], 'shorten_url' ),
						'clickToCallMessage' => rgar( $old_feed['meta'], 'call_message' ),
						'customerPhone'      => rgars( $old_feed['meta'], 'customer_fields/phone' ),
						'menuOption1'        => rgar( $old_feed['meta'], 'menu_option_1' ),
						'menuOption2'        => rgar( $old_feed['meta'], 'menu_option_2' ),
					);

					$this->insert_feed( $form_id, $is_active, $new_meta );
					$counter ++;
				}

				$old_settings = get_option( 'gf_twilio_settings' );

				$new_settings = array(
					'accountSid'  => $old_settings['account_sid'],
					'authToken'   => $old_settings['auth_token'],
					'bitlyLogin'  => $old_settings['bitly_login'],
					'bitlyApikey' => $old_settings['bitly_apikey'],
				);

				parent::update_plugin_settings( $new_settings );

				//set paypal delay setting
				$this->update_paypal_delay_settings( 'delay_twilio' );
			}
		}

		return;
	}

	/**
	 * Migrate the delayed payment setting for the PayPal add-on integration.
	 *
	 * @param $old_delay_setting_name
	 */
	public function update_paypal_delay_settings( $old_delay_setting_name ) {
		global $wpdb;
		$this->log_debug( __METHOD__ . '(): Checking to see if there are any delay settings that need to be migrated for PayPal Standard.' );

		$new_delay_setting_name = 'delay_' . $this->_slug;

		//get paypal feeds from old table
		$paypal_feeds_old = $this->get_old_paypal_feeds();

		//loop through feeds and look for delay setting and create duplicate with new delay setting for the framework version of PayPal Standard
		if ( ! empty( $paypal_feeds_old ) ) {
			$this->log_debug( __METHOD__ . '(): Old feeds found for ' . $this->_slug . ' - copying over delay settings.' );
			foreach ( $paypal_feeds_old as $old_feed ) {
				$meta = $old_feed['meta'];
				if ( ! rgempty( $old_delay_setting_name, $meta ) ) {
					$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
					//update paypal meta to have new setting
					$meta = maybe_serialize( $meta );
					$wpdb->update( "{$wpdb->prefix}rg_paypal", array( 'meta' => $meta ), array( 'id' => $old_feed['id'] ), array( '%s' ), array( '%d' ) );
				}
			}
		}

		//get paypal feeds from new framework table
		$paypal_feeds = $this->get_feeds_by_slug( 'gravityformspaypal' );
		if ( ! empty( $paypal_feeds ) ) {
			$this->log_debug( __METHOD__ . '(): New feeds found for ' . $this->_slug . ' - copying over delay settings.' );
			foreach ( $paypal_feeds as $feed ) {
				$meta = $feed['meta'];
				if ( ! rgempty( $old_delay_setting_name, $meta ) ) {
					$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
					$this->update_feed_meta( $feed['id'], $meta );
				}
			}
		}
	}

	/**
	 * Retrieve any old PayPal feeds.
	 *
	 * @return bool|array
	 */
	public function get_old_paypal_feeds() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_paypal';

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$form_table_name = GFFormsModel::get_form_table_name();
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM {$table_name} s
				INNER JOIN {$form_table_name} f ON s.form_id = f.id";

		$this->log_debug( __METHOD__ . "() getting old paypal feeds: {$sql}" );

		$results = $wpdb->get_results( $sql, ARRAY_A );

		$this->log_debug( __METHOD__ . "(): error?: {$wpdb->last_error}" );

		$count = sizeof( $results );

		$this->log_debug( __METHOD__ . "(): count: {$count}" );

		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;
	}

	/**
	 * Retrieve any old feeds which need migrating to the framework,
	 *
	 * @return bool|array
	 */
	public function get_old_feeds() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rg_twilio';

		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		$form_table_name = RGFormsModel::get_form_table_name();
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
			FROM $table_name s
			INNER JOIN $form_table_name f ON s.form_id = f.id";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		$count = sizeof( $results );
		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;
	}

}