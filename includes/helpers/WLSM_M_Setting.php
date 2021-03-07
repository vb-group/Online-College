<?php
defined( 'ABSPATH' ) || die();

class WLSM_M_Setting {
	public static function get_settings_general( $school_id ) {
		global $wpdb;
		$school_logo                 = NULL;
		$school_signature            = NULL;
		$student_logout_redirect_url = NULL;
		$hide_library                = 1;
		$hide_transport              = 1;


		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "general"', $school_id ) );
		if ( $settings ) {
			$settings                    = unserialize( $settings->setting_value );
			$school_logo                 = isset( $settings['school_logo'] ) ? $settings['school_logo'] : '';
			$school_signature            = isset( $settings['school_signature'] ) ? $settings['school_signature'] : '';
			$student_logout_redirect_url = isset( $settings['student_logout_redirect_url'] ) ? $settings['student_logout_redirect_url'] : '';
			$hide_library                = isset( $settings['hide_library'] ) ? $settings['hide_library'] : '';
			$hide_transport              = isset( $settings['hide_transport'] ) ? $settings['hide_transport'] : '';

		}

		return array(
			'school_logo'                 => $school_logo,
			'school_signature'            => $school_signature,
			'student_logout_redirect_url' => $student_logout_redirect_url,
			'hide_library'                => $hide_library,
			'hide_transport'              => $hide_transport,
		);
	}

	public static function get_settings_logs( $school_id ) {
		global $wpdb;
		$activity_logs     = false;
		$delete_after_days = 20;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "logs"', $school_id ) );
		if ( $settings ) {
			$settings          = unserialize( $settings->setting_value );
			$activity_logs     = isset( $settings['activity_logs'] ) ? (bool) $settings['activity_logs'] : false;
			$delete_after_days = isset( $settings['delete_after_days'] ) ? absint( $settings['delete_after_days'] ) : 20;
		}

		return array(
			'activity_logs'     => $activity_logs,
			'delete_after_days' => $delete_after_days,
		);
	}

	public static function get_settings_email( $school_id ) {
		global $wpdb;
		$carrier = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email"', $school_id ) );
		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$carrier  = isset( $settings['carrier'] ) ? $settings['carrier'] : '';
		}

		return array(
			'carrier' => $carrier,
		);
	}

	public static function get_settings_wp_mail( $school_id ) {
		global $wpdb;
		$from_name  = NULL;
		$from_email = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "wp_mail"', $school_id ) );
		if ( $settings ) {
			$settings  = unserialize( $settings->setting_value );
			$from_name = isset( $settings['from_name'] ) ? $settings['from_name'] : '';
			$from_email = isset( $settings['from_email'] ) ? $settings['from_email'] : '';
		}

		return array(
			'from_name'  => $from_name,
			'from_email' => $from_email,
		);
	}

	public static function get_settings_smtp( $school_id ) {
		global $wpdb;
		$from_name  = NULL;
		$host       = NULL;
		$username   = NULL;
		$password   = NULL;
		$encryption = NULL;
		$port       = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "smtp"', $school_id ) );

		if ( $settings ) {
			$settings   = unserialize( $settings->setting_value );
			$from_name  = isset( $settings['from_name'] ) ? $settings['from_name'] : '';
			$host       = isset( $settings['host'] ) ? $settings['host'] : '';
			$username   = isset( $settings['username'] ) ? $settings['username'] : '';
			$password   = isset( $settings['password'] ) ? $settings['password'] : '';
			$encryption = isset( $settings['encryption'] ) ? $settings['encryption'] : '';
			$port       = isset( $settings['port'] ) ? $settings['port'] : '';
		}

		return array(
			'from_name'  => $from_name,
			'host'       => $host,
			'username'   => $username,
			'password'   => $password,
			'encryption' => $encryption,
			'port'       => $port,
		);
	}

	public static function get_settings_email_student_admission( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_admission"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$subject  = isset( $settings['subject'] ) ? $settings['subject'] : '';
			$body     = isset( $settings['body'] ) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_student_registration_to_student( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_registration_to_student"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$subject  = isset( $settings['subject'] ) ? $settings['subject'] : '';
			$body     = isset( $settings['body'] ) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_student_registration_to_admin( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_registration_to_admin"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$subject  = isset( $settings['subject'] ) ? $settings['subject'] : '';
			$body     = isset( $settings['body'] ) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_invoice_generated( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_invoice_generated"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$subject  = isset( $settings['subject'] ) ? $settings['subject'] : '';
			$body     = isset( $settings['body'] ) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_online_fee_submission( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_online_fee_submission"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$subject  = isset( $settings['subject'] ) ? $settings['subject'] : '';
			$body     = isset( $settings['body'] ) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_offline_fee_submission( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_offline_fee_submission"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$subject  = isset( $settings['subject'] ) ? $settings['subject'] : '';
			$body     = isset( $settings['body'] ) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_inquiry_received_to_inquisitor( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_inquiry_received_to_inquisitor"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$subject  = isset( $settings['subject'] ) ? $settings['subject'] : '';
			$body     = isset( $settings['body'] ) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_email_inquiry_received_to_admin( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$subject = NULL;
		$body    = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_inquiry_received_to_admin"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$subject  = isset( $settings['subject'] ) ? $settings['subject'] : '';
			$body     = isset( $settings['body'] ) ? $settings['body'] : '';
		}

		return array(
			'enable'  => $enable,
			'subject' => $subject,
			'body'    => $body,
		);
	}

	public static function get_settings_sms( $school_id ) {
		global $wpdb;
		$carrier = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms"', $school_id ) );
		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$carrier  = isset( $settings['carrier'] ) ? $settings['carrier'] : '';
		}

		return array(
			'carrier' => $carrier,
		);
	}

	public static function get_settings_smsstriker( $school_id ) {
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "smsstriker"', $school_id ) );
		if ( $settings ) {
			$settings  = unserialize( $settings->setting_value );
			$username  = isset( $settings['username'] ) ? $settings['username'] : '';
			$password  = isset( $settings['password'] ) ? $settings['password'] : '';
			$sender_id = isset( $settings['sender_id'] ) ? $settings['sender_id'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
		);
	}

	public static function get_settings_msgclub( $school_id ) {
		global $wpdb;

		$auth_key         = NULL;
		$sender_id        = NULL;
		$route_id         = 1;
		$sms_content_type = 'Unicode';

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "msgclub"', $school_id ) );
		if ( $settings ) {
			$settings         = unserialize( $settings->setting_value );
			$auth_key         = isset( $settings['auth_key'] ) ? $settings['auth_key'] : '';
			$sender_id        = isset( $settings['sender_id'] ) ? $settings['sender_id'] : '';
			$route_id         = isset( $settings['route_id'] ) ? $settings['route_id'] : '';
			$sms_content_type = isset( $settings['sms_content_type'] ) ? $settings['sms_content_type'] : '';
		}

		return array(
			'auth_key'         => $auth_key,
			'sender_id'        => $sender_id,
			'route_id'         => $route_id,
			'sms_content_type' => $sms_content_type,
		);
	}

	public static function get_settings_pointsms( $school_id ) {
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;
		$channel   = NULL;
		$route     = NULL;
		$peid      = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pointsms"', $school_id ) );
		if ( $settings ) {
			$settings  = unserialize( $settings->setting_value );
			$username  = isset( $settings['username'] ) ? $settings['username'] : '';
			$password  = isset( $settings['password'] ) ? $settings['password'] : '';
			$sender_id = isset( $settings['sender_id'] ) ? $settings['sender_id'] : '';
			$channel   = isset( $settings['channel'] ) ? $settings['channel'] : '';
			$route     = isset( $settings['route'] ) ? $settings['route'] : '';
			$peid      = isset( $settings['peid'] ) ? $settings['peid'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,
			'channel'   => $channel,
			'route'     => $route,
			'peid'      => $peid,
		);
	}

	public static function get_settings_vinuthan($school_id)
	{
		global $wpdb;

		$username = NULL;
		$sender_id = NULL;
		$channel = NULL;
		$route = NULL;

		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "vinuthan"', $school_id));
		if ($settings) {
			$settings = unserialize($settings->setting_value);
			$username = isset($settings['username']) ? $settings['username'] : '';
			$password = isset($settings['password']) ? $settings['password'] : '';
			$sender_id = isset($settings['sender_id']) ? $settings['sender_id'] : '';
			$channel = isset($settings['channel']) ? $settings['channel'] : '';
			$route = isset($settings['route']) ? $settings['route'] : '';
		}

		return array(
			'username' => $username,
			'sender_id' => $sender_id,
			'channel' => $channel,
			'route' => $route,
		);
	}

	public static function get_settings_pob( $school_id ) {
		global $wpdb;

		$username  = NULL;
		$password  = NULL;
		$sender_id = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pob"', $school_id ) );
		if ( $settings ) {
			$settings  = unserialize( $settings->setting_value );
			$username  = isset( $settings['username'] ) ? $settings['username'] : '';
			$password  = isset( $settings['password'] ) ? $settings['password'] : '';
			$sender_id = isset( $settings['sender_id'] ) ? $settings['sender_id'] : '';
		}

		return array(
			'username'  => $username,
			'password'  => $password,
			'sender_id' => $sender_id,

		);
	}

	public static function get_settings_nexmo( $school_id ) {
		global $wpdb;

		$api_key    = NULL;
		$api_secret = NULL;
		$from       = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "nexmo"', $school_id ) );
		if ( $settings ) {
			$settings   = unserialize( $settings->setting_value );
			$api_key    = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
			$api_secret = isset( $settings['api_secret'] ) ? $settings['api_secret'] : '';
			$from       = isset( $settings['from'] ) ? $settings['from'] : '';
		}

		return array(
			'api_key'    => $api_key,
			'api_secret' => $api_secret,
			'from'       => $from,
		);
	}

	public static function get_settings_twilio( $school_id ) {
		global $wpdb;

		$sid   = NULL;
		$token = NULL;
		$from  = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "twilio"', $school_id ) );
		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$sid      = isset( $settings['sid'] ) ? $settings['sid'] : '';
			$token    = isset( $settings['token'] ) ? $settings['token'] : '';
			$from     = isset( $settings['from'] ) ? $settings['from'] : '';
		}

		return array(
			'sid'   => $sid,
			'token' => $token,
			'from'  => $from,
		);
	}

	public static function get_settings_msg91( $school_id ) {
		global $wpdb;

		$authkey = NULL;
		$route   = 4;
		$sender  = NULL;
		$country = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "msg91"', $school_id ) );
		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$authkey  = isset( $settings['authkey'] ) ? $settings['authkey'] : '';
			$route    = isset( $settings['route'] ) ? $settings['route'] : '';
			$sender   = isset( $settings['sender'] ) ? $settings['sender'] : '';
			$country  = isset( $settings['country'] ) ? $settings['country'] : '';
		}

		return array(
			'authkey' => $authkey,
			'route'   => $route,
			'sender'  => $sender,
			'country' => $country,
		);
	}

	public static function get_settings_textlocal( $school_id ) {
		global $wpdb;

		$api_key = NULL;
		$sender  = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "textlocal"', $school_id ) );
		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
			$sender   = isset( $settings['sender'] ) ? $settings['sender'] : '';
		}

		return array(
			'api_key' => $api_key,
			'sender'  => $sender,
		);
	}

	public static function get_settings_ebulksms( $school_id ) {
		global $wpdb;

		$username = NULL;
		$api_key  = NULL;
		$sender   = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "ebulksms"', $school_id ) );
		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$username = isset( $settings['username'] ) ? $settings['username'] : '';
			$api_key  = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
			$sender   = isset( $settings['sender'] ) ? $settings['sender'] : '';
		}

		return array(
			'username' => $username,
			'api_key'  => $api_key,
			'sender'   => $sender,
		);
	}

	public static function get_settings_charts( $school_id ) {
		global $wpdb;
		$chart_types  = array();
		$chart_enable = array();

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "charts"', $school_id ) );
		if ( $settings ) {
			$settings     = unserialize( $settings->setting_value );
			$chart_types  = isset( $settings['chart_types'] ) ? $settings['chart_types'] : array();
			$chart_enable = isset( $settings['chart_enable'] ) ? $settings['chart_enable'] : array();
		}

		if ( ! is_array( $chart_types ) ) {
			$chart_types = array();
		}

		if ( ! is_array( $chart_enable ) ) {
			$chart_enable = array();
		}

		foreach ( WLSM_Helper::charts() as $key => $value ) {
			if ( ! isset( $chart_types[ $key ] ) || ( ! in_array( $chart_types[ $key ], WLSM_Helper::chart_types() ) ) ) {
				$chart_types[ $key ] = WLSM_Helper::default_chart_types()[ $key ];
			}

			if ( ! isset( $chart_enable[ $key ] ) ) {
				$chart_enable[ $key ] = false;
			} else {
				$chart_enable[ $key ] = (bool) $chart_enable[ $key ];
			}
		}

		return array(
			'chart_types'  => $chart_types,
			'chart_enable' => $chart_enable,
		);
	}

	public static function get_settings_zoom( $school_id ) {
		global $wpdb;
		$api_key    = NULL;
		$api_secret = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "zoom"', $school_id ) );
		if ( $settings ) {
			$settings   = unserialize( $settings->setting_value );
			$api_key    = isset( $settings['api_key'] ) ? $settings['api_key'] : '';
			$api_secret = isset( $settings['api_secret'] ) ? $settings['api_secret'] : '';
		}

		return array(
			'api_key'    => $api_key,
			'api_secret' => $api_secret,
		);
	}

	public static function get_settings_inquiry( $school_id ) {
		global $wpdb;

		$form_title      = esc_html__( 'Admission Inquiry', 'school-management' );
		$phone_required  = true;
		$email_required  = false;
		$admin_email     = '';
		$admin_phone     = '';
		$success_message = '';

		$default_success_message = esc_html__( 'Your inquiry has been submitted successfully.', 'school-management' );

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "inquiry"', $school_id ) );
		if ( $settings ) {
			$settings        = unserialize( $settings->setting_value );
			$form_title      = isset( $settings['form_title'] ) ? $settings['form_title'] : '';
			$phone_required  = isset( $settings['phone_required'] ) ? (bool) $settings['phone_required'] : false;
			$email_required  = isset( $settings['email_required'] ) ? (bool) $settings['email_required'] : false;
			$admin_email     = isset( $settings['admin_email'] ) ? $settings['admin_email'] : '';
			$admin_phone     = isset( $settings['admin_phone'] ) ? $settings['admin_phone'] : '';
			$success_message = isset( $settings['success_message'] ) ? $settings['success_message'] : '';
		}

		if ( empty( $success_message ) ) {
			$success_message = $default_success_message;
		}

		return array(
			'form_title'      => $form_title,
			'phone_required'  => $phone_required,
			'email_required'  => $email_required,
			'admin_email'     => $admin_email,
			'admin_phone'     => $admin_phone,
			'success_message' => $success_message
		);
	}

	public static function get_settings_registration( $school_id ) {
		global $wpdb;

		$form_title            = esc_html__( 'Online Registration', 'school-management' );
		$login_user            = 0;
		$redirect_url          = '';
		$create_invoice        = 1;
		$auto_admission_number = 0; // Auto generate admission number when registering student from back-end.
		$admin_email           = '';
		$admin_phone           = '';
		$success_message       = '';

			$dob           = 1;
			$religion      = 1;
			$caste         = 1;
			$blood_group   = 1;
			$phone         = 1;
			$city          = 1;
			$state         = 1;
			$country       = 1;
			$transport     = 1;
			$parent_detail = 1;
			$parent_login  = 1;
			$id_number     = 1;

		$default_success_message = esc_html__( 'Your registration has been submitted. Please check your email.', 'school-management' );

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "registration"', $school_id ) );
		if ( $settings ) {
			$settings              = unserialize( $settings->setting_value );
			$form_title            = isset( $settings['form_title'] ) ? $settings['form_title'] : '';
			$login_user            = isset( $settings['login_user'] ) ? $settings['login_user'] : '';
			$redirect_url          = isset( $settings['redirect_url'] ) ? $settings['redirect_url'] : '';
			$create_invoice        = isset( $settings['create_invoice'] ) ? $settings['create_invoice'] : '';
			$auto_admission_number = isset( $settings['auto_admission_number'] ) ? $settings['auto_admission_number'] : '';
			$admin_email           = isset( $settings['admin_email'] ) ? $settings['admin_email'] : '';
			$admin_phone           = isset( $settings['admin_phone'] ) ? $settings['admin_phone'] : '';
			$success_message       = isset( $settings['success_message'] ) ? $settings['success_message'] : '';

			$dob           = isset( $settings['dob'] ) ? $settings['dob'] : '';
			$religion      = isset( $settings['religion'] ) ? $settings['religion'] : '';
			$caste         = isset( $settings['caste'] ) ? $settings['caste'] : '';
			$blood_group   = isset( $settings['blood_group'] ) ? $settings['blood_group'] : '';
			$phone         = isset( $settings['phone'] ) ? $settings['phone'] : '';
			$city          = isset( $settings['city'] ) ? $settings['city'] : '';
			$state         = isset( $settings['state'] ) ? $settings['state'] : '';
			$country       = isset( $settings['country'] ) ? $settings['country'] : '';
			$transport     = isset( $settings['transport'] ) ? $settings['transport'] : '';
			$parent_detail = isset( $settings['parent_detail'] ) ? $settings['parent_detail'] : '';
			$parent_login  = isset( $settings['parent_login'] ) ? $settings['parent_login'] : '';
			$id_number     = isset( $settings['id_number'] ) ? $settings['id_number'] : '';

		}

		if ( empty( $success_message ) ) {
			$success_message = $default_success_message;
		}

		return array(
			'form_title'            => $form_title,
			'login_user'            => (bool) $login_user,
			'redirect_url'          => $redirect_url,
			'create_invoice'        => (bool) $create_invoice,
			'auto_admission_number' => (bool) $auto_admission_number,
			'admin_email'           => $admin_email,
			'admin_phone'           => $admin_phone,
			'success_message'       => $success_message,
			'dob'           => (bool)$dob,
			'religion'      => (bool)$religion,
			'caste'         => (bool)$caste,
			'blood_group'   => (bool)$blood_group,
			'phone'         => (bool)$phone,
			'city'          => (bool)$city,
			'state'         => (bool)$state,
			'country'       => (bool)$country,
			'transport'     => (bool)$transport,
			'parent_detail' => (bool)$parent_detail,
			'id_number'     => (bool)$id_number,
			'parent_login'  => (bool)$parent_login

		);
	}

	public static function get_settings_dashboard($school_id) {
		global $wpdb;

		$school_invoice          = 1;
		$school_payment_history  = 1;
		$school_study_material   = 1;
		$school_home_work        = 1;
		$school_noticeboard      = 1;
		$school_events           = 1;
		$school_class_time_table = 1;
		$school_live_classes     = 1;
		$school_books_issues     = 1;
		$school_exam_time_table  = 1;
		$school_admit_card       = 1;
		$school_exam_result      = 1;
		$school_certificate      = 1;
		$school_attendance       = 1;
		$school_leave_request    = 1;


		$school_parent_id_card          = 1;
		$school_parent_fee_invoice      = 1;
		$school_parent_payement_history = 1;
		$school_parent_noticeboard      = 1;
		$school_parent_class_time_table = 1;
		$school_parent_exam_results     = 1;
		$school_parent_attendance       = 1;



		$settings = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "dashboard"', $school_id));
		if ($settings) {
			$settings                = unserialize($settings->setting_value);
			$school_invoice          = isset($settings['school_invoice']) ? $settings['school_invoice'] : '';
			$school_payment_history  = isset($settings['school_payment_history']) ? $settings['school_payment_history'] : '';
			$school_study_material   = isset($settings['school_study_material']) ? $settings['school_study_material'] : '';
			$school_home_work        = isset($settings['school_home_work']) ? $settings['school_home_work'] : '';
			$school_noticeboard      = isset($settings['school_noticeboard']) ? $settings['school_noticeboard'] : '';
			$school_events           = isset($settings['school_events']) ? $settings['school_events'] : '';
			$school_class_time_table = isset($settings['school_class_time_table']) ? $settings['school_class_time_table'] : '';
			$school_live_classes     = isset($settings['school_live_classes']) ? $settings['school_live_classes'] : '';
			$school_books_issues     = isset($settings['school_books_issues']) ? $settings['school_books_issues'] : '';
			$school_exam_time_table  = isset($settings['school_exam_time_table']) ? $settings['school_exam_time_table'] : '';
			$school_admit_card       = isset($settings['school_admit_card']) ? $settings['school_admit_card'] : '';
			$school_exam_result      = isset($settings['school_exam_result']) ? $settings['school_exam_result'] : '';
			$school_certificate      = isset($settings['school_certificate']) ? $settings['school_certificate'] : '';
			$school_attendance       = isset($settings['school_attendance']) ? $settings['school_attendance'] : '';
			$school_leave_request    = isset($settings['school_leave_request']) ? $settings['school_leave_request'] : '';

			$school_parent_id_card          = isset($settings['parent_id_card']) ? $settings['parent_id_card'] : '';
			$school_parent_fee_invoice      = isset($settings['parent_fee_invoice']) ? $settings['parent_fee_invoice'] : '';
			$school_parent_payement_history = isset($settings['parent_payement_history']) ? $settings['parent_payement_history'] : '';
			$school_parent_noticeboard      = isset($settings['parent_noticeboard']) ? $settings['parent_noticeboard'] : '';
			$school_parent_class_time_table = isset($settings['parent_class_time_table']) ? $settings['parent_class_time_table'] : '';
			$school_parent_exam_results     = isset($settings['parent_exam_results']) ? $settings['parent_exam_results'] : '';
			$school_parent_attendance       = isset($settings['parent_attendance']) ? $settings['parent_attendance'] : '';

		}

		return array(
			'school_invoice'          => (bool)$school_invoice,
			'school_payment_history'  => (bool)$school_payment_history,
			'school_study_material'   => (bool)$school_study_material,
			'school_home_work'        => (bool)$school_home_work,
			'school_noticeboard'      => (bool)$school_noticeboard,
			'school_events'           => (bool)$school_events,
			'school_class_time_table' => (bool)$school_class_time_table,
			'school_live_classes'     => (bool)$school_live_classes,
			'school_books_issues'     => (bool)$school_books_issues,
			'school_exam_time_table'  => (bool)$school_exam_time_table,
			'school_admit_card'       => (bool)$school_admit_card,
			'school_exam_result'      => (bool)$school_exam_result,
			'school_certificate'      => (bool)$school_certificate,
			'school_attendance'       => (bool)$school_attendance,
			'school_leave_request'    => (bool)$school_leave_request,

			'parent_id_card'          => (bool)$school_parent_id_card,
			'parent_fee_invoice'      => (bool)$school_parent_fee_invoice,
			'parent_payement_history' => (bool)$school_parent_payement_history,
			'parent_noticeboard'      => (bool)$school_parent_noticeboard,
			'parent_class_time_table' => (bool)$school_parent_class_time_table,
			'parent_exam_results'     => (bool)$school_parent_exam_results,
			'parent_attendance'       => (bool)$school_parent_attendance,


		);
	}



	public static function get_settings_sms_student_admission( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_admission"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_student_registration_to_student( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_registration_to_student"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_student_registration_to_admin( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_registration_to_admin"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_invoice_generated( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_invoice_generated"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_online_fee_submission( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_online_fee_submission"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_offline_fee_submission( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_offline_fee_submission"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_student_admission_to_parent( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_admission_to_parent"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_invoice_generated_to_parent( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_invoice_generated_to_parent"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_online_fee_submission_to_parent( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_online_fee_submission_to_parent"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_offline_fee_submission_to_parent( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_offline_fee_submission_to_parent"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_absent_student( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_absent_student"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_inquiry_received_to_inquisitor( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_inquiry_received_to_inquisitor"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_sms_inquiry_received_to_admin( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_inquiry_received_to_admin"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'message' => $message,
		);
	}

	public static function get_settings_razorpay( $school_id ) {
		global $wpdb;

		$enable          = 0;
		$razorpay_key    = NULL;
		$razorpay_secret = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "razorpay"', $school_id ) );

		if ( $settings ) {
			$settings        = unserialize( $settings->setting_value );
			$enable          = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$razorpay_key    = isset( $settings['razorpay_key'] ) ? $settings['razorpay_key'] : '';
			$razorpay_secret = isset( $settings['razorpay_secret'] ) ? $settings['razorpay_secret'] : '';
		}

		return array(
			'enable'          => $enable,
			'razorpay_key'    => $razorpay_key,
			'razorpay_secret' => $razorpay_secret,
		);
	}

	public static function get_settings_paytm( $school_id ) {
		global $wpdb;

		$enable           = 0;
		$merchant_id      = NULL;
		$merchant_key     = NULL;
		$industry_type_id = 'Retail';
		$website          = 'WEBSTAGING';
		$mode             = 'staging'; // or "production".

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "paytm"', $school_id ) );

		if ( $settings ) {
			$settings         = unserialize( $settings->setting_value );
			$enable           = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$merchant_id      = isset( $settings['merchant_id'] ) ? $settings['merchant_id'] : '';
			$merchant_key     = isset( $settings['merchant_key'] ) ? $settings['merchant_key'] : '';
			$industry_type_id = isset( $settings['industry_type_id'] ) ? $settings['industry_type_id'] : '';
			$website          = isset( $settings['website'] ) ? $settings['website'] : '';
			$mode             = isset( $settings['mode'] ) ? $settings['mode'] : '';
		}

		return array(
			'enable'           => $enable,
			'merchant_id'      => $merchant_id,
			'merchant_key'     => $merchant_key,
			'industry_type_id' => $industry_type_id,
			'website'          => $website,
			'mode'             => $mode,
		);
	}

	public static function get_settings_stripe( $school_id ) {
		global $wpdb;

		$enable          = 0;
		$publishable_key = NULL;
		$secret_key      = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "stripe"', $school_id ) );

		if ( $settings ) {
			$settings        = unserialize( $settings->setting_value );
			$enable          = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$publishable_key = isset( $settings['publishable_key'] ) ? $settings['publishable_key'] : '';
			$secret_key      = isset( $settings['secret_key'] ) ? $settings['secret_key'] : '';
		}

		return array(
			'enable'          => $enable,
			'publishable_key' => $publishable_key,
			'secret_key'      => $secret_key,
		);
	}

	public static function get_settings_paypal( $school_id ) {
		global $wpdb;

		$enable         = 0;
		$business_email = '';
		$mode           = 'sandbox';

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "paypal"', $school_id ) );

		if ( $settings ) {
			$settings       = unserialize( $settings->setting_value );
			$enable         = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$business_email = isset( $settings['business_email'] ) ? $settings['business_email'] : '';
			$mode           = isset( $settings['mode'] ) ? $settings['mode'] : '';
		}

		if ( 'live' === $mode ) {
			$payment_url = 'https://www.paypal.com/cgi-bin/webscr';
		} else {
			$payment_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}

		return array(
			'enable'         => $enable,
			'business_email' => $business_email,
			'mode'           => $mode,
			'payment_url'    => $payment_url,
			'notify_url'     => admin_url( 'admin-ajax.php' ) . '?action=wlsm-p-pay-with-paypal',
		);
	}

	public static function get_settings_pesapal( $school_id ) {
		global $wpdb;

		$enable          = 0;
		$consumer_key    = '';
		$consumer_secret = '';
		$mode            = 'sandbox';

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pesapal"', $school_id ) );

		if ( $settings ) {
			$settings        = unserialize( $settings->setting_value );
			$enable          = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$consumer_key    = isset( $settings['consumer_key'] ) ? $settings['consumer_key'] : '';
			$consumer_secret = isset( $settings['consumer_secret'] ) ? $settings['consumer_secret'] : '';
			$mode            = isset( $settings['mode'] ) ? $settings['mode'] : '';
		}

		if ( 'live' === $mode ) {
			$payment_url = 'https://www.pesapal.com/api/PostPesapalDirectOrderV4';
			$status_url  = 'https://www.pesapal.com/api/querypaymentstatus';
		} else {
			$payment_url = 'https://demo.pesapal.com/api/PostPesapalDirectOrderV4';
			$status_url  = 'https://demo.pesapal.com/api/querypaymentstatus';
		}

		return array(
			'enable'          => $enable,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'mode'            => $mode,
			'payment_url'     => $payment_url,
			'status_url'      => $status_url,
			'notify_url'      => admin_url( 'admin-ajax.php' ) . '?action=wlsm-p-pay-with-pesapal',
		);
	}

	public static function get_settings_paystack( $school_id ) {
		global $wpdb;

		$enable              = 0;
		$paystack_public_key = NULL;
		$paystack_secret_key = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "paystack"', $school_id ) );

		if ( $settings ) {
			$settings            = unserialize( $settings->setting_value );
			$enable              = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$paystack_public_key = isset( $settings['paystack_public_key'] ) ? $settings['paystack_public_key'] : '';
			$paystack_secret_key = isset( $settings['paystack_secret_key'] ) ? $settings['paystack_secret_key'] : '';
		}

		return array(
			'enable'              => $enable,
			'paystack_public_key' => $paystack_public_key,
			'paystack_secret_key' => $paystack_secret_key,
		);
	}

	public static function get_settings_bank_transfer( $school_id ) {
		global $wpdb;

		$enable  = 0;
		$branch  = NULL;
		$account = NULL;
		$name    = NULL;
		$message = NULL;

		$settings = $wpdb->get_row( $wpdb->prepare( 'SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "bank_transfer"', $school_id ) );

		if ( $settings ) {
			$settings = unserialize( $settings->setting_value );
			$enable   = isset( $settings['enable'] ) ? (bool) $settings['enable'] : 0;
			$branch   = isset( $settings['branch'] ) ? $settings['branch'] : '';
			$account  = isset( $settings['account'] ) ? $settings['account'] : '';
			$name     = isset( $settings['name'] ) ? $settings['name'] : '';
			$message  = isset( $settings['message'] ) ? $settings['message'] : '';
		}

		return array(
			'enable'  => $enable,
			'branch'  => $branch,
			'account' => $account,
			'name'    => $name,
			'message' => $message,
		);
	}

	public static function get_dash($invoices){
		foreach ($invoices as $row) {
			$display = $row->status;
			return $display;
		}

	}
}
