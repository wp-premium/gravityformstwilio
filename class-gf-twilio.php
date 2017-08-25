<?php

GFForms::include_feed_addon_framework();

/**
 * Gravity Forms Twilio Add-On.
 *
 * @since     1.0
 * @package   GravityForms
 * @author    Rocketgenius
 * @copyright Copyright (c) 2017, Rocketgenius
 */
class GFTwilio extends GFFeedAddOn {

	/**
	 * Contains an instance of this class, if available.
	 *
	 * @since  Unknown
	 * @access private
	 * @var    object $_instance If available, contains an instance of this class.
	 */
	private static $_instance = null;

	/**
	 * Defines the version of the Twilio Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_version Contains the version, defined from twilio.php
	 */
	protected $_version = GF_TWILIO_VERSION;

	/**
	 * Defines the minimum Gravity Forms version required.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_min_gravityforms_version The minimum version required.
	 */
	protected $_min_gravityforms_version = '1.9.11';

	/**
	 * Defines the plugin slug.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_slug The slug used for this plugin.
	 */
	protected $_slug = 'gravityformstwilio';

	/**
	 * Defines the main plugin file.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_path The path to the main plugin file, relative to the plugins folder.
	 */
	protected $_path = 'gravityformstwilio/twilio.php';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_full_path The full path.
	 */
	protected $_full_path = __FILE__;

	/**
	 * Defines the URL where this Add-On can be found.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string The URL of the Add-On.
	 */
	protected $_url = 'http://www.gravityforms.com';

	/**
	 * Defines the title of this Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_title The title of the Add-On.
	 */
	protected $_title = 'Twilio Add-On';

	/**
	 * Defines the short title of the Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_short_title The short title.
	 */
	protected $_short_title = 'Twilio';

	/**
	 * Defines if Add-On should use Gravity Forms servers for update data.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    bool
	 */
	protected $_enable_rg_autoupgrade = true;

	/**
	 * Defines the capability needed to access the Add-On settings page.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gravityforms_twilio';

	/**
	 * Defines the capability needed to access the Add-On form settings page.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gravityforms_twilio';

	/**
	 * Defines the capability needed to uninstall the Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gravityforms_twilio_uninstall';

	/**
	 * Defines the capabilities needed for the Twilio Add-On.
	 *
	 * @since  Unknown
	 * @access protected
	 * @var    array $_capabilities The capabilities needed for the Add-On.
	 */
	protected $_capabilities = array( 'gravityforms_twilio', 'gravityforms_twilio_uninstall' );

	/**
	 * Contains an instance of the Twilio API library, if available.
	 *
	 * @since  2.4
	 * @access protected
	 * @var    object $twilio If available, contains an instance of the Twilio API library.
	 */
	protected $twilio = null;

	/**
	 * Contains an instance of the Twilio test API library, if available.
	 *
	 * @since  2.4
	 * @access protected
	 * @var    object $twilio_test If available, contains an instance of the Twilio test API library.
	 */
	protected $twilio_test = null;

	/**
	 * Get instance of this class.
	 *
	 * @access public
	 * @static
	 *
	 * @return GFTwilio
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Plugin starting point. Handles hooks, loading of language files and PayPal delayed payment support.
	 *
	 * @since  Unknown
	 * @access public
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Send SMS only when a payment is received.', 'gravityformstwilio' )
			)
		);

	}





	// # PLUGIN SETTINGS -----------------------------------------------------------------------------------------------

	/**
	 * Configures the settings which should be rendered on the add-on settings tab.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'       => esc_html__( 'Twilio Account Information', 'gravityformstwilio' ),
				'description' => sprintf(
					esc_html__( 'Twilio provides a web-service API for businesses to build scalable and reliable communication apps. %1$s Sign up for a Twilio account%2$s to receive SMS messages when a Gravity Form is submitted.', 'gravityformstwilio' ),
					'<a href="http://www.twilio.com" target="_blank">',
					'</a>'
				),
				'fields'      => array(
					array(
						'name'              => 'apiMode',
						'label'             => esc_html__( 'API Mode', 'gravityformstwilio' ),
						'type'              => 'radio',
						'default_value'     => 'live',
						'horizontal'        => true,
						'choices'           => array(
							array(
								'value' => 'live',
								'label' => esc_html__( 'Live', 'gravityformstwilio' ),
							),
							array(
								'value' => 'test',
								'label' => esc_html__( 'Test', 'gravityformstwilio' ),
							),
						),
					),
					array(
						'name'              => 'accountSid',
						'label'             => esc_html__( 'Account SID', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
					array(
						'name'              => 'authToken',
						'label'             => esc_html__( 'Auth Token', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_api' ),
					),
					array(
						'name'              => 'testAccountSid',
						'label'             => esc_html__( 'Test Account SID', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_test_api' ),
					),
					array(
						'name'              => 'testAuthToken',
						'label'             => esc_html__( 'Test Auth Token', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'initialize_test_api' ),
					),
				),
			),
			array(
				'title'       => esc_html__( 'Bitly Account Information', 'gravityformstwilio' ),
				'description' => sprintf(
					esc_html__( 'Bitly helps you shorten, track and analyze your links. Enter your Bitly account information below to automatically shorten URLs in your SMS message. If you don\'t have a Bitly account, %1$ssign-up for one here%2$s', 'gravityformstwilio' ),
					'<a href="http://bit.ly" target="_blank">',
					'</a>.'
				),
				'fields'      => array(
					array(
						'name'              => 'bitlyAccessToken',
						'label'             => esc_html__( 'Access Token', 'gravityformstwilio' ),
						'type'              => 'text',
						'class'             => 'large',
						'feedback_callback' => array( $this, 'validate_bitly_credentials' ),
					),
					array(
						'name'              => 'bitlyLogin',
						'label'             => esc_html__( 'Login', 'gravityformstwilio' ),
						'type'              => 'hidden',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'validate_legacy_bitly_credentials' ),
					),
					array(
						'name'              => 'bitlyApikey',
						'label'             => esc_html__( 'API Key', 'gravityformstwilio' ),
						'type'              => 'hidden',
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'validate_legacy_bitly_credentials' ),
					),
				),
			),
		);

	}





	// # FEED SETTINGS -------------------------------------------------------------------------------------------------

	/**
	 * Setup fields for feed settings.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @uses GFTwilio::get_current_account()
	 * @uses GFTwilio::get_phone_numbers_as_choices()
	 *
	 * @return array
	 */
	public function feed_settings_fields() {

		return array(
			array(
				'title'       => esc_html__( 'Twilio Feed Settings', 'gravityformstwilio' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'                => 'feedName',
						'label'               => esc_html__( 'Name', 'gravityformstwilio' ),
						'type'                => 'text',
						'required'            => true,
						'class'               => 'medium',
						'tooltip'             => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Name', 'gravityformstwilio' ),
							esc_html__( 'Enter a feed name to uniquely identify this setup.', 'gravityformstwilio' )
						),
					),
					array(
						'name'                => 'fromNumber',
						'label'               => esc_html__( 'From', 'gravityformstwilio' ),
						'type'                => 'select_custom',
						'choices'             => $this->get_phone_numbers_as_choices( 'incoming_numbers' ),
						'required'            => true,
						'validation_callback' => array( $this, 'validate_from' ),
						'tooltip'             => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'From', 'gravityformstwilio' ),
							esc_html__( 'Phone number or Alphanumeric Sender ID that the message will be sent FROM.', 'gravityformstwilio' )
						),
					),
					array(
						'name'                => 'toNumber',
						'label'               => esc_html__( 'To Number', 'gravityformstwilio' ),
						'type'                => 'select_custom',
						'choices'             => $this->get_phone_numbers_as_choices( 'outgoing_numbers' ),
						'required'            => true,
						'input_class'         => 'merge-tag-support mt-position-right',
						'tooltip'             => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'To Number', 'gravityformstwilio' ),
							esc_html__( 'Phone number to send this message to. For Twilio trial accounts, you can only send SMS messages to validated numbers. To validate a number, log in to your Twilio account and navigate to the \'Numbers\' tab.', 'gravityformstwilio' )
						),
					),
					array(
						'name'                => 'smsMessage',
						'label'               => esc_html__( 'Message', 'gravityformstwilio' ),
						'type'                => 'textarea',
						'class'               => 'medium merge-tag-support mt-position-right',
						'tooltip'             => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Message', 'gravityformstwilio' ),
							esc_html__( 'Write the SMS message you would like to be sent. You can insert fields submitted by the user by selecting them from the \'Insert merge code\' drop down. SMS message are limited to 1600 characters. Messages larger than 160 characters will automatically be split into multiple SMS messages.', 'gravityformstwilio' )
						),
					),
					array(
						'name'                => 'shortenURL',
						'type'                => 'checkbox',
						'dependency'          => array( $this, 'can_shorten_url' ),
						'choices'             => array(
							array(
								'name'    => 'shortenURL',
								'label'   => esc_html__( 'Shorten URLs', 'gravityformstwilio' ),
								'tooltip' => sprintf(
									'<h6>%s</h6>%s',
									esc_html__( 'Shorten URLs', 'gravityformstwilio' ),
									esc_html__( 'Enable this option to automatically shorten all URLs in your SMS message.', 'gravityformstwilio' )
								),
							),
						),
					),
					array(
						'name'                => 'feed_condition',
						'label'               => esc_html__( 'Conditional Logic', 'gravityformstwilio' ),
						'type'                => 'feed_condition',
						'checkbox_label'      => esc_html__( 'Enable', 'gravityformstwilio' ),
						'instructions'        => esc_html__( 'Export to Twilio if', 'gravityformstwilio' ),
						'tooltip'             => sprintf(
							'<h6>%s</h6>%s',
							esc_html__( 'Conditional Logic', 'gravityformstwilio' ),
							esc_html__( 'When conditional logic is enabled, form submissions will only be exported to Twilio when the condition is met. When disabled, all form submissions will be exported.', 'gravityformstwilio' )
						),
					),
				),
			),
		);

	}

	/**
	 * Retrieve the from/to numbers for use on the feed settings page.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @param string $type The phone number type. Either incoming_numbers or outgoing_numbers.
	 *
	 * @uses GFAddOn::get_current_form()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFAPI::get_fields_by_type()
	 * @uses GFTwilio::initialize_api()
	 *
	 * @return array
	 */
	public function get_phone_numbers_as_choices( $type ) {

		// If API cannot be initialized, return.
		if ( ! $this->initialize_api() ) {
			return array();
		}

		// Prepare object type.
		$object_type = 'incoming_numbers' === $type ? 'incomingPhoneNumbers' : 'outgoingCallerIds';

		try {

			// Get Twilio phone numbers.
			$twilio_numbers = $this->twilio->{ $object_type }->read();

			// Log Twilio phone numbers.
			$this->log_debug( __METHOD__ . '(): Twilio ' . $type . ': ' . print_r( $twilio_numbers, true ) );

		} catch ( \Exception $e ) {

			// Log that we could not get the phone numbers.
			$this->log_error( __METHOD__ . '(): Unable to get the ' . $type . '; ' . $e->getMessage() . ' (' . $e->getCode() . ')' );

			return $phone_numbers;

		}

		// Prepare options by type.
		if ( 'incoming_numbers' === $type ) {

			// Initialize phone numbers array.
			$phone_numbers = array(
				array(
					'label'   => esc_html__( 'Twilio Phone Numbers', 'gravityformstwilio' ),
					'choices' => array(),
				),
				array(
					'label' => esc_html__( 'Use Alphanumeric Sender ID', 'gravityformstwilio' ),
					'value' => 'gf_custom',
				),
			);

			// Loop through Twilio phone numbers.
			foreach ( $twilio_numbers as $twilio_number ) {

				// Add Twilio phone number as choice.
				$phone_numbers[0]['choices'][] = array(
					'label' => esc_html( $twilio_number->phoneNumber ),
					'value' => esc_attr( $twilio_number->phoneNumber ),
				);

			}

		} else if ( 'outgoing_numbers' === $type ) {

			// Initialize phone numbers array.
			$phone_numbers = array(
				array(
					'label'   => esc_html__( 'Phone Fields', 'gravityformstwilio' ),
					'choices' => array(),
				),
				array(
					'label'   => esc_html__( 'Twilio Phone Numbers', 'gravityformstwilio' ),
					'choices' => array(),
				),
				array(
					'label' => esc_html__( 'Use Custom Phone Number', 'gravityformstwilio' ),
					'value' => 'gf_custom',
				),
			);

			// Get current form.
			$form = $this->get_current_form();

			// Get Phone fields.
			$phone_fields = GFAPI::get_fields_by_type( $form, array( 'phone' ) );

			// Add Phone fields to choices.
			if ( ! empty( $phone_fields ) ) {

				// Loop through Phone fields.
				foreach ( $phone_fields as $phone_field ) {

					// Add Phone field as choice.
					$phone_numbers[0]['choices'][] = array(
						'label' => esc_html( $phone_field->label ),
						'value' => 'field_' . esc_attr( $phone_field->id ),
					);

				}

			}

			// Loop through Twilio phone numbers.
			foreach ( $twilio_numbers as $twilio_number ) {

				// Add Twilio phone number as choice.
				$phone_numbers[1]['choices'][] = array(
					'label' => esc_html( $twilio_number->phoneNumber ),
					'value' => esc_attr( $twilio_number->phoneNumber ),
				);

			}

		}

		return $phone_numbers;

	}

	/**
	 * Validate the text input for the message From setting.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array  $field         The field setting.
	 * @param string $field_setting The field value.
	 *
	 * @uses GFAddOn::get_posted_settings()
	 * @uses GFAddOn::set_field_error()
	 */
	public function validate_from( $field, $field_setting ) {

		// If a custom From Number is not set, return.
		if ( $field_setting != 'gf_custom' ) {
			return;
		}

		// Get posted settings.
		$settings = $this->get_posted_settings();

		$field['name'] .= '_custom';
		$from           = rgar( $settings, 'fromNumber_custom' );

		// Alphanumeric Sender ID can't be more than 11 characters.
		if ( rgblank( $from ) ) {
			$this->set_field_error( $field );
		} elseif ( strlen( $from ) > 11 ) {
			$this->set_field_error( $field, __( 'The Alphanumeric Sender ID must be no longer than 11 characters.' ), 'gravityformstwilio' );
		} elseif ( ! preg_match( '/^[a-zA-Z0-9 ]+$/', $from ) ) {
			$this->set_field_error( $field, __( 'The Alphanumeric Sender ID only supports upper and lower case letters, the digits 0 through 9, and spaces.' ), 'gravityformstwilio' );
		}

	}

	/**
	 * Renders and initializes a drop down field with a input field for custom input based on the $field array.
	 * (Forked to add support for merge tags in input field.)
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @param array $field Field array containing the configuration options of this field
	 * @param bool  $echo  True to echo the output to the screen, false to simply return the contents as a string
	 *
	 * @return string The HTML for the field
	 */
	public function settings_select_custom( $field, $echo = true ) {

		// Prepare select field.
		$select_field             = $field;
		$select_field_value       = $this->get_setting( $select_field['name'], rgar( $select_field, 'default_value' ) );
		$select_field['onchange'] = '';
		$select_field['class']    = ( isset( $select_field['class'] ) ) ? $select_field['class'] . 'gaddon-setting-select-custom' : 'gaddon-setting-select-custom';

		// Prepare input field.
		$input_field          = $field;
		$input_field['name'] .= '_custom';
		$input_field['style'] = 'width:200px;max-width:90%;';
		$input_field['class'] = rgar( $field, 'input_class' );
		$input_field_display  = '';

		// Loop through select choices and make sure option for custom exists.
		$has_gf_custom = false;
		foreach ( $select_field['choices'] as $choice ) {

			if ( rgar( $choice, 'name' ) == 'gf_custom' || rgar( $choice, 'value' ) == 'gf_custom' ) {
				$has_gf_custom = true;
			}

			// If choice has choices, check inside those choices..
			if ( rgar( $choice, 'choices' ) ) {
				foreach ( $choice['choices'] as $subchoice ) {
					if ( rgar( $subchoice, 'name' ) == 'gf_custom' || rgar( $subchoice, 'value' ) == 'gf_custom' ) {
						$has_gf_custom = true;
					}
				}
			}

		}
		if ( ! $has_gf_custom ) {
			$select_field['choices'][] = array(
				'label' => esc_html__( 'Add Custom', 'gravityforms' ) .' ' . $select_field['label'],
				'value' => 'gf_custom'
			);
		}

		// If select value is "gf_custom", hide the select field and display the input field.
		if ( $select_field_value == 'gf_custom' || ( count( $select_field['choices'] ) == 1 && $select_field['choices'][0]['value'] == 'gf_custom' ) ) {
			$select_field['style'] = 'display:none;';
		} else {
			$input_field_display   = ' style="display:none;"';
		}

		// Add select field.
		$html = $this->settings_select( $select_field, false );

		// Add input field.
		$html .= '<div class="gaddon-setting-select-custom-container"'. $input_field_display .'>';
		$html .= count( $select_field['choices'] ) > 1 ? '<a href="#" class="select-custom-reset">Reset</a>' : '';
		$html .= $this->settings_text( $input_field, false );
		$html .= '</div>';

		if ( $echo ) {
			echo $html;
		}

		return $html;

	}

	/**
	 * Set feed creation control.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @use GFTwilio::initialize_api()
	 *
	 * @return bool
	 */
	public function can_create_feed() {

		return $this->initialize_api();

	}





	// # FEED LIST -----------------------------------------------------------------------------------------------------

	/**
	 * Setup columns for feed list table.
	 *
	 * @since  Unknown
	 * @access public
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
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $feed Feed object.
	 *
	 * @uses GFTwilio::get_message_from()
	 *
	 * @return string
	 */
	public function get_column_value_fromNumber( $feed ) {

		return $this->get_message_from( $feed );

	}

	/**
	 * Returns the value to be displayed in the From column.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $feed Feed object.
	 *
	 * @uses GFAddOn::get_current_form()
	 * @uses GFFormsModel::get_field()
	 * @uses GFTwilio::get_message_from()
	 *
	 * @return string
	 */
	public function get_column_value_toNumber( $feed ) {

		// If a custom value is set, return it.
		if ( 'gf_custom' === rgars( $feed, 'meta/toNumber' ) ) {
			return rgars( $feed, 'meta/toNumber_custom' );
		}

		// Get To Number value.
		$to_number = rgars( $feed, 'meta/toNumber' );

		// If a field is not selected, return number.
		if ( 'field_' !== substr( $to_number, 0, 6 ) ) {
			return $to_number;
		}

		// Get field ID.
		$phone_field = str_replace( 'field_', '', $to_number );

		// Get current form.
		$form = $this->get_current_form();

		// Get field.
		$phone_field = GFFormsModel::get_field( $form, $phone_field );

		return esc_html( $phone_field->label );

	}





	// # FEED PROCESSING -----------------------------------------------------------------------------------------------

	/**
	 * Initiate processing the feed.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param array $feed  The Feed object to be processed.
	 * @param array $entry The Entry object currently being processed.
	 * @param array $form  The Form object currently being processed.
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::log_debug()
	 * @uses GFCommon::replace_variables()
	 * @uses GFFeedAddOn::add_feed_error()
	 * @uses GFTwilio::get_message_form()
	 */
	public function process_feed( $feed, $entry, $form ) {

		// If API cannot be initialized, return.
		if ( ! $this->initialize_api() ) {
			$this->add_feed_error( sprintf( esc_html__( 'Unable to send SMS: %s (%d)', 'gravityformstwilio' ), 'API could not be initialized', 0 ), $feed, $entry, $form );
			return;
		}

		// Get plugin settings.
		$plugin_settings = $this->get_plugin_settings();

		// Prepare message arguments.
		$args = array(
			'to'          => $this->get_message_to( $feed, $entry, $form ),
			'from'        => $this->get_message_from( $feed ),
			'body'        => rgars( $feed, 'meta/smsMessage' ),
			'shorten_url' => rgars( $feed, 'meta/shortenURL' ),
		);

		/**
		 * Modify the TO number before the SMS is sent to Twilio.
		 *
		 * @deprecated 2.4 @use gform_twilio_message
		 *
		 * @param string $to      The number the SMS will be sent TO. Formatted with a '+' and country code e.g. +17571234567 (E.164 format).
		 * @param array  $entry   The Entry object.
		 * @param int    $feed_id The ID of the Feed Object which is currently being processed.
		 */
		$args['to'] = apply_filters( 'gform_twilio_set_to_phone_number', $args['to'], $entry, $feed['id'] );

		/**
		 * Modify the message arguments before the SMS is sent to Twilio.
		 *
		 * @since 2.4
		 *
		 * @param array $args  The arguments for the SMS message.
		 * @param array $feed  The Feed object.
		 * @param array $entry The Entry object.
		 * @param array $form  The Form object.
		 */
		$args = gf_apply_filters( array( 'gform_twilio_message', $form['id'], $feed['id'] ), $args, $feed, $entry, $form );

		// Replace merge tags and shorten URLs in message.
		if ( $args['shorten_url'] ) {

			// Remove spaces from all merge tags; we need to do this so we can handle URLs that have spaces in them.
			preg_match_all( '/{[^{]*?:(\d+(\.\d+)?)(:(.*?))?}/mi', $args['body'], $matches, PREG_SET_ORDER );
			if ( is_array( $matches ) ) {
				foreach ( $matches as $match ) {
					$new_tag      = str_replace( ' ', '', $match[0] );
					$args['body'] = str_replace( $match[0], $new_tag, $args['body'] );
				}
			}

			// Find URLs. Process merge tags in URL. Shorten URL.
			preg_match_all( '~(https?|ftp):\/\/\S+~', $args['body'], $matches, PREG_SET_ORDER );
			if ( is_array( $matches ) ) {
				foreach ( $matches as $match ) {
					$url          = GFCommon::replace_variables( $match[0], $form, $entry, false, true, false, 'text' );
					$args['body'] = str_replace( $match[0], $this->shorten_url( $url ), $args['body'] );
				}
			}

			// Find any remaining merge tags (field or meta).
			preg_match_all( '/{[A-z0-9:^\s]*}/m', $args['body'], $matches, PREG_SET_ORDER );
			if ( is_array( $matches ) && ! empty( $matches ) ) {

				// Surround merge tags with the <mt> delimiter.
				foreach ( $matches as $match ) {
					$args['body'] = str_replace( $match[0], '<mt>' . $match[0] . '</mt>', $args['body'] );
				}

				// Replace merge tags.
				$args['body'] = GFCommon::replace_variables( $args['body'], $form, $entry, false, true, false, 'text' );

				// Find any urls from the replaced merge tags and pass to regex_shorten_url.
				$args['body'] = preg_replace_callback( '~<mt>(https?|ftp):\/\/.*<\/mt>~', array(
					$this,
					'regex_shorten_url'
				), $args['body'] );

				// Remove any remaining <mt> delimiters.
				$args['body'] = str_replace( array( '<mt>', '</mt>' ), '', $args['body'] );

			}

		} else {

			// Replace merge tags.
			$args['body'] = GFCommon::replace_variables( $args['body'], $form, $entry, false, true, false, 'text' );

		}

		// Limit message to 1600 characters.
		$args['body'] = substr( $args['body'], 0, 1600 );

		// Prepare message if using test API mode.
		if ( 'test' === rgar( $plugin_settings, 'apiMode' ) && $this->initialize_test_api() ) {

			// Get API object.
			$api = $this->twilio_test;

			// Set the From Number to a valid test number.
			$args['from'] = '+15005550006';

			// Log the message to be sent.
			$this->log_debug( __METHOD__ . '(): Sending SMS to Twilio (Test Mode): ' . print_r( $args, true ) );

		} else {

			// Get API object.
			$api = $this->twilio;

			// Log the message to be sent.
			$this->log_debug( __METHOD__ . '(): Sending SMS to Twilio: ' . print_r( $args, true ) );

		}

		try {

			// Remove To Number from arguments.
			$to = rgar( $args, 'to' );
			unset( $args['to'] );

			// Send message.
			$message = $api->messages->create( $to, $args );

			// Log that the message was sent.
			$this->log_debug( __METHOD__ . '(): SMS successfully sent; ' . print_r( $message, true ) );

		} catch ( \Exception $e ) {

			// Log that message could not be sent.
			$this->add_feed_error( sprintf( esc_html__( 'Unable to send SMS: %s (%d)', 'gravityformstwilio' ), $e->getMessage(), $e->getCode() ), $feed, $entry, $form );

		}

	}





	// # HELPER METHODS ------------------------------------------------------------------------------------------------

	/**
	 * Initializes Twilio API if credentials are valid.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 *
	 * @return bool|null
	 */
	public function initialize_api() {

		// If API is alredy initialized, return true.
		if ( ! is_null( $this->twilio ) ) {
			return true;
		}

		// Load the API library.
		if ( ! class_exists( '\Twilio\Rest\Client' ) ) {
			require_once( 'includes/autoload.php' );
		}

		// Get plugin settings.
		$settings = $this->get_plugin_settings();

		// If account SID or auth token are empty, return.
		if ( ! rgar( $settings, 'accountSid' ) || ! rgar( $settings, 'authToken' ) ) {
			return null;
		}

		// Log that we are going to validate API credentials.
		$this->log_debug( __METHOD__ . '(): Validating API credentials.' );

		// Initialize a new Twilio object with the API credentials.
		$twilio = new Twilio\Rest\Client( $settings['accountSid'], $settings['authToken'] );

		try {

			// List all accounts.
			$twilio->api->accounts->read();

			// Assign Twilio API object to instance.
			$this->twilio = $twilio;

			// Log that authentication test passed.
			$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

			return true;

		} catch ( \Exception $e ) {

			// Log that authentication test failed.
			$this->log_error( __METHOD__ . '(): API credentials are invalid; '. $e->getMessage() . ' (' . $e->getCode() . ')' );

			return false;

		}

	}

	/**
	 * Initializes test Twilio API if credentials are valid.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 *
	 * @return bool|null
	 */
	public function initialize_test_api() {

		// If API is alredy initialized, return true.
		if ( ! is_null( $this->twilio_test ) ) {
			return true;
		}

		// Load the API library.
		if ( ! class_exists( '\Twilio\Rest\Client' ) ) {
			require_once( 'vendor/autoload.php' );
		}

		// Get plugin settings.
		$settings = $this->get_plugin_settings();

		// If account SID or auth token are empty, return.
		if ( ! rgar( $settings, 'testAccountSid' ) || ! rgar( $settings, 'testAuthToken' ) ) {
			return null;
		}

		// Log that we are going to validate API credentials.
		$this->log_debug( __METHOD__ . '(): Validating test API credentials.' );

		// Initialize a new Twilio object with the API credentials.
		$twilio = new Twilio\Rest\Client( $settings['testAccountSid'], $settings['testAuthToken'] );

		try {

			// List all accounts.
			$twilio->incomingPhoneNumbers->create(
			    array(
			        "voiceUrl" => "http://demo.twilio.com/docs/voice.xml",
			        "phoneNumber" => "+15005550006"
			    )
			);

			// Assign test Twilio API object to instance.
			$this->twilio_test = $twilio;

			// Log that authentication test passed.
			$this->log_debug( __METHOD__ . '(): Test API credentials are valid.' );

			return true;

		} catch ( \Exception $e ) {

			// Log that authentication test failed.
			$this->log_error( __METHOD__ . '(): Test API credentials are invalid; '. $e->getMessage() . ' (' . $e->getCode() . ')' );

			return false;

		}

	}

	/**
	 * Get current Twilio account details.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @uses GFAddOn::log_error()
	 * @uses GFTwilio::initialize_api()
	 *
	 * @return object|bool
	 */
	public function get_current_account() {

		// If API cannot be initialized, return.
		if ( ! $this->initialize_api() ) {
			return false;
		}

		try {

			// Get all accounts.
			$accounts = $this->twilio->api->accounts->read();

			return rgar( $accounts, 0 );

		} catch ( \Exception $e ) {

			// Log that accounts could not be retrieved.
			$this->log_error( __METHOD__ . '(): Unable to get current account; '. $e->getMessage() . ' (' . $e->getCode() . ')' );

			return false;

		}

	}

	/**
	 * Return the from number or Alphanumeric Sender ID.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $feed The Feed object.
	 *
	 * @return string
	 */
	public function get_message_from( $feed ) {

		return 'gf_custom' === rgars( $feed, 'meta/fromNumber' ) ? rgars( $feed, 'meta/fromNumber_custom' ) : rgars( $feed, 'meta/fromNumber' );

	}

	/**
	 * Return the To Number.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @param array $feed  The Feed object.
	 * @param array $entry The Entry object.
	 * @param array $form  The Form object.
	 *
	 * @uses GFCommon::replace_variables()
	 *
	 * @return string
	 */
	public function get_message_to( $feed, $entry, $form ) {

		// If a custom value is set, return it.
		if ( 'gf_custom' === rgars( $feed, 'meta/toNumber' ) ) {
			return GFCommon::replace_variables( $feed['meta']['toNumber_custom'], $form, $entry, false, true, false, 'text' );
		}

		// Get To Number value.
		$to_number = rgars( $feed, 'meta/toNumber' );

		// If a field is not selected, return number.
		if ( 'field_' !== substr( $to_number, 0, 6 ) ) {
			return $to_number;
		}

		// Get field ID.
		$phone_field = str_replace( 'field_', '', $to_number );

		// Get field value.
		$to_number = rgar( $entry, $phone_field );

		return $to_number;

	}





	// # BITLY ---------------------------------------------------------------------------------------------------------

	/**
	 * Determine if Bitly URL shortening is allowed.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @uses GFTwilio::validate_bitly_credentials()
	 * @uses GFTwilio::validate_legacy_bitly_credentials()
	 *
	 * @return bool
	 */
	public function can_shorten_url() {

		// Validate Bitly credentials.
		$is_valid = $this->validate_bitly_credentials();

		// Validate Bitly credentials.
		if ( ! is_null( $is_valid ) ) {
			return $is_valid;
		}

		// Validate legacy credentials.
		return $this->validate_legacy_bitly_credentials();

	}

	/**
	 * Validate Bitly credentials.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @param string $access_token Bitly access token.
	 *
	 * @uses GFAddOn::get_plugin_setting()
	 * @uses GFAddOn::maybe_decode_json()
	 *
	 * @return bool|null
	 */
	public function validate_bitly_credentials( $access_token = false ) {

		// If access token was not provided, retrieve it.
		if ( false === $access_token ) {
			$access_token = $this->get_plugin_setting( 'bitlyAccessToken' );
		}

		// If access token is not set, return.
		if ( rgblank( $access_token ) ) {
			return null;
		}

		// Get Bitly user info.
		$user_info = wp_remote_get( 'https://api-ssl.bitly.com/v3/user/info?access_token=' . $access_token );

		// If request was unsuccessful, return.
		if ( is_wp_error( $user_info ) ) {
			return false;
		}

		// Decode response.
		$user_info = wp_remote_retrieve_body( $user_info );
		$user_info = $this->maybe_decode_json( $user_info );

		// If response is not an array, return.
		if ( ! is_array( $user_info ) ) {
			return false;
		}

		return 200 === $user_info['status_code'];

	}

	/**
	 * Shorten the supplied url using Bitly.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param string $url The url to shorten.
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFAddOn::maybe_decode_json()
	 * @uses GFTwilio::legacy_shorten_url()
	 *
	 * @return string
	 */
	public function shorten_url( $url ) {

		// Get plugin settings.
		$settings = $this->get_plugin_settings();

		// If legacy Bitly credentials exist, use legacy shortener.
		if ( ! rgar( $settings, 'bitlyAccessToken' ) && rgar( $settings, 'bitlyLogin' ) && rgar( $settings, 'bitlyApikey' ) ) {
			$this->log_debug( __METHOD__ . '(): Using legacy URL shortener.' );
			return $this->legacy_shorten_url( $url );
		}

		// If access token is set but invalid, return.
		if ( rgar( $settings, 'bitlyAccessToken') && ! $this->validate_bitly_credentials() ) {
			$this->log_error( __METHOD__ . '(): Unable to shorten URL; invalid Bitly access token provided.' );
			return $url;
		}

		// Prepare base API request URL.
		$request_url = 'https://api-ssl.bitly.com/v3/shorten';

		// Add request parameters.
		$request_url = add_query_arg(
			array(
				'access_token' => $settings['bitlyAccessToken'],
				'longUrl'      => $url,
			),
			$request_url
		);

		// Shorten URL.
		$shortened_url = wp_remote_get( $request_url );

		// If request was unsuccessful, return.
		if ( is_wp_error( $shortened_url ) ) {
			return $url;
		}

		// Decode response.
		$shortened_url = wp_remote_retrieve_body( $shortened_url );
		$shortened_url = $this->maybe_decode_json( $shortened_url );

		// If response is not an array or an invalid status code is returned, return.
		if ( ! is_array( $shortened_url ) || 200 !== $shortened_url['status_code'] ) {
			return $url;
		}

		return $shortened_url['data']['url'];

	}

	/**
	 * Shorten URLs from the message body.
	 *
	 * @since  Unknown
	 * @access public
	 *
	 * @param array $matches The URL to be shortened.
	 *
	 * @uses GFTwilio::shorten_url()
	 *
	 * @return string
	 */
	public function regex_shorten_url( $matches ) {

		// Remove the <mt> delimiter from the string containing the URL before passing to Bitly.
		$url = str_replace( array( '<mt>', '</mt>' ), '', $matches[0] );

		return $this->shorten_url( $url );

	}

	/**
	 * Check if the Bitly credentials are valid by testing if a url can be shortened.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @uses GFTwilio::legacy_shorten_url()
	 *
	 * @return bool
	 */
	public function validate_legacy_bitly_credentials() {

		// Attempt to shorten URL.
		$url = $this->legacy_shorten_url( 'http://www.google.com' );

		return $url != 'http://www.google.com';
	}

	/**
	 * Shorten the supplied URL using legacy Bitly endpoint.
	 *
	 * @since  2.4
	 * @access public
	 *
	 * @param string $url The URL to shorten.
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 *
	 * @return string
	 */
	public function legacy_shorten_url( $url ) {

		// Log the URL to be shortened.
		$this->log_debug( __METHOD__ . "(): Processing URL => {$url}" );

		// Prepare request parameters.
		$encoded_url = urlencode( $url );
		$settings    = $this->get_plugin_settings();
		$login       = urlencode( rgar( $settings, 'bitlyLogin' ) );
		$apikey      = urlencode( rgar( $settings, 'bitlyApikey' ) );

		// Shorten URL.
		$response = wp_remote_get( "http://api.bit.ly/v3/shorten?login={$login}&apiKey={$apikey}&longUrl={$encoded_url}&format=txt" );

		// If URL could not be shortened, return.
		if ( is_wp_error( $response ) || $response['response']['code'] != 200 ) {
			$this->log_error( __METHOD__ . '(): Unable to shorten URL. Not changing.' );
			return $url;
		}

		// Log response from Bitly.
		$this->log_debug( __METHOD__ . '(): Response from bitly => ' . print_r( $response, true ) );

		// Check if shortened URL is valid.
		$is_valid = substr( trim( $response['body'] ), 0, 4 ) == 'http';

		return $is_valid ? trim( $response['body'] ) : $url;

	}





	// # UPGRADES ------------------------------------------------------------------------------------------------------

	/**
	 * Checks if a previous version was installed and if the feeds need migrating to the framework structure.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param string $previous_version The version number of the previously installed version.
	 *
	 * @uses GFAddOn::get_plugin_settings()
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::log_error()
	 * @uses GFAddOn::update_plugin_settings()
	 * @uses GFFeedAddOn::get_feeds()
	 * @uses GFFeedAddOn::insert_feed()
	 * @uses GFFeedAddOn::update_feed_meta()
	 * @uses GFTwilio::copy_feeds()
	 * @uses GFTwilio::initialize_api()
	 * @uses GFTwilio::update_paypal_delay_settings()
	 */
	public function upgrade( $previous_version ) {

		// Get previous version from pre Add-On Framework.
		if ( empty( $previous_version ) ) {
			$previous_version = get_option( 'gf_twilio_version' );
		}

		// Check if previous version is from before the Add-On Framework.
		$previous_is_pre_addon_framework = ! empty( $previous_version ) && version_compare( $previous_version, '2.0.dev1', '<' );

		// Check if previous version is from before Phone fields were supported for To Number.
		$previous_is_pre_to_number = ! empty( $previous_version ) && version_compare( $previous_version, '2.4.1', '<' );

		// Migrate feeds to Add-On Framework.
		if ( $previous_is_pre_addon_framework ) {

			// Get old feeds.
			$old_feeds = $this->get_old_feeds();

			// If old feeds were found, migrate them.
			if ( $old_feeds ) {

				// Initialize feed name counter.
				$counter = 1;

				// Loop through old feeds.
				foreach ( $old_feeds as $old_feed ) {

					// Prepare feed name.
					$feed_name = 'Feed ' . $counter;
					$form_id   = $old_feed['form_id'];
					$is_active = $old_feed['is_active'];

					// Prepare new feed meta.
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

					// Create new feed.
					$this->insert_feed( $form_id, $is_active, $new_meta );
					$counter ++;

				}

				// Get old plugin settings.
				$old_settings = get_option( 'gf_twilio_settings' );

				// Prepare new plugin settings.
				$new_settings = array(
					'accountSid'  => $old_settings['account_sid'],
					'authToken'   => $old_settings['auth_token'],
					'bitlyLogin'  => $old_settings['bitly_login'],
					'bitlyApikey' => $old_settings['bitly_apikey'],
				);

				// Save new plugin settings.
				$this->update_plugin_settings( $new_settings );

				// Set paypal delay setting.
				$this->update_paypal_delay_settings( 'delay_twilio' );

			}

		}

		// Migrate feeds to support Phone field for To Number.
		if ( $previous_is_pre_to_number ) {

			// If we cannot initialize the API, return.
			if ( ! $this->initialize_api() ) {
				return;
			}

			try {

				// Get Twilio phone numbers.
				$twilio_numbers = $this->twilio->outgoingCallerIds->read();

				// Log Twilio phone numbers.
				$this->log_debug( __METHOD__ . '(): Twilio outgoing caller IDs: ' . print_r( $twilio_numbers, true ) );

			} catch ( \Exception $e ) {

				// Log that we could not get the phone numbers.
				$this->log_error( __METHOD__ . '(): Unable to get the Twilio outgoing caller IDs; ' . $e->getMessage() . ' (' . $e->getCode() . ')' );

				return;

			}

			// Get existing feeds.
			$feeds = $this->get_feeds();

			// Loop through feeds.
			foreach ( $feeds as $feed ) {

				// Set number found variable.
				$number_found = false;

				// Loop through Twilio phone numbers.
				foreach ( $twilio_numbers as $twilio_number ) {

					// If the To Number matches the Twilio number, updated found number flag.
					if ( $feed['meta']['toNumber'] === $twilio_number->phoneNumber ) {
						$number_found = true;
						break;
					}

				}

				// If number was not found, move number to custom field.
				if ( ! $number_found ) {

					// Update To Number meta.
					$feed['meta']['toNumber_custom'] = $feed['meta']['toNumber'];
					$feed['meta']['toNumber']        = 'gf_custom';

					// Save feed meta.
					$this->update_feed_meta( $feed['id'], $feed['meta'] );

				}

			}

		}

		return;

	}

	/**
	 * Migrate the delayed payment setting for the PayPal Add-On integration.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @param string $old_delay_setting_name
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFFeedAddOn::get_feeds_by_slug()
	 * @uses GFFeedAddOn::update_feed_meta()
	 * @uses wpdb::update()
	 */
	public function update_paypal_delay_settings( $old_delay_setting_name ) {

		global $wpdb;

		// Log that we are beginning migration.
		$this->log_debug( __METHOD__ . '(): Checking to see if there are any delay settings that need to be migrated for PayPal Standard.' );

		// Get new delay setting name.
		$new_delay_setting_name = 'delay_' . $this->_slug;

		// Get paypal feeds from old table.
		$paypal_feeds_old = $this->get_old_paypal_feeds();

		// Loop through feeds, look for delay setting and create duplicate with new delay setting for the framework version of PayPal Standard.
		if ( ! empty( $paypal_feeds_old ) ) {

			// Log that we are migrating delay settings.
			$this->log_debug( __METHOD__ . '(): Old feeds found for ' . $this->_slug . ' - copying over delay settings.' );

			// Loop through PayPal feeds.
			foreach ( $paypal_feeds_old as $old_feed ) {

				// Get feed meta.
				$meta = $old_feed['meta'];

				// If feed was not delayed, skip it.
				if ( rgempty( $old_delay_setting_name, $meta ) ) {
					continue;
				}

				$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
				$meta                            = maybe_serialize( $meta );

				$wpdb->update( "{$wpdb->prefix}rg_paypal", array( 'meta' => $meta ), array( 'id' => $old_feed['id'] ), array( '%s' ), array( '%d' ) );

			}

		}

		// Get paypal feeds from new framework table.
		$paypal_feeds = $this->get_feeds_by_slug( 'gravityformspaypal' );

		// Loop through feeds, look for delay setting and create duplicate with new delay setting for the framework version of PayPal Standard.
		if ( ! empty( $paypal_feeds ) ) {

			// Log that we are migrating delay settings.
			$this->log_debug( __METHOD__ . '(): New feeds found for ' . $this->_slug . ' - copying over delay settings.' );

			// Loop through PayPal feeds.
			foreach ( $paypal_feeds as $feed ) {

				// Get feed meta.
				$meta = $feed['meta'];

				// If feed was not delayed, skip it.
				if ( rgempty( $old_delay_setting_name, $meta ) ) {
					continue;
				}

				$meta[ $new_delay_setting_name ] = $meta[ $old_delay_setting_name ];
				$this->update_feed_meta( $feed['id'], $meta );

			}

		}

	}

	/**
	 * Retrieve any old PayPal feeds.
	 *
	 * @since  2.0
	 * @access public
	 *
	 * @uses GFAddOn::log_debug()
	 * @uses GFAddOn::table_exists()
	 * @uses GFFormsModel::get_form_table_name()
	 * @uses wpdb::table_exists()
	 *
	 * @return bool|array
	 */
	public function get_old_paypal_feeds() {

		global $wpdb;

		// Define PayPal feeds table name.
		$table_name = $wpdb->prefix . 'rg_paypal';

		// If PayPal feeds table does not exist, exit.
		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		// Get forms table name.
		$form_table_name = GFFormsModel::get_form_table_name();

		// Prepare query.
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
				FROM {$table_name} s
				INNER JOIN {$form_table_name} f ON s.form_id = f.id";

		// Log query.
		$this->log_debug( __METHOD__ . "() getting old paypal feeds: {$sql}" );

		// Get results.
		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Log error.
		$this->log_debug( __METHOD__ . "(): error?: {$wpdb->last_error}" );

		// Get feed count.
		$count = count( $results );

		// Log feed count.
		$this->log_debug( __METHOD__ . "(): count: {$count}" );

		// Unserialize feed data.
		for ( $i = 0; $i < $count; $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;

	}

	/**
	 * Retrieve any old feeds which need migrating to the framework.
	 *
	 * @since 2.0
	 * @access public
	 *
	 * @uses GFAddOn::table_exists()
	 * @uses GFFormsModel::get_form_table_name()
	 * @uses wpdb::table_exists()
	 *
	 * @return bool|array
	 */
	public function get_old_feeds() {

		global $wpdb;

		// Define Twilio feeds table name.
		$table_name = $wpdb->prefix . 'rg_twilio';

		// If Twilio feeds table does not exist, exit.
		if ( ! $this->table_exists( $table_name ) ) {
			return false;
		}

		// Get forms table name.
		$form_table_name = RGFormsModel::get_form_table_name();

		// Prepare query.
		$sql             = "SELECT s.id, s.is_active, s.form_id, s.meta, f.title as form_title
			FROM $table_name s
			INNER JOIN $form_table_name f ON s.form_id = f.id";

		// Get feeds.
		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Unserialize feed data.
		for ( $i = 0; $i < count( $results ); $i ++ ) {
			$results[ $i ]['meta'] = maybe_unserialize( $results[ $i ]['meta'] );
		}

		return $results;

	}

}
