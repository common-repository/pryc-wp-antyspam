<?php
/*
 * Plugin Name: PRyC WP: AntiSPAM
 * Plugin URI: 
 * Description: Block SPAM without any type of CAPTCHA - plugin add "HoneyTrap" (and a few other tricks) for comment form to block SPAMbots. Work fine for all my and my client site :-)
 * Author: PRyC
 * Author URI: http://PRyC.pl
 * Version: 1.5.2
 */


/* CODE: */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ----- ----- ----- ----- ----- ----- ----- ----- */

# Block XML-RPC
if ( ! function_exists( 'pryc_wp_antyspam_disable_xmlrpc' ) ) :
function pryc_wp_antyspam_disable_xmlrpc() {
	# Read from DB
	$options = get_option( 'pryc_wp_antyspam_settings' );

	if ( ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_6'] ) ) && ( $options['pryc_wp_antyspam_section6_checkbox_field_6'] == "1" ) ) { 
		add_filter('xmlrpc_enabled', '__return_false');
	}
}
endif;
add_action( 'init', 'pryc_wp_antyspam_disable_xmlrpc' );



# Frontend actions

if ( ! function_exists( 'pryc_wp_antyspam_add_honeypot_comment_field' ) ) :
function pryc_wp_antyspam_add_honeypot_comment_field($fields) {

	# Read from DB
	$options = get_option( 'pryc_wp_antyspam_settings' );
	
	# Sanitize Author HTML Tag
	if ( ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_7'] ) ) && ( $options['pryc_wp_antyspam_section6_checkbox_field_7'] == "1" ) ) {
		if(isset($fields['author'])) wp_strip_all_tags( $fields['author'] );
	}

	
	# Hide URL field
	if ( ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_1'] ) ) && ( $options['pryc_wp_antyspam_section6_checkbox_field_1'] == "1" ) ) {
		if(isset($fields['url'])) unset( $fields['url'] );
	}
	
	# Hide e-mail field
	if ( ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_2'] ) ) && ( $options['pryc_wp_antyspam_section6_checkbox_field_2'] == "1" ) ) {
		if(isset($fields['email'])) unset( $fields['email'] );
	}

	# Fake "required"
	if ( ( !isset( $options['pryc_wp_antyspam_section4_checkbox_field_2'] ) ) || ( empty( $options['pryc_wp_antyspam_section4_checkbox_field_2'] ) ) ) {
		$pryc_wp_antyspam_honeypot_field_fake_required = '<span class="required">*</span>';
	} else {
		$pryc_wp_antyspam_honeypot_field_fake_required = '';
	}

	
	# HoneyTrap label
	if ( ( isset( $options['pryc_wp_antyspam_section4_text_field_1'] ) ) && !empty( $options['pryc_wp_antyspam_section4_text_field_1'] ) ) {	
		$pryc_wp_antyspam_honeypot_field_label = $options['pryc_wp_antyspam_section4_text_field_1']; 
	} else { $pryc_wp_antyspam_honeypot_field_label = __( 'AntiSPAM', 'pryc_wp_antyspam' ); }
	
	# Default text
	if ( ( isset( $options['pryc_wp_antyspam_section4_text_field_2'] ) ) && !empty( $options['pryc_wp_antyspam_section4_text_field_2'] ) ) {	
		$pryc_wp_antyspam_honeypot_field_placeholder = $options['pryc_wp_antyspam_section4_text_field_2']; 
	} else { $pryc_wp_antyspam_honeypot_field_placeholder = __( 'AntiSPAM: That its OK, do nothing!', 'pryc_wp_antyspam' ); }
	
	# Random field name ( theme name + md5 - digit )
	if ( ( isset( $options['pryc_wp_antyspam_section2_text_field_1'] ) ) && !empty( $options['pryc_wp_antyspam_section2_text_field_1'] ) ) {	
		$pryc_wp_antyspam_honeypot_field = $options['pryc_wp_antyspam_section2_text_field_1']; 
	} else { $pryc_wp_antyspam_honeypot_field = preg_replace('~[^A-Za-z]~','',md5(wp_get_theme())); }
	
	# Content when no JS
	if ( ( isset( $options['pryc_wp_antyspam_section4_text_field_3'] ) ) && !empty( $options['pryc_wp_antyspam_section4_text_field_3'] ) ) {	
		$pryc_wp_antyspam_honeypot_field_fake_value = $options['pryc_wp_antyspam_section4_text_field_3']; 
	} else { $pryc_wp_antyspam_honeypot_field_fake_value = __( 'To send a comment - delete this text', 'pryc_wp_antyspam' ); }
	
	
	# Add field
     $fields[$pryc_wp_antyspam_honeypot_field] = '<p class="' . $pryc_wp_antyspam_honeypot_field . '"><label for="' . $pryc_wp_antyspam_honeypot_field . '">' . $pryc_wp_antyspam_honeypot_field_label . ' ' . $pryc_wp_antyspam_honeypot_field_fake_required . '</label>' .
        '<input id="' . $pryc_wp_antyspam_honeypot_field . '" name="' . $pryc_wp_antyspam_honeypot_field . '" type="text" placeholder="' . $pryc_wp_antyspam_honeypot_field_placeholder . '" size="50" value="' . $pryc_wp_antyspam_honeypot_field_fake_value . '" /></p>' . wp_nonce_field($pryc_wp_antyspam_honeypot_field, $pryc_wp_antyspam_honeypot_field . '_nonce');
		# Add WP nonce (UP)
	
    return $fields;
 
}
endif;
add_filter( 'comment_form_default_fields','pryc_wp_antyspam_add_honeypot_comment_field' );



# Do all magic ;-)

if ( ! function_exists( 'pryc_wp_antyspam_verification_comment_meta_data' ) ) :
function pryc_wp_antyspam_verification_comment_meta_data( $commentdata ) {

	# Read from DB
	$options = get_option( 'pryc_wp_antyspam_settings' );	

	# Trackaback ON/OFF
	if ( ( ( !isset( $options['pryc_wp_antyspam_section6_checkbox_field_3'] ) ) || ( empty( $options['pryc_wp_antyspam_section6_checkbox_field_3'] ) ) ) && ( $commentdata['comment_type'] == 'trackback' ) ) { return $commentdata; }
	
	# Pingback ON/OFF
	if ( ( ( !isset( $options['pryc_wp_antyspam_section6_checkbox_field_4'] ) ) || ( empty( $options['pryc_wp_antyspam_section6_checkbox_field_4'] ) ) ) && ( $commentdata['comment_type'] == 'pingback' ) ) { return $commentdata; }
	
	
	# Random field name ( theme name + md5 - digit )
		
	if ( ( isset( $options['pryc_wp_antyspam_section2_text_field_1'] ) ) && !empty( $options['pryc_wp_antyspam_section2_text_field_1'] ) ) {	
		$pryc_wp_antyspam_honeypot_field = $options['pryc_wp_antyspam_section2_text_field_1']; 
	} else { $pryc_wp_antyspam_honeypot_field = preg_replace('~[^A-Za-z]~','',md5(wp_get_theme())); }
	
	
	# Check WP nonce
	//$get_nonce = $_REQUEST[$pryc_wp_antyspam_honeypot_field . '_nonce'];
	//if ( wp_verify_nonce($get_nonce, $pryc_wp_antyspam_honeypot_field ) ) { $nonce_crc ="nonce_ok"; } else { $nonce_crc ="nonce_error"; }

	
	# !is_admin - no chceck WP nonce
	# !is_user - no honetrap field
  #if ( ( ( ( isset( $_POST[$pryc_wp_antyspam_honeypot_field] ) ) && ( !empty( $_POST[$pryc_wp_antyspam_honeypot_field] ) ) ) || ( ( $nonce_crc =="nonce_error" ) && !is_admin() ) ) && ( !is_user_logged_in() ) ) {
  
  # TEMP: No nonce chceck
  if ( ( ( isset( $_POST[$pryc_wp_antyspam_honeypot_field] ) ) && ( !empty( $_POST[$pryc_wp_antyspam_honeypot_field] ) ) ) && ( !is_user_logged_in() ) ) {
  
	# Save log (if set)
	if ( ( isset( $options['pryc_wp_antyspam_section5_checkbox_field_2'] ) ) && !empty( $options['pryc_wp_antyspam_section5_checkbox_field_2'] ) ) {
  
		# log
		$pryc_wp_antyspam_uploadDir = wp_upload_dir();
		$pryc_wp_antyspam_logFile = $pryc_wp_antyspam_uploadDir['basedir'] . '/pryc_add_anty_spam_comment_log.csv';
		
		$pryc_data_time = explode(" ", current_time( 'mysql' )); 

		
		# Make file
		if ( !file_exists($pryc_wp_antyspam_logFile) ) {
			$makeFile = fopen($pryc_wp_antyspam_logFile, "w");
			file_put_contents( $pryc_wp_antyspam_logFile, trim( 'date; time; author; email; url; comment; antyspam; nonce' ) . PHP_EOL, FILE_APPEND );
		}
		
		# Get data
		if ( !empty( $_POST['author'] ) ) { $pryc_comment_author = $_POST['author']; } else { $pryc_comment_author = ""; }
		if ( !empty( $_POST['email'] ) ) { $pryc_comment_email = $_POST['email']; } else { $pryc_comment_email = ""; }
		if ( !empty( $_POST['url'] ) ) { $pryc_comment_url = $_POST['url']; } else { $pryc_comment_url = ""; }
		if ( !empty( $_POST['comment'] ) ) { $pryc_comment_comment = $_POST['comment']; } else { $pryc_comment_comment = ""; }
		# maybe_serialize - serialize data, if needed
		if ( !empty( $_POST[$pryc_wp_antyspam_honeypot_field] ) ) { $pryc_comment_antyspam = maybe_serialize( $_POST[$pryc_wp_antyspam_honeypot_field] ); } else { $pryc_comment_antyspam = ""; }
	  
		# Save log:
		file_put_contents( $pryc_wp_antyspam_logFile, trim( $pryc_data_time[0] . '; ' . $pryc_data_time[1] . '; ' . $pryc_comment_author . '; ' . $pryc_comment_email . '; ' . $pryc_comment_url . '; ' . $pryc_comment_comment . '; ' . $pryc_comment_antyspam . '; ' . $nonce_crc ) . PHP_EOL, FILE_APPEND );
	}

	

	# Mark as a SPAM (if set)
	if ( ( isset( $options['pryc_wp_antyspam_section5_checkbox_field_1'] ) ) && !empty( $options['pryc_wp_antyspam_section5_checkbox_field_1'] ) ) {
		$commentdata['comment_approved'] = 'spam';
		
		if ( !empty( $_POST[$pryc_wp_antyspam_honeypot_field] ) ) { $pryc_comment_antyspam = sanitize_text_field( $_POST[$pryc_wp_antyspam_honeypot_field] ); } else { $pryc_comment_antyspam = ""; }
		
		$commentdata['comment_content'] = '[' . __( 'Comment blocked by PRyC WP: AntiSPAM', 'pryc_wp_antyspam' ) . ']<br />(HoneyTrap: ' . $pryc_comment_antyspam . ')<br /><br />' . $commentdata['comment_content'] ;
		
		wp_insert_comment( $commentdata );
	}
	
	# Show error msg?
	if ( ( isset( $options['pryc_wp_antyspam_section3_checkbox_field_1'] ) ) && ( $options['pryc_wp_antyspam_section3_checkbox_field_1'] == "1" ) ) { 
		wp_die();
		
	} else {

		# Error msg:
		if ( ( isset( $options['pryc_wp_antyspam_section3_textarea_field_1'] ) ) && !empty( $options['pryc_wp_antyspam_section3_textarea_field_1'] ) ) { wp_die( $options['pryc_wp_antyspam_section3_textarea_field_1'] );	} else { wp_die( __( 'Ha! <a href="javascript:javascript:history.go(-1)">Click to back</a>', 'pryc_wp_antyspam' ) ); }
	
	}

	
  }
		
	return $commentdata;
  
}
endif;
add_filter( 'preprocess_comment', 'pryc_wp_antyspam_verification_comment_meta_data' );


/* ----- WP Admin ----- */

add_action( 'admin_menu', 'pryc_wp_antyspam_add_admin_menu' );
add_action( 'admin_init', 'pryc_wp_antyspam_settings_init' );

# Menu
function pryc_wp_antyspam_add_admin_menu() { 

	add_options_page( 'PRyC WP: AntiSPAM', 'PRyC WP: AntiSPAM', 'manage_options', 'pryc_wp_antyspam', 'pryc_wp_antyspam_options_page' );

}

# Prepare
function pryc_wp_antyspam_settings_init() { 

	register_setting( 'PRyC WP: AntiSPAM (pluginPage)', 'pryc_wp_antyspam_settings' );

	# S1
	add_settings_section(
		'pryc_wp_antyspam_section1', 
		__( 'Block SPAM without any type of CAPTCHA - plugin add "HoneyTrap" (and a few other tricks) for comment form to block SPAMbots.', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_settings_section_callback1', 
		'PRyC WP: AntiSPAM (pluginPage)'
	);
	
	# S2
	add_settings_section(
		'pryc_wp_antyspam_section2', 
		'<hr /><br />' . __( 'Frontend field name/CSS class', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_settings_section_callback2', 
		'PRyC WP: AntiSPAM (pluginPage)'
	);
	
	# S3
	add_settings_section(
		'pryc_wp_antyspam_section3', 
		'<hr /><br />' . __( 'Error text message', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_settings_section_callback3', 
		'PRyC WP: AntiSPAM (pluginPage)'
	);
	
	# S4
	add_settings_section(
		'pryc_wp_antyspam_section4', 
		'<hr /><br />' . __( 'Show/hide HoneyTrap field', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_settings_section_callback4', 
		'PRyC WP: AntiSPAM (pluginPage)'
	);
	
	# S5
	add_settings_section(
		'pryc_wp_antyspam_section5', 
		'<hr /><br />' . __( 'Log SPAM options', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_settings_section_callback5', 
		'PRyC WP: AntiSPAM (pluginPage)'
	);
	
	# S6
	add_settings_section(
		'pryc_wp_antyspam_section6', 
		'<hr /><br />' . __( 'Other options', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_settings_section_callback6', 
		'PRyC WP: AntiSPAM (pluginPage)'
	);
	
	/* ----- Field ----- */
	
	# Name field/CSS
	add_settings_field( 
		'pryc_wp_antyspam_section2_text_field_1', 
		__( 'Own field/CSS class name:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section2_text_field_1_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section2' 
	);
	
	# Error message
	add_settings_field( 
		'pryc_wp_antyspam_section3_textarea_field_1', 
		__( 'Error message:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section3_textarea_field_1_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section3' 
	);
	
	# Disable error message
	add_settings_field( 
		'pryc_wp_antyspam_section3_checkbox_field_1', 
		__( 'Disable error message (text):', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section3_checkbox_field_1_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section3' 
	);

	# Show field
	add_settings_field( 
		'pryc_wp_antyspam_section4_checkbox_field_1', 
		__( 'Show HoneyTrap field:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section4_checkbox_field_1_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section4' 
	);
	
	# Field label
	add_settings_field( 
		'pryc_wp_antyspam_section4_text_field_1', 
		__( 'Label:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section4_text_field_1_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section4' 
	);
	
	# Placeholder text
	add_settings_field( 
		'pryc_wp_antyspam_section4_text_field_2', 
		__( 'Placeholder:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section4_text_field_2_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section4' 
	);
	
	# Placeholder text
	add_settings_field( 
		'pryc_wp_antyspam_section4_text_field_3', 
		__( 'Fake value:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section4_text_field_3_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section4' 
	);
	
	# Fake required (*)
	add_settings_field( 
		'pryc_wp_antyspam_section4_checkbox_field_2', 
		__( 'Disable fake required (*):', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section4_checkbox_field_2_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section4' 
	);
	
	
	# Mark as a SPAM
	add_settings_field( 
		'pryc_wp_antyspam_section5_checkbox_field_1', 
		__( 'Mark as a SPAM:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section5_checkbox_field_1_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section5' 
	);
	
	# Log to file
	add_settings_field( 
		'pryc_wp_antyspam_section5_checkbox_field_2', 
		__( 'Log to file:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section5_checkbox_field_2_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section5' 
	);

	# URL ON/OFF
	add_settings_field( 
		'pryc_wp_antyspam_section6_checkbox_field_1', 
		__( 'Hide URL field:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section6_checkbox_field_1_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section6' 
	);
	
	# E-mail ON/OFF
	add_settings_field( 
		'pryc_wp_antyspam_section6_checkbox_field_2', 
		__( 'Hide e-mail field:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section6_checkbox_field_2_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section6' 
	);
	
	# Sanitize Author HTML Tag
	add_settings_field( 
		'pryc_wp_antyspam_section6_checkbox_field_7', 
		__( 'Sanitize Author field:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section6_checkbox_field_7_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section6' 
	);
	
	# Filter trackback
	add_settings_field( 
		'pryc_wp_antyspam_section6_checkbox_field_3', 
		__( 'Filter trackabck:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section6_checkbox_field_3_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section6' 
	);
	
	# Filter pingback
	add_settings_field( 
		'pryc_wp_antyspam_section6_checkbox_field_4', 
		__( 'Filter pingback:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section6_checkbox_field_4_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section6' 
	);
	
	# XML-RPC
	add_settings_field( 
		'pryc_wp_antyspam_section6_checkbox_field_6', 
		__( 'Disable XML-RPC:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section6_checkbox_field_6_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section6' 
	);
	
	# Clear plugin data @ uninstall
	add_settings_field( 
		'pryc_wp_antyspam_section6_checkbox_field_5', 
		__( 'Clear plugin data:', 'pryc_wp_antyspam' ), 
		'pryc_wp_antyspam_section6_checkbox_field_5_render', 
		'PRyC WP: AntiSPAM (pluginPage)', 
		'pryc_wp_antyspam_section6' 
	);
	
	
}


# Componets

# Name/CSS
function pryc_wp_antyspam_section2_text_field_1_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='text' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section2_text_field_1]' value='<?php 
	
	if ( isset( $options['pryc_wp_antyspam_section2_text_field_1'] ) && !empty( $options['pryc_wp_antyspam_section2_text_field_1'] )) {	
		echo $options['pryc_wp_antyspam_section2_text_field_1']; 
	} else { echo preg_replace('~[^A-Za-z]~','',md5(wp_get_theme())); }
	
	?>' cols='' style='width:100%' >
	<?php

}


# Error message
function pryc_wp_antyspam_section3_textarea_field_1_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	
	<textarea cols='' rows='5' style='width:100%' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section3_textarea_field_1]'><?php if ( isset( $options['pryc_wp_antyspam_section3_textarea_field_1'] ) && !empty( $options['pryc_wp_antyspam_section3_textarea_field_1'] )) { echo $options['pryc_wp_antyspam_section3_textarea_field_1']; } else { echo __( 'Ha! <a href="javascript:javascript:history.go(-1)">Click to back</a>', 'pryc_wp_antyspam' ); }	?></textarea>
	
	<?php
}

# Disable error message
function pryc_wp_antyspam_section3_checkbox_field_1_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section3_checkbox_field_1]' <?php if ( isset( $options['pryc_wp_antyspam_section3_checkbox_field_1'] ) ) { checked( $options['pryc_wp_antyspam_section3_checkbox_field_1'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'By default plugin show error message (look up)', 'pryc_wp_antyspam' );

}



# Show HoneyTrap
function pryc_wp_antyspam_section4_checkbox_field_1_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section4_checkbox_field_1]' <?php if ( isset( $options['pryc_wp_antyspam_section4_checkbox_field_1'] ) ) { checked( $options['pryc_wp_antyspam_section4_checkbox_field_1'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'By default HoneyTrap field is hidden from users (JavaScript and CSS)', 'pryc_wp_antyspam' );

}

# Label
function pryc_wp_antyspam_section4_text_field_1_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='text' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section4_text_field_1]' value='<?php 
	
	if ( isset( $options['pryc_wp_antyspam_section4_text_field_1'] ) ) {	
		echo $options['pryc_wp_antyspam_section4_text_field_1']; 
	} else { echo __( 'AntiSPAM', 'pryc_wp_antyspam' ); }
	
	?>' cols='' style='width:100%' >
	<?php

}

# Placeholder
function pryc_wp_antyspam_section4_text_field_2_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='text' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section4_text_field_2]' value='<?php 
	
	if ( isset( $options['pryc_wp_antyspam_section4_text_field_2'] ) ) {	
		echo $options['pryc_wp_antyspam_section4_text_field_2']; 
	} else { echo __( 'AntiSPAM: That its OK, do nothing!', 'pryc_wp_antyspam' ); }
	
	?>' cols='' style='width:100%' >
	<?php

}

# Value
function pryc_wp_antyspam_section4_text_field_3_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='text' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section4_text_field_3]' value='<?php 
	
	if ( isset( $options['pryc_wp_antyspam_section4_text_field_3'] ) ) {	
		echo $options['pryc_wp_antyspam_section4_text_field_3']; 
	} else { echo __( 'To send a comment - delete this text', 'pryc_wp_antyspam' ); }
	
	?>' cols='' style='width:100%' >
	<?php
	echo __( 'Content automatically deleted by JavaScript (No JS = show this text to manual del)', 'pryc_wp_antyspam' );

}


# Fake required (*)
function pryc_wp_antyspam_section4_checkbox_field_2_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section4_checkbox_field_2]' <?php if ( isset( $options['pryc_wp_antyspam_section4_checkbox_field_2'] ) ) { checked( $options['pryc_wp_antyspam_section4_checkbox_field_2'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'By default HoneyTrap field has a false/fake "required" mark (disable = more SPAM)', 'pryc_wp_antyspam' );

}


# Mark as a SPAM
function pryc_wp_antyspam_section5_checkbox_field_1_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section5_checkbox_field_1]' <?php if ( isset( $options['pryc_wp_antyspam_section5_checkbox_field_1'] ) ) { checked( $options['pryc_wp_antyspam_section5_checkbox_field_1'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'Mark bad comment as a SPAM (otherwise the comment will be automatically deleted)', 'pryc_wp_antyspam' );

}

# Log to file
function pryc_wp_antyspam_section5_checkbox_field_2_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section5_checkbox_field_2]' <?php if ( isset( $options['pryc_wp_antyspam_section5_checkbox_field_2'] ) ) { checked( $options['pryc_wp_antyspam_section5_checkbox_field_2'], 1 ); } ?> value='1'>
	
	<?php
	
		# log file
		$pryc_wp_antyspam_uploadDir = wp_upload_dir();
		$pryc_wp_antyspam_logFile = $pryc_wp_antyspam_uploadDir['basedir'] . '/pryc_add_anty_spam_comment_log.csv';
		#$pryc_wp_antyspam_logFile = get_stylesheet_directory() . '/pryc_add_anty_spam_comment_log.csv';
		
		# Del log file
		if ( ( isset( $_GET['pryc_wp_antyspam_get_cmd'] ) ) && ( $_GET['pryc_wp_antyspam_get_cmd'] == 'unlink_log_file' ) && ( isset( $_GET['pryc_wp_antyspam_get_cmd_wpnonce'] ) ) && ( wp_verify_nonce( $_GET['pryc_wp_antyspam_get_cmd_wpnonce'], 'unlink_log_file' ) ) && ( file_exists($pryc_wp_antyspam_logFile) ) ) { 
			@unlink( $pryc_wp_antyspam_logFile );
		}
		
		if ( file_exists($pryc_wp_antyspam_logFile) ) {
			$pryc_wp_antyspam_settings_show_logfile_link = "1";
			$pryc_wp_antyspam_downloadLogFile = $pryc_wp_antyspam_uploadDir['baseurl'] . '/pryc_add_anty_spam_comment_log.csv';
		}
	
	
	$pryc_wp_antyspam_settings_unlink_log_file = wp_nonce_url(admin_url('options-general.php?page=pryc_wp_antyspam&pryc_wp_antyspam_get_cmd=unlink_log_file'), 'unlink_log_file', 'pryc_wp_antyspam_get_cmd_wpnonce');
	
	echo __( 'Log bad comment to log file', 'pryc_wp_antyspam' );
	if ( $pryc_wp_antyspam_settings_show_logfile_link == "1" ) {	echo ' <a href="' . $pryc_wp_antyspam_downloadLogFile . '">(see log file</a> | <a href="' . $pryc_wp_antyspam_settings_unlink_log_file . '">delete log file)</a>'; } 
	
	
}


# URL ON/OFF
function pryc_wp_antyspam_section6_checkbox_field_1_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section6_checkbox_field_1]' <?php if ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_1'] ) ) { checked( $options['pryc_wp_antyspam_section6_checkbox_field_1'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'By default WordPress show URL field at comment form (hide = less SPAM)', 'pryc_wp_antyspam' );

}

# E-mail ON/OFF
function pryc_wp_antyspam_section6_checkbox_field_2_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section6_checkbox_field_2]' <?php if ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_2'] ) ) { checked( $options['pryc_wp_antyspam_section6_checkbox_field_2'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'By default WordPress show e-mail field at comment form (rather no effect)', 'pryc_wp_antyspam' );

}


# E-mail ON/OFF
function pryc_wp_antyspam_section6_checkbox_field_7_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section6_checkbox_field_7]' <?php if ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_7'] ) ) { checked( $options['pryc_wp_antyspam_section6_checkbox_field_7'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'Remove HTML Tag from Author field (recommended)', 'pryc_wp_antyspam' );

}


# Filtr trackabck
function pryc_wp_antyspam_section6_checkbox_field_3_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section6_checkbox_field_3]' <?php if ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_3'] ) ) { checked( $options['pryc_wp_antyspam_section6_checkbox_field_3'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'By default plugin don\'t block trackback', 'pryc_wp_antyspam' );

}

# Filtr pingback
function pryc_wp_antyspam_section6_checkbox_field_4_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section6_checkbox_field_4]' <?php if ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_4'] ) ) { checked( $options['pryc_wp_antyspam_section6_checkbox_field_4'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'By default plugin don\'t block pingback', 'pryc_wp_antyspam' );

}

# XML-RPC
function pryc_wp_antyspam_section6_checkbox_field_6_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section6_checkbox_field_6]' <?php if ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_6'] ) ) { checked( $options['pryc_wp_antyspam_section6_checkbox_field_6'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'By default plugin don\'t block XML-RPC. Disable XML-RPC to prevent some attack.', 'pryc_wp_antyspam' );

}

# Clear plugin data @ uninstall
function pryc_wp_antyspam_section6_checkbox_field_5_render() { 

	$options = get_option( 'pryc_wp_antyspam_settings' );
	?>
	<input type='checkbox' name='pryc_wp_antyspam_settings[pryc_wp_antyspam_section6_checkbox_field_5]' <?php if ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_5'] ) ) { checked( $options['pryc_wp_antyspam_section6_checkbox_field_5'], 1 ); } ?> value='1'>
	
	<?php
	echo __( 'Remove plugin data from database if You uninstall this plugin', 'pryc_wp_antyspam' );

}



# S1 txt
function pryc_wp_antyspam_settings_section_callback1() { 

	echo __( 'Plugin add "HoneyTrap" for comment form to block SPAMbots. Most SPAMbots fills all comment fields (especially marked as required). plugin also use a few other tricks to eliminate SPAM from SPAMbots.', 'pryc_wp_antyspam' );
	
	echo __( '<br /><br />', 'pryc_wp_antyspam' );
	
	echo __( 'Plugin will not stop people writing comments - to block this type of SPAM additionally install eg. Akismet or Antispam Bee.', 'pryc_wp_antyspam' );
	
	echo __( '<br /><br />', 'pryc_wp_antyspam' );
	
	echo __( 'REMEMBER: Don\'t use \' (single quotes), if must - use: \\\'', 'pryc_wp_antyspam' );
	
	
	
}

# S2 txt
function pryc_wp_antyspam_settings_section_callback2() { 
	
	echo __( 'To mask the field its name (default) is generated based on the theme name: ', 'pryc_wp_antyspam' );
	
	echo wp_get_theme() . ' = ' . preg_replace('~[^A-Za-z]~','',md5(wp_get_theme()));
	
	echo __( '<br /><br />', 'pryc_wp_antyspam' );
	
	echo __( 'You can set your own name.', 'pryc_wp_antyspam' );
	
}

# S3 txt
function pryc_wp_antyspam_settings_section_callback3() { 
	
	echo __( 'You can also set your own message, which appears in the case of detecting SPAM/SPAMbots.', 'pryc_wp_antyspam' );
	
}


# S4 txt
function pryc_wp_antyspam_settings_section_callback4() { 

	echo __( 'By default HoneyTrap field is hidden from users (@CSS+JavaScript), and usually there is no need to change that.', 'pryc_wp_antyspam' );
	
}

# S5 txt
function pryc_wp_antyspam_settings_section_callback5() { 

	echo __( 'Set how to log SPAM comment - useful for tests. Disable all when You have heavy trafic/lot of SPAM.', 'pryc_wp_antyspam' );
	
}

# S6 txt
function pryc_wp_antyspam_settings_section_callback6() { 

	echo __( 'Set other plugin/WordPress options:', 'pryc_wp_antyspam' );
	
}

# Save settings BTN
function pryc_wp_antyspam_options_page() { 

	?>
	<form action='options.php' method='post'>
		
		<h2>PRyC WP: AntiSPAM</h2>
		
		<?php
		settings_fields( 'PRyC WP: AntiSPAM (pluginPage)' );
		do_settings_sections( 'PRyC WP: AntiSPAM (pluginPage)' );
		submit_button();
		?>
		
	</form>
	<?php
	
	echo __( 'Remember clear CACHE after change settings/content!', 'pryc_wp_antyspam' );
	
	echo '<br /><br />';
	
	echo '<a href="http://cdn.pryc.eu/add/link/?link=paypal-wp-plugin-pryc-wp-antispam" target="_blank">' . __( 'Like my plugin? Give for a tidbit for my dogs :-)', 'pryc_wp_antyspam' ) . '</a>';	
}



/* Frontend CSS/code */
function pryc_wp_antyspam_frontend() {

	$options = get_option( 'pryc_wp_antyspam_settings' );
	
	if ( ( isset( $options['pryc_wp_antyspam_section2_text_field_1'] ) ) && !empty( $options['pryc_wp_antyspam_section2_text_field_1'] ) ) {	
		$pryc_wp_antyspam_honeypot_field_css = $options['pryc_wp_antyspam_section2_text_field_1']; 
	} else { $pryc_wp_antyspam_honeypot_field_css = preg_replace('~[^A-Za-z]~','',md5(wp_get_theme())); }
	
	
	
	// Clear field value (JS)/fake value
		echo '<script type="text/javascript">
				window.onload = pryc_wp_antyspam_honeypot_field_js;
				function pryc_wp_antyspam_honeypot_field_js()	{			
				document.getElementById("' . $pryc_wp_antyspam_honeypot_field_css . '").value = "";'
				;
			
				# Hide (or not) field 
				if ( ( !isset( $options['pryc_wp_antyspam_section4_checkbox_field_1'] ) ) || ( $options['pryc_wp_antyspam_section4_checkbox_field_1'] != "1" ) ) {
				
					echo 'var HideField = document.getElementsByTagName("p");
					var HideField = document.getElementsByClassName("' . $pryc_wp_antyspam_honeypot_field_css . '");
					HideField[0].style="display:none";';
				} # /Hide (or not) field 
		echo '}
			</script>';
	
}
//add_action('wp_head', 'pryc_wp_antyspam_frontend');
add_action('wp_footer', 'pryc_wp_antyspam_frontend');


/* Admin CSS */
function pryc_wp_antyspam_css_backend() {
?>
	<style type="text/css">
		body.settings_page_pryc_wp_antyspam #wpbody-content h3 { }
	</style>
	<?php
}
add_action('admin_head', 'pryc_wp_antyspam_css_backend');


# Uninstall plugin

register_uninstall_hook( __FILE__, 'pryc_wp_antyspam_uninstall' );
#register_deactivation_hook( __FILE__, 'pryc_wp_antyspam_uninstall' );

function pryc_wp_antyspam_uninstall() {

	$options = get_option( 'pryc_wp_antyspam_settings' );

	if ( ( isset( $options['pryc_wp_antyspam_section6_checkbox_field_5'] ) ) && ( !empty( $options['pryc_wp_antyspam_section6_checkbox_field_5'] ) ) ) {
		
		# Clear at uninstall
		$option_to_delete = 'pryc_wp_antyspam_settings';
		delete_option( $option_to_delete );
	}
	
}



/* END */

