<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Helper.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Session.php';

class WLSM_Config {
	public static function current_session() {
		$user_id = get_current_user_id();

		$session = NULL;

		$default_session_id = get_option( 'wlsm_current_session' );

		// Get current session from user meta.
		if ( $user_id && ( $current_session = get_user_meta( $user_id, 'wlsm_current_session', true ) ) ) {
			$session = WLSM_M_Session::fetch_session( $current_session );
		}

		// Get current session from setting.
		if ( ! $session ) {
			$current_session = $default_session_id;
			$current_session = absint( $current_session );
			if ( $current_session ) {
				$session = WLSM_M_Session::fetch_session( $current_session );
			}
		}

		if ( ! $session ) {
			$data = array(
				'ID'         => '',
				'label'      => '',
				'start_date' => '',
				'end_date'   => '',
			);
		} else {
			$data = array(
				'ID'         => $session->ID,
				'label'      => $session->label,
				'start_date' => $session->start_date,
				'end_date'   => $session->end_date,
			);
		}

		$data['default_session_id'] = $default_session_id;
		$data['sessions']           = WLSM_M_Session::fetch_sessions();

		return $data;
	}

	public static function currency() {
		$currency = get_option( 'wlsm_currency' );

		if ( isset( WLSM_Helper::currency_symbols()[ $currency ] ) ) {
			return $currency;
		}

		return self::get_default_currency();
	}

	public static function currency_symbol() {
		return WLSM_Helper::currency_symbols()[self::currency()];
	}

	public static function sanitize_money( $money ) {
		return (float) number_format( max( (float) $money, 0 ), 2, '.', '' );
	}

	public static function get_money_text( $money ) {
		$money = number_format( (float) $money, 2, '.', '' );
		if ( 0.00 == $money ) {
			return '-';
		}
		return self::currency_symbol() . number_format( (float) $money, 2, '.', '' );
	}

	public static function sanitize_marks( $marks ) {
		return abs( (float) $marks );
	}

	public static function sanitize_percentage( $total, $obtained, $decimal = 2 ) {
		if ( ! $total ) {
			return false;
		}

		$percentage = ( $obtained * 100 ) / $total;

		return (float) number_format( $percentage, $decimal, '.', '' );
	}

	public static function get_percentage_text( $total, $obtained, $decimal = 2 ) {
		if ( ! $total ) {
			return '-';
		}

		$percentage = ( $obtained * 100 ) / $total;

		return number_format( $percentage, $decimal, '.', '' ) . ' %';
	}

	public static function date_format() {
		$date_format = get_option( 'wlsm_date_format' );
		if ( ! $date_format ) {
			$date_format = self::get_default_date_format();
		}

		return $date_format;
	}

	public static function at_format() {
		$at_format = get_option( 'wlsm_at_format' );
		if ( ! $at_format ) {
			$at_format = self::get_default_at_format();
		}

		return $at_format;
	}

	public static function gdpr_text_inquiry() {
		$text = get_option( 'wlsm_gdpr_text_inquiry' );
		if ( ! $text ) {
			$text = self::get_default_gdpr_text();
		}

		return $text;
	}

	public static function gdpr_text_registration() {
		$text = get_option( 'wlsm_gdpr_text_registration' );
		if ( ! $text ) {
			$text = self::get_default_gdpr_text();
		}

		return $text;
	}

	public static function get_default_gdpr_text() {
		return sprintf(
			wp_kses(
				__( 'I agree with GDPR compliant terms & conditions.', 'school-management' ),
				array( 'a' => array( 'href' => array(), 'class' => array() ) )
			)
		);
	}

	public static function get_date_text( $date ) {
		if ( $date ) {
			return date_format( date_create( $date ), self::date_format() );
		}
		return '';
	}

	public static function get_time_text( $time ) {
		if ( $time ) {
			return date_format( date_create( $time ), self::get_default_time_format() );
		}
		return '';
	}

	public static function get_at_text( $at, $empty = '' ) {
		if ( $at ) {
			return date_format( date_create( $at ), self::at_format() );
		}
		return $empty;
	}

	public static function get_note_text( $note ) {
		if ( $note ) {
			return stripcslashes( $note );
		}
		return '-';
	}

	public static function limit_string( $content, $number_of_characters = 100 ) {
		if ( strlen( $content ) > $number_of_characters ) {
			$position = strpos( $content, ' ', $number_of_characters );
			$dots = '...';
			return substr( $content, 0 , $position ) . $dots;
		}

		return $content;
	}

	public static function sanitize_grade_criteria( $grade_criteria ) {
		if ( is_serialized( $grade_criteria ) ) {
			$grade_criteria = unserialize( $grade_criteria );
		}

		if ( ! is_array( $grade_criteria ) ) {
			$grade_criteria = array();
		}

		if ( ! isset( $grade_criteria['enable_overall_grade'] ) ) {
			$grade_criteria['enable_overall_grade'] = false;
		}

		if ( ! isset( $grade_criteria['marks_grades'] ) ) {
			$grade_criteria['marks_grades'] = array();
		}

		return $grade_criteria;
	}

	public static function get_default_grade_criteria() {
		return array(
			'enable_overall_grade' => false,
			'marks_grades'         => array(
				array(
					'min'   => 0,
					'max'   => 40,
					'grade' => 'F'
				),
				array(
					'min'   => 41,
					'max'   => 50,
					'grade' => 'D'
				),
				array(
					'min'   => 51,
					'max'   => 60,
					'grade' => 'C'
				),
				array(
					'min'   => 61,
					'max'   => 70,
					'grade' => 'B'
				),
				array(
					'min'   => 71,
					'max'   => 80,
					'grade' => 'B+'
				),
				array(
					'min'   => 81,
					'max'   => 90,
					'grade' => 'A'
				),
				array(
					'min'   => 91,
					'max'   => 100,
					'grade' => 'A+'
				),
			)
		);
	}

	public static function get_default_currency() {
		return 'USD';
	}

	public static function get_default_date_format() {
		return 'd-m-Y';
	}

	public static function get_default_at_format() {
		return self::get_default_date_format() . ' ' . self::get_default_time_format();
	}

	public static function get_default_time_format() {
		return 'h:i a';
	}

	public static function default_enrollment_settings() {
		return array(
			'prefix'  => '',
			'base'    => 0,
			'padding' => 6
		);
	}

	public static function default_admission_settings() {
		return array(
			'prefix'  => '',
			'base'    => 0,
			'padding' => 6
		);
	}
}
