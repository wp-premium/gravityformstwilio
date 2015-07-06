<?php

GFForms::include_feed_addon_framework();

class GFTwilio extends GFFeedAddOn {

	protected $_version = GF_TWILIO_VERSION;
	protected $_min_gravityforms_version = '1.8.17';
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

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFTwilio();
		}

		return self::$_instance;
	}

	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => __( 'Send SMS only when a payment is received.', 'gravityformstwilio' )
			)
		);

	}

	public function init_admin() {
		parent::init_admin();

		add_filter( 'gform_addon_navigation', array( $this, 'maybe_create_menu' ) );
	}

	//------- AJAX FUNCTIONS ------------------//

	public function init_ajax() {
		parent::init_ajax();

		add_action( 'wp_ajax_gf_dismiss_twilio_menu', array( $this, 'ajax_dismiss_menu' ) );

	}

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

	public function ajax_dismiss_menu() {

		$current_user = wp_get_current_user();
		update_metadata( 'user', $current_user->ID, 'dismiss_twilio_menu', '1' );
	}

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

	// ------- Plugin settings -------
	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'Twilio Account Information', 'gravityformstwilio' ),
				'description' => sprintf(
					__( 'Twilio provides a web-service API for businesses to build scalable and reliable communication apps. %1$s Sign up for a Twilio account%2$s', 'gravityformstwilio' ),
					'<a href="http://www.twilio.com" target="_blank">', '</a> to receive SMS messages when a Gravity Form is submitted.'
				),
				'fields'      => array(
					array(
						'name'              => 'accountSid',
						'label'             => __( 'Account SID', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_credentials' )
					),
					array(
						'name'              => 'authToken',
						'label'             => __( 'Auth Token', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_credentials' )
					)
				)
			),
			array(
				'title'       => __( 'Bitly Account Information', 'gravityformstwilio' ),
				'description' => sprintf(
					__( 'Bitly helps you shorten, track and analyze your links. Enter your Bitly account information below to automatically shorten URLs in your SMS message. If you don\'t have a Bitly account, %1$s sign-up for one here%2$s', 'gravityformstwilio' ),
					'<a href="http://bit.ly" target="_blank">', '</a>.'
				),
				'fields'      => array(
					array(
						'name'              => 'bitlyLogin',
						'label'             => __( 'Login', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_bitly_credentials' )
					),
					array(
						'name'              => 'bitlyApikey',
						'label'             => __( 'API Key', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_bitly_credentials' )
					)
				)
			)
		);

	}

	public function feed_settings_fields() {

		$account_info = $this->get_account_info();

		if ( $account_info['is_sandbox'] ) {
			$to_number_setting = array(
				'name'    => 'toNumber',
				'label'   => __( 'To Number', 'gravityformstwilio' ),
				'type'    => 'select',
				'choices' => $this->get_twilio_phone_numbers( 'outgoing_numbers', $account_info ),
				'tooltip' => '<h6>' . __( 'To Number', 'gravityformstwilio' ) . '</h6>' . __( 'Phone number to send this message to. For Twilio trial accounts, you can only send SMS messages to validated numbers. To validate a number, login to your Twilio account and navigate to the \'Numbers\' tab.', 'gravityformstwilio' ),
			);
		} else {
			$to_number_setting = array(
				'name'    => 'toNumber',
				'label'   => __( 'To Number', 'gravityformstwilio' ),
				'type'    => 'text',
				'tooltip' => '<h6>' . __( 'To Number', 'gravityformstwilio' ) . '</h6>' . __( 'Phone number to send this message to.', 'gravityformstwilio' ),
			);
		}


		return array(
			array(
				'title'       => __( 'Twilio Feed Settings', 'gravityformstwilio' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'     => 'feedName',
						'label'    => __( 'Name', 'gravityformstwilio' ),
						'type'     => 'text',
						'required' => true,
						'class'    => 'medium',
						'tooltip'  => '<h6>' . __( 'Name', 'gravityformstwilio' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityformstwilio' ),
					),
					array(
						'name'    => 'fromNumber',
						'label'   => __( 'From Number', 'gravityformstwilio' ),
						'type'    => 'select',
						'choices' => $this->get_twilio_phone_numbers( 'incoming_numbers', $account_info ),
						'tooltip' => '<h6>' . __( 'From Number', 'gravityformstwilio' ) . '</h6>' . __( 'Phone number that the message will be sent FROM.', 'gravityformstwilio' ),
					),
					$to_number_setting,
					array(
						'name'    => 'smsMessage',
						'label'   => __( 'Message', 'gravityformstwilio' ),
						'type'    => 'sms_message',
						'tooltip' => '<h6>' . __( 'Message', 'gravityformstwilio' ) . '</h6>' . __( 'Write the SMS message you would like to be sent. You can insert fields submitted by the user by selecting them from the \'Insert merge code\' drop down. SMS message are limited to 160 characters. Messages larger than 160 characters will automatically be split into multiple SMS messages.', 'gravityformstwilio' ),
					),
					array(
						'name'           => 'feed_condition',
						'label'          => __( 'Conditional Logic', 'gravityformstwilio' ),
						'type'           => 'feed_condition',
						'checkbox_label' => __( 'Enable', 'gravityformstwilio' ),
						'instructions'   => __( 'Export to Twilio if', 'gravityformstwilio' ),
						'tooltip'        => '<h6>' . __( 'Conditional Logic', 'gravityformstwilio' ) . '</h6>' . __( 'When conditional logic is enabled, form submissions will only be exported to Twilio when the condition is met. When disabled, all form submissions will be exported.', 'gravityformstwilio' )

					),
				),
			),
		);
	}

	public function feed_list_columns() {
		return array(
			'feedName'   => __( 'Name', 'gravityformstwilio' ),
			'fromNumber' => __( 'From Number', 'gravityformstwilio' ),
			'toNumber'   => __( 'To Number', 'gravityformstwilio' ),
		);
	}

	public function feed_list_message() {

		// ensures valid credentials were entered in the settings page
		if ( ! $this->is_valid_credentials() ) {

			return '<div>' .
			       sprintf( __( 'To get started, please configure your %sTwilio Settings.%s', 'gravityformstwilio' ), '<a href="' . esc_url( $this->get_plugin_settings_url() ) . '">', '</a>' ) .
			       '</div>';

		}

		return false;
	}

//	//-------- Form Settings ---------

	public function settings_sms_message( $field, $echo = true ) {

		$field['type']  = 'textarea';
		$field['class'] = 'medium merge-tag-support mt-position-right';

		$html = $this->settings_textarea( $field, false );

		$shortUrlField         = array();
		$shortUrlField['type'] = 'checkbox';
		$shortUrlField['name'] = 'shortenURL_';
		$checkboxes            = array(
			array(
				'label'   => __( 'Shorten URLs', 'gravityformstwilio' ),
				'name'    => 'shortenURL',
				'tooltip' => '<h6>' . __( 'Shorten URLs', 'gravityformstwilio' ) . '</h6>' . __( 'Enable this option to automatically shorten all URLs in your SMS message.', 'gravityformstwilio' ),
			),
		);

		$warning = '';
		if ( ! $this->is_valid_bitly_credentials() ) {
			$checkboxes[0]['disabled'] = 'disabled';
			$warning                   = '<div class="gfield_error" style="width:49%">' .
			                             sprintf( __( 'Bitly account required. %sEnter your Bitly account information%s to enable this option.', 'gravityforms' ), '<a href="' . esc_url( $this->get_plugin_settings_url() ) . '">', '</a>' ) .
			                             '</div>';

		}

		$shortUrlField['choices'] = $checkboxes;
		$html2                    = $this->settings_checkbox( $shortUrlField, false ) . $warning;


		if ( $echo ) {
			echo $html . $html2;
		}

		return $html . $html2;

	}

	// used to upgrade old feeds into new version
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

		return $phone_numbers;

	}

	public function process_feed( $feed, $entry, $form ) {

		$this->export_feed( $entry, $form, $feed );

	}

	public function export_feed( $entry, $form, $feed ) {

		$this->send_sms( $feed['meta']['fromNumber'], $feed['meta']['toNumber'], $feed['meta']['smsMessage'], $feed['meta']['shortenURL'], $feed['id'], $entry );
	}

	public function is_valid_bitly_credentials() {
		$url = $this->shorten_url( 'http://www.google.com' );

		return $url != 'http://www.google.com';
	}

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

	public function is_valid_credentials() {
		$api = $this->get_api();

		$response = $api->request( "{$api->base_path}" );
		$this->log_debug( __METHOD__ . '(): Response from Twilio => ' . print_r( $response, true ) );

		return ! $response->IsError;

	}

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
			$response = $api->request( "{$api->base_path}/SMS/Messages", 'POST', $data );
			$this->log_debug( __METHOD__ . '(): Response from Twilio for SMS => ' . print_r( $response, true ) );
		}
	}

	public function regex_shorten_url( $matches ) {
		//remove the mt delimiter from the string containing the url before passing to bitly
		$url = str_replace( array( '<mt>', '</mt>' ), '', $matches[0] );

		return $this->shorten_url( $url );
	}

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
				$text = preg_replace_callback( '~<mt>(https?|ftp):\/\/.*<\/mt>~', array( $this, 'regex_shorten_url' ), $text );

				// remove any remaining mt delimiters
				$text = str_replace( array( '<mt>', '</mt>' ), '', $text );
			}

		}
		else {
			// replace merge tags
			$text = GFCommon::replace_variables( $text, $form, $entry, false, true, false, 'text' );
		}

		return str_split( $text, 156 );
	}

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
}