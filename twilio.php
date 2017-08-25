<?php
/*
Plugin Name: Gravity Forms Twilio Add-On
Plugin URI: http://www.gravityforms.com
Description: Integrates Gravity Forms with Twilio, allowing SMS messages to be sent upon submitting a Gravity Form
Version: 2.4.1
Author: rocketgenius
Author URI: http://www.rocketgenius.com
Text Domain: gravityformstwilio
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2009-2015 rocketgenius

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

define( 'GF_TWILIO_VERSION', '2.4.1' );

// If Gravity Forms is loaded, bootstrap the Twilio Add-On.
add_action( 'gform_loaded', array( 'GF_Twilio_Bootstrap', 'load' ), 5 );

/**
 * Class GF_Twilio_Bootstrap
 *
 * Handles the loading of the Twilio Add-On and registers with the Add-On Framework.
 */
class GF_Twilio_Bootstrap {

	/**
	 * If the Feed Add-On Framework exists, Twilio Add-On is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load(){

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-twilio.php' );

		GFAddOn::register( 'GFTwilio' );
	}
}

/**
 * Returns an instance of the GFTwilio class
 *
 * @see    GFTwilio::get_instance()
 *
 * @return object GFTwilio
 */
function gf_twilio(){
	return GFTwilio::get_instance();
}
