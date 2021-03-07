<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Setting.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Config.php';

class WLSM_SMS {
	public static function sms_carriers() {
		return array(
			'smsstriker' => esc_html__( 'SMS Striker', 'school-management' ),
			'msgclub'    => esc_html__( 'Infigo Msg', 'school-management' ),
			'pointsms'   => esc_html__( 'Infigo Point', 'school-management' ),
			'nexmo'      => esc_html__( 'Nexmo', 'school-management' ),
			'twilio'     => esc_html__( 'Twilio', 'school-management' ),
			'msg91'      => esc_html__( 'Msg91', 'school-management' ),
			'textlocal'  => esc_html__( 'Textlocal', 'school-management' ),
			'ebulksms'   => esc_html__( 'EBulkSMS', 'school-management' ),
			'pob'        => esc_html__( 'Pob Talk', 'school-management' ),
			'vinuthan'   => esc_html__('vinuthan', 'school-management' ),
		);
	}

	public static function send_sms( $school_id, $to, $message, $sms_for = '', $placeholders = array() ) {
		if ( ! empty( $sms_for ) && count( $placeholders ) ) {
			if ( 'student_admission' === $sms_for ) {
				$available_placeholders = array_keys( self::student_admission_placeholders() );
			} elseif ( 'invoice_generated' === $sms_for ) {
				$available_placeholders = array_keys( self::invoice_generated_placeholders() );
			} elseif ( 'online_fee_submission' === $sms_for ) {
				$available_placeholders = array_keys( self::online_fee_submission_placeholders() );
			} elseif ( 'offline_fee_submission' === $sms_for ) {
				$available_placeholders = array_keys( self::offline_fee_submission_placeholders() );
			} elseif ( 'student_admission_to_parent' === $sms_for ) {
				$available_placeholders = array_keys( self::student_admission_to_parent_placeholders() );
			} elseif ( 'invoice_generated_to_parent' === $sms_for ) {
				$available_placeholders = array_keys( self::invoice_generated_to_parent_placeholders() );
			} elseif ( 'online_fee_submission_to_parent' === $sms_for ) {
				$available_placeholders = array_keys( self::online_fee_submission_to_parent_placeholders() );
			} elseif ( 'offline_fee_submission_to_parent' === $sms_for ) {
				$available_placeholders = array_keys( self::offline_fee_submission_to_parent_placeholders() );
			} elseif ( 'absent_student' === $sms_for ) {
				$available_placeholders = array_keys( self::absent_student_placeholders() );
			} elseif ( 'custom_message' === $sms_for ) {
				$available_placeholders = array_keys( self::custom_message_placeholders() );
			} elseif ( 'inquiry_received_to_inquisitor' === $sms_for ) {
				$available_placeholders = array_keys( self::inquiry_received_to_inquisitor_placeholders() );
			} elseif ( 'inquiry_received_to_admin' === $sms_for ) {
				$available_placeholders = array_keys( self::inquiry_received_to_admin_placeholders() );
			} elseif ( 'student_registration_to_student' === $sms_for ) {
				$available_placeholders = array_keys( self::student_registration_to_student_placeholders() );
			} elseif ( 'student_registration_to_admin' === $sms_for ) {
				$available_placeholders = array_keys( self::student_registration_to_admin_placeholders() );
			}

			if ( isset( $available_placeholders ) ) {
				foreach ( $placeholders as $key => $value ) {
					if ( in_array( $key, $available_placeholders ) ) {
						$message = str_replace( $key, $value, $message );
					}
				}
			}
		}

		$settings_sms = WLSM_M_Setting::get_settings_sms( $school_id );
		$sms_carrier  = $settings_sms['carrier'];

		if ( 'smsstriker' === $sms_carrier ) {
			return self::smsstriker( $school_id, $message, $to );
		} elseif ( 'msgclub' === $sms_carrier ) {
			return self::msgclub( $school_id, $message, $to );
		} elseif ( 'pointsms' === $sms_carrier ) {
			return self::pointsms( $school_id, $message, $to );
		} elseif ( 'nexmo' === $sms_carrier ) {
			return self::nexmo( $school_id, $message, $to );
		} elseif ( 'twilio' === $sms_carrier ) {
			return self::twilio( $school_id, $message, $to );
		} elseif ( 'msg91' === $sms_carrier ) {
			return self::msg91( $school_id, $message, $to );
		} elseif ( 'textlocal' === $sms_carrier ) {
			return self::textlocal( $school_id, $message, $to );
		} elseif ( 'ebulksms' === $sms_carrier ) {
			return self::ebulksms( $school_id, $message, $to );
		}elseif ( 'pob' === $sms_carrier ) {
			return self::pob( $school_id, $message, $to );
		}elseif ( 'vinuthan' === $sms_carrier ) {
			return self::vinuthan( $school_id, $message, $to );
		}
	}

	public static function smsstriker( $school_id, $message, $numbers ) {
		try {
			$smsstriker = WLSM_M_Setting::get_settings_smsstriker( $school_id );
			$username   = $smsstriker['username'];
			$password   = $smsstriker['password'];
			$sender_id  = $smsstriker['sender_id'];

			if ( is_array( $numbers ) ) {
				foreach ( $numbers as $key => $number ) {
					if ( ( 12 == strlen( $number ) ) && ( '91' == substr( $number, 0, 2 ) ) ) {
						$numbers[ $key ] = substr( $number, 2, 10 );
					} elseif ( ( 13 == strlen( $number ) ) && ( '+91' == substr( $number, 0, 3 ) ) ) {
						$numbers[ $key ] = substr( $number, 3, 10 );
					} elseif ( ( 11 == strlen( $number ) ) && ( '0' == substr( $number, 0, 1 ) ) ) {
						$numbers[ $key ] = substr( $number, 3, 10 );
					}
				}
				$number = implode( ', ', $numbers );
			} else {
				if ( ( 12 == strlen( $numbers ) ) && ( '91' == substr( $numbers, 0, 2 ) ) ) {
					$number = substr( $numbers, 2, 10 );
				} elseif ( ( 13 == strlen( $numbers ) ) && ( '+91' == substr( $numbers, 0, 3 ) ) ) {
					$number = substr( $numbers, 3, 10 );
				} elseif ( ( 11 == strlen( $numbers ) ) && ( '0' == substr( $numbers, 0, 1 ) ) ) {
					$number = substr( $numbers, 1, 10 );
				} else {
					$number = $numbers;
				}
			}

			if ( ! ( $username && $password && $sender_id ) ) {
				return false;
			}

			$data = array(
				"username"  => $username,
				"password"  => $password,
				"to"        => $number,
				"from"      => $sender_id,
				"msg"       => $message,
				"type"      => 1,
				"dnd_check" => 0,
			);

			$response = wp_remote_post( 'https://www.smsstriker.com/API/sms.php', $data );
			$result   = wp_remote_retrieve_body( $response );

			if ( $result ) {
				return true;
			}
		} catch ( Exception $e ) {
		}

		return false;
	}

	public static function msgclub( $school_id, $message, $numbers ) {
		try {
			$msgclub          = WLSM_M_Setting::get_settings_msgclub( $school_id );
			$auth_key         = $msgclub['auth_key'];
			$sender_id        = $msgclub['sender_id'];
			$route_id         = $msgclub['route_id'];
			$sms_content_type = $msgclub['sms_content_type'];

			if ( is_array( $numbers ) ) {
				$number = implode( ', ', $numbers );
			} else {
				$number = $numbers;
			}

			if ( ! ( $auth_key && $sender_id ) ) {
				return false;
			}

			$url = add_query_arg(
				array(
					'AUTH_KEY'       => $auth_key,
					'message'        => urlencode( $message ),
					'senderId'       => $sender_id,
					'routeId'        => $route_id,
					'mobileNos'      => $number,
					'smsContentType' => $sms_content_type,
				),
				'http://167.114.117.218/rest/services/sendSMS/sendGroupSms'
			);

			$response = wp_remote_get( $url );
			$result   = wp_remote_retrieve_body( $response );

			if ( $result ) {
				return true;
			}
		} catch ( Exception $e ) {
		}

		return false;
	}

	public static function pointsms( $school_id, $message, $numbers ) {
		try {
			$pointsms  = WLSM_M_Setting::get_settings_pointsms( $school_id );
			$username  = $pointsms['username'];
			$password  = $pointsms['password'];
			$sender_id = $pointsms['sender_id'];
			$channel   = $pointsms['channel'];
			$route     = $pointsms['route'];
			$peid      = $pointsms['peid'];

			if ( is_array( $numbers ) ) {
				foreach ( $numbers as $key => $number ) {
					if ( ( 12 == strlen( $number ) ) && ( '91' == substr( $number, 0, 2 ) ) ) {
						$numbers[ $key ] = substr( $number, 2, 10 );
					} elseif ( ( 13 == strlen( $number ) ) && ( '+91' == substr( $number, 0, 3 ) ) ) {
						$numbers[ $key ] = substr( $number, 3, 10 );
					} elseif ( ( 11 == strlen( $number ) ) && ( '0' == substr( $number, 0, 1 ) ) ) {
						$numbers[ $key ] = substr( $number, 3, 10 );
					}
				}
				$number = implode( ',', $numbers );
			} else {
				if ( ( 12 == strlen( $numbers ) ) && ( '91' == substr( $numbers, 0, 2 ) ) ) {
					$number = substr( $numbers, 2, 10 );
				} elseif ( ( 13 == strlen( $numbers ) ) && ( '+91' == substr( $numbers, 0, 3 ) ) ) {
					$number = substr( $numbers, 3, 10 );
				} elseif ( ( 11 == strlen( $numbers ) ) && ( '0' == substr( $numbers, 0, 1 ) ) ) {
					$number = substr( $numbers, 1, 10 );
				} else {
					$number = $numbers;
				}
			}

			if ( ! ( $username && $password && $sender_id ) ) {
				return false;
			}

			$url = add_query_arg(
				array(
					"user"     => $username,
					"password" => $password,
					"number"   => $number,
					"senderid" => $sender_id,
					"channel"  => $channel,
					"DCS"      => 0,
					"flashsms" => 0,
					"text"     => $message,
					"route"    => $route,
					"peid"     => $peid,
				),
				'http://45.113.189.74/api/mt/SendSMS'
			);

			$response = wp_remote_get( $url );
			$result   = wp_remote_retrieve_body( $response );
			if ( $result ) {
				return true;
			}
		} catch ( Exception $e ) {
		}

		return false;
	}

	public static function vinuthan($school_id, $message, $numbers)
	{
		try {
			$vinuthan = WLSM_M_Setting::get_settings_vinuthan($school_id);
			$username = $vinuthan['username'];
			$password = $vinuthan['password'];
			$sender_id = $vinuthan['sender_id'];
			$channel = $vinuthan['channel'];
			$route = $vinuthan['route'];

			if (is_array($numbers)) {
				foreach ($numbers as $key => $number) {
					if ((12 == strlen($number)) && ('91' == substr($number, 0, 2))) {
						$numbers[$key] = substr($number, 2, 10);
					} elseif ((13 == strlen($number)) && ('+91' == substr($number, 0, 3))) {
						$numbers[$key] = substr($number, 3, 10);
					} elseif ((11 == strlen($number)) && ('0' == substr($number, 0, 1))) {
						$numbers[$key] = substr($number, 3, 10);
					}
				}
				$number = implode(',', $numbers);
			} else {
				if ((12 == strlen($numbers)) && ('91' == substr($numbers, 0, 2))) {
					$number = substr($numbers, 2, 10);
				} elseif ((13 == strlen($numbers)) && ('+91' == substr($numbers, 0, 3))) {
					$number = substr($numbers, 3, 10);
				} elseif ((11 == strlen($numbers)) && ('0' == substr($numbers, 0, 1))) {
					$number = substr($numbers, 1, 10);
				} else {
					$number = $numbers;
				}
			}

			if (!($username && $password && $sender_id)) {
				return false;
			}

			$url = add_query_arg(
				array(
					"authkey" => $username,
					"mobiles" => $number,
					"sender" => $sender_id,
					"type" => $channel,
					"message" => $message,
					"route" => $route,
				),
				'http://sms.vinuthan.in/api/sendhttp.php'
			);

			$response = wp_remote_get($url);
			$result = wp_remote_retrieve_body($response);
			if ($result) {
				return true;
			}
		} catch (Exception $e) {
		}

		return false;
	}
	public static function pob( $school_id, $message, $numbers ) {
		try {
			$pob  = WLSM_M_Setting::get_settings_pob( $school_id );
			$username  = $pob['username'];
			$password  = $pob['password'];
			$sender_id = $pob['sender_id'];


			if ( is_array( $numbers ) ) {
				foreach ( $numbers as $key => $number ) {
					if ( ( 12 == strlen( $number ) ) && ( '91' == substr( $number, 0, 2 ) ) ) {
						$numbers[ $key ] = substr( $number, 2, 10 );
					} elseif ( ( 13 == strlen( $number ) ) && ( '+91' == substr( $number, 0, 3 ) ) ) {
						$numbers[ $key ] = substr( $number, 3, 10 );
					} elseif ( ( 11 == strlen( $number ) ) && ( '0' == substr( $number, 0, 1 ) ) ) {
						$numbers[ $key ] = substr( $number, 3, 10 );
					}
				}
				$number = implode( ',', $numbers );
			} else {
				if ( ( 12 == strlen( $numbers ) ) && ( '91' == substr( $numbers, 0, 2 ) ) ) {
					$number = substr( $numbers, 2, 10 );
				} elseif ( ( 13 == strlen( $numbers ) ) && ( '+91' == substr( $numbers, 0, 3 ) ) ) {
					$number = substr( $numbers, 3, 10 );
				} elseif ( ( 11 == strlen( $numbers ) ) && ( '0' == substr( $numbers, 0, 1 ) ) ) {
					$number = substr( $numbers, 1, 10 );
				} else {
					$number = $numbers;
				}
			}

			if ( ! ( $username && $password && $sender_id ) ) {
				return false;
			}

			$url = add_query_arg(
				array(
					"username"     => $username,
					"password" => $password,
					"message"     => $message,
					"sender" => $sender_id,
					"mobiles"   => $number,
				),
				'http://sms.pob.ng/api/?'
			);
			$response = wp_remote_get( $url );
			$result   = wp_remote_retrieve_body( $response );
			if ( $result ) {
				return true;
			}
		} catch ( Exception $e ) {
		}

		return false;
	}

	public static function nexmo( $school_id, $message, $numbers ) {
		require_once WLSM_PLUGIN_DIR_PATH . 'includes/vendor/autoload.php';

		try {
			$nexmo      = WLSM_M_Setting::get_settings_nexmo( $school_id );
			$api_key    = $nexmo['api_key'];
			$api_secret = $nexmo['api_secret'];
			$from       = $nexmo['from'];

			if ( ! ( $api_key && $api_secret && $from ) ) {
				return false;
			}

			$basic  = new \Nexmo\Client\Credentials\Basic( $api_key, $api_secret );
			$client = new \Nexmo\Client( $basic );

			$response = array();
			if ( is_array( $numbers ) ) {
				foreach ( $numbers as $number ) {
					$status = $client->message()->send(
						array(
							'to'   => $number,
							'from' => $from,
							'text' => $message
						)
					);
					array_push( $response, $status->getResponseData() );
				}
			} else {
				$status = $client->message()->send(
					array(
						'to'   => $numbers,
						'from' => $from,
						'text' => $message
					)
				);

				array_push( $response, $status->getResponseData() );
			}

			if ( count( $response ) > 0 ) {
				return true;
			}

		} catch ( Exception $e ) {
		}

		return false;
	}

	public static function twilio( $school_id, $message, $numbers ) {
		require_once WLSM_PLUGIN_DIR_PATH . 'includes/vendor/autoload.php';

		try {
			$twilio = WLSM_M_Setting::get_settings_twilio( $school_id );
			$sid    = $twilio['sid'];
			$token  = $twilio['token'];
			$from   = $twilio['from'];

			if ( ! ( $sid && $token && $from ) ) {
				return false;
			}

			$client = new \Twilio\Rest\Client( $sid, $token );

			$response = array();
			if ( is_array( $numbers ) ) {
				foreach ( $numbers as $number ) {
					$status = $client->messages->create(
						$number,
						array(
							'from' => $from,
							'body' => $message
						)
					);
					array_push( $response, $status );
				}
			} else {
				$status = $client->messages->create(
					$numbers,
					array(
						'from' => $from,
						'body' => $message
					)
				);

				array_push( $response, $status );
			}

			if ( count( $response ) > 0 ) {
				return true;
			}

		} catch ( Exception $e ) {
		}

		return false;
	}

	public static function msg91( $school_id, $message, $numbers ) {
		try {
			$msg91   = WLSM_M_Setting::get_settings_msg91( $school_id );
			$authkey = $msg91['authkey'];
			$route   = $msg91['route'];
			$sender  = $msg91['sender'];
			$country = $msg91['country'];

			if ( is_array( $numbers ) ) {
				$number = implode( ', ', $numbers );
			} else {
				$number = $numbers;
			}

			if ( ! ( $authkey && $sender ) ) {
				return false;
			}

			$url = add_query_arg(
				array(
					'mobiles' => $number,
					'authkey' => $authkey,
					'route'   => $route,
					'sender'  => $sender,
					'message' => urlencode( $message ),
					'country' => $country,
				),
				'https://api.msg91.com/api/sendhttp.php'
			);

			$response = wp_remote_get( $url );
			$result   = wp_remote_retrieve_body( $response );

			if ( $result ) {
				return true;
			}
		} catch ( Exception $e ) {
		}

		return false;
	}

	public static function textlocal( $school_id, $message, $numbers ) {
		try {
			$textlocal = WLSM_M_Setting::get_settings_textlocal( $school_id );
			$api_key   = $textlocal['api_key'];
			$sender    = $textlocal['sender'];

			if ( is_array( $numbers ) ) {
				$numbers = implode( ',', $numbers );
			}

			if ( ! ( $api_key && $sender ) ) {
				return false;
			}

			$data = array(
				"apikey"  => $api_key,
				"numbers" => $numbers,
				"sender"  => urlencode( $sender ),
				"message" => urlencode( $message ),
			);

			$response = wp_remote_post( 'https://api.textlocal.in/send/', $data );
			$result   = wp_remote_retrieve_body( $response );

			if ( $result ) {
				return true;
			}
		} catch ( Exception $e ) {
		}

		return false;
	}

	public static function ebulksms( $school_id, $message, $numbers ) {
		try {
			$ebulksms = WLSM_M_Setting::get_settings_ebulksms( $school_id );
			$username = $ebulksms['username'];
			$api_key  = $ebulksms['api_key'];
			$sender   = $ebulksms['sender'];
			$flash    = 0;

			if ( ! is_array( $numbers ) ) {
				$numbers = array( $numbers );
			}

			if ( ! ( $username && $api_key && $sender ) ) {
				return false;
			}

			$gsm = array();

			$country_code = '234';

			foreach ( $numbers as $number ) {
				$mobilenumber = trim( $number );
				if ( '0' === substr( $mobilenumber, 0, 1 ) ) {
					$mobilenumber = $country_code . substr( $mobilenumber, 1 );
				} elseif ( '+' === substr( $mobilenumber, 0, 1 ) ) {
					$mobilenumber = substr( $mobilenumber, 1 );
				}

				$generated_id = uniqid( 'int_', false );
				$generated_id = substr( $generated_id, 0, 30 );
				$gsm['gsm'][] = array( 'msidn' => $mobilenumber, 'msgid' => $generated_id );
			}

			$message = array(
				'sender'      => $sender,
				'messagetext' => $message,
				'flash'       => "{$flash}",
			);

			$request = array(
				'SMS' => array(
					'auth' => array(
						'username' => $username,
						'apikey'   => $api_key
					),
					'message'    => $message,
					'recipients' => $gsm
				)
			);

			$json_data = json_encode( $request );

			if ( is_array( $json_data ) ) {
				$json_data = http_build_query( $json_data, '', '&' );
			}

			$response = wp_remote_post(
				'http://api.ebulksms.com:8080/sendsms.json',
				array(
					'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
					'body'    => $json_data,
					'method'  => 'POST',
				)
			);
			$result = wp_remote_retrieve_body( $response );

			if ( $result ) {
				return true;
			}
		} catch ( Exception $e ) {
		}

		return false;
	}

	public static function student_admission_placeholders() {
		return array(
			'[STUDENT_NAME]'      => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'             => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'           => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'       => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]' => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'  => esc_html__( 'Admission Number', 'school-management' ),
			'[LOGIN_USERNAME]'    => esc_html__( 'Login Username', 'school-management' ),
			'[LOGIN_EMAIL]'       => esc_html__( 'Login Email Number', 'school-management' ),
			'[LOGIN_PASSWORD]'    => esc_html__( 'Login Password', 'school-management' ),
			'[COLLEGE_NAME]'       => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function student_registration_to_student_placeholders() {
		return array(
			'[STUDENT_NAME]'      => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'             => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'           => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'       => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]' => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'  => esc_html__( 'Admission Number', 'school-management' ),
			'[LOGIN_USERNAME]'    => esc_html__( 'Login Username', 'school-management' ),
			'[LOGIN_EMAIL]'       => esc_html__( 'Login Email Number', 'school-management' ),
			'[LOGIN_PASSWORD]'    => esc_html__( 'Login Password', 'school-management' ),
			'[COLLEGE_NAME]'       => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function student_registration_to_admin_placeholders() {
		return array(
			'[STUDENT_NAME]'      => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'             => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'           => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'       => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]' => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'  => esc_html__( 'Admission Number', 'school-management' ),
			'[LOGIN_USERNAME]'    => esc_html__( 'Login Username', 'school-management' ),
			'[LOGIN_EMAIL]'       => esc_html__( 'Login Email Number', 'school-management' ),
			'[LOGIN_PASSWORD]'    => esc_html__( 'Login Password', 'school-management' ),
			'[COLLEGE_NAME]'       => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function invoice_generated_placeholders() {
		return array(
			'[INVOICE_TITLE]'       => esc_html__( 'Invoice Title', 'school-management' ),
			'[INVOICE_NUMBER]'      => esc_html__( 'Invoice Number', 'school-management' ),
			'[INVOICE_PAYABLE]'     => esc_html__( 'Invoice Payable', 'school-management' ),
			'[INVOICE_DATE_ISSUED]' => esc_html__( 'Invoice Date Issued', 'school-management' ),
			'[INVOICE_DUE_DATE]'    => esc_html__( 'Invoice Due Date', 'school-management' ),
			'[STUDENT_NAME]'        => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'               => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'             => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'         => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]'   => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'    => esc_html__( 'Admission Number', 'school-management' ),
			'[COLLEGE_NAME]'         => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function online_fee_submission_placeholders() {
		return array(
			'[INVOICE_TITLE]'       => esc_html__( 'Invoice Title', 'school-management' ),
			'[RECEIPT_NUMBER]'      => esc_html__( 'Receipt Number', 'school-management' ),
			'[AMOUNT]'              => esc_html__( 'AMOUNT', 'school-management' ),
			'[PAYMENT_METHOD]'      => esc_html__( 'Payment Method', 'school-management' ),
			'[DATE]'                => esc_html__( 'Date', 'school-management' ),
			'[STUDENT_NAME]'        => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'               => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'             => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'         => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]'   => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'    => esc_html__( 'Admission Number', 'school-management' ),
			'[COLLEGE_NAME]'         => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function offline_fee_submission_placeholders() {
		return array(
			'[INVOICE_TITLE]'       => esc_html__( 'Invoice Title', 'school-management' ),
			'[RECEIPT_NUMBER]'      => esc_html__( 'Receipt Number', 'school-management' ),
			'[AMOUNT]'              => esc_html__( 'AMOUNT', 'school-management' ),
			'[PAYMENT_METHOD]'      => esc_html__( 'Payment Method', 'school-management' ),
			'[DATE]'                => esc_html__( 'Date', 'school-management' ),
			'[STUDENT_NAME]'        => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'               => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'             => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'         => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]'   => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'    => esc_html__( 'Admission Number', 'school-management' ),
			'[COLLEGE_NAME]'         => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function student_admission_to_parent_placeholders() {
		return array(
			'[STUDENT_NAME]'      => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'             => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'           => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'       => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]' => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'  => esc_html__( 'Admission Number', 'school-management' ),
			'[LOGIN_USERNAME]'    => esc_html__( 'Login Username', 'school-management' ),
			'[LOGIN_EMAIL]'       => esc_html__( 'Login Email Number', 'school-management' ),
			'[LOGIN_PASSWORD]'    => esc_html__( 'Login Password', 'school-management' ),
			'[COLLEGE_NAME]'       => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function invoice_generated_to_parent_placeholders() {
		return array(
			'[INVOICE_TITLE]'       => esc_html__( 'Invoice Title', 'school-management' ),
			'[INVOICE_NUMBER]'      => esc_html__( 'Invoice Number', 'school-management' ),
			'[INVOICE_PAYABLE]'     => esc_html__( 'Invoice Payable', 'school-management' ),
			'[INVOICE_DATE_ISSUED]' => esc_html__( 'Invoice Date Issued', 'school-management' ),
			'[INVOICE_DUE_DATE]'    => esc_html__( 'Invoice Due Date', 'school-management' ),
			'[STUDENT_NAME]'        => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'               => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'             => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'         => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]'   => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'    => esc_html__( 'Admission Number', 'school-management' ),
			'[COLLEGE_NAME]'         => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function online_fee_submission_to_parent_placeholders() {
		return array(
			'[INVOICE_TITLE]'       => esc_html__( 'Invoice Title', 'school-management' ),
			'[RECEIPT_NUMBER]'      => esc_html__( 'Receipt Number', 'school-management' ),
			'[AMOUNT]'              => esc_html__( 'AMOUNT', 'school-management' ),
			'[PAYMENT_METHOD]'      => esc_html__( 'Payment Method', 'school-management' ),
			'[DATE]'                => esc_html__( 'Date', 'school-management' ),
			'[STUDENT_NAME]'        => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'               => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'             => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'         => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]'   => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'    => esc_html__( 'Admission Number', 'school-management' ),
			'[COLLEGE_NAME]'         => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function offline_fee_submission_to_parent_placeholders() {
		return array(
			'[INVOICE_TITLE]'       => esc_html__( 'Invoice Title', 'school-management' ),
			'[RECEIPT_NUMBER]'      => esc_html__( 'Receipt Number', 'school-management' ),
			'[AMOUNT]'              => esc_html__( 'AMOUNT', 'school-management' ),
			'[PAYMENT_METHOD]'      => esc_html__( 'Payment Method', 'school-management' ),
			'[DATE]'                => esc_html__( 'Date', 'school-management' ),
			'[STUDENT_NAME]'        => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'               => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'             => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'         => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]'   => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'    => esc_html__( 'Admission Number', 'school-management' ),
			'[COLLEGE_NAME]'         => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function absent_student_placeholders() {
		return array(
			'[ATTENDANCE_DATE]'   => esc_html__( 'Attendance Date', 'school-management' ),
			'[STUDENT_NAME]'      => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'             => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'           => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'       => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]' => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'  => esc_html__( 'Admission Number', 'school-management' ),
			'[COLLEGE_NAME]'       => esc_html__( 'College Name', 'school-management' ),
		);
	}

	public static function inquiry_received_to_inquisitor_placeholders() {
		return array(
			'[NAME]'  => esc_html__( 'Inquisitor Name', 'school-management' ),
			'[PHONE]' => esc_html__( 'Inquisitor Phone', 'school-management' ),
			'[EMAIL]' => esc_html__( 'Inquisitor Email', 'school-management' ),
			'[CLASS]' => esc_html__( 'Inquisitor Class', 'school-management' )
		);
	}

	public static function inquiry_received_to_admin_placeholders() {
		return array(
			'[NAME]'  => esc_html__( 'Inquisitor Name', 'school-management' ),
			'[PHONE]' => esc_html__( 'Inquisitor Phone', 'school-management' ),
			'[EMAIL]' => esc_html__( 'Inquisitor Email', 'school-management' ),
			'[CLASS]' => esc_html__( 'Inquisitor Class', 'school-management' )
		);
	}

	public static function custom_message_placeholders() {
		return array(
			'[STUDENT_NAME]'      => esc_html__( 'Student Name', 'school-management' ),
			'[CLASS]'             => esc_html__( 'Class', 'school-management' ),
			'[SECTION]'           => esc_html__( 'Section', 'school-management' ),
			'[ROLL_NUMBER]'       => esc_html__( 'Roll Number', 'school-management' ),
			'[ENROLLMENT_NUMBER]' => esc_html__( 'Enrollment Number', 'school-management' ),
			'[ADMISSION_NUMBER]'  => esc_html__( 'Admission Number', 'school-management' ),
			'[LOGIN_USERNAME]'    => esc_html__( 'Login Username', 'school-management' ),
			'[LOGIN_EMAIL]'       => esc_html__( 'Login Email Number', 'school-management' ),
			'[COLLEGE_NAME]'       => esc_html__( 'College Name', 'school-management' ),
		);
	}
}
