<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Config.php';

class WLSM_P_Student {
	public static function view_study_material() {
		$study_material_id = isset( $_POST['study_material_id'] ) ? absint( $_POST['study_material_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-view-study-material-' . $study_material_id ], 'st-view-study-material-' . $study_material_id ) ) {
			die();
		}

		$user_id = get_current_user_id();

		try {
			ob_start();
			global $wpdb;

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$class_school_id = $student->class_school_id;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$class_school_id = $student->class_school_id;

			$study_material = $wpdb->get_row( $wpdb->prepare( WLSM_M::study_material_query(), $class_school_id, $study_material_id ) );

			if ( ! $study_material ) {
				throw new Exception( esc_html__( 'Study material not found.', 'school-management' ) );
			}

			$attachments = $study_material->attachments;
			if ( is_serialized( $attachments ) ) {
				$attachments = unserialize( $attachments );
			} else {
				if ( ! is_array( $attachments ) ) {
					$attachments = array();
				}
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

		ob_start();
		?>
		<ul class="wlsm-study-material-data">
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Title', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( stripslashes( $study_material->title ) ); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Description', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( stripslashes( $study_material->description ) ); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Url', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( stripslashes( $study_material->url ) ); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Date', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( WLSM_Config::get_date_text( $study_material->created_at ) ); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Attachments', 'school-management' ); ?>:</span>
				<span>
					<?php
					if ( count( $attachments ) ) {
					?>
					<ul class="wlsm-study-material-attachments">
					<?php
					foreach ( $attachments as $attachment ) {
						if ( ! empty ( $attachment ) ) {
							$file_name = basename( get_attached_file( $attachment ) );
						?>
						<li>
							<a target="_blank" href="<?php echo esc_url( wp_get_attachment_url( $attachment ) ); ?>">
								<?php echo esc_html( $file_name ); ?>
							</a>
						</li>
						<?php
						}
					}
					?>
					</ul>
					<?php
					}
					?>
				</span>
			</li>
		</ul>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function view_homework() {
		$homework_id = isset( $_POST['homework_id'] ) ? absint( $_POST['homework_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-view-homework-' . $homework_id ], 'st-view-homework-' . $homework_id ) ) {
			die();
		}

		$user_id = get_current_user_id();

		try {
			ob_start();
			global $wpdb;

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$section_id = $student->section_id;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$homework = $wpdb->get_row( $wpdb->prepare( WLSM_M::homework_query(), $school_id, $session_id, $section_id, $homework_id ) );
			$subject = $wpdb->get_row( $wpdb->prepare( WLSM_M::get_subject($homework->subject)  ) );

			if ( ! $homework ) {
				throw new Exception( esc_html__( 'Home work not found.', 'school-management' ) );
			}

			$attachments = $homework->attachments;
			if ( is_serialized( $attachments ) ) {
				$attachments = unserialize( $attachments );
			} else {
				if ( ! is_array( $attachments ) ) {
					$attachments = array();
				}
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

		ob_start();
		?>
		<ul class="wlsm-study-material-data">
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Title', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( stripslashes( $homework->title ) ); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Subject', 'school-management' ); ?>:</span>
				<span><?php
					esc_html_e($subject->label);
				?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Description', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( stripslashes( $homework->description ) ); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Date', 'school-management' ); ?>:</span>
				<span><?php echo esc_html( WLSM_Config::get_date_text( $homework->homework_date ) ); ?></span>
			</li>
			<li>
				<span class="wlsm-font-bold"><?php esc_html_e( 'Attachments', 'school-management' ); ?>:</span>
				<span>
					<?php
					if ( count( $attachments ) ) {
					?>
					<ul class="wlsm-study-material-attachments">
					<?php
					foreach ( $attachments as $attachment ) {
						if ( ! empty ( $attachment ) ) {
							$file_name = basename( get_attached_file( $attachment ) );
						?>
						<li>
							<a target="_blank" href="<?php echo esc_url( wp_get_attachment_url( $attachment ) ); ?>">
								<?php echo esc_html( $file_name ); ?>
							</a>
						</li>
						<?php
						}
					}
					?>
					</ul>
					<?php
					}
					?>
				</span>
			</li>
		</ul>
		<?php
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	public static function join_event() {
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-join-event-' . $event_id ], 'st-join-event-' . $event_id ) ) {
			die();
		}

		$user_id = get_current_user_id();

		try {
			ob_start();
			global $wpdb;

			$errors = array();

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$event = WLSM_M_Staff_Class::fetch_active_event( $school_id, $event_id, $student_id );

			if ( ! $event ) {
				throw new Exception( esc_html__( 'Event not found.', 'school-management' ) );
			}

			if ( $event->student_joined ) {
				throw new Exception( esc_html__( 'You have already joined.', 'school-management' ) );
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

				// Event participant data.
				$data = array(
					'student_record_id' => $student_id,
					'event_id'          => $event_id,
				);

				$data['created_at'] = current_time( 'Y-m-d H:i:s' );

				$success = $wpdb->insert( WLSM_EVENT_RESPONSES, $data );

				$buffer = ob_get_clean();
				if ( ! empty( $buffer ) ) {
					throw new Exception( $buffer );
				}

				if ( false === $success ) {
					throw new Exception( $wpdb->last_error );
				}

				$wpdb->query( 'COMMIT;' );

				$replace_text = esc_html__( 'Successfully Joined', 'school-management' );

				$message = esc_html__( 'You have joined the event successfully.', 'school-management' );

				wp_send_json_success( array( 'message' => $message, 'replace_text' => $replace_text ) );
			} catch ( Exception $exception ) {
				$wpdb->query( 'ROLLBACK;' );
				wp_send_json_error( $exception->getMessage() );
			}
		}
		wp_send_json_error( $errors );
	}

	public static function unjoin_event() {
		$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;

		if ( ! wp_verify_nonce( $_POST[ 'st-unjoin-event-' . $event_id ], 'st-unjoin-event-' . $event_id ) ) {
			die();
		}

		$user_id = get_current_user_id();

		try {
			ob_start();
			global $wpdb;

			$errors = array();

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			$event = WLSM_M_Staff_Class::fetch_active_event( $school_id, $event_id, $student_id );

			if ( ! $event ) {
				throw new Exception( esc_html__( 'Event not found.', 'school-management' ) );
			}

			if ( ! $event->student_joined ) {
				throw new Exception( esc_html__( 'You have not joined this event.', 'school-management' ) );
			}

			$event_response_id = $event->event_response_id;

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

				$success = $wpdb->delete( WLSM_EVENT_RESPONSES, array( 'ID' => $event_response_id ) );

				$buffer = ob_get_clean();
				if ( ! empty( $buffer ) ) {
					throw new Exception( $buffer );
				}

				if ( false === $success ) {
					throw new Exception( $wpdb->last_error );
				}

				$wpdb->query( 'COMMIT;' );

				$replace_text = esc_html__( 'You have left', 'school-management' );

				$message = esc_html__( 'You have left from this event.', 'school-management' );

				wp_send_json_success( array( 'message' => $message, 'replace_text' => $replace_text ) );
			} catch ( Exception $exception ) {
				$wpdb->query( 'ROLLBACK;' );
				wp_send_json_error( $exception->getMessage() );
			}
		}
		wp_send_json_error( $errors );
	}

	public static function submit_leave_request() {
		if ( ! wp_verify_nonce( $_POST['submit-student-leave-request'], 'submit-student-leave-request' ) ) {
			die();
		}

		$user_id = get_current_user_id();

		try {
			ob_start();
			global $wpdb;

			$student = WLSM_M_User::user_is_student( $user_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student( $school_id, $session_id, $student_id );

			if ( ! $student ) {
				throw new Exception( esc_html__( 'Student not found.', 'school-management' ) );
			}

			$description   = isset( $_POST['description'] ) ? sanitize_text_field( $_POST['description'] ) : '';
			$start_date    = isset( $_POST['start_date'] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $_POST['start_date'] ) ) : NULL;
			$end_date      = isset( $_POST['end_date'] ) ? DateTime::createFromFormat( WLSM_Config::date_format(), sanitize_text_field( $_POST['end_date'] ) ) : NULL;
			$multiple_days = isset( $_POST['multiple_days'] ) ? (bool) $_POST['multiple_days'] : 0;

			if ( $multiple_days ) {
				if ( $start_date >= $end_date ) {
					$errors['start_date'] = esc_html__( 'Start date must be lower than end date.', 'school-management' );
				}
			}

			if ( empty( $description ) ) {
				$errors['description'] = esc_html__( 'Please specify reason.', 'school-management' );
			}

			if ( empty( $start_date ) ) {
				if ( $multiple_days ) {
					$errors['start_date'] = esc_html__( 'Please specify leave start date.', 'school-management' );
				} else {
					$errors['start_date'] = esc_html__( 'Please specify leave date.', 'school-management' );
				}
			} else {
				$start_date = $start_date->format( 'Y-m-d' );
			}

			if ( $multiple_days ) {
				if ( empty( $end_date ) ) {
					$errors['end_date'] = esc_html__( 'Please specify leave end date.', 'school-management' );
				} else {
					$end_date = $end_date->format( 'Y-m-d' );
				}
			} else {
				$end_date = NULL;
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

				// Student leave data.
				$data = array(
					'student_record_id' => $student_id,
					'description'       => $description,
					'start_date'        => $start_date,
					'end_date'          => $end_date,
					'school_id'         => $school_id,
				);

				$data['created_at'] = current_time( 'Y-m-d H:i:s' );

				$success = $wpdb->insert( WLSM_LEAVES, $data );

				$buffer = ob_get_clean();
				if ( ! empty( $buffer ) ) {
					throw new Exception( $buffer );
				}

				if ( false === $success ) {
					throw new Exception( $wpdb->last_error );
				}

				$wpdb->query( 'COMMIT;' );

				$message = esc_html__( 'Leave request submitted successfully.', 'school-management' );

				wp_send_json_success( array( 'message' => $message ) );
			} catch ( Exception $exception ) {
				wp_send_json_error( $exception->getMessage() );
			}
		}
		wp_send_json_error( $errors );
	}
	public function submit_homework() {
		if (!wp_verify_nonce($_POST['submit-student-homework'], 'submit-student-homework')) {
			die();
		}

		$user_id = get_current_user_id();

		try {
			ob_start();
			global $wpdb;

			$student = WLSM_M_User::user_is_student($user_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$student_id = $student->ID;
			$school_id  = $student->school_id;
			$session_id = $student->session_id;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$submission_id       = isset($_POST['submission_id']) ? absint($_POST['submission_id']): 0;
			$description = isset($_POST['description']) ? sanitize_text_field($_POST['description']): '';
			$homework_update_id = isset($_POST['homework_update']) ? sanitize_text_field($_POST['homework_update']): '';
			$homework_sub_id = isset($_POST['homework_sub_id']) ? sanitize_text_field($_POST['homework_sub_id']): '';
			$attachment = ( isset( $_FILES['attachments'] ) && is_array( $_FILES['attachments'] ) ) ? $_FILES['attachments'] : NULL;

			if (empty($submission_id)) {
				$errors['submission_id'] = esc_html__('Please Enter Submission Subject', 'school-management');
			}

			if (empty($description)) {
				$errors['description'] = esc_html__('Please Enter discription', 'school-management');
			}

			if (isset($attachment['tmp_name']) && !empty($attachment['tmp_name'])) {
				if (!WLSM_Helper::is_valid_file($attachment, 'attachment')) {
					$errors['attachment'] = esc_html__('Please provide attachment PDF format.', 'school-management');
				}
			}

		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		if (count($errors) < 1) {
			try {
				$wpdb->query('BEGIN;');

				// Student homework data.
				$data = array(
					'submission_id' => $submission_id,
					'description'   => $description,
					'school_id'     => $school_id,
					'session_id'    => $session_id,
					'student_id'    => $student_id,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');
				// var_dump($homework_sub_id, $homework_update_id);
				if (!empty($attachment)) {
					$attachment = media_handle_upload('attachments', 0);
					if (is_wp_error($attachment)) {
						throw new Exception($attachment->get_error_message());
					}
					$data['attachments'] = $attachment;
				}
				if ($homework_update_id) {
					$success = $wpdb->update(WLSM_HOMEWORK_SUBMISSION, $data, array(
					'ID'            => $homework_sub_id,
					'submission_id' => $submission_id,
				));
				}else{
					$success = $wpdb->insert(WLSM_HOMEWORK_SUBMISSION, $data );
				}


				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$message = esc_html__('submitted successfully.', 'school-management');

				wp_send_json_success(array('message' => $message));
			} catch (Exception $exception) {
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}
}
