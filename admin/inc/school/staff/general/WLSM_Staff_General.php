<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_School.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Role.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Config.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Notify.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Admin.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Helper.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Email.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_SMS.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Class.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';

class WLSM_Staff_General
{
	public static function get_class_sections()
	{
		$current_user = WLSM_M_Role::can(array('manage_admissions', 'manage_students', 'manage_invoices', 'manage_transfer_student', 'manage_certificates', 'manage_library', 'manage_transport', 'manage_homework', 'manage_exams', 'manage_admins', 'manage_employees', 'manage_student_leaves'));

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['nonce'], 'get-class-sections')) {
				die();
			}

			$class_id = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;

			$all_sections = isset($_POST['all_sections']) ? absint($_POST['all_sections']) : 0;

			// Checks if class exists in the school.
			$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);

			if (!$class_school) {
				throw new Exception(esc_html__('Class not found.', 'school-management'));
			}

			$class_school_id = $class_school->ID;

			$sections = WLSM_M_Staff_General::fetch_class_sections($class_school_id);

			if ($all_sections) {
				$all_sections = (object) array('ID' => '', 'label' => esc_html__('All Sections', 'school-management'));
				array_unshift($sections, $all_sections);
			}

			$sections = array_map(function ($section) {
				$section->label = WLSM_M_Staff_Class::get_section_label_text($section->label);
				return $section;
			}, $sections);

			wp_send_json($sections);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function get_section_students()
	{
		$current_user = WLSM_M_Role::can(array('manage_invoices', 'manage_transfer_student', 'manage_certificates', 'manage_library', 'manage_student_leaves'));

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['nonce'], 'get-section-students')) {
				die();
			}

			$class_id   = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;
			$section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;

			$skip_transferred = isset($_POST['skip_transferred']) ? (bool) $_POST['skip_transferred'] : 1;
			$only_active      = isset($_POST['only_active']) ? (bool) $_POST['only_active'] : 1;

			// Checks if class exists in the school.
			$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);

			if (!$class_school) {
				throw new Exception(esc_html__('Class not found.', 'school-management'));
			}

			$class_school_id = $class_school->ID;

			if ($section_id) {
				// Checks if section exists.
				$section = WLSM_M_Staff_Class::get_section($school_id, $section_id, $class_school_id);

				if (!$section) {
					throw new Exception(esc_html__('Section not found.', 'school-management'));
				}

				$students = WLSM_M_Staff_General::fetch_section_students($session_id, $section->ID, $skip_transferred, $only_active);
			} else {
				$students = WLSM_M_Staff_General::fetch_class_students($session_id, $class_school_id, $skip_transferred, $only_active);
			}

			wp_send_json($students);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function get_class_subjects()
	{
		$current_user = WLSM_M_Role::can(array('manage_timetable', 'view_timetable', 'manage_live_classes'));

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['nonce'], 'get-class-subjects')) {
				die();
			}

			$class_id = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;

			$subjects = WLSM_M_Staff_Class::get_class_subjects($school_id, $class_id);

			$subjects = array_map(function ($subject) {
				$subject->label = sprintf(
					wp_kses(
						/* translators: 1: subject label, 2: subject code */
						_x('%1$s (%2$s)', 'Subject', 'school-management'),
						array('span' => array('class' => array()))
					),
					esc_html(WLSM_M_Staff_Class::get_subject_label_text($subject->label)),
					esc_html($subject->code)
				);

				return $subject;
			}, $subjects);

			wp_send_json($subjects);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function get_subject_teachers()
	{
		$current_user = WLSM_M_Role::can(array('manage_timetable', 'view_timetable', 'manage_live_classes'));

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['nonce'], 'get-subject-teachers')) {
				die();
			}

			$subject_id = isset($_POST['subject_id']) ? absint($_POST['subject_id']) : 0;

			$admins = WLSM_M_Staff_Class::get_subject_admins($school_id, $subject_id);

			$admins = array_map(function ($admin) {
				$admin->label = sprintf(
					wp_kses(
						/* translators: 1: Teacher name, 2: Teacher phone number */
						_x('%1$s (%2$s)', 'Teacher', 'school-management'),
						array('span' => array('class' => array()))
					),
					esc_html(WLSM_M_Staff_Class::get_name_text($admin->label)),
					esc_html(WLSM_M_Staff_Class::get_phone_text($admin->phone))
				);

				return $admin;
			}, $admins);

			wp_send_json($admins);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function get_school_classes()
	{
		$current_user = WLSM_M_Role::can(array('manage_transfer_student'));

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['nonce'], 'get-school-classes')) {
				die();
			}

			$new_school_id = isset($_POST['school_id']) ? absint($_POST['school_id']) : 0;

			// Checks if school exists.
			$school = WLSM_M_School::get_school_except($new_school_id, $school_id);

			if (!$school) {
				throw new Exception(esc_html__('School not found.', 'school-management'));
			}

			$classes = WLSM_M_Staff_General::fetch_school_classes($new_school_id);

			$classes = array_map(function ($class) {
				$class->label = WLSM_M_Class::get_label_text($class->label);
				return $class;
			}, $classes);

			wp_send_json($classes);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function get_school_class_sections()
	{
		$current_user = WLSM_M_Role::can(array('manage_transfer_student'));

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['nonce'], 'get-class-sections')) {
				die();
			}

			$new_school_id = isset($_POST['school_id']) ? absint($_POST['school_id']) : 0;
			$new_class_id  = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;

			// Checks if school exists.
			$school = WLSM_M_School::get_school_except($new_school_id, $school_id);

			if (!$school) {
				throw new Exception(esc_html__('School not found.', 'school-management'));
			}

			// Checks if class exists in the school.
			$class_school = WLSM_M_Staff_Class::get_class($new_school_id, $new_class_id);

			if (!$class_school) {
				throw new Exception(esc_html__('Class not found.', 'school-management'));
			}

			$class_school_id = $class_school->ID;

			$sections = WLSM_M_Staff_General::fetch_class_sections($class_school_id);

			$sections = array_map(function ($section) {
				$section->label = WLSM_M_Staff_Class::get_section_label_text($section->label);
				return $section;
			}, $sections);

			wp_send_json($sections);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function add_admission()
	{
		$current_user = WLSM_M_Role::can('manage_admissions');
		self::save_student($current_user);
	}

	public static function edit_student()
	{
		$current_user = WLSM_M_Role::can('manage_students');
		self::save_student($current_user);
	}

	public static function save_student($current_user)
	{
		if (!$current_user) {
			die();
		}

		$current_school = $current_user['school'];

		$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$page_url = WLSM_M_Staff_General::get_students_page_url();

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if ($student_id) {
				if (!wp_verify_nonce($_POST['edit-student-' . $student_id], 'edit-student-' . $student_id)) {
					die();
				}
			} else {
				if (!wp_verify_nonce($_POST['add-admission'], 'add-admission')) {
					die();
				}
			}

			$user_id        = NULL;
			$parent_user_id = NULL;

			// Checks if student exists.
			if ($student_id) {
				$student = WLSM_M_Staff_General::get_student($school_id, $session_id, $student_id, false, false, $restrict_to_section);

				if (!$student) {
					throw new Exception(esc_html__('Student not found.', 'school-management'));
				}

				$user_id        = $student->user_id;
				$parent_user_id = $student->parent_user_id;
			}

			// Registration settings.
			$settings_registration = WLSM_M_Setting::get_settings_registration($school_id);
			$auto_admission_number = $settings_registration['auto_admission_number'];

			// Personal Detail.
			$name            = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
			$gender          = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
			$dob             = isset($_POST['dob']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['dob'])) : NULL;
			$address         = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
			$city            = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
			$state           = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
			$country         = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
			$email           = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '';
			$phone           = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
			$religion        = isset($_POST['religion']) ? sanitize_text_field($_POST['religion']) : '';
			$caste           = isset($_POST['caste']) ? sanitize_text_field($_POST['caste']) : '';
			$blood_group     = isset($_POST['blood_group']) ? sanitize_text_field($_POST['blood_group']) : '';
			$id_number       = isset($_POST['id_number']) ? sanitize_text_field($_POST['id_number']) : '';
			$id_proof        = (isset($_FILES['id_proof']) && is_array($_FILES['id_proof'])) ? $_FILES['id_proof'] : NULL;
			$parent_id_proof = (isset($_FILES['parent_id_proof']) && is_array($_FILES['parent_id_proof'])) ? $_FILES['parent_id_proof'] : NULL;
			$note            = isset($_POST['note']) ? sanitize_text_field($_POST['note']) : '';

			// Admission Detail.
			$admission_date    = isset($_POST['admission_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['admission_date'])) : NULL;
			$class_id          = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;
			$section_id        = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
			$admission_number  = isset($_POST['admission_number']) ? sanitize_text_field($_POST['admission_number']) : '';
			$roll_number       = isset($_POST['roll_number']) ? sanitize_text_field($_POST['roll_number']) : '';
			$photo             = (isset($_FILES['photo']) && is_array($_FILES['photo'])) ? $_FILES['photo'] : NULL;

			// Parent Detail.
			$father_name       = isset($_POST['father_name']) ? sanitize_text_field($_POST['father_name']) : '';
			$father_phone      = isset($_POST['father_phone']) ? sanitize_text_field($_POST['father_phone']) : '';
			$father_occupation = isset($_POST['father_occupation']) ? sanitize_text_field($_POST['father_occupation']) : '';
			$mother_name       = isset($_POST['mother_name']) ? sanitize_text_field($_POST['mother_name']) : '';
			$mother_phone      = isset($_POST['mother_phone']) ? sanitize_text_field($_POST['mother_phone']) : '';
			$mother_occupation = isset($_POST['mother_occupation']) ? sanitize_text_field($_POST['mother_occupation']) : '';

			// Student Login Detail.
			$new_or_existing   = isset($_POST['student_new_or_existing']) ? sanitize_text_field($_POST['student_new_or_existing']) : '';
			$existing_username = isset($_POST['existing_username']) ? sanitize_text_field($_POST['existing_username']) : '';
			$new_login_email   = isset($_POST['new_login_email']) ? sanitize_text_field($_POST['new_login_email']) : '';
			$new_password      = isset($_POST['new_password']) ? sanitize_text_field($_POST['new_password']) : '';
			$username          = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
			$login_email       = isset($_POST['login_email']) ? sanitize_text_field($_POST['login_email']) : '';
			$password          = isset($_POST['password']) ? $_POST['password'] : '';

			// Parent / Guardian Login Detail.
			$parent_new_or_existing   = isset($_POST['parent_new_or_existing']) ? sanitize_text_field($_POST['parent_new_or_existing']) : '';
			$parent_existing_username = isset($_POST['parent_existing_username']) ? sanitize_text_field($_POST['parent_existing_username']) : '';
			$parent_new_login_email   = isset($_POST['parent_new_login_email']) ? sanitize_text_field($_POST['parent_new_login_email']) : '';
			$parent_new_password      = isset($_POST['parent_new_password']) ? sanitize_text_field($_POST['parent_new_password']) : '';
			$parent_username          = isset($_POST['parent_username']) ? sanitize_text_field($_POST['parent_username']) : '';
			$parent_login_email       = isset($_POST['parent_login_email']) ? sanitize_text_field($_POST['parent_login_email']) : '';
			$parent_password          = isset($_POST['parent_password']) ? $_POST['parent_password'] : '';

			// Fees.
			$fee_id     = (isset($_POST['fee_id']) && is_array($_POST['fee_id'])) ? $_POST['fee_id'] : array();
			$fee_label  = (isset($_POST['fee_label']) && is_array($_POST['fee_label'])) ? $_POST['fee_label'] : array();
			$fee_period = (isset($_POST['fee_period']) && is_array($_POST['fee_period'])) ? $_POST['fee_period'] : array();
			$fee_amount = (isset($_POST['fee_amount']) && is_array($_POST['fee_amount'])) ? $_POST['fee_amount'] : array();

			// Transport Detail.
			$route_vehicle_id = isset($_POST['route_vehicle_id']) ? absint($_POST['route_vehicle_id']) : 0;

			// Status.
			$is_active = isset($_POST['is_active']) ? (bool) $_POST['is_active'] : 1;

			if ($student_id) {
				$class_id = $student->class_id;
			} else {
				$inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;
			}

			// Start validation.
			$errors = array();

			// Personal Detail.
			if (empty($name)) {
				$errors['name'] = esc_html__('Please specify student name.', 'school-management');
			}
			if (strlen($name) > 60) {
				$errors['name'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}
			if (!empty($religion) && strlen($religion) > 40) {
				$errors['religion'] = esc_html__('Maximum length cannot exceed 40 characters.', 'school-management');
			}
			if (!empty($caste) && strlen($caste) > 40) {
				$errors['caste'] = esc_html__('Maximum length cannot exceed 40 characters.', 'school-management');
			}
			if (!empty($phone) && strlen($phone) > 40) {
				$errors['phone'] = esc_html__('Maximum length cannot exceed 40 characters.', 'school-management');
			}
			if (empty($email)) {
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$errors['email'] = esc_html__('Please provide a valid email.', 'school-management');
				} elseif (strlen($email) > 60) {
					$errors['email'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
				}
			}
			if (!empty($city) && strlen($city) > 60) {
				$errors['city'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}
			if (!empty($state) && strlen($state) > 60) {
				$errors['state'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}
			if (!empty($country) && strlen($country) > 60) {
				$errors['country'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}
			if (!in_array($gender, array_keys(WLSM_Helper::gender_list()))) {
				throw new Exception(esc_html__('Please specify gender.', 'school-management'));
			}
			if (!empty($blood_group) && !in_array($blood_group, array_keys(WLSM_Helper::blood_group_list()))) {
				throw new Exception(esc_html__('Please specify blood group.', 'school-management'));
			}
			if (!empty($dob)) {
				$dob = $dob->format('Y-m-d');
			} else {
				$dob = NULL;
			}

			// Admission Detail.
			if (empty($admission_date)) {
				$errors['admission_date'] = esc_html__('Please provide admission date.', 'school-management');
			} else {
				$admission_date = $admission_date->format('Y-m-d');
			}

			if (!$auto_admission_number) {
				if (empty($admission_number)) {
					$errors['admission_number'] = esc_html__('Please provide admission number.', 'school-management');
				}
				if (strlen($admission_number) > 60) {
					$errors['admission_number'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
				}
			}
			if (!empty($roll_number) && strlen($roll_number) > 30) {
				$errors['roll_number'] = esc_html__('Maximum length cannot exceed 30 characters.', 'school-management');
			}
			if (!$student_id) {
				if (empty($class_id)) {
					$errors['class_id'] = esc_html__('Please select a class.', 'school-management');
					wp_send_json_error($errors);
				}
			}
			if (empty($section_id)) {
				$errors['section_id'] = esc_html__('Please select section.', 'school-management');
				wp_send_json_error($errors);
			}
			if (isset($photo['tmp_name']) && !empty($photo['tmp_name'])) {
				if (!WLSM_Helper::is_valid_file($photo, 'image')) {
					$errors['photo'] = esc_html__('Please provide photo in JPG, JPEG or PNG format.', 'school-management');
				}
			}
			if (isset($id_proof['tmp_name']) && !empty($id_proof['tmp_name'])) {
				if (!WLSM_Helper::is_valid_file($id_proof, 'attachment')) {
					$errors['id_proof'] = esc_html__('File type is not supported.', 'school-management');
				}
			}
			if (isset($parent_id_proof['tmp_name']) && !empty($parent_id_proof['tmp_name'])) {
				if (!WLSM_Helper::is_valid_file($parent_id_proof, 'attachment')) {
					$errors['parent_id_proof'] = esc_html__('File type is not supported.', 'school-management');
				}
			}

			// Parent Detail.
			if (!empty($father_name) && strlen($father_name) > 60) {
				$errors['father_name'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}
			if (!empty($father_phone) && strlen($father_phone) > 40) {
				$errors['father_phone'] = esc_html__('Maximum length cannot exceed 40 characters.', 'school-management');
			}
			if (!empty($father_occupation) && strlen($father_occupation) > 60) {
				$errors['father_occupation'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}
			if (!empty($mother_name) && strlen($mother_name) > 60) {
				$errors['mother_name'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}
			if (!empty($mother_phone) && strlen($mother_phone) > 40) {
				$errors['mother_phone'] = esc_html__('Maximum length cannot exceed 40 characters.', 'school-management');
			}
			if (!empty($mother_occupation) && strlen($mother_occupation) > 60) {
				$errors['mother_occupation'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}

			// Checks if class exists in the school.
			if ($student_id) {
				$class_school_id = $student->class_school_id;
			} else {
				$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);
				if (!$class_school) {
					$errors['class_id'] = esc_html__('Class not found.', 'school-management');
					wp_send_json_error($errors);
				}

				$class_school_id = $class_school->ID;
			}

			// Checks if section exists.
			$section = WLSM_M_Staff_Class::get_section($school_id, $section_id, $class_school_id);
			if (!$section) {
				$errors['section_id'] = esc_html__('Section not found.', 'school-management');
				wp_send_json_error($errors);
			}

			if (!$auto_admission_number) {
				// Checks if admission number already exists for this session.
				if (!$student_id) {
					$student_exists = WLSM_M_Staff_General::get_admitted_student_id($school_id, $session_id, $admission_number);
				} else {
					$student_exists = WLSM_M_Staff_General::get_admitted_student_id($school_id, $session_id, $admission_number, $student_id);
				}

				if ($student_exists) {
					$errors['admission_number'] = esc_html__('Admission number already exists in this session.', 'school-management');
				}
			}

			// Checks if roll number already exists in the class for this session.
			if (!empty($roll_number)) {
				if ($student_id) {
					$student_exists = WLSM_M_Staff_General::get_student_with_roll_number($school_id, $session_id, $class_id, $roll_number, $student_id);
				} else {
					$student_exists = WLSM_M_Staff_General::get_student_with_roll_number($school_id, $session_id, $class_id, $roll_number);
				}

				if ($student_exists) {
					$errors['roll_number'] = esc_html__('Roll number already exists in this class.', 'school-management');
				}
			}

			// Student Login Detail.
			if ('existing_user' === $new_or_existing) {
				if (!$user_id) {
					if (empty($existing_username)) {
						$errors['existing_username'] = esc_html__('Please provide existing username.', 'school-management');
					}
				} else {
					if (empty($new_login_email)) {
						$errors['new_login_email'] = esc_html__('Please provide login email.', 'school-management');
					}
				}
			} elseif ('new_user' === $new_or_existing) {
				if (empty($username)) {
					$errors['username'] = esc_html__('Please provide username.', 'school-management');
				}
				if (empty($login_email)) {
					$errors['login_email'] = esc_html__('Please provide login email.', 'school-management');
				}
				if (!filter_var($login_email, FILTER_VALIDATE_EMAIL)) {
					$errors['login_email'] = esc_html__('Please provide a valid email.', 'school-management');
				}
				if (empty($password)) {
					$errors['password'] = esc_html__('Please provide login password.', 'school-management');
				}
			}

			// Parent / Guardian Login Detail.
			if ('existing_user' === $parent_new_or_existing) {
				if (!$parent_user_id) {
					if (empty($parent_existing_username)) {
						$errors['parent_existing_username'] = esc_html__('Please provide existing username.', 'school-management');
					}
				} else {
					if (empty($parent_new_login_email)) {
						$errors['parent_new_login_email'] = esc_html__('Please provide login email.', 'school-management');
					}
				}
			} elseif ('new_user' === $parent_new_or_existing) {
				if (empty($parent_username)) {
					$errors['parent_username'] = esc_html__('Please provide username.', 'school-management');
				}
				if (empty($parent_login_email)) {
					$errors['parent_login_email'] = esc_html__('Please provide login email.', 'school-management');
				}
				if (!filter_var($parent_login_email, FILTER_VALIDATE_EMAIL)) {
					$errors['parent_login_email'] = esc_html__('Please provide a valid email.', 'school-management');
				}
				if (empty($parent_password)) {
					$errors['parent_password'] = esc_html__('Please provide login password.', 'school-management');
				}
			}

			// Student fees.
			if (count($fee_label)) {
				if (1 !== count(array_unique(array(count($fee_label), count($fee_period), count($fee_amount))))) {
					wp_send_json_error(esc_html__('Invalid fees.', 'school-management'));
				} elseif (count($fee_label) !== count(array_unique($fee_label))) {
					wp_send_json_error(esc_html__('Fee type must be different.', 'school-management'));
				} else {
					foreach ($fee_label as $key => $value) {
						$fee_label[$key] = sanitize_text_field($fee_label[$key]);
						$fee_period[$key]  = sanitize_text_field($fee_period[$key]);
						$fee_amount[$key] = WLSM_Config::sanitize_money($fee_amount[$key]);

						if (empty($fee_label[$key])) {
							wp_send_json_error(esc_html__('Please specify fee type.', 'school-management'));
						} elseif (strlen($fee_label[$key]) > 100) {
							wp_send_json_error(esc_html__('Maximum length cannot exceed 100 characters.', 'school-management'));
						}

						if (!in_array($fee_period[$key], array_keys(WLSM_Helper::fee_period_list()))) {
							wp_send_json_error(esc_html__('Please specify fee period.', 'school-management'));
						}

						if ($fee_amount[$key] < 0) {
							$fee_amount[$key] = 0;
						}
					}
				}
			}

			// Transport Detail.
			if (!empty($route_vehicle_id)) {
				$route_vehicle = WLSM_M_Staff_Transport::get_route_vehicle($school_id, $route_vehicle_id);
				if (!$route_vehicle) {
					$errors['route_vehicle_id'] = esc_html__('Please select valid transport route vehicle.', 'school-management');
				}
			} else {
				$route_vehicle_id = NULL;
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

				if ($student_id) {
					$message = esc_html__('Student updated successfully.', 'school-management');
				} else {
					$message = esc_html__('Admission added successfully.', 'school-management');
				}

				// Parent user data.
				if ('existing_user' === $parent_new_or_existing) {
					if (!$parent_user_id) {
						// Existing user.
						$parent_user = get_user_by('login', $parent_existing_username);
						if (!$parent_user) {
							throw new Exception(esc_html__('Username does not exist.', 'school-management'));
						}

						$parent_user_id = $parent_user->ID;

						// Check if user has a student record.
						$parent_user_has_student_record = $wpdb->get_row($wpdb->prepare('SELECT sr.ID FROM ' . WLSM_STUDENT_RECORDS . ' as sr WHERE sr.user_id = %d', $parent_user_id));

						if ($parent_user_has_student_record) {
							throw new Exception(esc_html__('The user already has a student record.', 'school-management'));
						}
					} else {
						// Update email and password of existing user.
						$parent_user_data = array(
							'ID'         => $parent_user_id,
							'user_email' => $parent_new_login_email,
						);

						if (!empty($parent_new_password)) {
							$parent_user_data['user_pass'] = $parent_new_password;
						}

						$parent_user_id = wp_update_user($parent_user_data);
						if (is_wp_error($parent_user_id)) {
							throw new Exception($parent_user_id->get_error_message());
						}
					}
				} elseif ('new_user' === $parent_new_or_existing) {
					// New user.
					$parent_user_data = array(
						'user_email' => $parent_login_email,
						'user_login' => $parent_username,
						'user_pass'  => $parent_password,
					);

					$parent_user_id = wp_insert_user($parent_user_data);
					if (is_wp_error($parent_user_id)) {
						throw new Exception($parent_user_id->get_error_message());
					}
				} else {
					$parent_user_id = NULL;
				}

				// Student user data.
				$update_student_user_id = NULL;
				if ('existing_user' === $new_or_existing) {
					if (!$user_id) {
						// Existing user.
						$user = get_user_by('login', $existing_username);
						if (!$user) {
							throw new Exception(esc_html__('Username does not exist.', 'school-management'));
						}

						$user_id = $user->ID;

						// Check if user is a parent.
						$user_is_parent = $wpdb->get_row($wpdb->prepare('SELECT sr.ID FROM ' . WLSM_STUDENT_RECORDS . ' as sr WHERE sr.parent_user_id = %d', $user_id));

						if ($user_is_parent) {
							throw new Exception(esc_html__('The user has a parent record.', 'school-management'));
						}

						// Check if user already has a student record.
						if ($student_id) {
							$user_has_student_record = $wpdb->get_row($wpdb->prepare('SELECT sr.ID FROM ' . WLSM_STUDENT_RECORDS . ' as sr WHERE sr.user_id = %d AND sr.ID != %d', $user_id, $student_id));
						} else {
							$user_has_student_record = $wpdb->get_row($wpdb->prepare('SELECT sr.ID FROM ' . WLSM_STUDENT_RECORDS . ' as sr WHERE sr.user_id = %d', $user_id));
						}

						if ($user_has_student_record) {
							throw new Exception(esc_html__('The user already has a student record.', 'school-management'));
						}

						if (!$student_id) {
							$staff = WLSM_M_Admin::staff_in_school($school_id, $user_id);

							if ($staff) {
								throw new Exception(
									/* translators: %s: role */
									sprintf(esc_html__('User already exists with this username having a role of "%s".', 'school-management'), WLSM_M_Role::get_role_text($staff->role))
								);
							}

							if (user_can($user_id, WLSM_ADMIN_CAPABILITY)) {
								throw new Exception(esc_html__('User is a multi-school administrator.', 'school-management'));
							}
						}
					} else {
						// Update email and password of existing user.
						$user_data = array(
							'ID'         => $user_id,
							'user_email' => $new_login_email,
						);

						if (!empty($new_password)) {
							$user_data['user_pass'] = $new_password;
						}

						$user_id = wp_update_user($user_data);
						if (is_wp_error($user_id)) {
							throw new Exception($user_id->get_error_message());
						}
					}
				} elseif ('new_user' === $new_or_existing) {
					// New user.
					$user_data = array(
						'user_email' => $login_email,
						'user_login' => $username,
						'user_pass'  => $password,
					);

					$user_id = wp_insert_user($user_data);
					if (is_wp_error($user_id)) {
						throw new Exception($user_id->get_error_message());
					}
				} else {
					$user_id = NULL;
				}

				$update_student_user_id = $user_id;

				// Student record data.
				$student_record_data = array(
					'admission_number'  => $admission_number,
					'name'              => $name,
					'gender'            => $gender,
					'dob'               => $dob,
					'phone'             => $phone,
					'email'             => $email,
					'address'           => $address,
					'city'              => $city,
					'state'             => $state,
					'country'           => $country,
					'religion'          => $religion,
					'caste'             => $caste,
					'blood_group'       => $blood_group,
					'id_number'         => $id_number,
					'note'              => $note,
					'father_name'       => $father_name,
					'father_phone'      => $father_phone,
					'father_occupation' => $father_occupation,
					'mother_name'       => $mother_name,
					'mother_phone'      => $mother_phone,
					'mother_occupation' => $mother_occupation,
					'admission_date'    => $admission_date,
					'roll_number'       => $roll_number,
					'section_id'        => $section_id,
					'route_vehicle_id'  => $route_vehicle_id,
					'user_id'           => $update_student_user_id,
					'parent_user_id'    => $parent_user_id,
					'is_active'         => $is_active,
				);

				if ($auto_admission_number) {
					if ($student_id) {
						// If editing student and auto generate admission number then don't update admission number.
						unset($student_record_data['admission_number']);
					} else {
						// If new admission and auto generate admission number.
						$student_record_data['admission_number'] = WLSM_M_Staff_General::get_admission_number($school_id, $session_id);
					}
				}

				if ($student_id) {
					$student_record_data['photo_id']        = $student->photo_id;
					$student_record_data['id_proof']        = $student->id_proof;
					$student_record_data['parent_id_proof'] = $student->parent_id_proof;
				}

				if (!empty($photo)) {
					$photo = media_handle_upload('photo', 0);
					if (is_wp_error($photo)) {
						throw new Exception($photo->get_error_message());
					}
					$student_record_data['photo_id'] = $photo;
				}

				if (!empty($id_proof)) {
					$id_proof = media_handle_upload('id_proof', 0);
					if (is_wp_error($id_proof)) {
						throw new Exception($id_proof->get_error_message());
					}
					$student_record_data['id_proof'] = $id_proof;
				}

				if (!empty($parent_id_proof)) {
					$parent_id_proof = media_handle_upload('parent_id_proof', 0);
					if (is_wp_error($parent_id_proof)) {
						throw new Exception($parent_id_proof->get_error_message());
					}
					$student_record_data['parent_id_proof'] = $parent_id_proof;
				}

				if ($student_id) {
					$student_record_data['updated_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->update(WLSM_STUDENT_RECORDS, $student_record_data, array('ID' => $student_id));

					$is_insert = false;
				} else {
					$student_record_data['session_id'] = $session_id;

					$enrollment_number = WLSM_M_Staff_General::get_enrollment_number($school_id);

					$student_record_data['enrollment_number'] = $enrollment_number;

					$student_record_data['added_by'] = get_current_user_id();

					$student_record_data['created_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->insert(WLSM_STUDENT_RECORDS, $student_record_data);

					$new_student_id = $wpdb->insert_id;
					$student_id     = $new_student_id;

					$is_insert = true;

					$message .= '&nbsp;<a href="' . esc_url($page_url) . '&action=save&id=' . $student_id . '">' . esc_html__('Edit Student', 'school-management') . '</a>';

					if ($inquiry_id) {
						// Update inquiry status to inactive.
						$inquiry_data = array(
							'is_active'  => 0,
							'updated_at' => current_time('Y-m-d H:i:s')
						);

						$wpdb->update(
							WLSM_INQUIRIES,
							$inquiry_data,
							array('ID' => $inquiry_id, 'school_id' => $school_id)
						);
					}
				}

				// Fees.
				$place_holders_fee_labels = array();

				$fee_order = 10;
				if (count($fee_label)) {
					foreach ($fee_label as $key => $value) {
						array_push($place_holders_fee_labels, '%s');
						$fee_order++;

						// Student fee data.
						$student_fee_data = array(
							'amount'    => $fee_amount[$key],
							'period'    => $fee_period[$key],
							'fee_order' => $fee_order,
						);

						if ($is_insert) {
							// Student fee does not exist, insert student fee.
							$student_fee_data['label']             = $value;
							$student_fee_data['student_record_id'] = $student_id;

							$student_fee_data['created_at'] = current_time('Y-m-d H:i:s');

							$success = $wpdb->insert(WLSM_STUDENT_FEES, $student_fee_data);

							// Invoice data.
							$invoice_data = array(
								'label'           => $student_fee_data['label'],
								'amount'          => $student_fee_data['amount'],
								'date_issued'     => $student_fee_data['created_at'],
								'due_date'        => $student_fee_data['created_at'],
								'partial_payment' => 0,
							);

							$invoice_number = WLSM_M_Invoice::get_invoice_number($school_id);

							$invoice_data['invoice_number']    = $invoice_number;
							$invoice_data['student_record_id'] = $new_student_id;

							$invoice_data['added_by'] = $user_id;

							$invoice_data['created_at'] = $student_fee_data['created_at'];

							$success = $wpdb->insert(WLSM_INVOICES, $invoice_data);

							if (false === $success) {
								throw new Exception($wpdb->last_error);
							}
						} else {
							// Check if student fee exists for this fee label.
							$student_fee_exist = $wpdb->get_row($wpdb->prepare('SELECT sft.ID FROM ' . WLSM_STUDENT_FEES . ' as sft WHERE sft.student_record_id = %d AND sft.label = %s', $student_id, $value));

							if ($student_fee_exist) {
								// Student fee exists, update student fee.
								$student_fee_data['updated_at'] = current_time('Y-m-d H:i:s');

								$success = $wpdb->update(WLSM_STUDENT_FEES, $student_fee_data, array('ID' => $student_fee_exist->ID, 'student_record_id' => $student_id));
							} else {
								// Student fee does not exist, insert student fee.
								$student_fee_data['label']             = $value;
								$student_fee_data['student_record_id'] = $student_id;

								$student_fee_data['created_at'] = current_time('Y-m-d H:i:s');

								$success = $wpdb->insert(WLSM_STUDENT_FEES, $student_fee_data);
							}
						}
					}

					if (!$is_insert) {
						// Delete student fees not in fee_label array.
						$student_id_fee_labels = array_merge(array($student_id), array_values($fee_label));

						$success = $wpdb->query($wpdb->prepare('DELETE FROM ' . WLSM_STUDENT_FEES . ' WHERE student_record_id = %d AND label NOT IN (' . implode(', ', $place_holders_fee_labels) . ')', $student_id_fee_labels));
					}
				} else {
					// Delete student fees not in fee_label array.
					$success = $wpdb->query($wpdb->prepare('DELETE FROM ' . WLSM_STUDENT_FEES . ' WHERE student_record_id = %d', $student_id));
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				if (isset($new_student_id)) {
					// Notify for student admission.
					$data = array(
						'school_id'  => $school_id,
						'session_id' => $session_id,
						'student_id' => $new_student_id,
						'password'   => $password,
					);

					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_student_admission', $data);
					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_student_admission_to_parent', $data);
				}

				wp_send_json_success(array('message' => $message));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function get_students()
	{
		$current_user = WLSM_M_Role::can('manage_students');

		if (!$current_user) {
			die();
		}

		$current_school = $current_user['school'];

		$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);

		$can_delete_students = WLSM_M_Role::check_permission(array('delete_students'), $current_school['permissions']);

		$school_id     = $current_user['school']['id'];
		$session_id    = $current_user['session']['ID'];
		$session_label = $current_user['session']['label'];

		if (!wp_verify_nonce($_POST['get-students'], 'get-students')) {
			die();
		}

		$gdpr_enable = get_option('wlsm_gdpr_enable');

		$from_table = isset($_POST['from_table']) ? (bool) ($_POST['from_table']) : false;

		$output = array(
			'draw'            => 1,
			'recordsTotal'    => 0,
			'recordsFiltered' => 0,
			'data'            => array(),
		);

		$search_students_by = isset($_POST['search_students_by']) ? sanitize_text_field($_POST['search_students_by']) : '';

		$search_field   = isset($_POST['search_field']) ? sanitize_text_field($_POST['search_field']) : '';
		$search_keyword = isset($_POST['search_keyword']) ? sanitize_text_field($_POST['search_keyword']) : '';

		$class_id   = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;
		$section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;

		try {
			ob_start();
			global $wpdb;

			// Start validation.
			$errors = array();

			if (!in_array($search_students_by, array('search_by_keyword', 'search_by_class'))) {
				throw new Exception(esc_html__('Please specify search criteria.', 'school-management'));
			}

			if ('search_by_keyword' === $search_students_by) {
				if (!empty($search_field) && empty($search_keyword)) {
					$errors['search_keyword'] = esc_html__('Please enter search keyword.', 'school-management');
				} elseif (!empty($search_keyword) && empty($search_field)) {
					$errors['search_field'] = esc_html__('Please specify search field.', 'school-management');
				}

				$filter = array(
					'search_field'   => $search_field,
					'search_keyword' => $search_keyword,
				);
			} else {
				if (empty($class_id)) {
					$errors['class_id'] = esc_html__('Please select a class.', 'school-management');
				}

				$filter = array(
					'class_id'   => $class_id,
					'section_id' => $section_id,
				);
			}
		} catch (Exception $exception) {
			if ($from_table) {
				echo json_encode($output);
				die();
			}
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		if (count($errors) < 1) {
			if (!$from_table) {
				wp_send_json_success();
			}
			try {
				$filter['search_by'] = $search_students_by;

				$page_url = WLSM_M_Staff_General::get_students_page_url();

				$query = WLSM_M_Staff_General::fetch_students_query($school_id, $session_id, $filter, $restrict_to_section);

				$query_filter = $query;

				// Grouping.
				$group_by = ' ' . WLSM_M_Staff_General::fetch_students_query_group_by();

				$query        .= $group_by;
				$query_filter .= $group_by;

				// Searching.
				$condition = '';
				if (isset($_POST['search']['value'])) {
					$search_value = sanitize_text_field($_POST['search']['value']);
					if ('' !== $search_value) {
						$condition .= '' .
							'(sr.name LIKE "%' . $search_value . '%") OR ' .
							'(sr.admission_number LIKE "%' . $search_value . '%") OR ' .
							'(sr.enrollment_number LIKE "%' . $search_value . '%") OR ' .
							'(sr.phone LIKE "%' . $search_value . '%") OR ' .
							'(sr.email LIKE "%' . $search_value . '%") OR ' .
							'(sr.father_name LIKE "%' . $search_value . '%") OR ' .
							'(sr.father_phone LIKE "%' . $search_value . '%") OR ' .
							'(u.user_email LIKE "%' . $search_value . '%") OR ' .
							'(u.user_login LIKE "%' . $search_value . '%") OR ' .
							'(c.label LIKE "%' . $search_value . '%") OR ' .
							'(se.label LIKE "%' . $search_value . '%") OR ' .
							'(sr.roll_number LIKE "%' . $search_value . '%")';

						$search_value_lowercase = strtolower($search_value);
						if (preg_match('/^inac(|t|ti|tiv|tive)$/', $search_value_lowercase)) {
							$is_active = 0;
						} elseif (preg_match('/^acti(|v|ve)$/', $search_value_lowercase)) {
							$is_active = 1;
						}
						if (isset($is_active)) {
							$condition .= ' OR (sr.is_active = ' . $is_active . ')';
						}

						$admission_date = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

						if ($admission_date) {
							$format_admission_date = 'Y-m-d';
						} else {
							if ('d-m-Y' === WLSM_Config::date_format()) {
								if (!$admission_date) {
									$admission_date        = DateTime::createFromFormat('m-Y', $search_value);
									$format_admission_date = 'Y-m';
								}
							} elseif ('d/m/Y' === WLSM_Config::date_format()) {
								if (!$admission_date) {
									$admission_date        = DateTime::createFromFormat('m/Y', $search_value);
									$format_admission_date = 'Y-m';
								}
							} elseif ('Y-m-d' === WLSM_Config::date_format()) {
								if (!$admission_date) {
									$admission_date        = DateTime::createFromFormat('Y-m', $search_value);
									$format_admission_date = 'Y-m';
								}
							} elseif ('Y/m/d' === WLSM_Config::date_format()) {
								if (!$admission_date) {
									$admission_date        = DateTime::createFromFormat('Y/m', $search_value);
									$format_admission_date = 'Y-m';
								}
							}

							if (!$admission_date) {
								$admission_date        = DateTime::createFromFormat('Y', $search_value);
								$format_admission_date = 'Y';
							}
						}

						if ($admission_date && isset($format_admission_date)) {
							$admission_date = $admission_date->format($format_admission_date);
							$admission_date = ' OR (sr.admission_date LIKE "%' . $admission_date . '%")';

							$condition .= $admission_date;
						}

						$query_filter .= (' HAVING ' . $condition);
					}
				}

				// Ordering.
				$columns = array('sr.name', 'sr.name', 'sr.admission_number', 'sr.phone', 'sr.email', 'c.label', 'se.label', 'sr.roll_number', 'sr.father_name', 'sr.father_phone', 'u.user_email', 'u.user_login', 'sr.admission_date', 'sr.enrollment_number', 'sr.is_active', 'sr.from_front');
				if ($gdpr_enable) {
					array_push($columns, 'sr.gdpr_agreed');
				}
				if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
					$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
					$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

					$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
				} else {
					$query_filter .= ' ORDER BY sr.ID DESC';
				}

				// Limiting.
				$limit = '';
				if (-1 != $_POST['length']) {
					$start  = absint($_POST['start']);
					$length = absint($_POST['length']);

					$limit  = ' LIMIT ' . $start . ', ' . $length;
				}

				// Total query.
				$rows_query = WLSM_M_Staff_General::fetch_students_query_count($school_id, $session_id, $filter, $restrict_to_section);

				// Total rows count.
				$total_rows_count = $wpdb->get_var($rows_query);

				// Filtered rows count.
				if ($condition) {
					$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
				} else {
					$filter_rows_count = $total_rows_count;
				}

				// Filtered limit rows.
				$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

				$data = array();
				if (count($filter_rows_limit)) {
					foreach ($filter_rows_limit as $row) {
						// Table columns.
						$student_data = array(
							'<input type="checkbox" class="wlsm-select-single wlsm-bulk-students" name="bulk_data[]" value="' . esc_attr($row->ID) . '">',
							esc_html(WLSM_M_Staff_Class::get_name_text($row->student_name)),
							esc_html(WLSM_M_Staff_Class::get_admission_no_text($row->admission_number)),
							esc_html(WLSM_M_Staff_Class::get_phone_text($row->phone)),
							esc_html(WLSM_M_Staff_Class::get_name_text($row->email)),
							esc_html(WLSM_M_Class::get_label_text($row->class_label)),
							esc_html(WLSM_M_Staff_Class::get_section_label_text($row->section_label)),
							esc_html(WLSM_M_Staff_Class::get_roll_no_text($row->roll_number)),
							esc_html(WLSM_M_Staff_Class::get_name_text($row->father_name)),
							esc_html(WLSM_M_Staff_Class::get_phone_text($row->father_phone)),
							esc_html(WLSM_M_Staff_Class::get_name_text($row->login_email)),
							esc_html(WLSM_M_Staff_Class::get_name_text($row->username)),
							esc_html(WLSM_Config::get_date_text($row->admission_date)),
							esc_html($row->enrollment_number),
							esc_html(WLSM_M_Staff_Class::get_status_text($row->is_active)),
							esc_html(WLSM_M_Staff_Class::get_from_front_text($row->from_front))
						);

						if ($gdpr_enable) {
							array_push($student_data, esc_html(WLSM_M_Staff_General::get_gdpr_text($row->gdpr_agreed)));
						}

						$other_data = array(
							'<a class="text-primary wlsm-view-session-records" data-nonce="' . esc_attr(wp_create_nonce('view-session-records-' . $row->ID)) . '" data-student="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Session Records', 'school-management') . '" data-close="' . esc_attr__('Close', 'school-management') . '"><span class="dashicons dashicons-search"></span></a>',
							'<a class="text-success wlsm-print-id-card" data-nonce="' . esc_attr(wp_create_nonce('print-id-card-' . $row->ID)) . '" data-id-card="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Print ID Card', 'school-management') . '" data-close="' . esc_attr__('Close', 'school-management') . '"><i class="fas fa-print"></i></a>',
							'<a class="text-primary wlsm-view-student-detail" data-nonce="' . esc_attr(wp_create_nonce('view-student-detail-' . $row->ID)) . '" data-student="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Student Detail Report', 'school-management') . '" data-close="' . esc_attr__('Close', 'school-management') . '"><i class="fas fa-print"></i></a>',
							'<a class="text-primary wlsm-view-attendance-report" data-nonce="' . esc_attr(wp_create_nonce('view-attendance-report-' . $row->ID)) . '" data-student="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Attendance Report', 'school-management') . '" data-close="' . esc_attr__('Close', 'school-management') . '"><i class="fas fa-calendar-alt"></i></a>',
							'<a class="text-primary wlsm-print-fee-structure" data-nonce="' . esc_attr(wp_create_nonce('print-fee-structure-' . $row->ID)) . '" data-fee-structure="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Print Student Fee Structure', 'school-management') . '" data-close="' . esc_attr__('Close', 'school-management') . '"><i class="fas fa-print"></i></a>',
							'<a class="text-primary" href="' . esc_url($page_url . "&action=save&id=" . $row->ID) . '"><span class="dashicons dashicons-edit"></span></a>' . ($can_delete_students ? ('&nbsp;&nbsp;
							<a class="text-danger wlsm-delete-student" data-nonce="' . esc_attr(wp_create_nonce('delete-student-' . $row->ID)) . '" data-student="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '" data-message-content="' .
								sprintf(
									/* translators: %s: session label */
									esc_attr__('This will delete student record for the session %s.', 'school-management'),
									esc_html(WLSM_M_Session::get_label_text($session_label))
								) . '" data-cancel="' . esc_attr__('Cancel', 'school-management') . '" data-submit="' . esc_attr__('Confirm', 'school-management') . '"><span class="dashicons dashicons-trash"></span></a>') : '')
						);

						$data[] = array_merge($student_data, $other_data);
					}
				}

				$output = array(
					'draw'            => intval($_POST['draw']),
					'recordsTotal'    => $total_rows_count,
					'recordsFiltered' => $filter_rows_count,
					'data'            => $data,
					'export'          => array(
						'nonce'  => wp_create_nonce('export-staff-students-table'),
						'action' => 'wlsm-export-staff-students-table',
						'filter' => json_encode(
							array(
								'search_students_by' => $search_students_by,
								'search_field'       => $search_field,
								'search_keyword'     => $search_keyword,
								'class_id'           => $class_id,
								'section_id'         => $section_id,
							)
						)
					)
				);

				echo json_encode($output);
				die();
			} catch (Exception $exception) {
				if ($from_table) {
					echo json_encode($output);
					die();
				}
				wp_send_json_error($exception->getMessage());
			}
		}

		if ($from_table) {
			echo json_encode($output);
			die();
		}
		wp_send_json_error($errors);
	}

	public static function delete_student()
	{
		$current_user = WLSM_M_Role::can('delete_students');

		if (!$current_user) {
			die();
		}

		WLSM_Helper::check_demo();

		$current_school = $current_user['school'];

		$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['delete-student-' . $student_id], 'delete-student-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::get_student($school_id, $session_id, $student_id, false, false, $restrict_to_section);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			$success = $wpdb->delete(WLSM_STUDENT_RECORDS, array('ID' => $student_id));
			$message = esc_html__('Student record deleted successfully.', 'school-management');

			$exception = ob_get_clean();
			if (!empty($exception)) {
				throw new Exception($exception);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function view_session_records()
	{
		$current_user = WLSM_M_Role::can('manage_students');

		if (!$current_user) {
			die();
		}

		$current_school = $current_user['school'];

		$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$session_label = $current_user['session']['label'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['view-session-records-' . $student_id], 'view-session-records-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id, $restrict_to_section);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
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

		$student_old_records = array();
		$student_new_records = array();

		$student_current_session_record = array(
			array(
				'is_current'          => true,
				'session_label'       => $session_label,
				'enrollment_number'   => $student->enrollment_number,
				'student_name'        => WLSM_M_Staff_Class::get_name_text($student->student_name),
				'class_label'         => WLSM_M_Class::get_label_text($student->class_label),
				'section_label'       => WLSM_M_Staff_Class::get_section_label_text($student->section_label),
				'roll_number'         => WLSM_M_Staff_Class::get_roll_no_text($student->roll_number),
				'from_student_record' => $student->from_student_record,
				'to_school'           => $student->to_school,
			),
		);

		$to_student_record   = $student->ID;
		$from_student_record = $student->ID;

		while ($to_student_record = self::student_old_record_exists($to_student_record)) {
			$student = WLSM_M_Staff_General::get_student_record($school_id, $to_student_record);
			if ($student) {
				$student_old_record_data = array(
					'is_current'          => false,
					'session_label'       => $student->session_label,
					'enrollment_number'   => $student->enrollment_number,
					'student_name'        => WLSM_M_Staff_Class::get_name_text($student->student_name),
					'class_label'         => WLSM_M_Class::get_label_text($student->class_label),
					'section_label'       => WLSM_M_Staff_Class::get_section_label_text($student->section_label),
					'roll_number'         => WLSM_M_Staff_Class::get_roll_no_text($student->roll_number),
					'from_student_record' => $student->from_student_record,
					'to_school'           => $student->to_school,
				);
				array_push($student_old_records, $student_old_record_data);
			}
		}

		while ($from_student_record = self::student_new_record_exists($from_student_record)) {
			$student = WLSM_M_Staff_General::get_student_record($school_id, $from_student_record);
			if ($student) {
				$student_new_record_data = array(
					'is_current'          => false,
					'session_label'       => $student->session_label,
					'enrollment_number'   => $student->enrollment_number,
					'student_name'        => WLSM_M_Staff_Class::get_name_text($student->student_name),
					'class_label'         => WLSM_M_Class::get_label_text($student->class_label),
					'section_label'       => WLSM_M_Staff_Class::get_section_label_text($student->section_label),
					'roll_number'         => WLSM_M_Staff_Class::get_roll_no_text($student->roll_number),
					'from_student_record' => $student->from_student_record,
					'to_school'           => $student->to_school,
				);
				array_push($student_new_records, $student_new_record_data);
			}
		}

		$student_records = array_merge(array_reverse($student_old_records), $student_current_session_record, $student_new_records);

		ob_start();
?>
		<div class="wlsm">
			<?php
			foreach ($student_records as $student_record) {
			?>
				<ul class="border-bottom">
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e('Session', 'school-management'); ?></span>:
						<span <?php if ($student_record['is_current']) {
									echo 'class="wlsm-font-bold text-primary"';
								} ?>>
							<?php echo esc_html($student_record['session_label']); ?>
						</span>
						<?php if ($student_record['from_student_record']) { ?>
							<span class="text-dark">
								<?php
								printf(
									wp_kses(
										/* translators: %s: school name */
										__('(<span class="wlsm-font-bold">Transferred to:</span> %s)', 'school-management'),
										array('span' => array('class' => array()))
									),
									esc_html(WLSM_M_School::get_label_text($student_record['to_school']))
								);
								?>
							</span>
						<?php } ?>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e('Enrollment Number', 'school-management'); ?></span>:
						<span><?php echo esc_html($student_record['enrollment_number']); ?></span>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e('Student Name', 'school-management'); ?></span>:
						<span><?php echo esc_html($student_record['student_name']); ?></span>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e('Class', 'school-management'); ?></span>:
						<span><?php echo esc_html($student_record['class_label']); ?></span>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e('Section', 'school-management'); ?></span>:
						<span><?php echo esc_html($student_record['section_label']); ?></span>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e('Roll Number', 'school-management'); ?></span>:
						<span><?php echo esc_html($student_record['roll_number']); ?></span>
					</li>
				</ul>
			<?php
			}
			?>
		</div>

	<?php
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

	public static function print_id_card() {
		$current_user = WLSM_M_Role::can('manage_students', 'manage_transport');

		if (!$current_user) {
			die();
		}

		$current_school = $current_user['school'];

		$restrict_to_section = WLSM_M_Role::restrict_to_section($current_school);

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['print-id-card-' . $student_id], 'print-id-card-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id, $restrict_to_section);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
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

		ob_start();
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/id_card.php';
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

	public static function print_id_cards()
	{
		$current_user = WLSM_M_Role::can('manage_students');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$session_label = $current_user['session']['label'];

		if (!wp_verify_nonce($_POST['print-id-cards'], 'print-id-cards')) {
			die();
		}

		$class_id    = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;
		$section_id  = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
		$only_active = isset($_POST['only_active']) ? (bool) ($_POST['only_active']) : 1;

		try {
			ob_start();
			global $wpdb;

			// Start validation.
			$errors = array();

			if (empty($class_id)) {
				$errors['class_id'] = esc_html__('Please select a class.', 'school-management');
			} else {
				// Checks if class exists in the school.
				$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);

				if (!$class_school) {
					$errors['class_id'] = esc_html__('Class not found.', 'school-management');
				} else {
					$class_school_id = $class_school->ID;

					if ($section_id) {
						$section = WLSM_M_Staff_Class::fetch_section($school_id, $section_id, $class_school_id);
						if (!$section) {
							$errors['section_id'] = esc_html__('Section not found.', 'school-management');
						} else {
							$section_label = WLSM_M_Staff_Class::get_section_label_text($section->label);
						}
					} else {
						$section_label = esc_html__('All', 'school-management');
					}

					$class       = WLSM_M_Class::fetch_class($class_id);
					$class_label = WLSM_M_Class::get_label_text($class->label);
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
			$filter = array(
				'class_id'   => $class_id,
				'section_id' => $section_id,
				'search_by'  => 'search_by_class'
			);

			$query = WLSM_M_Staff_General::fetch_students_query($school_id, $session_id, $filter);

			// Grouping.
			$group_by = ' ' . WLSM_M_Staff_General::fetch_students_query_group_by();

			$query .= $group_by;
			$query .= ' ORDER BY sr.roll_number ASC, sr.ID ASC';

			$students = $wpdb->get_results($query);

			$students = array_filter($students, function ($student) use ($only_active) {
				if ($only_active && !$student->is_active) {
					return false;
				}
				return true;
			});

			ob_start();
			require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/id_cards.php';
			$html = ob_get_clean();

			$json = json_encode(array(
				'message_title' => esc_html__('Print ID Cards', 'school-management'),
			));

			wp_send_json_success(array('html' => $html, 'json' => $json));
		}

		wp_send_json_error($errors);
	}

	public static function view_attendance_report()
	{
		$current_user = WLSM_M_Role::can('manage_students');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$session_label = $current_user['session']['label'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['view-attendance-report-' . $student_id], 'view-attendance-report-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
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

		$attendance = WLSM_M_Staff_General::get_student_attendance_report($student->ID);
		ob_start();
	?>
		<div class="wlsm">
			<!-- Student details -->
			<ul class="wlsm-list-group">
				<li class="wlsm-list-item">
					<span class="wlsm-font-bold"><?php esc_html_e('Student Name', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)); ?></span>
				</li>
				<li class="wlsm-list-item">
					<span class="wlsm-font-bold"><?php esc_html_e('Enrollment Number', 'school-management'); ?>:</span>
					<span><?php echo esc_html($student->enrollment_number); ?></span>
				</li>
				<li class="wlsm-list-item">
					<span class="wlsm-font-bold"><?php esc_html_e('Class', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->class_label)); ?></span>
				</li>
				<li class="wlsm-list-item">
					<span class="wlsm-font-bold"><?php esc_html_e('Section', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Staff_Class::get_section_label_text($student->section_label)); ?></span>
				</li>
				<li class="wlsm-list-item">
					<span class="wlsm-font-bold"><?php esc_html_e('Roll Number', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)); ?></span>
				</li>
			</ul>

			<!-- Attendance report -->
			<?php require_once WLSM_PLUGIN_DIR_PATH . 'includes/partials/attendance_report.php'; ?>
		</div>
	<?php
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

	public static function view_student_detail() {
		$current_user = WLSM_M_Role::can('manage_students');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$session_label = $current_user['session']['label'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['view-student-detail-' . $student_id], 'view-student-detail-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id, $restrict_to_section);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
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

		ob_start();
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/view_student_detail.php';
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

	public static function print_fee_structure()
	{
		$current_user = WLSM_M_Role::can('manage_students');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['print-fee-structure-' . $student_id], 'print-fee-structure-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id);

			if (!$student) {
				throw new Exception(esc_html__('Student not found.', 'school-management'));
			}

			$fees = WLSM_M_Staff_Accountant::fetch_student_fees($school_id, $student_id);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		ob_start();
		require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/fee_structure.php';
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

	public static function student_old_record_exists($to_student_record)
	{
		global $wpdb;
		$student = $wpdb->get_row($wpdb->prepare('SELECT pm.from_student_record FROM ' . WLSM_PROMOTIONS . ' as pm WHERE pm.to_student_record = %d', $to_student_record));
		if ($student) {
			return $student->from_student_record;
		}
		return false;
	}

	public static function student_new_record_exists($from_student_record)
	{
		global $wpdb;
		$student = $wpdb->get_row($wpdb->prepare('SELECT pm.to_student_record FROM ' . WLSM_PROMOTIONS . ' as pm WHERE pm.from_student_record = %d', $from_student_record));
		if ($student) {
			return $student->to_student_record;
		}
		return false;
	}

	public static function student_transfer_new_record_exists($from_student_record)
	{
		global $wpdb;
		$student = $wpdb->get_row($wpdb->prepare('SELECT tf.to_student_record FROM ' . WLSM_TRANSFERS . ' as tf WHERE tf.from_student_record = %d', $from_student_record));
		if ($student) {
			return $student->to_student_record;
		}
		return false;
	}

	public static function fetch_admins()
	{
		$current_user = WLSM_M_Role::can('manage_admins');
		self::fetch_staff_records($current_user, WLSM_M_Role::get_admin_key());
	}

	public static function fetch_employees()
	{
		$current_user = WLSM_M_Role::can('manage_employees');
		self::fetch_staff_records($current_user, WLSM_M_Role::get_employee_key());
	}

	public static function fetch_staff_records($current_user, $role)
	{
		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		global $wpdb;

		if (WLSM_M_Role::get_admin_key() === $role) {
			$page_url = WLSM_M_Staff_General::get_admins_page_url();
		} else {
			$page_url = WLSM_M_Staff_General::get_employees_page_url();
		}

		$query = WLSM_M_Staff_General::fetch_staff_query($school_id, $role);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_M_Staff_General::fetch_staff_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(a.name LIKE "%' . $search_value . '%") OR ' .
					'(a.phone LIKE "%' . $search_value . '%") OR ' .
					'(a.email LIKE "%' . $search_value . '%") OR ' .
					'(a.salary LIKE "%' . $search_value . '%") OR ' .
					'(a.designation LIKE "%' . $search_value . '%") OR ' .
					'(r.name LIKE "%' . $search_value . '%") OR ' .
					'(u.user_email LIKE "%' . $search_value . '%") OR ' .
					'(u.user_login LIKE "%' . $search_value . '%")';

				$search_value_lowercase = strtolower($search_value);
				if (preg_match('/^inac(|t|ti|tiv|tive)$/', $search_value_lowercase)) {
					$is_active = 0;
				} elseif (preg_match('/^acti(|v|ve)$/', $search_value_lowercase)) {
					$is_active = 1;
				}
				if (isset($is_active)) {
					$condition .= ' OR (a.is_active = ' . $is_active . ')';
				}

				$joining_date = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($joining_date) {
					$format_joining_date = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$joining_date) {
							$joining_date        = DateTime::createFromFormat('m-Y', $search_value);
							$format_joining_date = 'Y-m';
						}
					} elseif ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$joining_date) {
							$joining_date        = DateTime::createFromFormat('m/Y', $search_value);
							$format_joining_date = 'Y-m';
						}
					} elseif ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$joining_date) {
							$joining_date        = DateTime::createFromFormat('Y-m', $search_value);
							$format_joining_date = 'Y-m';
						}
					} elseif ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$joining_date) {
							$joining_date        = DateTime::createFromFormat('Y/m', $search_value);
							$format_joining_date = 'Y-m';
						}
					}

					if (!$joining_date) {
						$joining_date        = DateTime::createFromFormat('Y', $search_value);
						$format_joining_date = 'Y';
					}
				}

				if ($joining_date && isset($format_joining_date)) {
					$joining_date = $joining_date->format($format_joining_date);
					$joining_date = ' OR (a.joining_date LIKE "%' . $joining_date . '%")';

					$condition .= $joining_date;
				}

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('a.name', 'a.phone', 'a.email', 'a.salary', 'a.designation', 'r.name', 'u.user_email', 'u.user_login', 'a.joining_date', 'a.is_active');
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY a.joining_date DESC, a.ID DESC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_M_Staff_General::fetch_staff_query_count($school_id, $role);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {
				if ($row->assigned_by_manager) {
					$delete_staff = '';
				} else {
					/* translators: %s: staff role */
					$delete_staff = '<a class="text-danger wlsm-delete-staff" data-nonce="' . esc_attr(wp_create_nonce('delete-staff-' . $row->ID)) . '" data-staff="' . esc_attr($row->ID) . '" href="#" data-role="' . esc_attr($role) . '" data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '" data-message-content="' . sprintf(esc_attr__('This will delete the %s.', 'school-management'), strtolower(WLSM_M_Role::get_role_text($role))) . '" data-cancel="' . esc_attr__('Cancel', 'school-management') . '" data-submit="' . esc_attr__('Confirm', 'school-management') . '"><span class="dashicons dashicons-trash"></span></a>';
				}

				if (WLSM_M_Role::get_admin_key() === $role) {
					$role_name = WLSM_M_Role::get_admin_text();

					$attendance_report = false;
				} else {
					$role_name = $row->role_name;

					$attendance_report = true;
				}

				$record = array(
					esc_html(WLSM_M_Staff_Class::get_name_text($row->name)),
					esc_html(WLSM_M_Staff_Class::get_phone_text($row->phone)),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->email)),
					esc_html(WLSM_Config::get_money_text($row->salary)),
					esc_html(WLSM_M_Staff_Class::get_designation_text($row->designation)),
					esc_html($role_name),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->login_email)),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->username)),
					esc_html(WLSM_Config::get_date_text($row->joining_date)),
					esc_html(WLSM_M_Staff_Class::get_status_text($row->is_active))
				);

				if ($attendance_report) {
					array_push($record, '<a class="text-primary wlsm-view-staff-attendance-report" data-nonce="' . esc_attr(wp_create_nonce('view-staff-attendance-report-' . $row->ID)) . '" data-staff="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Staff Attendance Report', 'school-management') . '" data-close="' . esc_attr__('Close', 'school-management') . '"><i class="fas fa-calendar-alt"></i></a>');
				}

				array_push($record, '<a class="text-primary" href="' . esc_url($page_url . "&action=save&id=" . $row->ID) . '"><span class="dashicons dashicons-edit"></span></a>&nbsp;&nbsp;' . $delete_staff);

				// Table columns.
				$data[] = $record;
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data,
		);

		echo json_encode($output);
		die();
	}

	public static function save_admin()
	{
		$current_user = WLSM_M_Role::can('manage_admins');
		self::save_staff($current_user, WLSM_M_Role::get_admin_key());
	}

	public static function save_employee()
	{
		$current_user = WLSM_M_Role::can('manage_employees');
		self::save_staff($current_user, WLSM_M_Role::get_employee_key());
	}

	public static function save_staff($current_user, $role)
	{
		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		if (WLSM_M_Role::get_admin_key() === $role) {
			$page_url = WLSM_M_Staff_General::get_admins_page_url();
		} else {
			$page_url = WLSM_M_Staff_General::get_employees_page_url();
		}

		try {
			ob_start();
			global $wpdb;

			$admin_id = isset($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;

			if ($admin_id) {
				if (!wp_verify_nonce($_POST['edit-' . $role . '-' . $admin_id], 'edit-' . $role . '-' . $admin_id)) {
					die();
				}
			} else {
				if (!wp_verify_nonce($_POST['add-' . $role . ''], 'add-' . $role . '')) {
					die();
				}
			}

			$user_id = NULL;

			$assigned_by_manager = 0;

			// Checks if staff exists.
			if ($admin_id) {
				$admin = WLSM_M_Staff_General::get_staff($school_id, $role, $admin_id);

				if (!$admin) {
					/* translators: %s: staff role */
					throw new Exception(sprintf(esc_html__('%s not found.', 'school-management'), WLSM_M_Role::get_role_text($role)));
				}

				$assigned_by_manager = $admin->assigned_by_manager;

				$user_id  = $admin->user_id;
				$staff_id = $admin->staff_id;
			}

			// Personal Detail.
			$name          = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
			$gender        = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
			$dob           = isset($_POST['dob']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['dob'])) : NULL;
			$address       = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';
			$email         = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '';
			$phone         = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
			$salary        = isset($_POST['salary']) ? WLSM_Config::sanitize_money($_POST['salary']) : 0;

			// Joining Detail.
			$joining_date = isset($_POST['joining_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['joining_date'])) : NULL;
			$staff_role   = isset($_POST['role']) ? absint($_POST['role']) : 0;
			$designation  = isset($_POST['designation']) ? sanitize_text_field($_POST['designation']) : '';
			$note         = isset($_POST['note']) ? sanitize_text_field($_POST['note']) : '';

			// Class Teacher.
			$class_id   = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;
			$section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;

			// Bus In-charge.
			$is_vehicle_incharge = isset($_POST['is_vehicle_incharge']) ? (bool) $_POST['is_vehicle_incharge'] : 0;
			$vehicle_id          = isset($_POST['vehicle_id']) ? absint($_POST['vehicle_id']) : 0;

			// Permissions
			$permissions = (isset($_POST['permission']) && is_array($_POST['permission'])) ? $_POST['permission'] : array();

			// Login Detail.
			$new_or_existing   = isset($_POST['staff_new_or_existing']) ? sanitize_text_field($_POST['staff_new_or_existing']) : '';
			$existing_username = isset($_POST['existing_username']) ? sanitize_text_field($_POST['existing_username']) : '';
			$new_login_email   = isset($_POST['new_login_email']) ? sanitize_text_field($_POST['new_login_email']) : '';
			$new_password      = isset($_POST['new_password']) ? sanitize_text_field($_POST['new_password']) : '';
			$username          = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
			$login_email       = isset($_POST['login_email']) ? sanitize_text_field($_POST['login_email']) : '';
			$password          = isset($_POST['password']) ? $_POST['password'] : '';

			// Status.
			$is_active = isset($_POST['is_active']) ? (bool) $_POST['is_active'] : 1;

			// Start validation.
			$errors = array();

			// Personal Detail.
			if (empty($name)) {
				$errors['name'] = esc_html__('Please specify name.', 'school-management');
			}
			if (strlen($name) > 60) {
				$errors['name'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}
			if (!empty($phone) && strlen($phone) > 40) {
				$errors['phone'] = esc_html__('Maximum length cannot exceed 40 characters.', 'school-management');
			}
			if (!empty($email)) {
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$errors['email'] = esc_html__('Please provide a valid email.', 'school-management');
				} elseif (strlen($email) > 60) {
					$errors['email'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
				}
			}
			if (!empty($designation) && strlen($designation) > 80) {
				$errors['designation'] = esc_html__('Maximum length cannot exceed 80 characters.', 'school-management');
			}
			if (!in_array($gender, array_keys(WLSM_Helper::gender_list()))) {
				$gender = NULL;
			}
			if (!empty($dob)) {
				$dob = $dob->format('Y-m-d');
			} else {
				$dob = NULL;
			}

			// Joining Detail.
			if (!empty($joining_date)) {
				$joining_date = $joining_date->format('Y-m-d');
			} else {
				$joining_date = NULL;
			}

			if (WLSM_M_Role::get_admin_key() === $role) {
				$staff_role = NULL;
			}

			// Class Teacher.
			if ($class_id) {
				// Checks if class exists in the school.
				$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);
				if (!$class_school) {
					$errors['class_id'] = esc_html__('Class not found.', 'school-management');
				} else {
					$class_school_id = $class_school->ID;
					if (!$section_id) {
						$errors['section_id'] = esc_html__('Please select a section.', 'school-management');
					} else {
						// Checks if section exists.
						$section = WLSM_M_Staff_Class::get_section($school_id, $section_id, $class_school_id);
						if (!$section) {
							$errors['section_id'] = esc_html__('Section not found.', 'school-management');
						}
					}
				}
			} else {
				$section_id = NULL;
			}

			// Bus In-charge.
			if ($is_vehicle_incharge) {
				if (!$vehicle_id) {
					$errors['vehicle_id'] = esc_html__('Please select a vehicle.', 'school-management');
				} else {
					$vehicle = WLSM_M_Staff_Transport::get_vehicle($school_id, $vehicle_id);

					if (!$vehicle) {
						$errors['vehicle_id'] = esc_html__('Vehicle not found.', 'school-management');
					}
				}
			} else {
				$vehicle_id = NULL;
			}

			// Permissions.
			if (empty($staff_role)) {
				$staff_role = NULL;
			} else {
				$staff_role_exists = WLSM_M_Staff_General::fetch_role($school_id, $staff_role);
				if (!$staff_role_exists) {
					$errors['role'] = esc_html__('Please select valid staff role.', 'school-management');
				} else {
					$role_permissions = $staff_role_exists->permissions;
					if (is_serialized($role_permissions)) {
						$role_permissions = unserialize($role_permissions);
						$permissions      = array_unique(array_merge($role_permissions, $permissions));
					}
				}
			}

			$permissions = WLSM_M_Role::get_role_permissions($role, $permissions);

			// Login Detail.
			if ('existing_user' === $new_or_existing) {
				if (!$user_id) {
					if (empty($existing_username)) {
						$errors['existing_username'] = esc_html__('Please provide existing username.', 'school-management');
					}
				} else {
					if (empty($new_login_email)) {
						$errors['new_login_email'] = esc_html__('Please provide login email.', 'school-management');
					}
				}
			} elseif ('new_user' === $new_or_existing) {
				if (empty($username)) {
					$errors['username'] = esc_html__('Please provide username.', 'school-management');
				}
				if (empty($login_email)) {
					$errors['login_email'] = esc_html__('Please provide login email.', 'school-management');
				}
				if (!filter_var($login_email, FILTER_VALIDATE_EMAIL)) {
					$errors['login_email'] = esc_html__('Please provide a valid email.', 'school-management');
				}
				if (empty($password)) {
					$errors['password'] = esc_html__('Please provide login password.', 'school-management');
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

				if ($admin_id) {
					/* translators: %s: staff role */
					$message = sprintf(esc_html__('%s updated successfully.', 'school-management'), WLSM_M_Role::get_role_text($role));
				} else {
					/* translators: %s: staff role */
					$message = sprintf(esc_html__('%s added successfully.', 'school-management'), WLSM_M_Role::get_role_text($role));
				}

				// Staff user data.
				$update_staff_user_id = NULL;
				if ('existing_user' === $new_or_existing) {
					if (!$user_id) {
						if (!$assigned_by_manager) {
							// Existing user.
							$user = get_user_by('login', $existing_username);
							if (!$user) {
								throw new Exception(esc_html__('Username does not exist.', 'school-management'));
							}

							$user_id = $user->ID;

							if (user_can($user_id, WLSM_ADMIN_CAPABILITY)) {
								throw new Exception(esc_html__('User is a multi-school administrator.', 'school-management'));
							}

							// Check if user already has a staff record in the school.
							if ($admin_id) {
								$user_has_staff_record = $wpdb->get_row($wpdb->prepare('SELECT sf.ID FROM ' . WLSM_STAFF . ' as sf WHERE sf.user_id = %d AND sf.school_id = %d AND sf.ID != %d', $user_id, $school_id, $staff_id));
							} else {
								$user_has_staff_record = $wpdb->get_row($wpdb->prepare('SELECT sf.ID FROM ' . WLSM_STAFF . ' as sf WHERE sf.user_id = %d AND sf.school_id = %d', $user_id, $school_id));
							}

							if ($user_has_staff_record) {
								throw new Exception(esc_html__('The user already has a staff record.', 'school-management'));
							}
						}
					} else {
						$user_data = array(
							'ID'         => $user_id,
							'user_email' => $new_login_email,
						);

						if (!empty($new_password)) {
							$user_data['user_pass'] = $new_password;
						}

						$user_id = wp_update_user($user_data);
						if (is_wp_error($user_id)) {
							throw new Exception($user_id->get_error_message());
						}
					}
				} elseif ('new_user' === $new_or_existing) {
					// New user.
					$user_data = array(
						'user_email' => $login_email,
						'user_login' => $username,
						'user_pass'  => $password,
					);

					$user_id = wp_insert_user($user_data);
					if (is_wp_error($user_id)) {
						throw new Exception($user_id->get_error_message());
					}
				} else {
					if (!$assigned_by_manager) {
						$user_id = NULL;
					}
				}

				$update_staff_user_id = $user_id;

				// Admin data.
				$admin_data = array(
					'name'         => $name,
					'gender'       => $gender,
					'dob'          => $dob,
					'phone'        => $phone,
					'email'        => $email,
					'address'      => $address,
					'salary'       => $salary,
					'designation'  => $designation,
					'note'         => $note,
					'joining_date' => $joining_date,
					'section_id'   => $section_id,
					'vehicle_id'   => $vehicle_id,
					'role_id'      => $staff_role,
					'is_active'    => $is_active,
				);

				$staff_data = array(
					'role'        => $role,
					'permissions' => serialize($permissions),
					'user_id'     => $user_id,
				);

				if ($admin_id) {
					// Update staff.
					$staff_data['updated_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->update(WLSM_STAFF, $staff_data, array('ID' => $staff_id));
				} else {
					// Add staff.
					$staff_data['created_at'] = current_time('Y-m-d H:i:s');

					$staff_data['school_id'] = $school_id;

					$success = $wpdb->insert(WLSM_STAFF, $staff_data);

					$staff_id = $wpdb->insert_id;
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if ($admin_id) {
					// Update admin.
					$admin_data['updated_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->update(WLSM_ADMINS, $admin_data, array('ID' => $admin_id, 'staff_id' => $staff_id));
				} else {
					// Add admin.
					$admin_data['created_at'] = current_time('Y-m-d H:i:s');

					$admin_data['staff_id'] = $staff_id;

					$success = $wpdb->insert(WLSM_ADMINS, $admin_data);
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$current_school_exists = false;
				if ($current_school_id = get_user_meta($user_id, 'wlsm_school_id', true)) {
					$staff_in_school = WLSM_M_Admin::user_in_school($current_school_id, $user_id);

					if ($staff_in_school) {
						$current_school_exists = true;
					}
				}

				if (!$current_school_exists) {
					update_user_meta($user_id, 'wlsm_school_id', $school_id);
				}

				$wpdb->query('COMMIT;');

				$reload = false;
				if ($admin_id) {
					$reload = true;
				}

				wp_send_json_success(array('message' => $message, 'reload' => $reload));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function delete_admin()
	{
		$current_user = WLSM_M_Role::can('manage_admins');
		self::delete_staff($current_user, WLSM_M_Role::get_admin_key());
	}

	public static function delete_employee()
	{
		$current_user = WLSM_M_Role::can('manage_employees');
		self::delete_staff($current_user, WLSM_M_Role::get_employee_key());
	}

	public static function delete_staff($current_user, $role)
	{
		if (!$current_user) {
			die();
		}

		WLSM_Helper::check_demo();

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$admin_id = isset($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;

			if (!wp_verify_nonce($_POST['delete-staff-' . $admin_id], 'delete-staff-' . $admin_id)) {
				die();
			}

			// Checks if staff exists.
			$admin = WLSM_M_Staff_General::get_staff($school_id, $role, $admin_id);

			if (!$admin) {
				/* translators: %s: staff role */
				throw new Exception(sprintf(esc_html__('%s not found.', 'school-management'), WLSM_M_Role::get_role_text($role)));
			}

			if ($admin->assigned_by_manager) {
				die();
			}

			$staff_id = $admin->staff_id;
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		try {
			$wpdb->query('BEGIN;');

			$success = $wpdb->delete(WLSM_STAFF, array('ID' => $staff_id));
			/* translators: %s: staff role */
			$message = sprintf(esc_html__('%s deleted successfully.', 'school-management'), WLSM_M_Role::get_role_text($role));

			$exception = ob_get_clean();
			if (!empty($exception)) {
				throw new Exception($exception);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function view_staff_attendance_report()
	{
		$current_user = WLSM_M_Role::can('manage_employees');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$session_label = $current_user['session']['label'];

		try {
			ob_start();
			global $wpdb;

			$admin_id = isset($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;

			if (!wp_verify_nonce($_POST['view-staff-attendance-report-' . $admin_id], 'view-staff-attendance-report-' . $admin_id)) {
				die();
			}

			$role = WLSM_M_Role::get_employee_key();

			// Checks if staff exists.
			$admin = WLSM_M_Staff_General::fetch_staff($school_id, $role, $admin_id);

			if (!$admin) {
				/* translators: %s: staff role */
				throw new Exception(sprintf(esc_html__('%s not found.', 'school-management'), WLSM_M_Role::get_role_text($role)));
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

		$attendance = WLSM_M_Staff_General::get_staff_attendance_report($admin->ID);

		$role = $admin->role;

		if (WLSM_M_Role::get_admin_key() === $role) {
			$role_name = WLSM_M_Role::get_admin_text();
		} else {
			$role_name = $admin->role_name;
		}
		ob_start();
	?>
		<div class="wlsm">
			<!-- Staff details -->
			<ul class="wlsm-list-group">
				<li class="wlsm-list-item">
					<span class="wlsm-font-bold"><?php esc_html_e('Name', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($admin->name)); ?></span>
				</li>
				<li class="wlsm-list-item">
					<span class="wlsm-font-bold"><?php esc_html_e('Phone', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Staff_Class::get_phone_text($admin->phone)); ?></span>
				</li>
				<li class="wlsm-list-item">
					<span class="wlsm-font-bold"><?php esc_html_e('Designation', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Staff_Class::get_designation_text($admin->designation)); ?></span>
				</li>
				<li class="wlsm-list-item">
					<span class="wlsm-font-bold"><?php esc_html_e('Role', 'school-management'); ?>:</span>
					<span><?php echo esc_html($role_name); ?></span>
				</li>
			</ul>

			<!-- Attendance report -->
			<?php require_once WLSM_PLUGIN_DIR_PATH . 'includes/partials/attendance_report.php'; ?>
		</div>
		<?php
		$html = ob_get_clean();

		wp_send_json_success(array('html' => $html));
	}

	public static function manage_staff_attendance()
	{
		$current_user = WLSM_M_Role::can('manage_staff_attendance');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		if (!wp_verify_nonce($_POST['nonce'], 'manage-staff-attendance')) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$attendance_date = isset($_POST['attendance_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['attendance_date'])) : NULL;

			// Start validation.
			$errors = array();

			if (empty($attendance_date)) {
				$errors['attendance_date'] = esc_html__('Please specify date.', 'school-management');
			} else {
				$attendance_date = $attendance_date->format('Y-m-d');
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
				ob_start();

				// Get active staff of school.
				$admins = WLSM_M_Staff_Class::get_active_staff($school_id);

				if (count($admins)) {
					$all_admins_ids = array_map(function ($admin) {
						return $admin->ID;
					}, $admins);

					// Get saved staff attendance.
					$all_admin_ids_count = count($all_admins_ids);

					$place_holders = array_fill(0, $all_admin_ids_count, '%s');

					$all_admin_ids_format = implode(', ', $place_holders);

					$prepare = array_merge(array($attendance_date), $all_admins_ids);

					$saved_attendance = $wpdb->get_results($wpdb->prepare('SELECT admin_id, status FROM ' . WLSM_STAFF_ATTENDANCE . ' WHERE attendance_date = "%s" AND admin_id IN (' . $all_admin_ids_format . ')', $prepare), OBJECT_K);
		?>
					<input type="hidden" name="attendance_date_final" value="<?php echo esc_attr($_POST['attendance_date']); ?>">

					<!-- Staff attendance. -->
					<div class="wlsm-form-section">
						<div class="row">
							<div class="col-md-12">
								<div class="wlsm-form-sub-heading-small wlsm-font-bold">
									<span><?php esc_html_e('Manage Staff Attendance', 'school-management'); ?></span>
									<span class="float-md-right">
										<?php
										printf(
											wp_kses(
												/* translators: %s: date of attendance */
												__('Date: <span class="text-dark wlsm-font-bold">%s</span>', 'school-management'),
												array('span' => array('class' => array()))
											),
											esc_html(WLSM_Config::get_date_text($attendance_date))
										);
										?>
									</span>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-md-12">
								<div class="table-responsive w-100">
									<table class="table table-bordered wlsm-staff-attendance-table">
										<thead>
											<tr class="bg-primary text-white">
												<th><?php esc_html_e('Name', 'school-management'); ?></th>
												<th><?php esc_html_e('Phone', 'school-management'); ?></th>
												<th><?php esc_html_e('Designation', 'school-management'); ?></th>
												<th><?php esc_html_e('Role', 'school-management'); ?></th>
												<th><?php esc_html_e('Login Username', 'school-management'); ?></th>
												<th>
													<?php esc_html_e('Status', 'school-management'); ?>&nbsp;
													<button type="button" class="btn wlsm-btn-xs btn-success mr-1 wlsm-mark-all-present">
														<i class="fas fa-check"></i>
														<?php esc_html_e('Mark All Present', 'school-management'); ?>
													</button>
													<button type="button" class="btn wlsm-btn-xs btn-danger wlsm-mark-all-absent">
														<i class="fas fa-times"></i>
														<?php esc_html_e('Mark All Absent', 'school-management'); ?>
													</button>
												</th>
											</tr>
										</thead>
										<tbody>
											<?php
											foreach ($admins as $row) {
												if (isset($saved_attendance[$row->ID])) {
													$attendance = $saved_attendance[$row->ID];
													$status     = $attendance->status;
												} else {
													$status = '';
												}

												$role = $row->role;

												if (WLSM_M_Role::get_admin_key() === $role) {
													$role_name = WLSM_M_Role::get_admin_text();
												} else {
													$role_name = $row->role_name;
												}
											?>
												<tr>
													<td>
														<input type="hidden" name="staff[<?php echo esc_attr($row->ID); ?>]" value="<?php echo esc_attr($row->ID); ?>">
														<?php echo esc_html(WLSM_M_Staff_Class::get_name_text($row->name)); ?>
													</td>
													<td>
														<?php echo esc_html(WLSM_M_Staff_Class::get_phone_text($row->phone)); ?>
													</td>
													<td>
														<?php echo esc_html(WLSM_M_Staff_Class::get_designation_text($row->designation)); ?>
													</td>
													<td>
														<?php echo esc_html($role_name); ?>
													</td>
													<td>
														<?php echo esc_html(WLSM_M_Staff_Class::get_name_text($row->username)); ?>
													</td>
													<td>
														<?php foreach (WLSM_Helper::attendance_status() as $key => $value) { ?>
															<div class="form-check form-check-inline">
																<input <?php checked($key, $status, true); ?> class="form-check-input wlsm-attendance-status-input" type="radio" name="status[<?php echo esc_attr($row->ID); ?>]" id="wlsm_attendance_status_<?php echo esc_attr($key); ?>_<?php echo esc_attr($row->ID); ?>" value="<?php echo esc_attr($key); ?>">
																<label class="ml-1 form-check-label wlsm-font-bold" for="wlsm_attendance_status_<?php echo esc_attr($key); ?>_<?php echo esc_attr($row->ID); ?>">
																	<?php echo esc_html($value); ?>
																</label>
															</div>
														<?php } ?>
													</td>
												</tr>
											<?php
											}
											?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>

					<div class="row mt-2 mb-2">
						<div class="col-md-12 text-center">
							<?php
							printf(
								wp_kses(
									/* translators: %s: date of attendance */
									__('Attendance Date: <span class="wlsm-font-bold">%s</span>', 'school-management'),
									array('span' => array('class' => array()))
								),
								esc_html(WLSM_Config::get_date_text($attendance_date))
							);
							?>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-md-12 text-center">
							<button type="submit" class="btn btn-sm btn-success" id="wlsm-take-staff-attendance-btn" data-message-title="<?php esc_attr_e('Confirm!', 'school-management'); ?>" data-message-content="<?php esc_attr_e('Are you sure to save attendance status?', 'school-management'); ?>" data-submit="<?php esc_attr_e('Save', 'school-management'); ?>" data-cancel="<?php esc_attr_e('Cancel', 'school-management'); ?>">
								<i class="fas fa-save"></i>&nbsp;
								<?php esc_html_e('Save Changes', 'school-management'); ?>
							</button>
						</div>
					</div>
				<?php
				} else {
				?>
					<div class="alert alert-warning wlsm-font-bold">
						<i class="fas fa-exclamation-triangle"></i>
						<?php esc_html_e('There is no active staff.', 'school-management'); ?>
					</div>
				<?php
				}
				$html = ob_get_clean();

				wp_send_json_success(array('html' => $html));
			} catch (Exception $exception) {
				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					$response = $buffer;
				} else {
					$response = $exception->getMessage();
				}
				wp_send_json_error($response);
			}
		}
		wp_send_json_error($errors);
	}

	public static function take_staff_attendance()
	{
		$current_user = WLSM_M_Role::can('manage_staff_attendance');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		if (!wp_verify_nonce($_POST['take-staff-attendance'], 'take-staff-attendance')) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$attendance_date = isset($_POST['attendance_date_final']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['attendance_date_final'])) : NULL;

			// Start validation.
			if (empty($attendance_date)) {
				throw new Exception(esc_html__('Please specify date.', 'school-management'));
			} else {
				$attendance_date = $attendance_date->format('Y-m-d');
			}

			// Get active staff of school.
			$admins = WLSM_M_Staff_Class::get_active_staff($school_id);

			$all_admin_ids = array_map(function ($admin) {
				return $admin->ID;
			}, $admins);

			$admin_ids  = (isset($_POST['staff']) && is_array($_POST['staff'])) ? $_POST['staff'] : array();
			$status_ids = (isset($_POST['status']) && is_array($_POST['status'])) ? $_POST['status'] : array();

			$allowed_status_ids = array_keys(WLSM_Helper::attendance_status());

			$unique_status_ids = array_unique($status_ids);

			if (array_intersect($unique_status_ids, $allowed_status_ids) != $unique_status_ids) {
				wp_send_json_error(esc_html__('Please select valid attendance status.', 'school-management'));
			}

			$admin_ids_keys  = array_keys($admin_ids);
			$status_ids_keys = array_keys($status_ids);

			if (!count($admin_ids)) {
				wp_send_json_error(esc_html__('No staff found.', 'school-management'));
			} else if ((array_intersect($admin_ids, $all_admin_ids) != $admin_ids) || ($admin_ids_keys != array_values($admin_ids))) {
				wp_send_json_error(esc_html__('Please select valid staff.', 'school-management'));
			} else if (array_intersect($admin_ids_keys, $status_ids_keys) != $admin_ids_keys) {
				wp_send_json_error(esc_html__('Invalid selection of staff or attendance status.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			foreach ($admin_ids_keys as $admin_id) {
				if (isset($admins[$admin_id])) {
					$admin  = $admins[$admin_id];
					$status = $status_ids[$admin_id];

					if (!empty($status)) {
						$sql = 'INSERT INTO ' . WLSM_STAFF_ATTENDANCE . ' (attendance_date, admin_id, added_by, status) VALUES ("%s", %d, %d, "%s") ON DUPLICATE KEY UPDATE status = "%s", updated_at = "%s"';

						$success = $wpdb->query($wpdb->prepare($sql, $attendance_date, $admin_id, get_current_user_id(), $status, $status, current_time('Y-m-d H:i:s')));

						$buffer = ob_get_clean();
						if (!empty($buffer)) {
							throw new Exception($buffer);
						}

						if (false === $success) {
							throw new Exception($wpdb->last_error);
						}
					} else {
						$wpdb->delete(
							WLSM_STAFF_ATTENDANCE,
							array('attendance_date' => $attendance_date, 'admin_id' => $admin_id)
						);
						$buffer = ob_get_clean();
						if (!empty($buffer)) {
							throw new Exception($buffer);
						}
					}
				} else {
					throw new Exception(esc_html__('Please select valid staff.', 'school-management'));
				}
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('Attendance saved successfully.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function view_staff_attendance()
	{
		$current_user = WLSM_M_Role::can('manage_staff_attendance');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		if (!wp_verify_nonce($_POST['nonce'], 'view-staff-attendance')) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$year_month = isset($_POST['year_month']) ? DateTime::createFromFormat('F Y', sanitize_text_field($_POST['year_month'])) : NULL;

			// Start validation.
			$errors = array();

			if (empty($year_month)) {
				$errors['year_month'] = esc_html__('Please specify the month.', 'school-management');
			} else {
				$month_format = $year_month->format('F');
				$year_format  = $year_month->format('Y');

				$month = $year_month->format('m');
				$year  = $year_month->format('Y');

				$number_of_days = $year_month->format('t');

				$start_date = new DateTime("{$year}-{$month}-01");
				$end_date   = new DateTime("{$year}-{$month}-{$number_of_days}");

				$date_range = new DatePeriod($start_date, DateInterval::createFromDateString('1 day'), $end_date->modify('+1 day'));
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
				ob_start();

				// Get active staff of school.
				$admins = WLSM_M_Staff_Class::get_active_staff($school_id);

				if (count($admins)) {
					$all_admin_ids = array_map(function ($admin) {
						return $admin->ID;
					}, $admins);

					// Get saved staff attendance.
					$all_admin_ids_count = count($all_admin_ids);

					$place_holders = array_fill(0, $all_admin_ids_count, '%s');

					$all_admin_ids_format = implode(', ', $place_holders);

					$prepare = array_merge(array($year, $month), $all_admin_ids);

					$saved_attendance = $wpdb->get_results($wpdb->prepare('SELECT admin_id, attendance_date, status FROM ' . WLSM_STAFF_ATTENDANCE . ' WHERE YEAR(attendance_date) = %d AND MONTH(attendance_date) = %d AND admin_id IN (' . $all_admin_ids_format . ')', $prepare));

					require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/staff_attendance_sheet.php';
				} else {
				?>
					<div class="alert alert-warning wlsm-font-bold">
						<i class="fas fa-exclamation-triangle"></i>
						<?php esc_html_e('There is no active staff.', 'school-management'); ?>
					</div>
				<?php
				}
				$html = ob_get_clean();

				wp_send_json_success(array('html' => $html));
			} catch (Exception $exception) {
				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					$response = $buffer;
				} else {
					$response = $exception->getMessage();
				}
				wp_send_json_error($response);
			}
		}
		wp_send_json_error($errors);
	}

	public static function fetch_staff_leaves()
	{
		$current_user = WLSM_M_Role::can('manage_staff_leaves');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		global $wpdb;

		$page_url = WLSM_M_Staff_General::get_staff_leaves_page_url();

		$query = WLSM_M_Staff_General::fetch_staff_leave_query($school_id);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_M_Staff_General::fetch_staff_leave_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(a.name LIKE "%' . $search_value . '%") OR ' .
					'(a.phone LIKE "%' . $search_value . '%") OR ' .
					'(lv.description LIKE "%' . $search_value . '%") OR ' .
					'(u.user_login LIKE "%' . $search_value . '%")';

				$search_value_lowercase = strtolower($search_value);
				if (preg_match('/^unappr(|o|ove|oved)$/', $search_value_lowercase)) {
					$is_approved = 0;
				} elseif (preg_match('/^appr(|o|ove|oved)$/', $search_value_lowercase)) {
					$is_approved = 1;
				}
				if (isset($is_approved)) {
					$condition .= ' OR (lv.is_approved = ' . $is_approved . ')';
				}

				$start_date = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($start_date) {
					$format_start_date = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$start_date) {
							$start_date        = DateTime::createFromFormat('m-Y', $search_value);
							$format_start_date = 'Y-m';
						}
					} else if ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$start_date) {
							$start_date        = DateTime::createFromFormat('m/Y', $search_value);
							$format_start_date = 'Y-m';
						}
					} else if ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$start_date) {
							$start_date        = DateTime::createFromFormat('Y-m', $search_value);
							$format_start_date = 'Y-m';
						}
					} else if ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$start_date) {
							$start_date        = DateTime::createFromFormat('Y/m', $search_value);
							$format_start_date = 'Y-m';
						}
					}

					if (!$start_date) {
						$start_date        = DateTime::createFromFormat('Y', $search_value);
						$format_start_date = 'Y';
					}
				}

				if ($start_date && isset($format_start_date)) {
					$start_date = $start_date->format($format_start_date);
					$start_date = ' OR (lv.start_date LIKE "%' . $start_date . '%")';

					$condition .= $start_date;
				}

				$end_date = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($end_date) {
					$format_end_date = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$end_date) {
							$end_date        = DateTime::createFromFormat('m-Y', $search_value);
							$format_end_date = 'Y-m';
						}
					} else if ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$end_date) {
							$end_date        = DateTime::createFromFormat('m/Y', $search_value);
							$format_end_date = 'Y-m';
						}
					} else if ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$end_date) {
							$end_date        = DateTime::createFromFormat('Y-m', $search_value);
							$format_end_date = 'Y-m';
						}
					} else if ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$end_date) {
							$end_date        = DateTime::createFromFormat('Y/m', $search_value);
							$format_end_date = 'Y-m';
						}
					}

					if (!$end_date) {
						$end_date        = DateTime::createFromFormat('Y', $search_value);
						$format_end_date = 'Y';
					}
				}

				if ($end_date && isset($format_end_date)) {
					$end_date = $end_date->format($format_end_date);
					$end_date = ' OR (lv.end_date LIKE "%' . $end_date . '%")';

					$condition .= $end_date;
				}

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('a.name', 'a.phone', 'u.user_login', 'lv.description', 'lv.start_date', 'lv.is_approved');
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY lv.ID DESC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_M_Staff_General::fetch_staff_leave_query_count($school_id);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {

				if ($row->end_date) {
					$leave_date = sprintf(
						wp_kses(
							/* translators: 1: leave start date, 2: leave end date */
							__('<span class="wlsm-font-bold">%1$s</span> to <br><span class="wlsm-font-bold">%2$s</span>', 'school-management'),
							array('span' => array('class' => array()), 'br' => array())
						),
						esc_html(WLSM_Config::get_date_text($row->start_date)),
						esc_html(WLSM_Config::get_date_text($row->end_date))
					);
				} else {
					$leave_date = '<span class="wlsm-font-bold">' . esc_html(WLSM_Config::get_date_text($row->start_date)) . '</span>';
				}

				// Table columns.
				$data[] = array(
					esc_html(WLSM_M_Staff_Class::get_name_text($row->name)),
					esc_html(WLSM_M_Staff_Class::get_phone_text($row->phone)),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->username)),
					esc_html(WLSM_Config::limit_string(WLSM_M_Staff_Class::get_name_text($row->description))),
					$leave_date,
					WLSM_M_Staff_Class::get_leave_approval_text($row->is_approved, true),
					'<a class="text-primary" href="' . esc_url($page_url . "&action=save&id=" . $row->ID) . '"><span class="dashicons dashicons-edit"></span></a>&nbsp;&nbsp;
					<a class="text-danger wlsm-delete-staff-leave" data-nonce="' . esc_attr(wp_create_nonce('delete-staff-leave-' . $row->ID)) . '" data-staff-leave="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '" data-message-content="' . esc_attr__('This will delete the staff leave.', 'school-management') . '" data-cancel="' . esc_attr__('Cancel', 'school-management') . '" data-submit="' . esc_attr__('Confirm', 'school-management') . '"><span class="dashicons dashicons-trash"></span></a>'
				);
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data,
		);

		echo json_encode($output);
		die();
	}

	public static function save_staff_leave()
	{
		$current_user = WLSM_M_Role::can('manage_staff_leaves');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$staff_leave_id = isset($_POST['staff_leave_id']) ? absint($_POST['staff_leave_id']) : 0;

			if ($staff_leave_id) {
				if (!wp_verify_nonce($_POST['edit-staff-leave-' . $staff_leave_id], 'edit-staff-leave-' . $staff_leave_id)) {
					die();
				}
			} else {
				if (!wp_verify_nonce($_POST['add-staff-leave'], 'add-staff-leave')) {
					die();
				}
			}

			// Checks if staff leave exists.
			if ($staff_leave_id) {
				$staff_leave = WLSM_M_Staff_General::get_staff_leave($school_id, $staff_leave_id);

				if (!$staff_leave) {
					throw new Exception(esc_html__('Leave not found.', 'school-management'));
				}
			}

			$description   = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
			$start_date    = isset($_POST['start_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['start_date'])) : NULL;
			$end_date      = isset($_POST['end_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['end_date'])) : NULL;
			$admin_id      = isset($_POST['staff']) ? absint($_POST['staff']) : 0;
			$is_approved   = isset($_POST['is_approved']) ? (bool) $_POST['is_approved'] : 0;
			$multiple_days = isset($_POST['multiple_days']) ? (bool) $_POST['multiple_days'] : 0;

			// Start validation.
			$errors = array();

			if (empty($admin_id)) {
				$errors['keyword'] = esc_html__('Please search and select a staff.', 'school-management');
				wp_send_json_error($errors);
			}

			// Checks if active staff exists.
			$staff = WLSM_M_Staff_General::get_active_admin($school_id, $admin_id);

			if (!$staff) {
				throw new Exception(esc_html__('Staff not found.', 'school-management'));
			}

			if ($multiple_days) {
				if ($start_date >= $end_date) {
					$errors['start_date'] = esc_html__('Start date must be lower than end date.', 'school-management');
				}
			}

			if (empty($start_date)) {
				if ($multiple_days) {
					$errors['start_date'] = esc_html__('Please specify leave start date.', 'school-management');
				} else {
					$errors['start_date'] = esc_html__('Please specify leave date.', 'school-management');
				}
			} else {
				$start_date = $start_date->format('Y-m-d');
			}

			if ($multiple_days) {
				if (empty($end_date)) {
					$errors['end_date'] = esc_html__('Please specify leave end date.', 'school-management');
				} else {
					$end_date = $end_date->format('Y-m-d');
				}
			} else {
				$end_date = NULL;
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

				if ($staff_leave_id) {
					$message = esc_html__('Leave updated successfully.', 'school-management');
					$reset   = false;
				} else {
					$message = esc_html__('Leave added successfully.', 'school-management');
					$reset   = true;
				}

				// Staff leave data.
				$data = array(
					'admin_id'    => $admin_id,
					'description' => $description,
					'start_date'  => $start_date,
					'end_date'    => $end_date,
					'is_approved' => $is_approved,
					'school_id'   => $school_id,
				);

				if ($staff_leave_id) {
					$data['updated_at'] = current_time('Y-m-d H:i:s');

					if ($is_approved && (!$staff_leave->approved_by)) {
						$data['approved_by'] = get_current_user_id();
					}

					$success = $wpdb->update(WLSM_LEAVES, $data, array('ID' => $staff_leave_id));
				} else {
					$data['added_by'] = get_current_user_id();

					if ($is_approved) {
						$data['approved_by'] = get_current_user_id();
					}

					$data['created_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->insert(WLSM_LEAVES, $data);
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				wp_send_json_success(array('message' => $message, 'reset' => $reset));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function delete_staff_leave()
	{
		$current_user = WLSM_M_Role::can('manage_staff_leaves');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$staff_leave_id = isset($_POST['staff_leave_id']) ? absint($_POST['staff_leave_id']) : 0;

			if (!wp_verify_nonce($_POST['delete-staff-leave-' . $staff_leave_id], 'delete-staff-leave-' . $staff_leave_id)) {
				die();
			}

			// Checks if staff leave exists.
			$staff_leave = WLSM_M_Staff_General::get_staff_leave($school_id, $staff_leave_id);

			if (!$staff_leave) {
				throw new Exception(esc_html__('Leave not found.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			$success = $wpdb->delete(WLSM_LEAVES, array('ID' => $staff_leave_id));
			$message = esc_html__('Leave deleted successfully.', 'school-management');

			$exception = ob_get_clean();
			if (!empty($exception)) {
				throw new Exception($exception);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function fetch_roles()
	{
		$current_user = WLSM_M_Role::can('manage_roles');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		global $wpdb;

		$page_url = WLSM_M_Staff_General::get_roles_page_url();

		$query = WLSM_M_Staff_General::fetch_role_query($school_id);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_M_Staff_General::fetch_role_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(r.name LIKE "%' . $search_value . '%")';

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('r.name');
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY r.name ASC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_M_Staff_General::fetch_role_query_count($school_id);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {
				// Table columns.
				$data[] = array(
					esc_html($row->name),
					'<a class="text-primary" href="' . esc_url($page_url . "&action=save&id=" . $row->ID) . '"><span class="dashicons dashicons-edit"></span></a>&nbsp;&nbsp;
					<a class="text-danger wlsm-delete-role" data-nonce="' . esc_attr(wp_create_nonce('delete-role-' . $row->ID)) . '" data-role="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '" data-message-content="' . esc_attr__('This will delete the role.', 'school-management') . '" data-cancel="' . esc_attr__('Cancel', 'school-management') . '" data-submit="' . esc_attr__('Confirm', 'school-management') . '"><span class="dashicons dashicons-trash"></span></a>'
				);
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data,
		);

		echo json_encode($output);
		die();
	}

	public static function save_role()
	{
		$current_user = WLSM_M_Role::can('manage_roles');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			$role_id = isset($_POST['role_id']) ? absint($_POST['role_id']) : 0;

			if ($role_id) {
				if (!wp_verify_nonce($_POST['edit-role-' . $role_id], 'edit-role-' . $role_id)) {
					die();
				}

				WLSM_Helper::check_demo();
			} else {
				if (!wp_verify_nonce($_POST['add-role'], 'add-role')) {
					die();
				}
			}

			// Checks if role exists.
			if ($role_id) {
				$role = WLSM_M_Staff_General::get_role($school_id, $role_id);

				if (!$role) {
					throw new Exception(esc_html__('Role not found.', 'school-management'));
				}
			}

			$name        = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
			$permissions = (isset($_POST['permission']) && is_array($_POST['permission'])) ? $_POST['permission'] : array();

			// Start validation.
			$errors = array();

			if (empty($name)) {
				$errors['name'] = esc_html__('Please provide role name.', 'school-management');
			}
			if (strlen($name) > 60) {
				$errors['name'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}

			if (WLSM_M_Role::get_admin_key() === strtolower(trim($name))) {
				$errors['name'] = esc_html__('This role is reserved for school administrators.', 'school-management');
				wp_send_json_error($errors);
			}

			// Checks if role already exists with this name.
			if ($role_id) {
				$role_exist = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) as count FROM ' . WLSM_ROLES . ' as r WHERE r.name = %s AND r.school_id = %d AND r.ID != %d', $name, $school_id, $role_id));
			} else {
				$role_exist = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) as count FROM ' . WLSM_ROLES . ' as r WHERE r.name = %s AND r.school_id = %d', $name, $school_id));
			}

			if ($role_exist) {
				$errors['name'] = esc_html__('Role already exists with this name.', 'school-management');
			}

			$permissions = array_intersect($permissions, array_keys(WLSM_M_Role::get_permissions()));
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

				if ($role_id) {
					$message = esc_html__('Role updated successfully.', 'school-management');
					$reset   = false;
				} else {
					$message = esc_html__('Role added successfully.', 'school-management');
					$reset   = true;
				}

				$permissions = serialize($permissions);

				// Role data.
				$data = array(
					'name'        => $name,
					'permissions' => $permissions,
				);

				if ($role_id) {
					$data['updated_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->update(WLSM_ROLES, $data, array('ID' => $role_id, 'school_id' => $school_id));
				} else {
					$data['created_at'] = current_time('Y-m-d H:i:s');

					$data['school_id'] = $school_id;

					$success = $wpdb->insert(WLSM_ROLES, $data);
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				wp_send_json_success(array('message' => $message, 'reset' => $reset));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function delete_role()
	{
		$current_user = WLSM_M_Role::can('manage_roles');

		if (!$current_user) {
			die();
		}

		WLSM_Helper::check_demo();

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			$role_id = isset($_POST['role_id']) ? absint($_POST['role_id']) : 0;

			if (!wp_verify_nonce($_POST['delete-role-' . $role_id], 'delete-role-' . $role_id)) {
				die();
			}

			// Checks if role exists.
			$role = WLSM_M_Staff_General::get_role($school_id, $role_id);

			if (!$role) {
				throw new Exception(esc_html__('Role not found.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			$success = $wpdb->delete(WLSM_ROLES, array('ID' => $role_id));
			$message = esc_html__('Role deleted successfully.', 'school-management');

			$exception = ob_get_clean();
			if (!empty($exception)) {
				throw new Exception($exception);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function get_role_permissions()
	{
		$current_user = WLSM_M_Role::can('manage_employees');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['nonce'], 'get-role-permissions')) {
				die();
			}

			$role_id = isset($_POST['role_id']) ? absint($_POST['role_id']) : 0;

			// Checks if role exists in the school.
			$role = WLSM_M_Staff_General::fetch_role($school_id, $role_id);

			if (!$role) {
				throw new Exception(esc_html__('Role not found.', 'school-management'));
			}

			$permissions = array();

			if ($role->permissions) {
				$permissions = $role->permissions;
				if (is_serialized($permissions)) {
					$permissions = unserialize($permissions);
				}
			}

			wp_send_json($permissions);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json(array());
		}
	}

	public static function fetch_certificates()
	{
		$current_user = WLSM_M_Role::can('manage_certificates');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		global $wpdb;

		$page_url = WLSM_M_Staff_General::get_certificates_page_url();

		$query = WLSM_M_Staff_General::fetch_certificate_query($school_id, $session_id);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_M_Staff_General::fetch_certificate_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(cf.label LIKE "%' . $search_value . '%")';

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('cf.label', 'students_count');
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY cf.ID DESC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_M_Staff_General::fetch_certificate_query_count($school_id, $session_id);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {

				// Table columns.
				$data[] = array(
					esc_html(WLSM_M_Staff_Class::get_certificate_label_text($row->label)),
					'<a class="text-primary wlsm-font-bold" href="' . esc_url($page_url . "&action=students&id=" . $row->ID) . '">' . absint($row->students_count) . '</a>',
					'<a class="text-primary wlsm-font-bold" href="' . esc_url($page_url . "&action=distribute&id=" . $row->ID) . '">' . esc_html__('Distribute Certificate', 'school-management') . '</a>',
					'<a class="text-primary" href="' . esc_url($page_url . "&action=save&id=" . $row->ID) . '"><span class="dashicons dashicons-edit"></span></a>&nbsp;&nbsp;
					<a class="text-danger wlsm-delete-certificate" data-nonce="' . esc_attr(wp_create_nonce('delete-certificate-' . $row->ID)) . '" data-certificate="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '" data-message-content="' . esc_attr__('This will delete the certificate.', 'school-management') . '" data-cancel="' . esc_attr__('Cancel', 'school-management') . '" data-submit="' . esc_attr__('Confirm', 'school-management') . '"><span class="dashicons dashicons-trash"></span></a>'
				);
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data,
		);

		echo json_encode($output);
		die();
	}

	public static function save_certificate()
	{
		$current_user = WLSM_M_Role::can('manage_certificates');

		if (!$current_user) {
			die();
		}

		$fields = WLSM_Helper::get_certificate_dynamic_fields();

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			$page_url = WLSM_M_Staff_General::get_certificates_page_url();

			$certificate_id = isset($_POST['certificate_id']) ? absint($_POST['certificate_id']) : 0;

			if ($certificate_id) {
				if (!wp_verify_nonce($_POST['edit-certificate-' . $certificate_id], 'edit-certificate-' . $certificate_id)) {
					die();
				}
			} else {
				if (!wp_verify_nonce($_POST['add-certificate'], 'add-certificate')) {
					die();
				}
			}

			// Checks if certificate exists.
			if ($certificate_id) {
				$certificate = WLSM_M_Staff_General::get_certificate($school_id, $certificate_id);

				if (!$certificate) {
					throw new Exception(esc_html__('Certificate not found.', 'school-management'));
				}
			}

			$label = isset($_POST['label']) ? sanitize_text_field($_POST['label']) : '';
			$image = (isset($_FILES['image']) && is_array($_FILES['image'])) ? $_FILES['image'] : NULL;

			// Start validation.
			$errors = array();

			if (empty($label)) {
				$errors['label'] = esc_html__('Please provide certificate title.', 'school-management');
			}
			if (strlen($label) > 191) {
				$errors['label'] = esc_html__('Maximum length cannot exceed 191 characters.', 'school-management');
			}

			if (isset($image['tmp_name']) && !empty($image['tmp_name'])) {
				if (!WLSM_Helper::is_valid_file($image, 'image')) {
					$errors['image'] = esc_html__('Please provide certificate image in JPG, JPEG or PNG format.', 'school-management');
				}
			}

			$fields_to_save = array();

			if ($certificate_id) {
				foreach ($fields as $field_key => $field_value) {
					if (isset($_POST['enable-' . $field_key]) && ((bool) $_POST['enable-' . $field_key])) {
						$field_data = array(
							'enable' => 1,
						);
					} else {
						$field_data = array(
							'enable' => 0,
						);
					}

					$field_data['props'] = array();
					foreach ($field_value['props'] as $key => $prop) {
						$unit  = $prop['unit'];
						$value = $prop['value'];

						if (isset($_POST[$field_key . '-' . $key])) {
							$value = $_POST[$field_key . '-' . $key];
						}

						$field_data['props'][$key] = array(
							'value' => $value,
							'unit'  => $unit
						);
					}

					$fields_to_save[$field_key] = $field_data;
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

				if ($certificate_id) {
					$message = esc_html__('Certificate updated successfully.', 'school-management');
					$reset   = false;
				} else {
					$message = esc_html__('Certificate added successfully.', 'school-management');
					$reset   = true;
				}

				// Certificate data.
				$data = array(
					'label'  => $label,
					'fields' => count($fields_to_save) ? serialize($fields_to_save) : NULL,
				);

				if ($certificate_id) {
					$data['image_id'] = $certificate->image_id;
				}

				if (!empty($image)) {
					$image = media_handle_upload('image', 0);
					if (is_wp_error($image)) {
						throw new Exception($image->get_error_message());
					}
					$data['image_id'] = $image;
				}

				if ($certificate_id) {
					$data['updated_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->update(WLSM_CERTIFICATES, $data, array('ID' => $certificate_id, 'school_id' => $school_id));
				} else {
					$data['created_at'] = current_time('Y-m-d H:i:s');

					$data['school_id'] = $school_id;

					$success = $wpdb->insert(WLSM_CERTIFICATES, $data);

					$certificate_id = $wpdb->insert_id;
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				wp_send_json_success(array('message' => $message, 'reset' => $reset, 'url' => esc_url($page_url) . '&action=save&id=' . $certificate_id));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function delete_certificate()
	{
		$current_user = WLSM_M_Role::can('manage_certificates');

		if (!$current_user) {
			die();
		}

		WLSM_Helper::check_demo();

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			$certificate_id = isset($_POST['certificate_id']) ? absint($_POST['certificate_id']) : 0;

			if (!wp_verify_nonce($_POST['delete-certificate-' . $certificate_id], 'delete-certificate-' . $certificate_id)) {
				die();
			}

			// Checks if certificate exists.
			$certificate = WLSM_M_Staff_General::get_certificate($school_id, $certificate_id);

			if (!$certificate) {
				throw new Exception(esc_html__('Certificate not found.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			$success = $wpdb->delete(WLSM_CERTIFICATES, array('ID' => $certificate_id));
			$message = esc_html__('Certificate deleted successfully.', 'school-management');

			$exception = ob_get_clean();
			if (!empty($exception)) {
				throw new Exception($exception);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			if ($certificate->image_id) {
				$attachment_id_to_delete = $certificate->image_id;
			}

			if (isset($attachment_id_to_delete)) {
				wp_delete_attachment($attachment_id_to_delete, true);
			}

			$wpdb->query('COMMIT;');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function distribute_certificate()
	{
		$current_user = WLSM_M_Role::can('manage_certificates');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$page_url = WLSM_M_Staff_General::get_certificates_page_url();

			$certificate_id = isset($_POST['certificate_id']) ? absint($_POST['certificate_id']) : 0;

			if (!wp_verify_nonce($_POST['distribute-certificate-' . $certificate_id], 'distribute-certificate-' . $certificate_id)) {
				die();
			}

			// Checks if certificate exists.
			$certificate = WLSM_M_Staff_General::get_certificate($school_id, $certificate_id);

			if (!$certificate) {
				throw new Exception(esc_html__('Certificate not found.', 'school-management'));
			}

			$student_ids = (isset($_POST['student']) && is_array($_POST['student'])) ? $_POST['student'] : array();
			$date_issued = isset($_POST['date_issued']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['date_issued'])) : NULL;

			// Start validation.
			$errors = array();

			if (!count($student_ids)) {
				$errors['student[]'] = esc_html__('Please select at least one student.', 'school-management');
			} else {
				// Checks if students exists.
				$students_count = WLSM_M_Staff_General::get_students_count($school_id, $session_id, $student_ids, false, false);

				if ($students_count != count($student_ids)) {
					throw new Exception(esc_html__('Student(s) not found.', 'school-management'));
				}
			}

			if (empty($date_issued)) {
				$errors['date_issued'] = esc_html__('Please specify date issued.', 'school-management');
			} else {
				$date_issued = $date_issued->format('Y-m-d');
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

				// Student certificate data.
				$certificate_student_data = array(
					'certificate_id' => $certificate_id,
					'date_issued'    => $date_issued,
				);

				foreach ($student_ids as $student_id) {
					$certificate_student_data['student_record_id'] = $student_id;

					// Checks if student already has this certificate issued.
					$certificate_exists = $wpdb->get_row(
						$wpdb->prepare('SELECT cfsr.ID FROM ' . WLSM_CERTIFICATE_STUDENT . ' as cfsr WHERE cfsr.certificate_id = %d AND cfsr.student_record_id = %d', $certificate_id, $certificate_student_data['student_record_id'])
					);

					if ($certificate_exists) {
						$enrollment_number = WLSM_M_Staff_General::get_student_enrollment_number($student_id);
						throw new Exception(
							sprintf(
								/* translators: %s: enrollment number */
								esc_html__('Certificate already issued for enrollment number %s.', 'school-management'),
								$enrollment_number
							)
						);
					}

					$certificate_number = WLSM_M_Staff_General::get_certificate_number($school_id);

					$certificate_student_data['certificate_number'] = $certificate_number;

					$certificate_student_data['created_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->insert(WLSM_CERTIFICATE_STUDENT, $certificate_student_data);
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$message = esc_html__('Certificate distributed successfully.', 'school-management');
				$reset   = true;

				$wpdb->query('COMMIT;');

				wp_send_json_success(array('message' => $message, 'reset' => $reset, 'url' => esc_url($page_url) . '&action=students&id=' . $certificate_id));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function fetch_certificates_distributed()
	{
		$current_user = WLSM_M_Role::can('manage_certificates');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		global $wpdb;

		$certificate_id = isset($_POST['certificate']) ? absint($_POST['certificate']) : 0;

		if (!wp_verify_nonce($_POST['certificate-' . $certificate_id], 'certificate-' . $certificate_id)) {
			die();
		}

		$page_url = WLSM_M_Staff_General::get_certificates_page_url();

		$query = WLSM_M_Staff_General::fetch_certificates_distributed_query($school_id, $session_id, $certificate_id);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_M_Staff_General::fetch_certificates_distributed_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(cfsr.certificate_number LIKE "%' . $search_value . '%") OR ' .
					'(sr.enrollment_number LIKE "%' . $search_value . '%") OR ' .
					'(sr.name LIKE "%' . $search_value . '%") OR ' .
					'(c.label LIKE "%' . $search_value . '%") OR ' .
					'(se.label LIKE "%' . $search_value . '%") OR ' .
					'(sr.roll_number LIKE "%' . $search_value . '%") OR ' .
					'(sr.phone LIKE "%' . $search_value . '%")';

				$date_issued = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($date_issued) {
					$format_date_issued = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$date_issued) {
							$date_issued        = DateTime::createFromFormat('m-Y', $search_value);
							$format_date_issued = 'Y-m';
						}
					} elseif ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$date_issued) {
							$date_issued        = DateTime::createFromFormat('m/Y', $search_value);
							$format_date_issued = 'Y-m';
						}
					} elseif ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$date_issued) {
							$date_issued        = DateTime::createFromFormat('Y-m', $search_value);
							$format_date_issued = 'Y-m';
						}
					} elseif ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$date_issued) {
							$date_issued        = DateTime::createFromFormat('Y/m', $search_value);
							$format_date_issued = 'Y-m';
						}
					}

					if (!$date_issued) {
						$date_issued        = DateTime::createFromFormat('Y', $search_value);
						$format_date_issued = 'Y';
					}
				}

				if ($date_issued && isset($format_date_issued)) {
					$date_issued = $date_issued->format($format_date_issued);
					$date_issued = ' OR (cfsr.date_issued LIKE "%' . $date_issued . '%")';

					$condition .= $date_issued;
				}

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('cfsr.certificate_number', 'sr.enrollment_number', 'sr.name', 'c.label', 'se.label', 'sr.roll_number', 'cfsr.date_issued', 'sr.phone');
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY cfsr.ID DESC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_M_Staff_General::fetch_certificates_distributed_query_count($school_id, $session_id, $certificate_id);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {

				// Table columns.
				$data[] = array(
					esc_html($row->certificate_number),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->student_name)),
					esc_html($row->enrollment_number),
					esc_html(WLSM_M_Class::get_label_text($row->class_label)),
					esc_html(WLSM_M_Staff_Class::get_section_label_text($row->section_label)),
					esc_html(WLSM_M_Staff_Class::get_roll_no_text($row->roll_number)),
					esc_html(WLSM_Config::get_date_text($row->date_issued)),
					esc_html(WLSM_M_Staff_Class::get_phone_text($row->phone)),
					'<a class="text-primary wlsm-font-bold" href="' . esc_url($page_url . "&action=students&certificate_student_id=" . $row->ID) . '"><span class="dashicons dashicons-search"></span></a>&nbsp;
					<a class="text-danger wlsm-delete-certificate-distributed" data-nonce="' . esc_attr(wp_create_nonce('delete-certificate-distributed-' . $row->ID)) . '" data-certificate-distributed="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '" data-message-content="' . esc_attr__('This will delete the student certificate.', 'school-management') . '" data-cancel="' . esc_attr__('Cancel', 'school-management') . '" data-submit="' . esc_attr__('Confirm', 'school-management') . '"><span class="dashicons dashicons-trash"></span></a>'
				);
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data,
		);

		echo json_encode($output);
		die();
	}

	public static function delete_certificate_distributed()
	{
		$current_user = WLSM_M_Role::can('manage_certificates');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$certificate_student_id = isset($_POST['certificate_student_id']) ? absint($_POST['certificate_student_id']) : 0;

			if (!wp_verify_nonce($_POST['delete-certificate-distributed-' . $certificate_student_id], 'delete-certificate-distributed-' . $certificate_student_id)) {
				die();
			}

			// Checks if student certificate exists in the school.
			$certificate_student = WLSM_M_Staff_General::get_certificate_distributed($school_id, $session_id, $certificate_student_id);

			if (!$certificate_student) {
				throw new Exception(esc_html__('Student certificate not found.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			$success = $wpdb->delete(WLSM_CERTIFICATE_STUDENT, array('ID' => $certificate_student_id));
			$message = esc_html__('Student certificate deleted successfully.', 'school-management');

			$exception = ob_get_clean();
			if (!empty($exception)) {
				throw new Exception($exception);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function send_notification()
	{
		$current_user = WLSM_M_Role::can('send_notifications');

		if (!$current_user) {
			die();
		}

		if (!wp_verify_nonce($_POST['send-notification'], 'send-notification')) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$student_ids = (isset($_POST['student']) && is_array($_POST['student'])) ? $_POST['student'] : array();

		$send_email = (isset($_POST['send_email'])) ? (bool) $_POST['send_email'] : false;
		$send_sms   = (isset($_POST['send_sms'])) ? (bool) $_POST['send_sms'] : false;

		$email_subject = (isset($_POST['email_subject'])) ? sanitize_text_field($_POST['email_subject']) : '';
		$email_body    = (isset($_POST['email_body'])) ? wp_kses_post($_POST['email_body']) : '';

		$sms_message = (isset($_POST['sms_message'])) ? sanitize_text_field($_POST['sms_message']) : '';

		$to_parent_phone = (isset($_POST['to_parent_phone'])) ? (bool) $_POST['to_parent_phone'] : false;

		try {
			ob_start();
			global $wpdb;

			// Start validation.
			$errors = array();

			if (!count($student_ids)) {
				$errors['student[]'] = esc_html__('Please select at least one student.', 'school-management');
			} else {
				// Checks if students exists.
				$students_count = WLSM_M_Staff_General::get_students_count($school_id, $session_id, $student_ids, false, false);

				if ($students_count != count($student_ids)) {
					throw new Exception(esc_html__('Student(s) not found.', 'school-management'));
				}
			}

			if (!$send_email && !$send_sms) {
				throw new Exception(esc_html__('Please send either email or sms notfication.', 'school-management'));
			} else {
				if ($send_email) {
					if (empty($email_subject)) {
						$errors['email_subject'] = esc_html__('Please provide email subject.', 'school-management');
					}

					if (empty($email_body)) {
						$errors['email_body'] = esc_html__('Please provide email body.', 'school-management');
					}
				}

				if ($send_sms) {
					if (empty($sms_message)) {
						$errors['sms_message'] = esc_html__('Please provide message.', 'school-management');
					}
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
				foreach ($student_ids as $student_id) {
					// Notify for custom message.
					$data = array(
						'school_id'  => $school_id,
						'session_id' => $session_id,
						'student_id' => $student_id,
						'email'      => array(
							'send'    => $send_email,
							'subject' => $email_subject,
							'body'    => $email_body
						),
						'sms'        => array(
							'send'      => $send_sms,
							'message'   => $sms_message,
							'to_parent' => $to_parent_phone
						)
					);

					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_custom_message', $data);
				}

				$message = esc_html__('Notifications has been scheduled in a queue.', 'school-management');
				$reset   = false;

				wp_send_json_success(array('message' => $message, 'reset' => $reset));
			} catch (Exception $exception) {
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function fetch_inquiries()
	{
		$current_user = WLSM_M_Role::can('manage_inquiries');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		global $wpdb;

		$gdpr_enable = get_option('wlsm_gdpr_enable');

		$page_url = WLSM_M_Staff_General::get_inquiries_page_url();

		$query = WLSM_M_Staff_General::fetch_inquiry_query($school_id);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_M_Staff_General::fetch_inquiry_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(iq.name LIKE "%' . $search_value . '%") OR ' .
					'(iq.phone LIKE "%' . $search_value . '%") OR ' .
					'(iq.email LIKE "%' . $search_value . '%") OR ' .
					'(iq.message LIKE "%' . $search_value . '%") OR ' .
					'(iq.note LIKE "%' . $search_value . '%") OR ' .
					'(c.label LIKE "%' . $search_value . '%")';

				$search_value_lowercase = strtolower($search_value);
				if (preg_match('/^inac(|t|ti|tiv|tive)$/', $search_value_lowercase)) {
					$is_active = 0;
				} elseif (preg_match('/^acti(|v|ve)$/', $search_value_lowercase)) {
					$is_active = 1;
				}
				if (isset($is_active)) {
					$condition .= ' OR (iq.is_active = ' . $is_active . ')';
				}

				$created_at = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($created_at) {
					$format_created_at = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('m-Y', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('m/Y', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('Y-m', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('Y/m', $search_value);
							$format_created_at = 'Y-m';
						}
					}

					if (!$created_at) {
						$created_at        = DateTime::createFromFormat('Y', $search_value);
						$format_created_at = 'Y';
					}
				}

				if ($created_at && isset($format_created_at)) {
					$created_at = $created_at->format($format_created_at);
					$created_at = ' OR (iq.created_at LIKE "%' . $created_at . '%")';

					$condition .= $created_at;
				}

				$next_follow_up = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($next_follow_up) {
					$format_next_follow_up = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$next_follow_up) {
							$next_follow_up        = DateTime::createFromFormat('m-Y', $search_value);
							$format_next_follow_up = 'Y-m';
						}
					} elseif ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$next_follow_up) {
							$next_follow_up        = DateTime::createFromFormat('m/Y', $search_value);
							$format_next_follow_up = 'Y-m';
						}
					} elseif ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$next_follow_up) {
							$next_follow_up        = DateTime::createFromFormat('Y-m', $search_value);
							$format_next_follow_up = 'Y-m';
						}
					} elseif ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$next_follow_up) {
							$next_follow_up        = DateTime::createFromFormat('Y/m', $search_value);
							$format_next_follow_up = 'Y-m';
						}
					}

					if (!$next_follow_up) {
						$next_follow_up        = DateTime::createFromFormat('Y', $search_value);
						$format_next_follow_up = 'Y';
					}
				}

				if ($next_follow_up && isset($format_next_follow_up)) {
					$next_follow_up = $next_follow_up->format($format_next_follow_up);
					$next_follow_up = ' OR (iq.next_follow_up LIKE "%' . $next_follow_up . '%")';

					$condition .= $next_follow_up;
				}

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('c.label', 'iq.name', 'iq.phone', 'iq.email', 'iq.message', 'iq.created_at', 'iq.next_follow_up', 'iq.is_active');
		if ($gdpr_enable) {
			array_push($columns, 'iq.gdpr_agreed');
		}
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY iq.ID DESC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_M_Staff_General::fetch_inquiry_query_count($school_id);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			$students_page_url = WLSM_M_Staff_General::get_students_page_url();
			foreach ($filter_rows_limit as $row) {
				if ($row->message) {
					$view_message = '<a class="text-primary wlsm-view-inquiry-message" data-nonce="' . esc_attr(wp_create_nonce('view-inquiry-message-' . $row->ID)) . '" data-inquiry="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Inquiry Message', 'school-management') . '" data-close="' . esc_attr__('Close', 'school-management') . '"><span class="dashicons dashicons-search"></span></a>';
				} else {
					$view_message = '-';
				}

				if ($row->is_active) {
					$add_admission = '<br><small class="wlsm-font-bold"><a href="' . esc_url($students_page_url . '&action=save&inquiry_id=' . $row->ID) . '">' . esc_html__('Add Admission', 'school-management') . '</small></a>';
				} else {
					$add_admission = '';
				}

				$inquiry_data = array(
					esc_html(WLSM_M_Class::get_label_text($row->class_label)),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->name)),
					esc_html(WLSM_M_Staff_Class::get_phone_text($row->phone)),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->email)),
					$view_message,
					esc_html(WLSM_Config::get_date_text($row->created_at)),
					esc_html($row->next_follow_up ? WLSM_Config::get_date_text($row->next_follow_up) : '-'),
					esc_html(WLSM_M_Staff_General::get_inquiry_status_text($row->is_active)) . $add_admission
				);

				if ($gdpr_enable) {
					array_push($inquiry_data, esc_html(WLSM_M_Staff_General::get_gdpr_text($row->gdpr_agreed)));
				}

				array_push($inquiry_data, '<a class="text-primary" href="' . esc_url($page_url . "&action=save&id=" . $row->ID) . '"><span class="dashicons dashicons-edit"></span></a>&nbsp;&nbsp;
					<a class="text-danger wlsm-delete-inquiry" data-nonce="' . esc_attr(wp_create_nonce('delete-inquiry-' . $row->ID)) . '" data-inquiry="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '" data-message-content="' . esc_attr__('This will delete the inquiry.', 'school-management') . '" data-cancel="' . esc_attr__('Cancel', 'school-management') . '" data-submit="' . esc_attr__('Confirm', 'school-management') . '"><span class="dashicons dashicons-trash"></span></a>');

				// Table columns.
				$data[] = $inquiry_data;
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data,
			'export'          => array(
				'nonce'  => wp_create_nonce('export-staff-inquiries-table'),
				'action' => 'wlsm-export-staff-inquiries-table',
				'filter' => ''
			)
		);

		echo json_encode($output);
		die();
	}

	public static function save_inquiry()
	{
		$current_user = WLSM_M_Role::can('manage_inquiries');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			$inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;

			if ($inquiry_id) {
				if (!wp_verify_nonce($_POST['edit-inquiry-' . $inquiry_id], 'edit-inquiry-' . $inquiry_id)) {
					die();
				}
			} else {
				if (!wp_verify_nonce($_POST['add-inquiry'], 'add-inquiry')) {
					die();
				}
			}

			// Checks if inquiry exists.
			if ($inquiry_id) {
				$inquiry = WLSM_M_Staff_General::get_inquiry($school_id, $inquiry_id);

				if (!$inquiry) {
					throw new Exception(esc_html__('Inquiry not found.', 'school-management'));
				}
			}

			// Inquiry.
			$name     = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
			$phone    = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
			$email    = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '';
			$message  = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
			$class_id = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;

			// Status.
			$is_active      = isset($_POST['is_active']) ? (bool) $_POST['is_active'] : 1;
			$next_follow_up = isset($_POST['next_follow_up']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['next_follow_up'])) : NULL;
			$note           = isset($_POST['note']) ? sanitize_text_field($_POST['note']) : '';

			// Start validation.
			$errors = array();

			if (empty($name)) {
				$errors['name'] = esc_html__('Please specify name.', 'school-management');
			}
			if (strlen($name) > 60) {
				$errors['name'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
			}

			if (!empty($phone) && strlen($phone) > 40) {
				$errors['phone'] = esc_html__('Maximum length cannot exceed 40 characters.', 'school-management');
			}

			if (!empty($email)) {
				if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$errors['email'] = esc_html__('Please provide a valid email.', 'school-management');
				} elseif (strlen($email) > 60) {
					$errors['email'] = esc_html__('Maximum length cannot exceed 60 characters.', 'school-management');
				}
			}

			if (!empty($next_follow_up)) {
				$next_follow_up = $next_follow_up->format('Y-m-d');
			} else {
				$next_follow_up = NULL;
			}

			if (!empty($class_id)) {
				$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);
				if (!$class_school) {
					$errors['class_id'] = esc_html__('Class not found.', 'school-management');
				} else {
					$class_school_id = $class_school->ID;
				}
			} else {
				$class_school_id = NULL;
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

				// Inquiry data.
				$data = array(
					'name'            => $name,
					'phone'           => $phone,
					'email'           => $email,
					'message'         => $message,
					'note'            => $note,
					'is_active'       => $is_active,
					'next_follow_up'  => $next_follow_up,
					'class_school_id' => $class_school_id,
				);

				if ($inquiry_id) {
					$data['updated_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->update(WLSM_INQUIRIES, $data, array('ID' => $inquiry_id, 'school_id' => $school_id));
				} else {
					$data['created_at'] = current_time('Y-m-d H:i:s');

					$data['school_id'] = $school_id;

					$success = $wpdb->insert(WLSM_INQUIRIES, $data);
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				if ($inquiry_id) {
					$message = esc_html__('Inquiry updated successfully.', 'school-management');
					$reset   = false;
				} else {
					$message = esc_html__('Inquiry added successfully.', 'school-management');
					$reset   = true;
				}

				$wpdb->query('COMMIT;');

				wp_send_json_success(array('message' => $message, 'reset' => $reset));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function delete_inquiry()
	{
		$current_user = WLSM_M_Role::can('manage_inquiries');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			$inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;

			if (!wp_verify_nonce($_POST['delete-inquiry-' . $inquiry_id], 'delete-inquiry-' . $inquiry_id)) {
				die();
			}

			// Checks if inquiry exists.
			$inquiry = WLSM_M_Staff_General::get_inquiry($school_id, $inquiry_id);

			if (!$inquiry) {
				throw new Exception(esc_html__('Inquiry not found.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			$success = $wpdb->delete(WLSM_INQUIRIES, array('ID' => $inquiry_id));
			$message = esc_html__('Inquiry deleted successfully.', 'school-management');

			$exception = ob_get_clean();
			if (!empty($exception)) {
				throw new Exception($exception);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$wpdb->query('COMMIT;');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function view_inquiry_message()
	{
		$current_user = WLSM_M_Role::can('manage_inquiries');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$inquiry_id = isset($_POST['inquiry_id']) ? absint($_POST['inquiry_id']) : 0;

			if (!wp_verify_nonce($_POST['view-inquiry-message-' . $inquiry_id], 'view-inquiry-message-' . $inquiry_id)) {
				die();
			}

			// Checks if inquiry exists.
			$inquiry = WLSM_M_Staff_General::get_inquiry_message($school_id, $inquiry_id);

			if (!$inquiry) {
				throw new Exception(esc_html__('Inquiry not found.', 'school-management'));
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

		wp_send_json_success(esc_html(WLSM_Config::get_note_text($inquiry->message)));
	}

	public static function fetch_logs()
	{
		$current_user = WLSM_M_Role::can('manage_logs');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		global $wpdb;

		$page_url = WLSM_Log::get_logs_page_url();

		$query = WLSM_Log::fetch_log_query($school_id);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_Log::fetch_log_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(lg.log_value LIKE "%' . $search_value . '%") OR ' .
					'(lg.log_group LIKE "%' . $search_value . '%")';

				$created_at = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($created_at) {
					$format_created_at = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('m-Y', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('m/Y', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('Y-m', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('Y/m', $search_value);
							$format_created_at = 'Y-m';
						}
					}

					if (!$created_at) {
						$created_at        = DateTime::createFromFormat('Y', $search_value);
						$format_created_at = 'Y';
					}
				}

				if ($created_at && isset($format_created_at)) {
					$created_at = $created_at->format($format_created_at);
					$created_at = ' OR (lg.created_at LIKE "%' . $created_at . '%")';

					$condition .= $created_at;
				}

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('lg.log_value', 'lg.log_group', 'lg.created_at');
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY lg.ID DESC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_Log::fetch_log_query_count($school_id);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {
				// Table columns.
				$data[] = array(
					wp_kses($row->log_value, array('span' => array('class' => array()))),
					esc_html(ucwords($row->log_group)),
					esc_html($row->created_at)
				);
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data
		);

		echo json_encode($output);
		die();
	}

	public static function fetch_staff_leave_requests()
	{
		$user_info = WLSM_M_Role::get_user_info();

		$current_school = $user_info['current_school'];

		if (!$current_school) {
			die;
		}

		$role = $current_school['role'];

		if (WLSM_M_Role::get_employee_key() !== $role) {
			die;
		}

		$school_id = $current_school['id'];

		$admin = WLSM_M_Role::get_user_admin($school_id);

		if (!$admin) {
			throw new Exception(esc_html__('Staff not found.', 'school-management'));
		}

		$admin_id = $admin->ID;

		global $wpdb;

		$query = WLSM_M_Staff_General::fetch_staff_leave_query($school_id, $admin_id);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_M_Staff_General::fetch_staff_leave_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(lv.description LIKE "%' . $search_value . '%")';

				$search_value_lowercase = strtolower($search_value);
				if (preg_match('/^unappr(|o|ove|oved)$/', $search_value_lowercase)) {
					$is_approved = 0;
				} elseif (preg_match('/^appr(|o|ove|oved)$/', $search_value_lowercase)) {
					$is_approved = 1;
				}
				if (isset($is_approved)) {
					$condition .= ' OR (lv.is_approved = ' . $is_approved . ')';
				}

				$start_date = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($start_date) {
					$format_start_date = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$start_date) {
							$start_date        = DateTime::createFromFormat('m-Y', $search_value);
							$format_start_date = 'Y-m';
						}
					} else if ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$start_date) {
							$start_date        = DateTime::createFromFormat('m/Y', $search_value);
							$format_start_date = 'Y-m';
						}
					} else if ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$start_date) {
							$start_date        = DateTime::createFromFormat('Y-m', $search_value);
							$format_start_date = 'Y-m';
						}
					} else if ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$start_date) {
							$start_date        = DateTime::createFromFormat('Y/m', $search_value);
							$format_start_date = 'Y-m';
						}
					}

					if (!$start_date) {
						$start_date        = DateTime::createFromFormat('Y', $search_value);
						$format_start_date = 'Y';
					}
				}

				if ($start_date && isset($format_start_date)) {
					$start_date = $start_date->format($format_start_date);
					$start_date = ' OR (lv.start_date LIKE "%' . $start_date . '%")';

					$condition .= $start_date;
				}

				$end_date = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($end_date) {
					$format_end_date = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$end_date) {
							$end_date        = DateTime::createFromFormat('m-Y', $search_value);
							$format_end_date = 'Y-m';
						}
					} else if ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$end_date) {
							$end_date        = DateTime::createFromFormat('m/Y', $search_value);
							$format_end_date = 'Y-m';
						}
					} else if ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$end_date) {
							$end_date        = DateTime::createFromFormat('Y-m', $search_value);
							$format_end_date = 'Y-m';
						}
					} else if ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$end_date) {
							$end_date        = DateTime::createFromFormat('Y/m', $search_value);
							$format_end_date = 'Y-m';
						}
					}

					if (!$end_date) {
						$end_date        = DateTime::createFromFormat('Y', $search_value);
						$format_end_date = 'Y';
					}
				}

				if ($end_date && isset($format_end_date)) {
					$end_date = $end_date->format($format_end_date);
					$end_date = ' OR (lv.end_date LIKE "%' . $end_date . '%")';

					$condition .= $end_date;
				}

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('lv.description', 'lv.start_date', 'lv.is_approved');
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY lv.ID DESC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_M_Staff_General::fetch_staff_leave_query_count($school_id, $admin_id);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {

				if ($row->end_date) {
					$leave_date = sprintf(
						wp_kses(
							/* translators: 1: leave start date, 2: leave end date */
							__('<span class="wlsm-font-bold">%1$s</span> to <br><span class="wlsm-font-bold">%2$s</span>', 'school-management'),
							array('span' => array('class' => array()), 'br' => array())
						),
						esc_html(WLSM_Config::get_date_text($row->start_date)),
						esc_html(WLSM_Config::get_date_text($row->end_date))
					);
				} else {
					$leave_date = '<span class="wlsm-font-bold">' . esc_html(WLSM_Config::get_date_text($row->start_date)) . '</span>';
				}

				// Table columns.
				$data[] = array(
					esc_html(WLSM_Config::limit_string(WLSM_M_Staff_Class::get_name_text($row->description))),
					$leave_date,
					WLSM_M_Staff_Class::get_leave_approval_text($row->is_approved, true)
				);
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data,
		);

		echo json_encode($output);
		die();
	}

	public static function submit_staff_leave_request()
	{
		if (!wp_verify_nonce($_POST['submit-staff-leave-request'], 'submit-staff-leave-request')) {
			die();
		}

		$user_info = WLSM_M_Role::get_user_info();

		$current_school = $user_info['current_school'];

		if (!$current_school) {
			die;
		}

		$role = $current_school['role'];

		if (WLSM_M_Role::get_employee_key() !== $role) {
			die;
		}

		$school_id = $current_school['id'];

		try {
			ob_start();
			global $wpdb;

			$admin = WLSM_M_Role::get_user_admin($school_id);

			if (!$admin) {
				throw new Exception(esc_html__('Staff not found.', 'school-management'));
			}

			$admin_id = $admin->ID;

			$description   = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
			$start_date    = isset($_POST['start_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['start_date'])) : NULL;
			$end_date      = isset($_POST['end_date']) ? DateTime::createFromFormat(WLSM_Config::date_format(), sanitize_text_field($_POST['end_date'])) : NULL;
			$multiple_days = isset($_POST['multiple_days']) ? (bool) $_POST['multiple_days'] : 0;

			// Start validation.
			$errors = array();

			if ($multiple_days) {
				if ($start_date >= $end_date) {
					$errors['start_date'] = esc_html__('Start date must be lower than end date.', 'school-management');
				}
			}

			if (empty($description)) {
				$errors['description'] = esc_html__('Please specify reason.', 'school-management');
			}

			if (empty($start_date)) {
				if ($multiple_days) {
					$errors['start_date'] = esc_html__('Please specify leave start date.', 'school-management');
				} else {
					$errors['start_date'] = esc_html__('Please specify leave date.', 'school-management');
				}
			} else {
				$start_date = $start_date->format('Y-m-d');
			}

			if ($multiple_days) {
				if (empty($end_date)) {
					$errors['end_date'] = esc_html__('Please specify leave end date.', 'school-management');
				} else {
					$end_date = $end_date->format('Y-m-d');
				}
			} else {
				$end_date = NULL;
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

				// Staff leave data.
				$data = array(
					'admin_id'    => $admin_id,
					'description' => $description,
					'start_date'  => $start_date,
					'end_date'    => $end_date,
					'school_id'   => $school_id,
				);

				$data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_LEAVES, $data);

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$message = esc_html__('Leave request submitted successfully.', 'school-management');

				wp_send_json_success(array('message' => $message, 'reset' => true));
			} catch (Exception $exception) {
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function manage_promotion()
	{
		$current_user = WLSM_M_Role::can('manage_promote');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$session_label = $current_user['session']['label'];

		if (!wp_verify_nonce($_POST['nonce'], 'manage-promotion')) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$new_session_id = isset($_POST['promote_to_session']) ? absint($_POST['promote_to_session']) : 0;
			$from_class_id  = isset($_POST['from_class']) ? absint($_POST['from_class']) : 0;
			$to_class_id    = isset($_POST['to_class']) ? absint($_POST['to_class']) : 0;

			// Start validation.
			$errors = array();

			if (empty($new_session_id)) {
				$errors['promote_to_session'] = esc_html__('Please select new session.', 'school-management');
			} elseif ($session_id == $new_session_id) {
				$errors['promote_to_session'] = esc_html__('New session must be different from current session.', 'school-management');
			} elseif (!WLSM_M_Staff_General::is_next_session($session_id, $new_session_id)) {
				$errors['promote_to_session'] = esc_html__('Start date of new session must be after end date of current session.', 'school-management');
			} else {
				// Check if new session exists.
				$new_session = WLSM_M_Session::fetch_session($new_session_id);
				if (!$new_session) {
					$errors['promote_to_session'] = esc_html__('Session not found.', 'school-management');
				}
			}

			if (empty($from_class_id)) {
				$errors['from_class'] = esc_html__('Please select promotion from class.', 'school-management');
			} else {
				// Check if old class exists in the school.
				$from_class = WLSM_M_Staff_General::get_class_school($school_id, $from_class_id);

				if (!$from_class) {
					$errors['from_class'] = esc_html__('Class not found.', 'school-management');
				} else {
					// Get sections of old class.
					$from_sections = WLSM_M_Staff_General::fetch_class_sections($from_class->ID);
				}
			}

			if (empty($to_class_id)) {
				$errors['to_class'] = esc_html__('Please select promotion to class.', 'school-management');
			} else {
				if ($from_class_id == $to_class_id) {
					$errors['to_class'] = esc_html__('Promotion to class can\'t be the same.', 'school-management');
				} else {
					// Check if new class exists in the school.
					$to_class = WLSM_M_Staff_General::get_class_school($school_id, $to_class_id);

					if (!$to_class) {
						$errors['to_class'] = esc_html__('Class not found.', 'school-management');
					} else {
						// Get sections of new class.
						$to_sections = WLSM_M_Staff_General::fetch_class_sections($to_class->ID);
					}
				}
			}

			// Get class students in current session.
			$students = WLSM_M_Staff_General::get_class_students($school_id, $session_id, $from_class_id);
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
				ob_start();

				if (count($students)) {
				?>
					<input type="hidden" name="promote_to_session_final" value="<?php echo esc_attr($new_session_id); ?>">
					<input type="hidden" name="from_class_final" value="<?php echo esc_attr($from_class_id); ?>">
					<input type="hidden" name="to_class_final" value="<?php echo esc_attr($to_class_id); ?>">

					<!-- Map sections. -->
					<div class="wlsm-form-section">
						<div class="row">
							<div class="col-md-12">
								<div class="wlsm-form-sub-heading wlsm-font-bold">
									<?php esc_html_e('Map Class Sections', 'school-management'); ?>
									<br>
									<small class="text-dark">
										<em><?php esc_html_e('Select sections mapping of old class to the new class.', 'school-management'); ?></em>
									</small>
								</div>
							</div>
						</div>

						<?php if (count($from_sections)) { ?>
							<div class="row mt-2 mb-1">
								<div class="col-md-6">
									<div class="wlsm-font-bold h6">
										<?php
										/* translators: %s: class name */
										printf(esc_html__('From Class: %s', 'school-management'), esc_html(WLSM_M_Class::get_label_text($from_class->label)));
										?>
									</div>
								</div>
								<div class="col-md-6">
									<div class="wlsm-font-bold h6">
										<?php
										/* translators: %s: class name */
										printf(esc_html__('To Class: %s', 'school-management'), esc_html(WLSM_M_Class::get_label_text($to_class->label)));
										?>
									</div>
								</div>
							</div>
						<?php } ?>

						<?php
						foreach ($from_sections as $key => $from_section) {
						?>
							<hr>
							<div class="form-row mt-1">
								<div class="form-group col-md-6">
									<label class="wlsm-font-bold" for="wlsm-from-section-<?php echo esc_attr($key); ?>">
										<span class="wlsm-important">*</span> <?php esc_html_e('Students From Section', 'school-management'); ?>:
									</label>
									<input type="hidden" name="from_section[]" value="<?php echo esc_attr($from_section->ID); ?>">
									<div class="ml-2">
										<?php echo esc_html(WLSM_M_Staff_Class::get_section_label_text($from_section->label)); ?>
									</div>
								</div>

								<div class="form-group col-md-6">
									<label class="wlsm-font-bold" for="wlsm-to-section-<?php echo esc_attr($key); ?>">
										<span class="wlsm-important">*</span> <?php esc_html_e('Assign to Section', 'school-management'); ?>:
									</label>
									<select name="to_section[]" id="wlsm-to-section-<?php echo esc_attr($key); ?>" class="form-control">
										<?php
										foreach ($to_sections as $key => $to_section) {
										?>
											<option value="<?php echo esc_attr($to_section->ID); ?>">
												<?php echo esc_html(WLSM_M_Staff_Class::get_section_label_text($to_section->label)); ?>
											</option>
										<?php
										}
										?>
									</select>
								</div>
							</div>
						<?php
						}
						?>
					</div>

					<!-- Students to be promoted. -->
					<div class="wlsm-form-section">
						<div class="row">
							<div class="col-md-12">
								<div class="wlsm-form-sub-heading wlsm-font-bold">
									<?php
									printf(
										wp_kses(
											/* translators: %s: class name */
											__('Students of Class: <span class="text-secondary">%s</span>', 'school-management'),
											array('span' => array('class' => array()))
										),
										esc_html(WLSM_M_Class::get_label_text($from_class->label))
									);
									?>
									<br>
									<small class="text-dark">
										<em><?php esc_html_e('Select students to enroll in next session.', 'school-management'); ?></em>
									</small>
								</div>
							</div>
						</div>
						<div class="table-responsive w-100">
							<table class="table table-bordered wlsm-students-to-promote-table">
								<thead>
									<tr class="bg-primary text-white">
										<th><input type="checkbox" name="select_all" id="wlsm-select-all" value="1"></th>
										<th><?php esc_html_e('Enrollment Number', 'school-management'); ?></th>
										<th><?php esc_html_e('Student Name', 'school-management'); ?></th>
										<th><?php esc_html_e('Section', 'school-management'); ?></th>
										<th><?php esc_html_e('Roll Number', 'school-management'); ?></th>
										<th><?php esc_html_e('Options', 'school-management'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($students as $row) { ?>
										<tr>
											<td>
												<input type="checkbox" class="wlsm-select-single" name="student[<?php echo esc_attr($row->ID); ?>]" value="<?php echo esc_attr($row->ID); ?>">
											</td>
											<td>
												<?php echo esc_html($row->enrollment_number); ?>
											</td>
											<td>
												<?php echo esc_html(WLSM_M_Staff_Class::get_name_text($row->name)); ?>
											</td>
											<td>
												<?php echo esc_html(WLSM_M_Staff_Class::get_section_label_text($row->section_label)); ?>
											</td>
											<td>
												<?php echo esc_html(WLSM_M_Staff_Class::get_roll_no_text($row->roll_number)); ?>
											</td>
											<td>
												<select name="new_session_class[<?php echo esc_attr($row->ID); ?>]">
													<option value="<?php echo esc_attr($to_class_id); ?>">
														<?php
														/* translators: %s: class name */
														printf(esc_html__('Enroll to Class - %s', 'school-management'), esc_html(WLSM_M_Class::get_label_text($to_class->label)));
														?>
													</option>
													<option value="<?php echo esc_attr($from_class_id); ?>">
														<?php
														/* translators: %s: class name */
														printf(esc_html__('Enroll to Class - %s', 'school-management'), esc_html(WLSM_M_Class::get_label_text($from_class->label)));
														?>
													</option>
												</select>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>

					<div class="row mt-2 mb-2">
						<div class="col-md-12 text-center">
							<?php
							printf(
								wp_kses(
									/* translators: 1: current session, 2: new session */
									__('Session From <span class="wlsm-font-bold">%1$s</span> to <span class="wlsm-font-bold">%2$s</span>', 'school-management'),
									array('span' => array('class' => array()))
								),
								esc_html(WLSM_M_Session::get_label_text($session_label)),
								esc_html(WLSM_M_Session::get_label_text($new_session->label))
							);
							?>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-md-12 text-center">
							<button type="submit" class="btn btn-sm btn-success" id="wlsm-promote-student-btn" data-message-title="<?php esc_attr_e('Confirm Promotion!', 'school-management'); ?>" data-message-content="<?php esc_attr_e('Are you sure to enroll these selected students for the next session?', 'school-management'); ?>" data-submit="<?php esc_attr_e('Promote', 'school-management'); ?>" data-cancel="<?php esc_attr_e('Cancel', 'school-management'); ?>">
								<?php
								echo esc_html(_n('Promote Student', 'Promote Students', $students, 'school-management'));
								?>
							</button>
						</div>
					</div>
				<?php
				} else {
				?>
					<div class="alert alert-warning wlsm-font-bold">
						<i class="fas fa-exclamation-triangle"></i>
						<?php esc_html_e('There is no student in this class or students were already promoted.', 'school-management'); ?>
					</div>
				<?php
				}
				$html = ob_get_clean();

				wp_send_json_success(array('html' => $html));
			} catch (Exception $exception) {
				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					$response = $buffer;
				} else {
					$response = $exception->getMessage();
				}
				wp_send_json_error($response);
			}
		}
		wp_send_json_error($errors);
	}

	public static function promote_student()
	{
		$current_user = WLSM_M_Role::can('manage_promote');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		if (!wp_verify_nonce($_POST['promote-student-' . $session_id], 'promote-student-' . $session_id)) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$new_session_id = isset($_POST['promote_to_session_final']) ? absint($_POST['promote_to_session_final']) : 0;
			$from_class_id  = isset($_POST['from_class_final']) ? absint($_POST['from_class_final']) : 0;
			$to_class_id    = isset($_POST['to_class_final']) ? absint($_POST['to_class_final']) : 0;

			// Start validation.
			if (empty($new_session_id)) {
				wp_send_json_error(esc_html__('Please select new session.', 'school-management'));
			} elseif ($session_id == $new_session_id) {
				wp_send_json_error(esc_html__('New session must be different from current session.', 'school-management'));
			} elseif (!WLSM_M_Staff_General::is_next_session($session_id, $new_session_id)) {
				wp_send_json_error(esc_html__('Start date of new session must be after end date of current session.', 'school-management'));
			} else {
				// Check if new session exists.
				$new_session = WLSM_M_Session::fetch_session($new_session_id);
				if (!$new_session) {
					wp_send_json_error(esc_html__('Session not found.', 'school-management'));
				}
			}

			if (empty($from_class_id)) {
				wp_send_json_error(esc_html__('Please select promotion from class.', 'school-management'));
			} else {
				// Check if old class exists in the school.
				$from_class = WLSM_M_Staff_General::get_class_school($school_id, $from_class_id);

				if (!$from_class) {
					wp_send_json_error(esc_html__('Class not found.', 'school-management'));
				} else {
					// Get sections of old class.
					$from_sections = WLSM_M_Staff_General::fetch_class_sections($from_class->ID);
				}
			}

			if (empty($to_class_id)) {
				wp_send_json_error(esc_html__('Please select promotion to class.', 'school-management'));
			} else {
				if ($from_class_id == $to_class_id) {
					wp_send_json_error(esc_html__('Promotion to class can\'t be the same.', 'school-management'));
				} else {
					// Check if new class exists in the school.
					$to_class = WLSM_M_Staff_General::get_class_school($school_id, $to_class_id);

					if (!$to_class) {
						wp_send_json_error(esc_html__('Class not found.', 'school-management'));
					} else {
						// Get sections of new class.
						$to_sections = WLSM_M_Staff_General::fetch_class_sections($to_class->ID);
					}
				}
			}

			// Get class students in current session.
			$students = WLSM_M_Staff_General::get_class_students_data($school_id, $session_id, $from_class_id);

			$all_student_ids = array_map(function ($student) {
				return $student->ID;
			}, $students);

			$all_from_sections_ids = array_map(function ($section) {
				return $section->ID;
			}, $from_sections);

			$all_to_sections_ids = array_map(function ($section) {
				return $section->ID;
			}, $to_sections);

			$from_section_ids = (isset($_POST['from_section']) && is_array($_POST['from_section'])) ? $_POST['from_section'] : array();
			$to_section_ids   = (isset($_POST['to_section']) && is_array($_POST['to_section'])) ? $_POST['to_section'] : array();
			$student_ids      = (isset($_POST['student']) && is_array($_POST['student'])) ? $_POST['student'] : array();
			$new_class_ids    = (isset($_POST['new_session_class']) && is_array($_POST['new_session_class'])) ? $_POST['new_session_class'] : array();

			$student_ids_keys   = array_keys($student_ids);
			$new_class_ids_keys = array_keys($new_class_ids);

			if (!count($student_ids)) {
				wp_send_json_error(esc_html__('Please select students.', 'school-management'));
			} elseif ((array_intersect($student_ids, $all_student_ids) != $student_ids) || ($student_ids_keys != array_values($student_ids))) {
				wp_send_json_error(esc_html__('Please select valid students.', 'school-management'));
			} elseif (array_intersect($student_ids_keys, $new_class_ids_keys) != $student_ids_keys) {
				wp_send_json_error(esc_html__('Invalid selection of students or new classes.', 'school-management'));
			}

			if (array_intersect($new_class_ids, array($from_class_id, $to_class_id)) != $new_class_ids) {
				wp_send_json_error(esc_html__('Please select valid class for each student.', 'school-management'));
			}

			if (count($all_from_sections_ids) != count($to_section_ids)) {
				wp_send_json_error(esc_html__('Please select corresponding new sections for mapping.', 'school-management'));
			} elseif (array_intersect($to_section_ids, $all_to_sections_ids) != $to_section_ids) {
				wp_send_json_error(esc_html__('Please select valid new sections for mapping.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			foreach ($student_ids_keys as $student_id) {
				if (isset($students[$student_id])) {
					$student = $students[$student_id];

					// Update old record.
					$old_student_data = array(
						'user_id'        => NULL,
						'parent_user_id' => NULL,
						'is_active'      => 0,
						'updated_at'     => current_time('Y-m-d H:i:s'),
					);

					$success = $wpdb->update(WLSM_STUDENT_RECORDS, $old_student_data, array('ID' => $student_id));

					$buffer = ob_get_clean();
					if (!empty($buffer)) {
						throw new Exception($buffer);
					}

					if (false === $success) {
						throw new Exception($wpdb->last_error);
					}

					// Insert new record.
					$old_section_id = $student->section_id;
					$section_index  = array_search($old_section_id, $from_section_ids);

					if ($from_class_id == $new_class_ids[$student_id]) {
						// Keep in the same class.
						$new_section_id = $old_section_id;
					} else {
						// Promote to a new class.
						$new_section_id = $to_section_ids[$section_index];
					}

					if (!$new_section_id) {
						throw new Exception(esc_html__('Please select corresponding new sections for mapping.', 'school-management'));
					}

					// Student data.
					$student_data = array(
						'enrollment_number' => WLSM_M_Staff_General::get_enrollment_number($school_id),
						'admission_number'  => $student->admission_number,
						'name'              => $student->name,
						'gender'            => $student->gender,
						'dob'               => $student->dob,
						'phone'             => $student->phone,
						'email'             => $student->email,
						'address'           => $student->address,
						'city'              => $student->city,
						'state'             => $student->state,
						'country'           => $student->country,
						'religion'          => $student->religion,
						'caste'             => $student->caste,
						'blood_group'       => $student->blood_group,
						'father_name'       => $student->father_name,
						'father_phone'      => $student->father_phone,
						'father_occupation' => $student->father_occupation,
						'mother_name'       => $student->mother_name,
						'mother_phone'      => $student->mother_phone,
						'mother_occupation' => $student->mother_occupation,
						'id_number'         => $student->id_number,
						'id_proof'          => $student->id_proof,
						'parent_id_proof'   => $student->parent_id_proof,
						'note'              => $student->note,
						'admission_date'    => $student->admission_date,
						'roll_number'       => $student->roll_number,
						'photo_id'          => $student->photo_id,
						'route_vehicle_id'  => $student->route_vehicle_id,
						'section_id'        => $new_section_id,
						'session_id'        => $new_session_id,
						'user_id'           => $student->user_id,
						'parent_user_id'    => $student->parent_user_id,
						'added_by'          => $student->added_by,
						'is_active'         => 1,
					);

					$student_data['created_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->insert(WLSM_STUDENT_RECORDS, $student_data);

					$buffer = ob_get_clean();
					if (!empty($buffer)) {
						throw new Exception($buffer);
					}

					if (false === $success) {
						throw new Exception($wpdb->last_error);
					}

					$old_student_id = $student_id;
					$new_student_id = $wpdb->insert_id;

					// Insert promotion record.
					$promotion_data = array(
						'from_student_record' => $old_student_id,
						'to_student_record'   => $new_student_id,
					);

					$promotion_data['created_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->insert(WLSM_PROMOTIONS, $promotion_data);

					// Copy fee structure of old student record to new student record.
					$fees = WLSM_M_Staff_Accountant::fetch_student_fees($school_id, $old_student_id);
					if (count($fees)) {
						foreach ($fees as $fee) {
							$fee_data = array(
								'label'             => $fee->label,
								'amount'            => $fee->amount,
								'period'            => $fee->period,
								'fee_order'         => $fee->fee_order,
								'student_record_id' => $new_student_id,
							);

							$fee_data['created_at'] = current_time('Y-m-d H:i:s');

							$success = $wpdb->insert(WLSM_STUDENT_FEES, $fee_data);
						}
					}

					$buffer = ob_get_clean();
					if (!empty($buffer)) {
						throw new Exception($buffer);
					}

					if (false === $success) {
						throw new Exception($wpdb->last_error);
					}
				} else {
					throw new Exception(esc_html__('Please select valid students.', 'school-management'));
				}
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('Students promoted successfully.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function transfer_student()
	{
		$current_user = WLSM_M_Role::can('manage_transfer_student');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		if (!wp_verify_nonce($_POST['transfer-student-' . $session_id], 'transfer-student-' . $session_id)) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$class_id   = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;
			$section_id = isset($_POST['section_id']) ? absint($_POST['section_id']) : 0;
			$student_id = isset($_POST['student']) ? absint($_POST['student']) : 0;

			$new_school_id  = isset($_POST['transfer_to_school']) ? absint($_POST['transfer_to_school']) : 0;
			$new_class_id   = isset($_POST['transfer_to_class']) ? absint($_POST['transfer_to_class']) : 0;
			$new_section_id = isset($_POST['transfer_to_section']) ? absint($_POST['transfer_to_section']) : 0;

			$note = isset($_POST['note']) ? sanitize_text_field($_POST['note']) : '';

			// Start validation.
			$errors = array();

			if (empty($class_id)) {
				$errors['class_id'] = esc_html__('Please select a valid class.', 'school-management');
			} else {
				// Checks if class exists in the school.
				$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);

				if (!$class_school) {
					$errors['class_id'] = esc_html__('Class not found.', 'school-management');
				} else {
					$class_school_id = $class_school->ID;
					if (!empty($section_id)) {
						// Checks if section exists.
						$section = WLSM_M_Staff_Class::get_section($school_id, $section_id, $class_school_id);

						if (!$section) {
							$errors['section_id'] = esc_html__('Class not found.', 'school-management');
						}
					}
				}
			}

			// Checks if student exists.
			if (!$student_id) {
				$errors['student'] = esc_html__('Please select a student.', 'school-management');
			} else {
				$student = WLSM_M_Staff_General::get_student_to_transfer($school_id, $session_id, $student_id);
				if (!$student) {
					$errors['student'] = esc_html__('Student not found.', 'school-management');
				}
			}

			// Checks if school exists.
			$new_school = WLSM_M_School::get_school_except($new_school_id, $school_id);
			if (!$new_school) {
				$errors['transfer_to_school'] = esc_html__('Please select a school.', 'school-management');
			} else {
				// Checks if class exists in the school.
				$new_class_school = WLSM_M_Staff_Class::get_class($new_school_id, $new_class_id);

				$sections = WLSM_M_Staff_General::fetch_class_sections($class_school_id);
				if (!$new_class_school) {
					$errors['transfer_to_class'] = esc_html__('Please select a class.', 'school-management');
				} else {
					$new_class_school_id = $new_class_school->ID;

					$new_section = WLSM_M_Staff_Class::get_section($new_school_id, $new_section_id, $new_class_school_id);
					if (!$new_section) {
						$errors['transfer_to_section'] = esc_html__('Please select a section.', 'school-management');
					}
				}
			}

			if (strlen($note) > 255) {
				$errors['note'] = esc_html__('Maximum length cannot exceed 255 characters.', 'school-management');
			}

			if (count($errors) > 0) {
				wp_send_json_error($errors);
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

				// Update old record.
				$old_student_data = array(
					'user_id'        => NULL,
					'parent_user_id' => NULL,
					'is_active'      => 0,
					'updated_at'     => current_time('Y-m-d H:i:s'),
				);

				$success = $wpdb->update(WLSM_STUDENT_RECORDS, $old_student_data, array('ID' => $student_id));

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				// Insert new record.
				// New student data.
				$new_student_data = array(
					'enrollment_number' => WLSM_M_Staff_General::get_enrollment_number($new_school_id),
					'name'              => $student->name,
					'gender'            => $student->gender,
					'dob'               => $student->dob,
					'phone'             => $student->phone,
					'email'             => $student->email,
					'address'           => $student->address,
					'city'              => $student->city,
					'state'             => $student->state,
					'country'           => $student->country,
					'religion'          => $student->religion,
					'caste'             => $student->caste,
					'blood_group'       => $student->blood_group,
					'father_name'       => $student->father_name,
					'father_phone'      => $student->father_phone,
					'father_occupation' => $student->father_occupation,
					'mother_name'       => $student->mother_name,
					'mother_phone'      => $student->mother_phone,
					'mother_occupation' => $student->mother_occupation,
					'id_number'         => $student->id_number,
					'id_proof'          => $student->id_proof,
					'parent_id_proof'   => $student->parent_id_proof,
					'photo_id'          => $student->photo_id,
					'section_id'        => $new_section_id,
					'session_id'        => $session_id,
					'user_id'           => $student->user_id,
					'parent_user_id'    => $student->parent_user_id,
					'is_active'         => 0,
				);

				$new_student_data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_STUDENT_RECORDS, $new_student_data);

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$old_student_id = $student_id;
				$new_student_id = $wpdb->insert_id;

				// Insert transfer record.
				$transfer_data = array(
					'from_student_record' => $old_student_id,
					'to_student_record'   => $new_student_id,
					'to_school'           => $new_school->label,
					'note'                => $note,
				);

				$transfer_data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_TRANSFERS, $transfer_data);

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$wpdb->query('COMMIT;');

				$message = esc_html__('Student transferred successfully.', 'school-management');

				wp_send_json_success(array('message' => $message));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function fetch_transferred_to_school()
	{
		$current_user = WLSM_M_Role::can('manage_transfer_student');

		if (!$current_user) {
			die();
		}

		$current_school = $current_user['school'];

		$can_manage_students = WLSM_M_Role::check_permission(array('manage_students'), $current_school['permissions']);

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$session_label = $current_user['session']['label'];

		global $wpdb;

		$page_url = WLSM_M_Staff_General::get_students_page_url();

		$query = WLSM_M_Staff_General::fetch_transferred_to_query($school_id, $session_id);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_M_Staff_General::fetch_transferred_to_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(sr.name LIKE "%' . $search_value . '%") OR ' .
					'(sr.admission_number LIKE "%' . $search_value . '%") OR ' .
					'(sr.enrollment_number LIKE "%' . $search_value . '%") OR ' .
					'(sr.phone LIKE "%' . $search_value . '%") OR ' .
					'(sr.email LIKE "%' . $search_value . '%") OR ' .
					'(sr.father_name LIKE "%' . $search_value . '%") OR ' .
					'(sr.father_phone LIKE "%' . $search_value . '%") OR ' .
					'(c.label LIKE "%' . $search_value . '%") OR ' .
					'(se.label LIKE "%' . $search_value . '%") OR ' .
					'(sr.roll_number LIKE "%' . $search_value . '%") OR ' .
					'(tf.to_school LIKE "%' . $search_value . '%")';

				$search_value_lowercase = strtolower($search_value);
				if (preg_match('/^inac(|t|ti|tiv|tive)$/', $search_value_lowercase)) {
					$is_active = 0;
				} elseif (preg_match('/^acti(|v|ve)$/', $search_value_lowercase)) {
					$is_active = 1;
				}
				if (isset($is_active)) {
					$condition .= ' OR (sr.is_active = ' . $is_active . ')';
				}

				$admission_date = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($admission_date) {
					$format_admission_date = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$admission_date) {
							$admission_date        = DateTime::createFromFormat('m-Y', $search_value);
							$format_admission_date = 'Y-m';
						}
					} elseif ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$admission_date) {
							$admission_date        = DateTime::createFromFormat('m/Y', $search_value);
							$format_admission_date = 'Y-m';
						}
					} elseif ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$admission_date) {
							$admission_date        = DateTime::createFromFormat('Y-m', $search_value);
							$format_admission_date = 'Y-m';
						}
					} elseif ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$admission_date) {
							$admission_date        = DateTime::createFromFormat('Y/m', $search_value);
							$format_admission_date = 'Y-m';
						}
					}

					if (!$admission_date) {
						$admission_date        = DateTime::createFromFormat('Y', $search_value);
						$format_admission_date = 'Y';
					}
				}

				if ($admission_date && isset($format_admission_date)) {
					$admission_date = $admission_date->format($format_admission_date);
					$admission_date = ' OR (sr.admission_date LIKE "%' . $admission_date . '%")';

					$condition .= $admission_date;
				}

				$created_at = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($created_at) {
					$format_created_at = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('m-Y', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('m/Y', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('Y-m', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('Y/m', $search_value);
							$format_created_at = 'Y-m';
						}
					}

					if (!$created_at) {
						$created_at        = DateTime::createFromFormat('Y', $search_value);
						$format_created_at = 'Y';
					}
				}

				if ($created_at && isset($format_created_at)) {
					$created_at = $created_at->format($format_created_at);
					$created_at = ' OR (tf.created_at LIKE "%' . $created_at . '%")';

					$condition .= $created_at;
				}

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('sr.name', 'sr.admission_number', 'sr.phone', 'sr.email', 'c.label', 'se.label', 'sr.roll_number', 'sr.father_name', 'sr.father_phone', 'sr.admission_date', 'sr.enrollment_number', 'sr.is_active', 'tf.to_school', 'tf.created_at');
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY sr.ID DESC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_M_Staff_General::fetch_transferred_to_query_count($school_id, $session_id);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {
				if ($row->note) {
					$view_note = '<a class="text-primary wlsm-view-transferred-to-note" data-nonce="' . esc_attr(wp_create_nonce('view-transferred-to-note-' . $row->ID)) . '" data-transferred-to="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Note', 'school-management') . '" data-close="' . esc_attr__('Close', 'school-management') . '"><span class="dashicons dashicons-search"></span></a>';
				} else {
					$view_note = '-';
				}

				$action = '<a class="text-danger wlsm-delete-transferred-to" data-nonce="' . esc_attr(wp_create_nonce('delete-transferred-to-' . $row->ID)) . '" data-transferred-to="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '" data-message-content="' . sprintf(
					/* translators: %s: session label */
					esc_attr__('This will delete transferred student record for the session %s.', 'school-management'),
					esc_html(WLSM_M_Session::get_label_text($session_label))
				) . '" data-cancel="' . esc_attr__('Cancel', 'school-management') . '" data-submit="' . esc_attr__('Confirm', 'school-management') . '"><span class="dashicons dashicons-trash"></span></a>';

				if ($can_manage_students) {
					$edit_student = '<a class="text-primary" href="' . esc_url($page_url . "&action=save&id=" . $row->ID) . '"><span class="dashicons dashicons-edit"></span></a>&nbsp;&nbsp;';

					$action = $edit_student . $action;
				}

				// Table columns.
				$data[] = array(
					esc_html(WLSM_M_Staff_Class::get_name_text($row->student_name)),
					esc_html(WLSM_M_Staff_Class::get_admission_no_text($row->admission_number)),
					esc_html(WLSM_M_Staff_Class::get_phone_text($row->phone)),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->email)),
					esc_html(WLSM_M_Class::get_label_text($row->class_label)),
					esc_html(WLSM_M_Staff_Class::get_section_label_text($row->section_label)),
					esc_html(WLSM_M_Staff_Class::get_roll_no_text($row->roll_number)),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->father_name)),
					esc_html(WLSM_M_Staff_Class::get_phone_text($row->father_phone)),
					esc_html(WLSM_Config::get_date_text($row->admission_date)),
					esc_html($row->enrollment_number),
					esc_html(WLSM_M_Staff_Class::get_status_text($row->is_active)),
					esc_html(WLSM_M_School::get_label_text($row->to_school)),
					esc_html(WLSM_Config::get_date_text($row->transfer_date)),
					$view_note,
					$action
				);
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data,
		);

		echo json_encode($output);
		die();
	}

	public static function view_transferred_to_note()
	{
		$current_user = WLSM_M_Role::can('manage_transfer_student');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['view-transferred-to-note-' . $student_id], 'view-transferred-to-note-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::get_transferred_to_school_note($school_id, $session_id, $student_id);

			if (!$student) {
				throw new Exception(esc_html__('Note not found.', 'school-management'));
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

		wp_send_json_success(esc_html(WLSM_Config::get_note_text($student->note)));
	}

	public static function delete_transferred_to()
	{
		$current_user = WLSM_M_Role::can('manage_transfer_student');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['delete-transferred-to-' . $student_id], 'delete-transferred-to-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::get_transferred_to($school_id, $session_id, $student_id);

			if (!$student) {
				throw new Exception(esc_html__('Transferred student record not found.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			$success = $wpdb->delete(WLSM_STUDENT_RECORDS, array('ID' => $student_id));
			$message = esc_html__('Transferred student record deleted successfully.', 'school-management');

			$exception = ob_get_clean();
			if (!empty($exception)) {
				throw new Exception($exception);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			if (!$student->to_student_record) {
				$success = $wpdb->delete(WLSM_TRANSFERS, array('ID' => $student->transfer_id));

				$exception = ob_get_clean();
				if (!empty($exception)) {
					throw new Exception($exception);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}
			}

			$wpdb->query('COMMIT;');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function fetch_transferred_from_school()
	{
		$current_user = WLSM_M_Role::can('manage_transfer_student');

		if (!$current_user) {
			die();
		}

		$current_school = $current_user['school'];

		$can_manage_students = WLSM_M_Role::check_permission(array('manage_students'), $current_school['permissions']);

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$session_label = $current_user['session']['label'];

		global $wpdb;

		$page_url = WLSM_M_Staff_General::get_students_page_url();

		$query = WLSM_M_Staff_General::fetch_transferred_from_query($school_id, $session_id);

		$query_filter = $query;

		// Grouping.
		$group_by = ' ' . WLSM_M_Staff_General::fetch_transferred_from_query_group_by();

		$query        .= $group_by;
		$query_filter .= $group_by;

		// Searching.
		$condition = '';
		if (isset($_POST['search']['value'])) {
			$search_value = sanitize_text_field($_POST['search']['value']);
			if ('' !== $search_value) {
				$condition .= '' .
					'(sr.name LIKE "%' . $search_value . '%") OR ' .
					'(sr.admission_number LIKE "%' . $search_value . '%") OR ' .
					'(sr.enrollment_number LIKE "%' . $search_value . '%") OR ' .
					'(sr.phone LIKE "%' . $search_value . '%") OR ' .
					'(sr.email LIKE "%' . $search_value . '%") OR ' .
					'(sr.father_name LIKE "%' . $search_value . '%") OR ' .
					'(sr.father_phone LIKE "%' . $search_value . '%") OR ' .
					'(c.label LIKE "%' . $search_value . '%") OR ' .
					'(se.label LIKE "%' . $search_value . '%") OR ' .
					'(sr.roll_number LIKE "%' . $search_value . '%") OR ' .
					'(tf.to_school LIKE "%' . $search_value . '%")';

				$search_value_lowercase = strtolower($search_value);
				if (preg_match('/^inac(|t|ti|tiv|tive)$/', $search_value_lowercase)) {
					$is_active = 0;
				} elseif (preg_match('/^acti(|v|ve)$/', $search_value_lowercase)) {
					$is_active = 1;
				}
				if (isset($is_active)) {
					$condition .= ' OR (sr.is_active = ' . $is_active . ')';
				}

				$admission_date = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($admission_date) {
					$format_admission_date = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$admission_date) {
							$admission_date        = DateTime::createFromFormat('m-Y', $search_value);
							$format_admission_date = 'Y-m';
						}
					} elseif ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$admission_date) {
							$admission_date        = DateTime::createFromFormat('m/Y', $search_value);
							$format_admission_date = 'Y-m';
						}
					} elseif ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$admission_date) {
							$admission_date        = DateTime::createFromFormat('Y-m', $search_value);
							$format_admission_date = 'Y-m';
						}
					} elseif ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$admission_date) {
							$admission_date        = DateTime::createFromFormat('Y/m', $search_value);
							$format_admission_date = 'Y-m';
						}
					}

					if (!$admission_date) {
						$admission_date        = DateTime::createFromFormat('Y', $search_value);
						$format_admission_date = 'Y';
					}
				}

				if ($admission_date && isset($format_admission_date)) {
					$admission_date = $admission_date->format($format_admission_date);
					$admission_date = ' OR (sr.admission_date LIKE "%' . $admission_date . '%")';

					$condition .= $admission_date;
				}

				$created_at = DateTime::createFromFormat(WLSM_Config::date_format(), $search_value);

				if ($created_at) {
					$format_created_at = 'Y-m-d';
				} else {
					if ('d-m-Y' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('m-Y', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('d/m/Y' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('m/Y', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('Y-m-d' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('Y-m', $search_value);
							$format_created_at = 'Y-m';
						}
					} elseif ('Y/m/d' === WLSM_Config::date_format()) {
						if (!$created_at) {
							$created_at        = DateTime::createFromFormat('Y/m', $search_value);
							$format_created_at = 'Y-m';
						}
					}

					if (!$created_at) {
						$created_at        = DateTime::createFromFormat('Y', $search_value);
						$format_created_at = 'Y';
					}
				}

				if ($created_at && isset($format_created_at)) {
					$created_at = $created_at->format($format_created_at);
					$created_at = ' OR (tf.created_at LIKE "%' . $created_at . '%")';

					$condition .= $created_at;
				}

				$query_filter .= (' HAVING ' . $condition);
			}
		}

		// Ordering.
		$columns = array('sr.name', 'sr.admission_number', 'sr.phone', 'sr.email', 'c.label', 'se.label', 'sr.roll_number', 'sr.father_name', 'sr.father_phone', 'sr.admission_date', 'sr.enrollment_number', 'sr.is_active', 'tf.to_school', 'tf.created_at');
		if (isset($_POST['order']) && isset($columns[$_POST['order']['0']['column']])) {
			$order_by  = sanitize_text_field($columns[$_POST['order']['0']['column']]);
			$order_dir = sanitize_text_field($_POST['order']['0']['dir']);

			$query_filter .= ' ORDER BY ' . $order_by . ' ' . $order_dir;
		} else {
			$query_filter .= ' ORDER BY sr.ID DESC';
		}

		// Limiting.
		$limit = '';
		if (-1 != $_POST['length']) {
			$start  = absint($_POST['start']);
			$length = absint($_POST['length']);

			$limit  = ' LIMIT ' . $start . ', ' . $length;
		}

		// Total query.
		$rows_query = WLSM_M_Staff_General::fetch_transferred_from_query_count($school_id, $session_id);

		// Total rows count.
		$total_rows_count = $wpdb->get_var($rows_query);

		// Filtered rows count.
		if ($condition) {
			$filter_rows_count = $wpdb->get_var($rows_query . ' AND (' . $condition . ')');
		} else {
			$filter_rows_count = $total_rows_count;
		}

		// Filtered limit rows.
		$filter_rows_limit = $wpdb->get_results($query_filter . $limit);

		$data = array();

		if (count($filter_rows_limit)) {
			foreach ($filter_rows_limit as $row) {
				if ($row->note) {
					$view_note = '<a class="text-primary wlsm-view-transferred-from-note" data-nonce="' . esc_attr(wp_create_nonce('view-transferred-from-note-' . $row->ID)) . '" data-transferred-from="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Note', 'school-management') . '" data-close="' . esc_attr__('Close', 'school-management') . '"><span class="dashicons dashicons-search"></span></a>';
				} else {
					$view_note = '-';
				}

				$action = '<a class="text-danger wlsm-delete-transferred-from" data-nonce="' . esc_attr(wp_create_nonce('delete-transferred-from-' . $row->ID)) . '" data-transferred-from="' . esc_attr($row->ID) . '" href="#" data-message-title="' . esc_attr__('Please Confirm!', 'school-management') . '" data-message-content="' . sprintf(esc_attr__('This will delete transferred student record for the session %s.', 'school-management'), esc_html(WLSM_M_Session::get_label_text($session_label))) . '" data-cancel="' . esc_attr__('Cancel', 'school-management') . '" data-submit="' . esc_attr__('Confirm', 'school-management') . '"><span class="dashicons dashicons-trash"></span></a>';

				if ($can_manage_students) {
					$edit_student = '<a class="text-primary" href="' . esc_url($page_url . "&action=save&id=" . $row->ID) . '"><span class="dashicons dashicons-edit"></span></a>&nbsp;&nbsp;';

					$action = $edit_student . $action;
				}

				// Table columns.
				$data[] = array(
					esc_html(WLSM_M_Staff_Class::get_name_text($row->student_name)),
					esc_html(WLSM_M_Staff_Class::get_admission_no_text($row->admission_number)),
					esc_html(WLSM_M_Staff_Class::get_phone_text($row->phone)),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->email)),
					esc_html(WLSM_M_Class::get_label_text($row->class_label)),
					esc_html(WLSM_M_Staff_Class::get_section_label_text($row->section_label)),
					esc_html(WLSM_M_Staff_Class::get_roll_no_text($row->roll_number)),
					esc_html(WLSM_M_Staff_Class::get_name_text($row->father_name)),
					esc_html(WLSM_M_Staff_Class::get_phone_text($row->father_phone)),
					esc_html(WLSM_Config::get_date_text($row->admission_date)),
					esc_html($row->enrollment_number),
					esc_html(WLSM_M_Staff_Class::get_status_text($row->is_active)),
					esc_html(WLSM_M_School::get_label_text($row->to_school)),
					esc_html(WLSM_Config::get_date_text($row->transfer_date)),
					$view_note,
					$action
				);
			}
		}

		$output = array(
			'draw'            => intval($_POST['draw']),
			'recordsTotal'    => $total_rows_count,
			'recordsFiltered' => $filter_rows_count,
			'data'            => $data,
		);

		echo json_encode($output);
		die();
	}

	public static function view_transferred_from_note()
	{
		$current_user = WLSM_M_Role::can('manage_transfer_student');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['view-transferred-from-note-' . $student_id], 'view-transferred-from-note-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::get_transferred_from_school_note($school_id, $session_id, $student_id);

			if (!$student) {
				throw new Exception(esc_html__('Note not found.', 'school-management'));
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

		wp_send_json_success(esc_html(WLSM_Config::get_note_text($student->note)));
	}

	public static function delete_transferred_from()
	{
		$current_user = WLSM_M_Role::can('manage_transfer_student');

		if (!$current_user) {
			die();
		}

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		try {
			ob_start();
			global $wpdb;

			$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

			if (!wp_verify_nonce($_POST['delete-transferred-from-' . $student_id], 'delete-transferred-from-' . $student_id)) {
				die();
			}

			// Checks if student exists.
			$student = WLSM_M_Staff_General::get_transferred_from($school_id, $session_id, $student_id);

			if (!$student) {
				throw new Exception(esc_html__('Transferred student record not found.', 'school-management'));
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

		try {
			$wpdb->query('BEGIN;');

			$success = $wpdb->delete(WLSM_STUDENT_RECORDS, array('ID' => $student_id));
			$message = esc_html__('Transferred student record deleted successfully.', 'school-management');

			$exception = ob_get_clean();
			if (!empty($exception)) {
				throw new Exception($exception);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			if (!$student->from_student_record) {
				$success = $wpdb->delete(WLSM_TRANSFERS, array('ID' => $student->transfer_id));

				$exception = ob_get_clean();
				if (!empty($exception)) {
					throw new Exception($exception);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}
			}

			$wpdb->query('COMMIT;');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function fetch_stats_payments()
	{
		$current_user = WLSM_M_Role::can('manage_invoices');

		if (!$current_user) {
			die();
		}

		if (!wp_verify_nonce($_REQUEST['security'], 'wlsm-security')) {
			die();
		}

		global $wpdb;

		$current_school = $current_user['school'];

		$can_delete_payments = WLSM_M_Role::check_permission(array('delete_payments'), $current_school['permissions']);

		$school_id  = $current_user['school']['id'];
		$session_id = $current_user['session']['ID'];

		$invoices_page_url = WLSM_M_Staff_Accountant::get_invoices_page_url();

		// Last 15 Payments
		$payments = $wpdb->get_results(
			$wpdb->prepare('SELECT sr.name as student_name, sr.admission_number, sr.phone, sr.father_name, sr.father_phone, p.ID, p.receipt_number, p.amount, p.payment_method, p.transaction_id, p.created_at, p.invoice_label, p.invoice_payable, p.invoice_id, i.label as invoice_title, c.label as class_label, se.label as section_label FROM ' . WLSM_PAYMENTS . ' as p
			JOIN ' . WLSM_SCHOOLS . ' as s ON s.ID = p.school_id
			JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.ID = p.student_record_id
			JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
			JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
			JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
			JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
			LEFT OUTER JOIN ' . WLSM_INVOICES . ' as i ON i.ID = p.invoice_id
			WHERE p.school_id = %d AND ss.ID = %d GROUP BY p.ID ORDER BY p.ID DESC LIMIT 15', $school_id, $session_id)
		);

		$output['data'] = array();

		foreach ($payments as $row) {
			if ($row->invoice_id) {
				$invoice_title = '<a target="_blank" href="' . esc_url($invoices_page_url . '&action=save&id=' . $row->invoice_id) . '">' . esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($row->invoice_title)) . '</a>';
			} else {
				$invoice_title = '<span class="text-danger">' . esc_html__('Deleted', 'school-management') . '<br><span class="text-secondary">' . $row->invoice_label . '<br><small>' . esc_html(WLSM_Config::get_money_text($row->invoice_payable))  . ' ' . esc_html__('Payable', 'school-management') . '</small></span></span>';
			}

			$data = array(
				esc_html(WLSM_M_Invoice::get_receipt_number_text($row->receipt_number)),
				esc_html(WLSM_Config::get_money_text($row->amount)),
				esc_html(WLSM_M_Invoice::get_payment_method_text($row->payment_method)),
				esc_html(WLSM_M_Invoice::get_transaction_id_text($row->transaction_id)),
				esc_html(WLSM_Config::get_date_text($row->created_at)),
				$invoice_title,
				esc_html(WLSM_M_Staff_Class::get_name_text($row->student_name)),
				esc_html(WLSM_M_Staff_Class::get_admission_no_text($row->admission_number)),
				esc_html(WLSM_M_Class::get_label_text($row->class_label)),
				esc_html(WLSM_M_Staff_Class::get_section_label_text($row->section_label)),
				esc_html(WLSM_M_Staff_Class::get_phone_text($row->phone)),
				esc_html(WLSM_M_Staff_Class::get_name_text($row->father_name)),
				esc_html(WLSM_M_Staff_Class::get_phone_text($row->father_phone))
			);

			if ($can_delete_payments) {
				ob_start();
				?>
				<a class="text-danger wlsm-delete-payment" data-nonce="<?php echo esc_attr(wp_create_nonce('delete-payment-' . $row->ID)); ?>" data-payment="<?php echo esc_attr($row->ID); ?>" href="#" data-message-title="<?php esc_attr_e('Please Confirm!', 'school-management'); ?>" data-message-content="<?php esc_attr_e('This will delete the payment.', 'school-management'); ?>" data-cancel="<?php esc_attr_e('Cancel', 'school-management'); ?>" data-submit="<?php esc_attr_e('Confirm', 'school-management'); ?>"><span class="dashicons dashicons-trash"></span></a>
<?php
				$delete_payment = ob_get_clean();
				array_push($data, $delete_payment);
			}

			$output['data'][] = $data;
		}

		echo json_encode($output);
		die();
	}

	public static function save_school_general_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['save-school-general-settings'], 'save-school-general-settings')) {
				die();
			}

			$school_logo      = (isset($_FILES['school_logo']) && is_array($_FILES['school_logo'])) ? $_FILES['school_logo'] : NULL;
			$school_signature = (isset($_FILES['school_signature']) && is_array($_FILES['school_signature'])) ? $_FILES['school_signature'] : NULL;
			$remove_school_logo          = isset($_POST['remove_school_logo']) ? (bool) $_POST['remove_school_logo'] : 0;
			$student_logout_redirect_url = isset($_POST['student_logout_redirect_url']) ? esc_url_raw($_POST['student_logout_redirect_url']) : '';
			$hide_library           = isset($_POST['student_hide_library']) ? (bool) ($_POST['student_hide_library']) : '';
			$hide_transport           = isset($_POST['student_hide_transport']) ? (bool) ($_POST['student_hide_transport']) : '';

			// Start validation.
			$errors = array();

			if (!$remove_school_logo && (isset($school_logo['tmp_name']) && !empty($school_logo['tmp_name']))) {
				if (!WLSM_Helper::is_valid_file($school_logo, 'image')) {
					$errors['school_logo'] = esc_html__('Please provide school logo in JPG, JPEG or PNG format.', 'school-management');
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

				$general = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "general"', $school_id));

				if ($remove_school_logo) {
					$school_logo = NULL;
				} else {
					if (!empty($school_logo)) {
						$school_logo = media_handle_upload('school_logo', 0);
						if (is_wp_error($school_logo)) {
							throw new Exception($school_logo->get_error_message());
						}
					}
				}

				if ($remove_school_signature) {
					$school_signature = NULL;
				} else {
					if (!empty($school_signature)) {
						$school_signature = media_handle_upload('school_signature', 0);
						if (is_wp_error($school_signature)) {
							throw new Exception($school_signature->get_error_message());
						}
					}
				}

				if (!$school_logo) {
					$school_logo = NULL;
				}

				$general_data = array(
					'school_logo'                 => $school_logo,
					'school_signature'            => $school_signature,
					'student_logout_redirect_url' => $student_logout_redirect_url,
					'hide_library'                => $hide_library,
					'hide_transport'              => $hide_transport,
				);

				if (!$general) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'general',
							'setting_value' => serialize($general_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$general_saved_data = unserialize($general->setting_value);

					if (isset($general_saved_data['school_logo']) && !empty($general_saved_data['school_logo'])) {
						if ($remove_school_logo) {
							// If remove school logo is checked, delete saved logo.
							$school_logo_delete_id = $general_saved_data['school_logo'];
						} elseif (!$general_data['school_logo']) {
							// If no school logo is provided from input, use saved school logo.
							$general_data['school_logo'] = $general_saved_data['school_logo'];
						} else {
							// If school logo is provided from input, delete saved school logo.
							$school_logo_delete_id = $general_saved_data['school_logo'];
						}
					}
					if (isset($general_saved_data['school_signature']) && !empty($general_saved_data['school_signature'])) {
						if ($remove_school_signature) {
							// If remove school logo is checked, delete saved logo.
							$school_signature_delete_id = $general_saved_data['school_signature'];
						} elseif (!$general_data['school_signature']) {
							// If no school logo is provided from input, use saved school logo.
							$general_data['school_signature'] = $general_saved_data['school_signature'];
						} else {
							// If school logo is provided from input, delete saved school logo.
							$school_signature_delete_id = $general_saved_data['school_signature'];
						}
					}

					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($general_data)),
						array('ID'            => $general->ID)
					);
					if (isset($school_logo_delete_id)) {
						wp_delete_attachment($school_logo_delete_id, true);
					}

					if (isset($school_signature_delete_id)) {
						wp_delete_attachment($school_signature_delete_id, true);
					}
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				$wpdb->query('COMMIT;');

				$message = esc_html__('General settings saved.', 'school-management');

				wp_send_json_success(array('message' => $message));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function save_school_email_carrier_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['save-school-email-carrier-settings'], 'save-school-email-carrier-settings')) {
				die();
			}

			$email_carrier = isset($_POST['email_carrier']) ? sanitize_text_field($_POST['email_carrier']) : 'wp_mail';

			$wp_mail_from_name  = isset($_POST['wp_mail_from_name']) ? sanitize_text_field($_POST['wp_mail_from_name']) : '';
			$wp_mail_from_email = isset($_POST['wp_mail_from_email']) ? sanitize_text_field($_POST['wp_mail_from_email']) : '';

			$smtp_from_name  = isset($_POST['smtp_from_name']) ? sanitize_text_field($_POST['smtp_from_name']) : '';
			$smtp_host       = isset($_POST['smtp_host']) ? sanitize_text_field($_POST['smtp_host']) : '';
			$smtp_username   = isset($_POST['smtp_username']) ? sanitize_text_field($_POST['smtp_username']) : '';
			$smtp_password   = isset($_POST['smtp_password']) ? $_POST['smtp_password'] : '';
			$smtp_encryption = isset($_POST['smtp_encryption']) ? sanitize_text_field($_POST['smtp_encryption']) : '';
			$smtp_port       = isset($_POST['smtp_port']) ? sanitize_text_field($_POST['smtp_port']) : '';

			// Start validation.
			$errors = array();

			if (!in_array($email_carrier, array_keys(WLSM_Email::email_carriers()))) {
				$errors['email_carrier'] = esc_html__('Please select a valid email carrier.', 'school-management');
			}

			if (!empty($wp_mail_from_email) && !filter_var($wp_mail_from_email, FILTER_VALIDATE_EMAIL)) {
				$errors['wp_mail_from_email'] = esc_html__('Please provide a valid email.', 'school-management');
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

				// Email Carrier.
				$email = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email"', $school_id));

				$email_data = array(
					'carrier' => $email_carrier,
				);

				if (!$email) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'email',
							'setting_value' => serialize($email_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($email_data)),
						array('ID'            => $email->ID)
					);
				}

				// WP_Mail.
				$wp_mail = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "wp_mail"', $school_id));

				$wp_mail_data = array(
					'from_name'  => $wp_mail_from_name,
					'from_email' => $wp_mail_from_email,
				);

				if (!$wp_mail) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'wp_mail',
							'setting_value' => serialize($wp_mail_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($wp_mail_data)),
						array('ID'            => $wp_mail->ID)
					);
				}

				// SMTP.
				$smtp = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "smtp"', $school_id));

				$smtp_data = array(
					'from_name'  => $smtp_from_name,
					'host'       => $smtp_host,
					'username'   => $smtp_username,
					'password'   => $smtp_password,
					'encryption' => $smtp_encryption,
					'port'       => $smtp_port,
				);

				if (!$smtp) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'smtp',
							'setting_value' => serialize($smtp_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$smtp_saved_data = unserialize($smtp->setting_value);

					if (empty($smtp_data['password'])) {
						$smtp_data['password'] = $smtp_saved_data['password'];
					}

					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($smtp_data)),
						array('ID'            => $smtp->ID)
					);
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				$wpdb->query('COMMIT;');

				$message = esc_html__('Email settings saved.', 'school-management');

				wp_send_json_success(array('message' => $message));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function save_school_email_templates_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['save-school-email-templates-settings'], 'save-school-email-templates-settings')) {
				die();
			}

			$email_student_admission_enable  = isset($_POST['email_student_admission_enable']) ? (bool) ($_POST['email_student_admission_enable']) : 0;
			$email_student_admission_subject = isset($_POST['email_student_admission_subject']) ? sanitize_text_field(stripslashes($_POST['email_student_admission_subject'])) : '';
			$email_student_admission_body    = isset($_POST['email_student_admission_body']) ? wp_kses_post(stripslashes($_POST['email_student_admission_body'])) : '';

			$email_invoice_generated_enable  = isset($_POST['email_invoice_generated_enable']) ? (bool) ($_POST['email_invoice_generated_enable']) : 0;
			$email_invoice_generated_subject = isset($_POST['email_invoice_generated_subject']) ? sanitize_text_field(stripslashes($_POST['email_invoice_generated_subject'])) : '';
			$email_invoice_generated_body    = isset($_POST['email_invoice_generated_body']) ? wp_kses_post(stripslashes($_POST['email_invoice_generated_body'])) : '';

			$email_online_fee_submission_enable  = isset($_POST['email_online_fee_submission_enable']) ? (bool) ($_POST['email_online_fee_submission_enable']) : 0;
			$email_online_fee_submission_subject = isset($_POST['email_online_fee_submission_subject']) ? sanitize_text_field(stripslashes($_POST['email_online_fee_submission_subject'])) : '';
			$email_online_fee_submission_body    = isset($_POST['email_online_fee_submission_body']) ? wp_kses_post(stripslashes($_POST['email_online_fee_submission_body'])) : '';

			$email_offline_fee_submission_enable  = isset($_POST['email_offline_fee_submission_enable']) ? (bool) ($_POST['email_offline_fee_submission_enable']) : 0;
			$email_offline_fee_submission_subject = isset($_POST['email_offline_fee_submission_subject']) ? sanitize_text_field(stripslashes($_POST['email_offline_fee_submission_subject'])) : '';
			$email_offline_fee_submission_body    = isset($_POST['email_offline_fee_submission_body']) ? wp_kses_post(stripslashes($_POST['email_offline_fee_submission_body'])) : '';

			$email_inquiry_received_to_inquisitor_enable  = isset($_POST['email_inquiry_received_to_inquisitor_enable']) ? (bool) ($_POST['email_inquiry_received_to_inquisitor_enable']) : 0;
			$email_inquiry_received_to_inquisitor_subject = isset($_POST['email_inquiry_received_to_inquisitor_subject']) ? sanitize_text_field(stripslashes($_POST['email_inquiry_received_to_inquisitor_subject'])) : '';
			$email_inquiry_received_to_inquisitor_body    = isset($_POST['email_inquiry_received_to_inquisitor_body']) ? wp_kses_post(stripslashes($_POST['email_inquiry_received_to_inquisitor_body'])) : '';

			$email_inquiry_received_to_admin_enable  = isset($_POST['email_inquiry_received_to_admin_enable']) ? (bool) ($_POST['email_inquiry_received_to_admin_enable']) : 0;
			$email_inquiry_received_to_admin_subject = isset($_POST['email_inquiry_received_to_admin_subject']) ? sanitize_text_field(stripslashes($_POST['email_inquiry_received_to_admin_subject'])) : '';
			$email_inquiry_received_to_admin_body    = isset($_POST['email_inquiry_received_to_admin_body']) ? wp_kses_post(stripslashes($_POST['email_inquiry_received_to_admin_body'])) : '';

			$email_student_registration_to_student_enable  = isset($_POST['email_student_registration_to_student_enable']) ? (bool) ($_POST['email_student_registration_to_student_enable']) : 0;
			$email_student_registration_to_student_subject = isset($_POST['email_student_registration_to_student_subject']) ? sanitize_text_field(stripslashes($_POST['email_student_registration_to_student_subject'])) : '';
			$email_student_registration_to_student_body    = isset($_POST['email_student_registration_to_student_body']) ? wp_kses_post(stripslashes($_POST['email_student_registration_to_student_body'])) : '';

			$email_student_registration_to_admin_enable  = isset($_POST['email_student_registration_to_admin_enable']) ? (bool) ($_POST['email_student_registration_to_admin_enable']) : 0;
			$email_student_registration_to_admin_subject = isset($_POST['email_student_registration_to_admin_subject']) ? sanitize_text_field(stripslashes($_POST['email_student_registration_to_admin_subject'])) : '';
			$email_student_registration_to_admin_body    = isset($_POST['email_student_registration_to_admin_body']) ? wp_kses_post(stripslashes($_POST['email_student_registration_to_admin_body'])) : '';
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		try {
			$wpdb->query('BEGIN;');

			// Email Student Admission.
			$email_student_admission = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_admission"', $school_id));

			$email_student_admission_data = array(
				'enable'  => $email_student_admission_enable,
				'subject' => $email_student_admission_subject,
				'body'    => $email_student_admission_body,
			);

			if (!$email_student_admission) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'email_student_admission',
						'setting_value' => serialize($email_student_admission_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($email_student_admission_data)),
					array('ID'            => $email_student_admission->ID)
				);
			}

			// Email Invoice Generated.
			$email_invoice_generated = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_invoice_generated"', $school_id));

			$email_invoice_generated_data = array(
				'enable'  => $email_invoice_generated_enable,
				'subject' => $email_invoice_generated_subject,
				'body'    => $email_invoice_generated_body,
			);

			if (!$email_invoice_generated) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'email_invoice_generated',
						'setting_value' => serialize($email_invoice_generated_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($email_invoice_generated_data)),
					array('ID'            => $email_invoice_generated->ID)
				);
			}

			// Email Online Fee Submission.
			$email_online_fee_submission = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_online_fee_submission"', $school_id));

			$email_online_fee_submission_data = array(
				'enable'  => $email_online_fee_submission_enable,
				'subject' => $email_online_fee_submission_subject,
				'body'    => $email_online_fee_submission_body,
			);

			if (!$email_online_fee_submission) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'email_online_fee_submission',
						'setting_value' => serialize($email_online_fee_submission_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($email_online_fee_submission_data)),
					array('ID'            => $email_online_fee_submission->ID)
				);
			}

			// Email Offline Fee Submission.
			$email_offline_fee_submission = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_offline_fee_submission"', $school_id));

			$email_offline_fee_submission_data = array(
				'enable'  => $email_offline_fee_submission_enable,
				'subject' => $email_offline_fee_submission_subject,
				'body'    => $email_offline_fee_submission_body,
			);

			if (!$email_offline_fee_submission) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'email_offline_fee_submission',
						'setting_value' => serialize($email_offline_fee_submission_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($email_offline_fee_submission_data)),
					array('ID'            => $email_offline_fee_submission->ID)
				);
			}

			// Email Inquiry Received to Inquisitor.
			$email_inquiry_received_to_inquisitor = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_inquiry_received_to_inquisitor"', $school_id));

			$email_inquiry_received_to_inquisitor_data = array(
				'enable'  => $email_inquiry_received_to_inquisitor_enable,
				'subject' => $email_inquiry_received_to_inquisitor_subject,
				'body'    => $email_inquiry_received_to_inquisitor_body,
			);

			if (!$email_inquiry_received_to_inquisitor) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'email_inquiry_received_to_inquisitor',
						'setting_value' => serialize($email_inquiry_received_to_inquisitor_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($email_inquiry_received_to_inquisitor_data)),
					array('ID'            => $email_inquiry_received_to_inquisitor->ID)
				);
			}

			// Email Inquiry Received to Admin.
			$email_inquiry_received_to_admin = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_inquiry_received_to_admin"', $school_id));

			$email_inquiry_received_to_admin_data = array(
				'enable'  => $email_inquiry_received_to_admin_enable,
				'subject' => $email_inquiry_received_to_admin_subject,
				'body'    => $email_inquiry_received_to_admin_body,
			);

			if (!$email_inquiry_received_to_admin) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'email_inquiry_received_to_admin',
						'setting_value' => serialize($email_inquiry_received_to_admin_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($email_inquiry_received_to_admin_data)),
					array('ID'            => $email_inquiry_received_to_admin->ID)
				);
			}

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			// Email Student Registration to Student.
			$email_student_registration_to_student = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_registration_to_student"', $school_id));

			$email_student_registration_to_student_data = array(
				'enable'  => $email_student_registration_to_student_enable,
				'subject' => $email_student_registration_to_student_subject,
				'body'    => $email_student_registration_to_student_body,
			);

			if (!$email_student_registration_to_student) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'email_student_registration_to_student',
						'setting_value' => serialize($email_student_registration_to_student_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($email_student_registration_to_student_data)),
					array('ID'            => $email_student_registration_to_student->ID)
				);
			}

			// Email Student Registration to Admin.
			$email_student_registration_to_admin = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "email_student_registration_to_admin"', $school_id));

			$email_student_registration_to_admin_data = array(
				'enable'  => $email_student_registration_to_admin_enable,
				'subject' => $email_student_registration_to_admin_subject,
				'body'    => $email_student_registration_to_admin_body,
			);

			if (!$email_student_registration_to_admin) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'email_student_registration_to_admin',
						'setting_value' => serialize($email_student_registration_to_admin_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($email_student_registration_to_admin_data)),
					array('ID'            => $email_student_registration_to_admin->ID)
				);
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('Email templates saved.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function send_test_email()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['send-test-email'], 'send-test-email')) {
				die();
			}

			$template = isset($_POST['template']) ? sanitize_text_field($_POST['template']) : '';
			$email_to = isset($_POST['to']) ? sanitize_text_field($_POST['to']) : '';

			if (empty($email_to)) {
				wp_send_json_error(esc_html__('Please provide an email.', 'school-management'));
			}

			if (!filter_var($email_to, FILTER_VALIDATE_EMAIL)) {
				wp_send_json_error(esc_html__('Please provide a valid email.', 'school-management'));
			}

			$method_name = 'get_settings_email_' . $template;

			if (!method_exists('WLSM_M_Setting', $method_name)) {
				wp_send_json_error(esc_html__('Email template not found.', 'school-management'));
			}

			$settings = WLSM_M_Setting::$method_name($school_id);

			$subject = $settings['subject'];
			$body    = $settings['body'];

			$sent = WLSM_Email::send_email($school_id, $email_to, $subject, $body);

			if ($sent) {
				wp_send_json_success(array('message' => 'Email sent.'));
			}

			wp_send_json_error(esc_html__('Email was not sent.', 'school-management'));
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}
	}

	public static function save_school_sms_carrier_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['save-school-sms-carrier-settings'], 'save-school-sms-carrier-settings')) {
				die();
			}

			$sms_carrier = isset($_POST['sms_carrier']) ? sanitize_text_field($_POST['sms_carrier']) : 'wp_mail';

			$smsstriker_sender_id = isset($_POST['smsstriker_sender_id']) ? sanitize_text_field($_POST['smsstriker_sender_id']) : '';
			$smsstriker_username  = isset($_POST['smsstriker_username']) ? sanitize_text_field($_POST['smsstriker_username']) : '';
			$smsstriker_password  = isset($_POST['smsstriker_password']) ? $_POST['smsstriker_password'] : '';

			$msgclub_auth_key         = isset($_POST['msgclub_auth_key']) ? sanitize_text_field($_POST['msgclub_auth_key']) : '';
			$msgclub_sender_id        = isset($_POST['msgclub_sender_id']) ? sanitize_text_field($_POST['msgclub_sender_id']) : '';
			$msgclub_route_id         = isset($_POST['msgclub_route_id']) ? sanitize_text_field($_POST['msgclub_route_id']) : '';
			$msgclub_sms_content_type = isset($_POST['msgclub_sms_content_type']) ? sanitize_text_field($_POST['msgclub_sms_content_type']) : '';

			$pob_sender_id = isset($_POST['pob_sender_id']) ? sanitize_text_field($_POST['pob_sender_id']) : '';
			$pob_username  = isset($_POST['pob_username']) ? sanitize_text_field($_POST['pob_username']) : '';
			$pob_password  = isset($_POST['pob_password']) ? $_POST['pob_password'] : '';

			$pointsms_sender_id = isset($_POST['pointsms_sender_id']) ? sanitize_text_field($_POST['pointsms_sender_id']) : '';
			$pointsms_username  = isset($_POST['pointsms_username']) ? sanitize_text_field($_POST['pointsms_username']) : '';
			$pointsms_password  = isset($_POST['pointsms_password']) ? $_POST['pointsms_password'] : '';
			$pointsms_channel   = isset($_POST['pointsms_channel']) ? sanitize_text_field($_POST['pointsms_channel']) : '';
			$pointsms_route     = isset($_POST['pointsms_route']) ? sanitize_text_field($_POST['pointsms_route']) : '';
			$pointsms_peid      = isset($_POST['pointsms_peid']) ? sanitize_text_field($_POST['pointsms_peid']) : '';

			$vinuthan_sender_id = isset($_POST['vinuthansms_sender_id']) ? sanitize_text_field($_POST['vinuthansms_sender_id']) : '';
			$vinuthan_username = isset($_POST['vinuthansms_username']) ? sanitize_text_field($_POST['vinuthansms_username']) : '';
			$vinuthan_channel = isset($_POST['vinuthansms_channel']) ? sanitize_text_field($_POST['vinuthansms_channel']) : '';
			$vinuthan_route = isset($_POST['vinuthansms_route']) ? sanitize_text_field($_POST['vinuthansms_route']) : '';

			$nexmo_api_key    = isset($_POST['nexmo_api_key']) ? sanitize_text_field($_POST['nexmo_api_key']) : '';
			$nexmo_api_secret = isset($_POST['nexmo_api_secret']) ? sanitize_text_field($_POST['nexmo_api_secret']) : '';
			$nexmo_from       = isset($_POST['nexmo_from']) ? sanitize_text_field($_POST['nexmo_from']) : '';

			$twilio_sid   = isset($_POST['twilio_sid']) ? sanitize_text_field($_POST['twilio_sid']) : '';
			$twilio_token = isset($_POST['twilio_token']) ? sanitize_text_field($_POST['twilio_token']) : '';
			$twilio_from  = isset($_POST['twilio_from']) ? sanitize_text_field($_POST['twilio_from']) : '';

			$msg91_authkey = isset($_POST['msg91_authkey']) ? sanitize_text_field($_POST['msg91_authkey']) : '';
			$msg91_route   = isset($_POST['msg91_route']) ? sanitize_text_field($_POST['msg91_route']) : '';
			$msg91_sender  = isset($_POST['msg91_sender']) ? sanitize_text_field($_POST['msg91_sender']) : '';
			$msg91_country = isset($_POST['msg91_country']) ? sanitize_text_field($_POST['msg91_country']) : '';

			$textlocal_api_key = isset($_POST['textlocal_api_key']) ? sanitize_text_field($_POST['textlocal_api_key']) : '';
			$textlocal_sender  = isset($_POST['textlocal_sender']) ? sanitize_text_field($_POST['textlocal_sender']) : '';

			$ebulksms_username = isset($_POST['ebulksms_username']) ? sanitize_text_field($_POST['ebulksms_username']) : '';
			$ebulksms_api_key  = isset($_POST['ebulksms_api_key']) ? sanitize_text_field($_POST['ebulksms_api_key']) : '';
			$ebulksms_sender   = isset($_POST['ebulksms_sender']) ? sanitize_text_field($_POST['ebulksms_sender']) : '';

			// Start validation.
			$errors = array();

			if (!in_array($sms_carrier, array_keys(WLSM_SMS::sms_carriers()))) {
				$errors['sms_carrier'] = esc_html__('Please select a valid sms carrier.', 'school-management');
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

				// SMS Carrier.
				$sms = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms"', $school_id));

				$sms_data = array(
					'carrier' => $sms_carrier,
				);

				if (!$sms) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'sms',
							'setting_value' => serialize($sms_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($sms_data)),
						array('ID'            => $sms->ID)
					);
				}

				// SMS Striker.
				$smsstriker = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "smsstriker"', $school_id));

				$smsstriker_data = array(
					'sender_id' => $smsstriker_sender_id,
					'username'  => $smsstriker_username,
					'password'  => $smsstriker_password,
				);

				if (!$smsstriker) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'smsstriker',
							'setting_value' => serialize($smsstriker_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$smsstriker_saved_data = unserialize($smsstriker->setting_value);

					if (empty($smsstriker_data['password'])) {
						$smsstriker_data['password'] = $smsstriker_saved_data['password'];
					}

					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($smsstriker_data)),
						array('ID'            => $smsstriker->ID)
					);
				}

				// MsgClub.
				$msgclub = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "msgclub"', $school_id));

				$msgclub_data = array(
					'auth_key'         => $msgclub_auth_key,
					'sender_id'        => $msgclub_sender_id,
					'route_id'         => $msgclub_route_id,
					'sms_content_type' => $msgclub_sms_content_type,
				);

				if (!$msgclub) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'msgclub',
							'setting_value' => serialize($msgclub_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($msgclub_data)),
						array('ID'            => $msgclub->ID)
					);
				}

				// Point SMS.
				$pointsms = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pointsms"', $school_id));

				$pointsms_data = array(
					'sender_id' => $pointsms_sender_id,
					'username'  => $pointsms_username,
					'password'  => $pointsms_password,
					'channel'   => $pointsms_channel,
					'route'     => $pointsms_route,
					'peid'       => $pointsms_peid,
				);

				if (!$pointsms) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'pointsms',
							'setting_value' => serialize($pointsms_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$pointsms_saved_data = unserialize($pointsms->setting_value);

					if (empty($pointsms_data['password'])) {
						$pointsms_data['password'] = $pointsms_saved_data['password'];
					}

					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($pointsms_data)),
						array('ID'            => $pointsms->ID)
					);
				}

				// vinuthan SMS.
				$vinuthan = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "vinuthan"', $school_id));

				$vinuthan_data = array(
					'sender_id' => $vinuthan_sender_id,
					'username' => $vinuthan_username,
					'channel' => $vinuthan_channel,
					'route' => $vinuthan_route,
				);

				if (!$vinuthan) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key' => 'vinuthan',
							'setting_value' => serialize($vinuthan_data),
							'school_id' => $school_id,
						)
					);
				} else {
					$vinuthan_saved_data = unserialize($vinuthan->setting_value);

					if (empty($vinuthan_data['password'])) {
						$vinuthan_data['password'] = $vinuthan_saved_data['password'];
					}

					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($vinuthan_data)),
						array('ID' => $vinuthan->ID)
					);
				}

				// Point SMS.
				$pob = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pob"', $school_id));

				$pob_data = array(
					'sender_id' => $pob_sender_id,
					'username'  => $pob_username,
					'password'  => $pob_password,
				);

				if (!$pob) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'pob',
							'setting_value' => serialize($pob_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$pob_saved_data = unserialize($pob->setting_value);

					if (empty($pob_data['password'])) {
						$pob_data['password'] = $pob_saved_data['password'];
					}

					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($pob_data)),
						array('ID'            => $pob->ID)
					);
				}

				// Nexmo.
				$nexmo = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "nexmo"', $school_id));

				$nexmo_data = array(
					'api_key'    => $nexmo_api_key,
					'api_secret' => $nexmo_api_secret,
					'from'       => $nexmo_from,
				);

				if (!$nexmo) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'nexmo',
							'setting_value' => serialize($nexmo_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($nexmo_data)),
						array('ID'            => $nexmo->ID)
					);
				}

				// Twilio.
				$twilio = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "twilio"', $school_id));

				$twilio_data = array(
					'sid'   => $twilio_sid,
					'token' => $twilio_token,
					'from'  => $twilio_from,
				);

				if (!$twilio) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'twilio',
							'setting_value' => serialize($twilio_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($twilio_data)),
						array('ID'            => $twilio->ID)
					);
				}

				// Msg91.
				$msg91 = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "msg91"', $school_id));

				$msg91_data = array(
					'authkey' => $msg91_authkey,
					'route'   => $msg91_route,
					'sender'  => $msg91_sender,
					'country' => $msg91_country,
				);

				if (!$msg91) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'msg91',
							'setting_value' => serialize($msg91_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($msg91_data)),
						array('ID'            => $msg91->ID)
					);
				}

				// Textlocal.
				$textlocal = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "textlocal"', $school_id));

				$textlocal_data = array(
					'api_key' => $textlocal_api_key,
					'sender'  => $textlocal_sender,
				);

				if (!$textlocal) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'textlocal',
							'setting_value' => serialize($textlocal_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($textlocal_data)),
						array('ID'            => $textlocal->ID)
					);
				}

				// EBulkSMS.
				$ebulksms = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "ebulksms"', $school_id));

				$ebulksms_data = array(
					'username' => $ebulksms_username,
					'api_key'  => $ebulksms_api_key,
					'sender'   => $ebulksms_sender,
				);

				if (!$ebulksms) {
					$wpdb->insert(
						WLSM_SETTINGS,
						array(
							'setting_key'   => 'ebulksms',
							'setting_value' => serialize($ebulksms_data),
							'school_id'     => $school_id,
						)
					);
				} else {
					$wpdb->update(
						WLSM_SETTINGS,
						array('setting_value' => serialize($ebulksms_data)),
						array('ID'            => $ebulksms->ID)
					);
				}

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				$wpdb->query('COMMIT;');

				$message = esc_html__('SMS settings saved.', 'school-management');

				wp_send_json_success(array('message' => $message));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($exception->getMessage());
			}
		}
		wp_send_json_error($errors);
	}

	public static function save_school_sms_templates_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['save-school-sms-templates-settings'], 'save-school-sms-templates-settings')) {
				die();
			}

			$sms_student_admission_enable  = isset($_POST['sms_student_admission_enable']) ? (bool) ($_POST['sms_student_admission_enable']) : 0;
			$sms_student_admission_message = isset($_POST['sms_student_admission_message']) ? sanitize_text_field(stripslashes($_POST['sms_student_admission_message'])) : '';

			$sms_invoice_generated_enable  = isset($_POST['sms_invoice_generated_enable']) ? (bool) ($_POST['sms_invoice_generated_enable']) : 0;
			$sms_invoice_generated_message = isset($_POST['sms_invoice_generated_message']) ? sanitize_text_field(stripslashes($_POST['sms_invoice_generated_message'])) : '';

			$sms_online_fee_submission_enable  = isset($_POST['sms_online_fee_submission_enable']) ? (bool) ($_POST['sms_online_fee_submission_enable']) : 0;
			$sms_online_fee_submission_message = isset($_POST['sms_online_fee_submission_message']) ? sanitize_text_field(stripslashes($_POST['sms_online_fee_submission_message'])) : '';

			$sms_offline_fee_submission_enable  = isset($_POST['sms_offline_fee_submission_enable']) ? (bool) ($_POST['sms_offline_fee_submission_enable']) : 0;
			$sms_offline_fee_submission_message = isset($_POST['sms_offline_fee_submission_message']) ? sanitize_text_field(stripslashes($_POST['sms_offline_fee_submission_message'])) : '';

			$sms_student_admission_to_parent_enable  = isset($_POST['sms_student_admission_to_parent_enable']) ? (bool) ($_POST['sms_student_admission_to_parent_enable']) : 0;
			$sms_student_admission_to_parent_message = isset($_POST['sms_student_admission_to_parent_message']) ? sanitize_text_field(stripslashes($_POST['sms_student_admission_to_parent_message'])) : '';

			$sms_invoice_generated_to_parent_enable  = isset($_POST['sms_invoice_generated_to_parent_enable']) ? (bool) ($_POST['sms_invoice_generated_to_parent_enable']) : 0;
			$sms_invoice_generated_to_parent_message = isset($_POST['sms_invoice_generated_to_parent_message']) ? sanitize_text_field(stripslashes($_POST['sms_invoice_generated_to_parent_message'])) : '';

			$sms_online_fee_submission_to_parent_enable  = isset($_POST['sms_online_fee_submission_to_parent_enable']) ? (bool) ($_POST['sms_online_fee_submission_to_parent_enable']) : 0;
			$sms_online_fee_submission_to_parent_message = isset($_POST['sms_online_fee_submission_to_parent_message']) ? sanitize_text_field(stripslashes($_POST['sms_online_fee_submission_to_parent_message'])) : '';

			$sms_offline_fee_submission_to_parent_enable  = isset($_POST['sms_offline_fee_submission_to_parent_enable']) ? (bool) ($_POST['sms_offline_fee_submission_to_parent_enable']) : 0;
			$sms_offline_fee_submission_to_parent_message = isset($_POST['sms_offline_fee_submission_to_parent_message']) ? sanitize_text_field(stripslashes($_POST['sms_offline_fee_submission_to_parent_message'])) : '';

			$sms_absent_student_enable  = isset($_POST['sms_absent_student_enable']) ? (bool) ($_POST['sms_absent_student_enable']) : 0;
			$sms_absent_student_message = isset($_POST['sms_absent_student_message']) ? sanitize_text_field(stripslashes($_POST['sms_absent_student_message'])) : '';

			$sms_inquiry_received_to_inquisitor_enable  = isset($_POST['sms_inquiry_received_to_inquisitor_enable']) ? (bool) ($_POST['sms_inquiry_received_to_inquisitor_enable']) : 0;
			$sms_inquiry_received_to_inquisitor_message = isset($_POST['sms_inquiry_received_to_inquisitor_message']) ? sanitize_text_field(stripslashes($_POST['sms_inquiry_received_to_inquisitor_message'])) : '';

			$sms_inquiry_received_to_admin_enable  = isset($_POST['sms_inquiry_received_to_admin_enable']) ? (bool) ($_POST['sms_inquiry_received_to_admin_enable']) : 0;
			$sms_inquiry_received_to_admin_message = isset($_POST['sms_inquiry_received_to_admin_message']) ? sanitize_text_field(stripslashes($_POST['sms_inquiry_received_to_admin_message'])) : '';

			$sms_student_registration_to_student_enable  = isset($_POST['sms_student_registration_to_student_enable']) ? (bool) ($_POST['sms_student_registration_to_student_enable']) : 0;
			$sms_student_registration_to_student_message = isset($_POST['sms_student_registration_to_student_message']) ? sanitize_text_field(stripslashes($_POST['sms_student_registration_to_student_message'])) : '';

			$sms_student_registration_to_admin_enable  = isset($_POST['sms_student_registration_to_admin_enable']) ? (bool) ($_POST['sms_student_registration_to_admin_enable']) : 0;
			$sms_student_registration_to_admin_message = isset($_POST['sms_student_registration_to_admin_message']) ? sanitize_text_field(stripslashes($_POST['sms_student_registration_to_admin_message'])) : '';
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		try {
			$wpdb->query('BEGIN;');

			// SMS Student Admission.
			$sms_student_admission = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_admission"', $school_id));

			$sms_student_admission_data = array(
				'enable'  => $sms_student_admission_enable,
				'message' => $sms_student_admission_message,
			);

			if (!$sms_student_admission) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_student_admission',
						'setting_value' => serialize($sms_student_admission_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_student_admission_data)),
					array('ID'            => $sms_student_admission->ID)
				);
			}

			// SMS Invoice Generated.
			$sms_invoice_generated = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_invoice_generated"', $school_id));

			$sms_invoice_generated_data = array(
				'enable'  => $sms_invoice_generated_enable,
				'message' => $sms_invoice_generated_message,
			);

			if (!$sms_invoice_generated) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_invoice_generated',
						'setting_value' => serialize($sms_invoice_generated_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_invoice_generated_data)),
					array('ID'            => $sms_invoice_generated->ID)
				);
			}

			// SMS Online Fee Submission.
			$sms_online_fee_submission = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_online_fee_submission"', $school_id));

			$sms_online_fee_submission_data = array(
				'enable'  => $sms_online_fee_submission_enable,
				'message' => $sms_online_fee_submission_message,
			);

			if (!$sms_online_fee_submission) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_online_fee_submission',
						'setting_value' => serialize($sms_online_fee_submission_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_online_fee_submission_data)),
					array('ID'            => $sms_online_fee_submission->ID)
				);
			}

			// SMS Offline Fee Submission.
			$sms_offline_fee_submission = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_offline_fee_submission"', $school_id));

			$sms_offline_fee_submission_data = array(
				'enable'  => $sms_offline_fee_submission_enable,
				'message' => $sms_offline_fee_submission_message,
			);

			if (!$sms_offline_fee_submission) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_offline_fee_submission',
						'setting_value' => serialize($sms_offline_fee_submission_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_offline_fee_submission_data)),
					array('ID'            => $sms_offline_fee_submission->ID)
				);
			}

			// SMS Student Admission To Parent.
			$sms_student_admission_to_parent = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_admission_to_parent"', $school_id));

			$sms_student_admission_to_parent_data = array(
				'enable'  => $sms_student_admission_to_parent_enable,
				'message' => $sms_student_admission_to_parent_message,
			);

			if (!$sms_student_admission_to_parent) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_student_admission_to_parent',
						'setting_value' => serialize($sms_student_admission_to_parent_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_student_admission_to_parent_data)),
					array('ID'            => $sms_student_admission_to_parent->ID)
				);
			}

			// SMS Invoice Generated To Parent.
			$sms_invoice_generated_to_parent = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_invoice_generated_to_parent"', $school_id));

			$sms_invoice_generated_to_parent_data = array(
				'enable'  => $sms_invoice_generated_to_parent_enable,
				'message' => $sms_invoice_generated_to_parent_message,
			);

			if (!$sms_invoice_generated_to_parent) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_invoice_generated_to_parent',
						'setting_value' => serialize($sms_invoice_generated_to_parent_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_invoice_generated_to_parent_data)),
					array('ID'            => $sms_invoice_generated_to_parent->ID)
				);
			}

			// SMS Online Fee Submission To Parent.
			$sms_online_fee_submission_to_parent = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_online_fee_submission_to_parent"', $school_id));

			$sms_online_fee_submission_to_parent_data = array(
				'enable'  => $sms_online_fee_submission_to_parent_enable,
				'message' => $sms_online_fee_submission_to_parent_message,
			);

			if (!$sms_online_fee_submission_to_parent) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_online_fee_submission_to_parent',
						'setting_value' => serialize($sms_online_fee_submission_to_parent_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_online_fee_submission_to_parent_data)),
					array('ID'            => $sms_online_fee_submission_to_parent->ID)
				);
			}

			// SMS Offline Fee Submission To Parent.
			$sms_offline_fee_submission_to_parent = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_offline_fee_submission_to_parent"', $school_id));

			$sms_offline_fee_submission_to_parent_data = array(
				'enable'  => $sms_offline_fee_submission_to_parent_enable,
				'message' => $sms_offline_fee_submission_to_parent_message,
			);

			if (!$sms_offline_fee_submission_to_parent) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_offline_fee_submission_to_parent',
						'setting_value' => serialize($sms_offline_fee_submission_to_parent_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_offline_fee_submission_to_parent_data)),
					array('ID'            => $sms_offline_fee_submission_to_parent->ID)
				);
			}

			// SMS Absent Student.
			$sms_absent_student = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_absent_student"', $school_id));

			$sms_absent_student_data = array(
				'enable'  => $sms_absent_student_enable,
				'message' => $sms_absent_student_message,
			);

			if (!$sms_absent_student) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_absent_student',
						'setting_value' => serialize($sms_absent_student_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_absent_student_data)),
					array('ID'            => $sms_absent_student->ID)
				);
			}

			// SMS Inquiry Received to Inquisitor.
			$sms_inquiry_received_to_inquisitor = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_inquiry_received_to_inquisitor"', $school_id));

			$sms_inquiry_received_to_inquisitor_data = array(
				'enable'  => $sms_inquiry_received_to_inquisitor_enable,
				'message' => $sms_inquiry_received_to_inquisitor_message,
			);

			if (!$sms_inquiry_received_to_inquisitor) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_inquiry_received_to_inquisitor',
						'setting_value' => serialize($sms_inquiry_received_to_inquisitor_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_inquiry_received_to_inquisitor_data)),
					array('ID'            => $sms_inquiry_received_to_inquisitor->ID)
				);
			}

			// SMS Inquiry Received to Admin.
			$sms_inquiry_received_to_admin = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_inquiry_received_to_admin"', $school_id));

			$sms_inquiry_received_to_admin_data = array(
				'enable'  => $sms_inquiry_received_to_admin_enable,
				'message' => $sms_inquiry_received_to_admin_message,
			);

			if (!$sms_inquiry_received_to_admin) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_inquiry_received_to_admin',
						'setting_value' => serialize($sms_inquiry_received_to_admin_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_inquiry_received_to_admin_data)),
					array('ID'            => $sms_inquiry_received_to_admin->ID)
				);
			}

			// SMS Student Registration to Student.
			$sms_student_registration_to_student = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_registration_to_student"', $school_id));

			$sms_student_registration_to_student_data = array(
				'enable'  => $sms_student_registration_to_student_enable,
				'message' => $sms_student_registration_to_student_message,
			);

			if (!$sms_student_registration_to_student) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_student_registration_to_student',
						'setting_value' => serialize($sms_student_registration_to_student_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_student_registration_to_student_data)),
					array('ID'            => $sms_student_registration_to_student->ID)
				);
			}

			// SMS Student Registration to Admin.
			$sms_student_registration_to_admin = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "sms_student_registration_to_admin"', $school_id));

			$sms_student_registration_to_admin_data = array(
				'enable'  => $sms_student_registration_to_admin_enable,
				'message' => $sms_student_registration_to_admin_message,
			);

			if (!$sms_student_registration_to_admin) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'sms_student_registration_to_admin',
						'setting_value' => serialize($sms_student_registration_to_admin_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($sms_student_registration_to_admin_data)),
					array('ID'            => $sms_student_registration_to_admin->ID)
				);
			}

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('SMS templates saved.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function send_test_sms()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['send-test-sms'], 'send-test-sms')) {
				die();
			}

			$template = isset($_POST['template']) ? sanitize_text_field($_POST['template']) : '';
			$sms_to   = isset($_POST['to']) ? sanitize_text_field($_POST['to']) : '';

			if (empty($sms_to)) {
				wp_send_json_error(esc_html__('Please provide a phone number.', 'school-management'));
			}

			$method_name = 'get_settings_sms_' . $template;

			if (!method_exists('WLSM_M_Setting', $method_name)) {
				wp_send_json_error(esc_html__('SMS template not found.', 'school-management'));
			}

			$settings = WLSM_M_Setting::$method_name($school_id);

			$message = $settings['message'];

			$sent = WLSM_SMS::send_sms($school_id, $sms_to, $message);

			if ($sent) {
				wp_send_json_success(array('message' => 'SMS sent.'));
			}

			wp_send_json_error(esc_html__('SMS was not sent.', 'school-management'));
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = $buffer;
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}
	}

	public static function save_school_payment_method_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		try {
			ob_start();
			global $wpdb;

			if (!wp_verify_nonce($_POST['save-school-payment-method-settings'], 'save-school-payment-method-settings')) {
				die();
			}

			$razorpay_enable = isset($_POST['razorpay_enable']) ? (bool) ($_POST['razorpay_enable']) : 0;
			$razorpay_key    = isset($_POST['razorpay_key']) ? sanitize_text_field($_POST['razorpay_key']) : '';
			$razorpay_secret = isset($_POST['razorpay_secret']) ? sanitize_text_field($_POST['razorpay_secret']) : '';

			$stripe_enable          = isset($_POST['stripe_enable']) ? (bool) ($_POST['stripe_enable']) : 0;
			$stripe_publishable_key = isset($_POST['stripe_publishable_key']) ? sanitize_text_field($_POST['stripe_publishable_key']) : '';
			$stripe_secret_key      = isset($_POST['stripe_secret_key']) ? sanitize_text_field($_POST['stripe_secret_key']) : '';

			$paypal_enable         = isset($_POST['paypal_enable']) ? (bool) ($_POST['paypal_enable']) : 0;
			$paypal_business_email = isset($_POST['paypal_business_email']) ? sanitize_text_field($_POST['paypal_business_email']) : '';
			$paypal_mode           = isset($_POST['paypal_mode']) ? sanitize_text_field($_POST['paypal_mode']) : 'sandbox';

			$pesapal_enable          = isset($_POST['pesapal_enable']) ? (bool) ($_POST['pesapal_enable']) : 0;
			$pesapal_consumer_key    = isset($_POST['pesapal_consumer_key']) ? sanitize_text_field($_POST['pesapal_consumer_key']) : '';
			$pesapal_consumer_secret = isset($_POST['pesapal_consumer_secret']) ? sanitize_text_field($_POST['pesapal_consumer_secret']) : '';
			$pesapal_mode            = isset($_POST['pesapal_mode']) ? sanitize_text_field($_POST['pesapal_mode']) : 'sandbox';

			$paystack_enable     = isset($_POST['paystack_enable']) ? (bool) ($_POST['paystack_enable']) : 0;
			$paystack_public_key = isset($_POST['paystack_public_key']) ? sanitize_text_field($_POST['paystack_public_key']) : '';
			$paystack_secret_key = isset($_POST['paystack_secret_key']) ? sanitize_text_field($_POST['paystack_secret_key']) : '';

			$paytm_enable           = isset($_POST['paytm_enable']) ? (bool) ($_POST['paytm_enable']) : 0;
			$paytm_merchant_id      = isset($_POST['paytm_merchant_id']) ? sanitize_text_field($_POST['paytm_merchant_id']) : '';
			$paytm_merchant_key     = isset($_POST['paytm_merchant_key']) ? sanitize_text_field($_POST['paytm_merchant_key']) : '';
			$paytm_industry_type_id = isset($_POST['paytm_industry_type_id']) ? sanitize_text_field($_POST['paytm_industry_type_id']) : '';
			$paytm_website          = isset($_POST['paytm_website']) ? sanitize_text_field($_POST['paytm_website']) : '';
			$paytm_mode             = isset($_POST['paytm_mode']) ? sanitize_text_field($_POST['paytm_mode']) : '';

			$bank_transfer_enable  = isset($_POST['bank_transfer_enable']) ? (bool) ($_POST['bank_transfer_enable']) : 0;
			$bank_transfer_branch  = isset($_POST['bank_transfer_branch']) ? sanitize_text_field($_POST['bank_transfer_branch']) : '';
			$bank_transfer_account = isset($_POST['bank_transfer_account']) ? sanitize_text_field($_POST['bank_transfer_account']) : '';
			$bank_transfer_name    = isset($_POST['bank_transfer_name']) ? sanitize_text_field($_POST['bank_transfer_name']) : '';
			$bank_transfer_message = isset($_POST['bank_transfer_message']) ? sanitize_text_field($_POST['bank_transfer_message']) : '';

			if (!in_array($paypal_mode, array('sandbox', 'live'))) {
				$paypal_mode = 'sandbox';
			}

			if (!in_array($pesapal_mode, array('sandbox', 'live'))) {
				$paypal_mode = 'sandbox';
			}

			if (!in_array($paytm_mode, array('staging', 'production'))) {
				$paytm_mode = 'staging';
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

		try {
			$wpdb->query('BEGIN;');

			// Razorpay.
			$razorpay = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "razorpay"', $school_id));

			$razorpay_data = array(
				'enable'         => $razorpay_enable,
				'razorpay_key'    => $razorpay_key,
				'razorpay_secret' => $razorpay_secret,
			);

			if (!$razorpay) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'razorpay',
						'setting_value' => serialize($razorpay_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($razorpay_data)),
					array('ID'            => $razorpay->ID)
				);
			}

			// Stripe.
			$stripe = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "stripe"', $school_id));

			$stripe_data = array(
				'enable'          => $stripe_enable,
				'publishable_key' => $stripe_publishable_key,
				'secret_key'      => $stripe_secret_key,
			);

			if (!$stripe) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'stripe',
						'setting_value' => serialize($stripe_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($stripe_data)),
					array('ID'            => $stripe->ID)
				);
			}

			// PayPal.
			$paypal = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "paypal"', $school_id));

			$paypal_data = array(
				'enable'         => $paypal_enable,
				'business_email' => $paypal_business_email,
				'mode'           => $paypal_mode,
			);

			if (!$paypal) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'paypal',
						'setting_value' => serialize($paypal_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($paypal_data)),
					array('ID'            => $paypal->ID)
				);
			}

			// Pesapal.
			$pesapal = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "pesapal"', $school_id));

			$pesapal_data = array(
				'enable'          => $pesapal_enable,
				'consumer_key'    => $pesapal_consumer_key,
				'consumer_secret' => $pesapal_consumer_secret,
				'mode'            => $pesapal_mode,
			);

			if (!$pesapal) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'pesapal',
						'setting_value' => serialize($pesapal_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($pesapal_data)),
					array('ID'            => $pesapal->ID)
				);
			}

			// Paystack.
			$paystack = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "paystack"', $school_id));

			$paystack_data = array(
				'enable'              => $paystack_enable,
				'paystack_public_key' => $paystack_public_key,
				'paystack_secret_key' => $paystack_secret_key,
			);

			if (!$paystack) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'paystack',
						'setting_value' => serialize($paystack_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($paystack_data)),
					array('ID'            => $paystack->ID)
				);
			}

			// Paytm.
			$paytm = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "paytm"', $school_id));

			$paytm_data = array(
				'enable'           => $paytm_enable,
				'merchant_id'      => $paytm_merchant_id,
				'merchant_key'     => $paytm_merchant_key,
				'industry_type_id' => $paytm_industry_type_id,
				'website'          => $paytm_website,
				'mode'             => $paytm_mode,
			);

			if (!$paytm) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'paytm',
						'setting_value' => serialize($paytm_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($paytm_data)),
					array('ID'            => $paytm->ID)
				);
			}

			// Bank transfer.
			$bank_transfer = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "bank_transfer"', $school_id));

			$bank_transfer_data = array(
				'enable'  => $bank_transfer_enable,
				'branch'  => $bank_transfer_branch,
				'account' => $bank_transfer_account,
				'name'    => $bank_transfer_name,
				'message' => $bank_transfer_message,
			);

			if (!$bank_transfer) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'bank_transfer',
						'setting_value' => serialize($bank_transfer_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($bank_transfer_data)),
					array('ID'            => $bank_transfer->ID)
				);
			}

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('Payment settings saved.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function save_school_inquiry_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		if (!wp_verify_nonce($_POST['save-school-inquiry-settings'], 'save-school-inquiry-settings')) {
			die();
		}

		global $wpdb;

		try {
			$wpdb->query('BEGIN;');

			$form_title      = isset($_POST['inquiry_form_title']) ? sanitize_text_field($_POST['inquiry_form_title']) : '';
			$phone_required  = isset($_POST['inquiry_phone_required']) ? (bool) ($_POST['inquiry_phone_required']) : 0;
			$email_required  = isset($_POST['inquiry_email_required']) ? (bool) ($_POST['inquiry_email_required']) : 0;
			$success_message = isset($_POST['inquiry_success_message']) ? sanitize_text_field($_POST['inquiry_success_message']) : '';
			$admin_phone     = isset($_POST['inquiry_admin_phone']) ? sanitize_text_field($_POST['inquiry_admin_phone']) : '';
			$admin_email     = isset($_POST['inquiry_admin_email']) ? sanitize_text_field($_POST['inquiry_admin_email']) : '';

			// Settings Inquiry.
			$settings_inquiry = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "inquiry"', $school_id));

			$inquiry_data = array(
				'form_title'      => $form_title,
				'phone_required'  => $phone_required,
				'email_required'  => $email_required,
				'success_message' => $success_message,
				'admin_phone'     => $admin_phone,
				'admin_email'     => $admin_email
			);

			if (!$settings_inquiry) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'inquiry',
						'setting_value' => serialize($inquiry_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($inquiry_data)),
					array('ID'            => $settings_inquiry->ID)
				);
			}

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('Inquiry settings saved.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function save_school_registration_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		if (!wp_verify_nonce($_POST['save-school-registration-settings'], 'save-school-registration-settings')) {
			die();
		}

		global $wpdb;

		try {
			$wpdb->query('BEGIN;');

			$form_title            = isset($_POST['registration_form_title']) ? sanitize_text_field($_POST['registration_form_title']) : '';
			$login_user            = isset($_POST['registration_login_user']) ? (bool) ($_POST['registration_login_user']) : '';
			$redirect_url          = isset($_POST['redirect_url']) ? esc_url_raw($_POST['redirect_url']) : '';
			$create_invoice        = isset($_POST['registration_create_invoice']) ? (bool) ($_POST['registration_create_invoice']) : '';
			$auto_admission_number = isset($_POST['registration_auto_admission_number']) ? (bool) ($_POST['registration_auto_admission_number']) : '';
			$success_message       = isset($_POST['registration_success_message']) ? sanitize_text_field($_POST['registration_success_message']) : '';
			$admin_phone           = isset($_POST['registration_admin_phone']) ? sanitize_text_field($_POST['registration_admin_phone']) : '';
			$admin_email           = isset($_POST['registration_admin_email']) ? sanitize_text_field($_POST['registration_admin_email']) : '';

			// Registraistion form options

			$dob           = isset($_POST['registration_dob']) ? (bool) ($_POST['registration_dob']) : '';
			$religion      = isset($_POST['registration_religion']) ? (bool) ($_POST['registration_religion']) : '';
			$caste         = isset($_POST['registration_caste']) ? (bool) ($_POST['registration_caste']) : '';
			$blood_group   = isset($_POST['registration_blood_group']) ? (bool) ($_POST['registration_blood_group']) : '';
			$phone         = isset($_POST['registration_phone']) ? (bool) ($_POST['registration_phone']) : '';
			$city          = isset($_POST['registration_city']) ? (bool) ($_POST['registration_city']) : '';
			$state         = isset($_POST['registration_state']) ? (bool) ($_POST['registration_state']) : '';
			$country       = isset($_POST['registration_country']) ? (bool) ($_POST['registration_country']) : '';
			$transport     = isset($_POST['registration_transport']) ? (bool) ($_POST['registration_transport']) : '';
			$parent_detail = isset($_POST['registration_parent_detail']) ? (bool) ($_POST['registration_parent_detail']) : '';
			$parent_login  = isset($_POST['registration_parent_login']) ? (bool) ($_POST['registration_parent_login']) : '';
			$id_number     = isset($_POST['registration_id_number']) ? (bool) ($_POST['registration_id_number']) : '';

			// Settings Registration.
			$settings_registration = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "registration"', $school_id));

			$registration_data = array(
				'form_title'            => $form_title,
				'login_user'            => $login_user,
				'redirect_url'          => $redirect_url,
				'create_invoice'        => $create_invoice,
				'auto_admission_number' => $auto_admission_number,
				'success_message'       => $success_message,
				'admin_phone'           => $admin_phone,
				'admin_email'           => $admin_email,
				'dob'           => $dob,
				'religion'      => $religion,
				'caste'         => $caste,
				'blood_group'   => $blood_group,
				'phone'         => $phone,
				'city'          => $city,
				'state'         => $state,
				'country'       => $country,
				'transport'     => $transport,
				'parent_detail' => $parent_detail,
				'id_number'     => $id_number,
				'parent_login'  => $parent_login
			);

			if (!$settings_registration) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'registration',
						'setting_value' => serialize($registration_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($registration_data)),
					array('ID'            => $settings_registration->ID)
				);
			}

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('Registration settings saved.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function save_school_dashboard_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		if (!wp_verify_nonce($_POST['save-school-dashboard-settings'], 'save-school-dashboard-settings')) {
			die();
		}

		global $wpdb;

		try {
			$wpdb->query('BEGIN;');

			// Registraistion form options

			$school_invoice          = isset($_POST['invoice']) ? (bool)($_POST['invoice']) : '';
			$school_payment_history  = isset($_POST['payment_history']) ? (bool)($_POST['payment_history']) : '';
			$school_study_material   = isset($_POST['study_material']) ? (bool)($_POST['study_material']) : '';
			$school_home_work        = isset($_POST['home_work']) ? (bool)($_POST['home_work']) : '';
			$school_noticeboard      = isset($_POST['noticeboard']) ? (bool)($_POST['noticeboard']) : '';
			$school_events           = isset($_POST['events']) ? (bool)($_POST['events']) : '';
			$school_class_time_table = isset($_POST['class_time_table']) ? (bool)($_POST['class_time_table']) : '';
			$school_live_classes     = isset($_POST['live_classes']) ? (bool)($_POST['live_classes']) : '';
			$school_books_issues     = isset($_POST['books_issues']) ? (bool)($_POST['books_issues']) : '';
			$school_exam_time_table  = isset($_POST['exam_time_table']) ? (bool)($_POST['exam_time_table']) : '';
			$school_admit_card       = isset($_POST['admit_card']) ? (bool)($_POST['admit_card']) : '';
			$school_exam_result      = isset($_POST['exam_result']) ? (bool)($_POST['exam_result']) : '';
			$school_certificate      = isset($_POST['certificate']) ? (bool)($_POST['certificate']) : '';
			$school_attendance       = isset($_POST['attendance']) ? (bool)($_POST['attendance']) : '';
			$school_leave_request    = isset($_POST['leave_request']) ? (bool)($_POST['leave_request']) : '';


			$parent_id_card          = isset($_POST['parent_id_card']) ? (bool)($_POST['parent_id_card']) : '';
			$parent_fee_invoice      = isset($_POST['parent_fee_invoice']) ? (bool)($_POST['parent_fee_invoice']) : '';
			$parent_payement_history = isset($_POST['parent_payement_history']) ? (bool)($_POST['parent_payement_history']) : '';
			$parent_noticeboard      = isset($_POST['parent_noticeboard']) ? (bool)($_POST['parent_noticeboard']) : '';
			$parent_class_time_table = isset($_POST['parent_class_time_table']) ? (bool)($_POST['parent_class_time_table']) : '';
			$parent_exam_results     = isset($_POST['parent_exam_results']) ? (bool)($_POST['parent_exam_results']) : '';
			$parent_attendance       = isset($_POST['parent_attendance']) ? (bool)($_POST['parent_attendance']) : '';

			// var_dump($parent_id_card);
			// Settings Registration.
			$settings_dashboard = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "dashboard"', $school_id));

			$dashboard_data = array(

				'school_invoice'          => $school_invoice,
				'school_payment_history'  => $school_payment_history,
				'school_study_material'   => $school_study_material,
				'school_home_work'        => $school_home_work,
				'school_noticeboard'      => $school_noticeboard,
				'school_events'           => $school_events,
				'school_class_time_table' => $school_class_time_table,
				'school_live_classes'     => $school_live_classes,
				'school_books_issues'     => $school_books_issues,
				'school_exam_time_table'  => $school_exam_time_table,
				'school_admit_card'       => $school_admit_card,
				'school_exam_result'      => $school_exam_result,
				'school_certificate'      => $school_certificate,
				'school_attendance'       => $school_attendance,
				'school_leave_request'    => $school_leave_request,

				'parent_id_card'          => $parent_id_card,
				'parent_fee_invoice'      => $parent_fee_invoice,
				'parent_payement_history' => $parent_payement_history,
				'parent_noticeboard'      => $parent_noticeboard,
				'parent_class_time_table' => $parent_class_time_table,
				'parent_exam_results'     => $parent_exam_results,
				'parent_attendance'       => $parent_attendance,

			);

			if (!$settings_dashboard) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'dashboard',
						'setting_value' => serialize($dashboard_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($dashboard_data)),
					array('ID'            => $settings_dashboard->ID)
				);
			}

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('dashboard settings saved.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function save_school_charts_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		if (!wp_verify_nonce($_POST['save-school-charts-settings'], 'save-school-charts-settings')) {
			die();
		}

		global $wpdb;

		try {
			$wpdb->query('BEGIN;');

			$charts      = WLSM_Helper::charts();
			$chart_types = WLSM_Helper::chart_types();

			// Settings Charts.
			$settings_charts = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "charts"', $school_id));

			$chart_types_data  = array();
			$chart_enable_data = array();

			foreach ($charts as $key => $value) {
				$chart_type_name   = 'chart_type_' . $key;
				$chart_enable_name = 'chart_enable_' . $key;

				$chart_type   = isset($_POST[$chart_type_name]) ? sanitize_text_field($_POST[$chart_type_name]) : '';
				$chart_enable = isset($_POST[$chart_enable_name]) ? (bool) ($_POST[$chart_enable_name]) : false;

				if (!in_array($chart_type, $chart_types)) {
					$chart_type = WLSM_Helper::default_chart_types()[$key];
				}

				$chart_types_data[$key]  = $chart_type;
				$chart_enable_data[$key] = $chart_enable;
			}

			$settings_charts_data = array(
				'chart_types'  => $chart_types_data,
				'chart_enable' => $chart_enable_data
			);

			if (!$settings_charts) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'charts',
						'setting_value' => serialize($settings_charts_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($settings_charts_data)),
					array('ID'            => $settings_charts->ID)
				);
			}

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('Charts settings saved.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function save_school_zoom_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		if (!wp_verify_nonce($_POST['save-school-zoom-settings'], 'save-school-zoom-settings')) {
			die();
		}

		global $wpdb;

		try {
			$wpdb->query('BEGIN;');

			$zoom_api_key    = isset($_POST['zoom_api_key']) ? sanitize_text_field($_POST['zoom_api_key']) : '';
			$zoom_api_secret = isset($_POST['zoom_api_secret']) ? sanitize_text_field($_POST['zoom_api_secret']) : '';

			// Settings Zoom.
			$settings_zoom = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "zoom"', $school_id));

			$zoom_data = array(
				'api_key'    => $zoom_api_key,
				'api_secret' => $zoom_api_secret,
			);

			if (!$settings_zoom) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'zoom',
						'setting_value' => serialize($zoom_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($zoom_data)),
					array('ID'            => $settings_zoom->ID)
				);
			}

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('Zoom settings saved.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}

	public static function save_school_logs_settings()
	{
		$current_user = WLSM_M_Role::can('manage_settings');

		if (!$current_user) {
			die();
		}

		$school_id = $current_user['school']['id'];

		if (!wp_verify_nonce($_POST['save-school-logs-settings'], 'save-school-logs-settings')) {
			die();
		}

		global $wpdb;

		try {
			$wpdb->query('BEGIN;');

			$activity_logs     = isset($_POST['activity_logs']) ? (bool) ($_POST['activity_logs']) : false;
			$delete_after_days = isset($_POST['delete_after_days']) ? absint($_POST['delete_after_days']) : 20;

			// Settings Logs.
			$settings_logs = $wpdb->get_row($wpdb->prepare('SELECT ID, setting_value FROM ' . WLSM_SETTINGS . ' WHERE school_id = %d AND setting_key = "logs"', $school_id));

			$logs_data = array(
				'activity_logs'     => $activity_logs,
				'delete_after_days' => $delete_after_days,
			);

			if (!$settings_logs) {
				$wpdb->insert(
					WLSM_SETTINGS,
					array(
						'setting_key'   => 'logs',
						'setting_value' => serialize($logs_data),
						'school_id'     => $school_id,
					)
				);
			} else {
				$wpdb->update(
					WLSM_SETTINGS,
					array('setting_value' => serialize($logs_data)),
					array('ID'            => $settings_logs->ID)
				);
			}

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			$wpdb->query('COMMIT;');

			$message = esc_html__('Logs settings saved.', 'school-management');

			wp_send_json_success(array('message' => $message));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}
	}
}
