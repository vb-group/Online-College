<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Import.php';

class WLSM_Staff_Import {
	public static function bulk_import_student() {
		$current_user = WLSM_M_Role::can( 'manage_admissions' );

		if ( ! $current_user ) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			if ( ! wp_verify_nonce( $_POST['bulk-import-student'], 'bulk-import-student' ) ) {
				die();
			}

			// Start validation.
			$errors = array();

			$csv = ( isset( $_FILES['csv'] ) && is_array( $_FILES['csv'] ) ) ? $_FILES['csv'] : NULL;

			if ( isset( $csv['tmp_name'] ) && ! empty( $csv['tmp_name'] ) ) {
				if ( ! WLSM_Helper::is_valid_file( $csv, 'csv' ) ) {
					$errors['csv'] = esc_html__( 'Please provide valid csv file.', 'school-management' );
				}
			} else {
				$errors['csv'] = esc_html__( 'Please provide valid csv file.', 'school-management' );
			}

			if ( count( $errors ) >= 1 ) {
				wp_send_json_error( $errors );
			}

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		if ( count( $errors ) < 1 ) {
			try {
				$wpdb->query( 'BEGIN;' );

				$csv_file = fopen( $csv['tmp_name'], 'r' );

				fgetcsv( $csv_file );

				$row = 1;
				while ( $line = fgetcsv( $csv_file ) ) {
					$row++;

					$name              = sanitize_text_field( $line[0] );
					$admission_number  = sanitize_text_field( $line[1] );
					$admission_date    = ! empty( $line[2] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $line[2] ) ) : NULL;
					$class_label       = sanitize_text_field( $line[3] );
					$section_label     = sanitize_text_field( $line[4] );
					$roll_number       = sanitize_text_field( $line[5] );
					$gender            = sanitize_text_field( $line[6] );
					$dob               = ! empty( $line[7] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $line[7] ) ) : NULL;
					$phone             = sanitize_text_field( $line[8] );
					$email             = sanitize_text_field( $line[9] );
					$address           = sanitize_text_field( $line[10] );
					$religion          = sanitize_text_field( $line[11] );
					$caste             = sanitize_text_field( $line[12] );
					$blood_group       = sanitize_text_field( $line[13] );
					$father_name       = sanitize_text_field( $line[14] );
					$father_phone      = sanitize_text_field( $line[15] );
					$father_occupation = sanitize_text_field( $line[16] );
					$mother_name       = sanitize_text_field( $line[17] );
					$mother_phone      = sanitize_text_field( $line[18] );
					$mother_occupation = sanitize_text_field( $line[19] );
					$is_active         = (bool) $line[20];

					// Personal Detail.
					if ( empty( $name ) ) {
						throw new Exception( esc_html__( 'Please specify student name.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 1 ) );
					}
					if ( strlen( $name ) > 60 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 60 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 1 ) );
					}
					if ( ! empty( $religion ) && strlen( $religion ) > 40 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 40 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 12 ) );
					}
					if ( ! empty( $caste ) && strlen( $caste ) > 40 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 40 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 13 ) );
					}
					if ( ! empty( $phone ) && strlen( $phone ) > 40 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 40 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 9 ) );
					}
					if ( ! empty( $email ) ) {
						if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
							throw new Exception( esc_html__( 'Please provide a valid email.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 10 ) );
						} else if ( strlen( $email ) > 60 ) {
							throw new Exception( esc_html__( 'Maximum length cannot exceed 60 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 10 ) );
						}
					}
					if ( ! in_array( $gender, array_keys( WLSM_Helper::gender_list() ) ) ) {
						throw new Exception( esc_html__( 'Please specify gender.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 7 ) );
					}
					if ( ! empty( $blood_group ) && ! in_array( $blood_group, array_keys( WLSM_Helper::blood_group_list() ) ) ) {
						throw new Exception( esc_html__( 'Please specify blood group.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 14 ) );
					}
					if ( ! empty( $dob ) ) {
						$dob = $dob->format( 'Y-m-d' );
					} else {
						$dob = NULL;
					}
					// Admission Detail.
					if ( empty( $admission_date ) ) {
						throw new Exception( esc_html__('Please provide admission date. [ CHANGE DATE FORMAT INSIDE SCHOOL MANAGEMENT SETTINGS ]', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 3 ) );
					} else {
						$admission_date = $admission_date->format( 'Y-m-d' );
					}
					if ( empty( $admission_number ) ) {
						throw new Exception( esc_html__( 'Please provide admission number. or Duplicate roll number', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 2 ) );
					}
					if ( strlen( $admission_number ) > 60 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 60 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 2 ) );
					}
					if ( ! empty( $roll_number ) && strlen( $roll_number ) > 30 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 30 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 6 ) );
					}
					if ( empty( $class_label ) ) {
						throw new Exception( esc_html__( 'Please specify class.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 4 ) );
					} else {
						// Search class with this label.
						// Checks if class exists in the school.
						$class_school = WLSM_M_Staff_Class::get_class_with_label( $school_id, $class_label );
						if ( ! $class_school ) {
							throw new Exception( esc_html__( 'Class not found.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 4 ) );
						} else {
							$class_school_id = $class_school->ID;
							$class_id        = $class_school->class_id;
						}

						if ( empty( $section_label ) ) {
							throw new Exception( esc_html__( 'Please specify section.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 5 ) );
						} else {
							// Search section with this label.
							// Checks if section exists.
							$section = WLSM_M_Staff_Class::get_section_with_label( $school_id, $section_label, $class_school_id );

							if ( ! $section ) {
								throw new Exception( esc_html__( 'Section not found.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 5 ) );
							} else {
								$section_id = $section->ID;
							}

							// Checks if admission number already exists for this session.
							$student_exists = WLSM_M_Staff_General::get_admitted_student_id( $school_id, $session_id, $admission_number );

							if ( $student_exists ) {
								throw new Exception( esc_html__( 'Admission number already exists in this session.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 2 ) );
							}

							// Checks if roll number already exists in the class for this session.
							$student_exists = WLSM_M_Staff_General::get_student_with_roll_number( $school_id, $session_id, $class_id, $roll_number );

							if ( $student_exists ) {
								throw new Exception( esc_html__( 'Roll number already exists in this class.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 6 ) );
							}
						}
					}

					// Parent Detail.
					if ( ! empty( $father_name ) && strlen( $father_name ) > 60 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 60 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 15 ) );
					}
					if ( ! empty( $father_phone ) && strlen( $father_phone ) > 40 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 40 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 16 ) );
					}
					if ( ! empty( $father_occupation ) && strlen( $father_occupation ) > 60 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 60 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 17 ) );
					}
					if ( ! empty( $mother_name ) && strlen( $mother_name ) > 60 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 60 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 18 ) );
					}
					if ( ! empty( $mother_phone ) && strlen( $mother_phone ) > 40 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 40 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 19 ) );
					}
					if ( ! empty( $mother_occupation ) && strlen( $mother_occupation ) > 60 ) {
						throw new Exception( esc_html__( 'Maximum length cannot exceed 60 characters.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 20 ) );
					}

					if ( $is_active ) {
						$is_active = 1;
					} else {
						$is_active = 0;
					}

					// Student record data.
					$student_record_data = array(
						'admission_number'  => $admission_number,
						'name'              => $name,
						'gender'            => $gender,
						'dob'               => $dob,
						'phone'             => $phone,
						'email'             => $email,
						'address'           => $address,
						'religion'          => $religion,
						'caste'             => $caste,
						'blood_group'       => $blood_group,
						'father_name'       => $father_name,
						'father_phone'      => $father_phone,
						'father_occupation' => $father_occupation,
						'mother_name'       => $mother_name,
						'mother_phone'      => $mother_phone,
						'mother_occupation' => $mother_occupation,
						'admission_date'    => $admission_date,
						'roll_number'       => $roll_number,
						'section_id'        => $section_id,
						'is_active'         => $is_active,
					);

					$student_record_data['session_id'] = $session_id;

					$enrollment_number = WLSM_M_Staff_General::get_enrollment_number( $school_id );

					$student_record_data['enrollment_number'] = $enrollment_number;

					$student_record_data['created_at'] = current_time( 'Y-m-d H:i:s' );

					$success = $wpdb->insert( WLSM_STUDENT_RECORDS, $student_record_data );

					$new_student_id = $wpdb->insert_id;
					$student_id     = $new_student_id;

					$buffer = ob_get_clean();
					if ( ! empty( $buffer ) ) {
						throw new Exception( $buffer );
					}

					if ( false === $success ) {
						throw new Exception( $wpdb->last_error );
					}
				}

				fclose( $csv_file );

				$message = esc_html__( 'Students imported successfully.', 'school-management' );

				$wpdb->query( 'COMMIT;' );

				wp_send_json_success( array( 'message' => $message ) );
			} catch ( Exception $exception ) {
				$wpdb->query( 'ROLLBACK;' );
				fclose( $csv_file );
				wp_send_json_error( $exception->getMessage() );
			}
		}
		wp_send_json_error( $errors );
	}

	public static function bulk_import_exam_results() {
		$current_user = WLSM_M_Role::can( 'manage_admissions' );

		if ( ! $current_user ) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$exam_id = isset( $_POST['exam_id'] ) ? absint( $_POST['exam_id'] ) : 0;

			if ( ! wp_verify_nonce( $_POST[ 'bulk-import-results-' . $exam_id ], 'bulk-import-results-' . $exam_id ) ) {
				die();
			}

			$exam = WLSM_M_Staff_Examination::fetch_exam( $school_id, $exam_id );

			if ( ! $exam ) {
				die;
			}

			// Start validation.
			$errors = array();

			$csv = ( isset( $_FILES['csv'] ) && is_array( $_FILES['csv'] ) ) ? $_FILES['csv'] : NULL;

			if ( isset( $csv['tmp_name'] ) && ! empty( $csv['tmp_name'] ) ) {
				if ( ! WLSM_Helper::is_valid_file( $csv, 'csv' ) ) {
					$errors['csv'] = esc_html__( 'Please provide valid csv file.', 'school-management' );
				}
			} else {
				$errors['csv'] = esc_html__( 'Please provide valid csv file.', 'school-management' );
			}

			if ( count( $errors ) >= 1 ) {
				wp_send_json_error( $errors );
			}

		} catch ( Exception $exception ) {
			$buffer = ob_get_clean();
			if ( ! empty( $buffer ) ) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error( $response );
		}

		if ( count( $errors ) < 1 ) {
			try {
				$wpdb->query( 'BEGIN;' );

				$exam_papers = WLSM_M_Staff_Examination::get_exam_papers_by_exam_id( $school_id, $exam_id );

				$cols_before_papers = 4;

				$csv_file = fopen( $csv['tmp_name'], 'r' );

				fgetcsv( $csv_file );

				$row = 1;
				while ( $line = fgetcsv( $csv_file ) ) {
					$row++;

					$exam_roll_number = isset( $line[0] ) ? sanitize_text_field( $line[0] ) : '';

					if ( empty( $exam_roll_number ) ) {
						throw new Exception( esc_html__( 'Please provide exam roll number.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 1 ) );
					}

					// Checks if admit card exists for exam roll number.
					$admit_card = WLSM_M_Staff_Examination::get_admit_card_by_exam_roll_number( $school_id, $exam_id, $exam_roll_number );

					if ( ! $admit_card ) {
						throw new Exception( esc_html__( 'Admit card not found.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, 1 ) );
					}

					$admit_card_id = $admit_card->ID;

					$obtained_marks_col_before = 3;

					$exam_results = WLSM_M_Staff_Examination::get_exam_results_by_admit_card( $school_id, $admit_card_id );

					$i = 0;
					foreach ( $exam_papers as $key => $exam_paper ) {
						$i++;

						$obtained_marks_col = $obtained_marks_col_before + $i;

						$obtained_marks_input = ( isset( $line[ $obtained_marks_col ] ) && ( '' !== $line[ $obtained_marks_col ] ) ) ? WLSM_Config::sanitize_marks( $line[ $obtained_marks_col ] ) : '';

						if ( '' === $obtained_marks_input ) {
							throw new Exception( esc_html__( 'Please specify marks obtained.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, $obtained_marks_col + 1 ) );
						}

						if ( $obtained_marks_input > $exam_paper->maximum_marks ) {
							throw new Exception( esc_html__( 'Marks obtained can\'t be greater than maximum marks.', 'school-management' ) . WLSM_Import::get_csv_error_msg( $row, $obtained_marks_col + 1 ) );
						}

						$marks_obtained = $obtained_marks_input;

						if ( isset( $exam_results[ $exam_paper->ID ] ) ) {
							// If result exists, update.
							$exam_result = $exam_results[ $exam_paper->ID ];

							$exam_result_data = array(
								'obtained_marks' => $marks_obtained,
								'updated_at'     => current_time( 'Y-m-d H:i:s' )
							);

							$success = $wpdb->update( WLSM_EXAM_RESULTS, $exam_result_data, array( 'ID' => $exam_result->ID ) );

						} else {
							// If result do not exist, insert.
							$exam_result_data = array(
								'obtained_marks' => $marks_obtained,
								'exam_paper_id'  => $exam_paper->ID,
								'admit_card_id'  => $admit_card_id
							);

							$exam_result_data['created_at'] = current_time( 'Y-m-d H:i:s' );

							$success = $wpdb->insert( WLSM_EXAM_RESULTS, $exam_result_data );
						}
					}

					$buffer = ob_get_clean();
					if ( ! empty( $buffer ) ) {
						throw new Exception( $buffer );
					}

					if ( false === $success ) {
						throw new Exception( $wpdb->last_error );
					}
				}

				fclose( $csv_file );

				$message = esc_html__( 'Exam results imported successfully.', 'school-management' );

				$wpdb->query( 'COMMIT;' );

				wp_send_json_success( array( 'message' => $message ) );
			} catch ( Exception $exception ) {
				$wpdb->query( 'ROLLBACK;' );
				fclose( $csv_file );
				wp_send_json_error( $exception->getMessage() );
			}
		}
		wp_send_json_error( $errors );
	}
}
