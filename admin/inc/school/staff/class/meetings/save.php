<?php
defined( 'ABSPATH' ) || die();

$page_url = WLSM_M_Staff_Class::get_meetings_page_url();

$school_id = $current_school['id'];

require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/class/meetings/check_compatibility.php';

$meeting = NULL;

$nonce_action = 'add-meeting';

$class_id   = NULL;
$subject_id = NULL;
$start_at   = NULL;
$class_type = 8;
$duration   = 40;
$password   = '';
$topic      = '';
$agenda     = '';

$recurrence_type   = '';
$repeat_interval   = '';
$weekly_days       = array();
$monthly_day       = '';
$end_times         = '';
$end_at            = '';
$registration_type = 1;

$approval_type     = 0;
$join_before_host  = 1;
$host_video        = '';
$participant_video = '';
$mute_upon_entry   = '';

$subjects = array();
$teachers = array();

if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
	$id      = absint( $_GET['id'] );
	$meeting = WLSM_M_Staff_Class::fetch_meeting( $school_id, $id );

	if ( $meeting ) {
		$nonce_action = 'edit-meeting-' . $meeting->ID;

		$class_id   = $meeting->class_id;
		$subject_id = $meeting->subject_id;
		$teacher_id = $meeting->admin_id;
		$meeting_id = $meeting->meeting_id;
		$start_at   = $meeting->start_at;
		$class_type = $meeting->type;
		$duration   = $meeting->duration;
		$password   = $meeting->password;
		$topic      = $meeting->topic;
		$agenda     = $meeting->agenda;

		$recurrence_type   = $meeting->recurrence_type;
		$repeat_interval   = $meeting->repeat_interval;
		$weekly_days       = $meeting->weekly_days;
		$monthly_day       = $meeting->monthly_day;
		$end_times         = $meeting->end_times;
		$end_at            = $meeting->end_at;
		$registration_type = $meeting->registration_type;

		$weekly_days = explode( ',', $weekly_days );
		if ( ! is_array( $weekly_days ) ) {
			$weekly_days = array();
		}

		$approval_type     = $meeting->approval_type;
		$join_before_host  = $meeting->join_before_host;
		$host_video        = $meeting->host_video;
		$participant_video = $meeting->participant_video;
		$mute_upon_entry   = $meeting->mute_upon_entry;

		$zoom_meeting_id = $meeting->meeting_id;
		$class_label     = $meeting->class_label;

		$class_school = WLSM_M_Staff_Class::get_class( $school_id, $class_id );

		if ( $class_school ) {
			$class_school_id = $class_school->ID;
		}

		$subjects = WLSM_M_Staff_Class::get_class_subjects( $school_id, $class_id );

		$subject = WLSM_M_Staff_Class::get_class_subject( $school_id, $class_id, $subject_id );

		if ( $subject ) {
			$teachers = WLSM_M_Staff_Class::fetch_subject_admins( $school_id, $subject_id );
		}
	}
}

$classes             = WLSM_M_Staff_Class::fetch_classes( $school_id );
$types               = WLSM_Helper::meeting_types();
$recurrence_types    = WLSM_Helper::meeting_recurrence_types();
$meeting_weekly_days = WLSM_Helper::meeting_weekly_days();
$approval_types      = WLSM_Helper::meeting_approval_types();
$registration_types  = WLSM_Helper::meeting_registration_types();
?>
<div class="row">
	<div class="col-md-12">
		<div class="mt-3 text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading-box">
				<span class="wlsm-section-heading">
					<?php
					if ( $meeting ) {
						printf(
							wp_kses(
								/* translators: 1: zoom meeting id, 2: class */
								__( 'Edit Live Class: %1$s - %2$s (Zoom Meeting ID)', 'school-management' ),
								array(
									'span' => array( 'class' => array() )
								)
							),
							esc_html( WLSM_M_Class::get_label_text( $class_label ) ),
							esc_html( $zoom_meeting_id )
						);
					} else {
						esc_html_e( 'Add New Live Class', 'school-management' );
					}
					?>
				</span>
			</span>
			<span class="float-md-right">
				<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-video"></i>&nbsp;
					<?php esc_html_e( 'View All', 'school-management' ); ?>
				</a>
			</span>
		</div>
		<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" id="wlsm-save-meeting-form">

			<?php $nonce = wp_create_nonce( $nonce_action ); ?>
			<input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">

			<input type="hidden" name="action" value="wlsm-save-meeting">

			<?php if ( $meeting ) { ?>
			<input type="hidden" name="meeting_id" value="<?php echo esc_attr( $meeting->ID ); ?>">
			<?php } ?>

			<div class="wlsm-form-section">
				<div class="form-row">
					<div class="form-group col-md-4">
						<label for="wlsm_class" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e( 'Class', 'school-management' ); ?>:
						</label>
						<select name="class_id" class="form-control selectpicker wlsm_class_subjects" data-nonce-subjects="<?php echo esc_attr( wp_create_nonce( 'get-class-subjects' ) ); ?>" id="wlsm_class" data-live-search="true">
							<option value=""><?php esc_html_e( 'Select Class', 'school-management' ); ?></option>
							<?php foreach ( $classes as $class ) { ?>
							<option <?php selected( $class->ID, $class_id, true ); ?> value="<?php echo esc_attr( $class->ID ); ?>" <?php selected( $class->ID, $class_id, true ); ?>>
								<?php echo esc_html( WLSM_M_Class::get_label_text( $class->label ) ); ?>
							</option>
							<?php } ?>
						</select>
					</div>

					<div class="form-group col-md-4">
						<label for="wlsm_section" class="wlsm-font-bold">
							<?php esc_html_e( 'Subject', 'school-management' ); ?>:
						</label>
						<select name="subject_id" class="form-control selectpicker wlsm_subject_teachers" id="wlsm_subject" data-nonce-teachers="<?php echo esc_attr( wp_create_nonce( 'get-subject-teachers' ) ); ?>" data-live-search="true" title="<?php esc_attr_e( 'Select Subject', 'school-management' ); ?>">
							<?php foreach ( $subjects as $subject ) { ?>
							<option value="<?php echo esc_attr( $subject->ID ); ?>" <?php selected( $subject->ID, $subject_id, true ); ?>>
								<?php
								printf(
									wp_kses(
										/* translators: 1: subject label, 2: subject code */
										_x( '%1$s (%2$s)', 'Subject', 'school-management' ),
										array( 'span' => array( 'class' => array() ) )
									),
									esc_html( WLSM_M_Staff_Class::get_subject_label_text( $subject->label ) ),
									esc_html( $subject->code )
								);
								?>
							</option>
							<?php } ?>
						</select>
					</div>

					<div class="form-group col-md-4">
						<label for="wlsm_teacher" class="wlsm-font-bold">
							<?php esc_html_e( 'Teacher', 'school-management' ); ?>:
						</label>
						<select name="admin_id" class="form-control selectpicker" id="wlsm_teacher" data-live-search="true" title="<?php esc_attr_e( 'Select Teacher', 'school-management' ); ?>">
							<option value=""></option>
							<?php foreach ( $teachers as $teacher ) { ?>
							<option value="<?php echo esc_attr( $teacher->ID ); ?>" <?php selected( $teacher->ID, $teacher_id, true ); ?>>
								<?php
								printf(
									wp_kses(
										/* translators: 1: Teacher name, 2: Teacher phone number */
										_x( '%1$s (%2$s)', 'Teacher', 'school-management' ),
										array( 'span' => array( 'class' => array() ) )
									),
									esc_html( WLSM_M_Staff_Class::get_name_text( $teacher->name ) ),
									esc_html( WLSM_M_Staff_Class::get_phone_text( $teacher->phone ) )
								);
								?>
							</option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>

			<div class="wlsm-form-section">
				<div class="form-row">
					<div class="form-group col-sm-12 col-md-4">
						<label for="wlsm_type" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> <?php esc_html_e( 'Class Type', 'school-management' ); ?>:
						</label>
						<select name="type" class="form-control selectpicker" id="wlsm_type" data-live-search="true" title="<?php esc_attr_e( 'Select Class Type', 'school-management' ); ?>">
							<?php foreach ( $types as $key => $type ) { ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $class_type, true ); ?>>
								<?php echo esc_html( $type ); ?>
							</option>
							<?php } ?>
						</select>
					</div>

					<div class="form-group col-sm-6 col-md-4">
						<label for="wlsm_start_at" class="wlsm-font-bold">
							<?php esc_html_e( 'Start Date / Time', 'school-management' ); ?>:
						</label>
						<input type="text" name="start_at" class="form-control wlsm_at" id="wlsm_start_at" placeholder="<?php esc_attr_e( 'Enter start date and time', 'school-management' ); ?>" value="<?php echo esc_attr( WLSM_Config::get_at_text( $start_at ) ); ?>">
					</div>

					<div class="form-group col-sm-6 col-md-4">
						<label for="wlsm_duration" class="wlsm-font-bold">
							<?php esc_html_e( 'Duration (minutes)', 'school-management' ); ?>:
						</label>
						<input type="number" step="1" name="duration" class="form-control" id="wlsm_duration" placeholder="<?php esc_attr_e( 'Enter duration', 'school-management' ); ?>" value="<?php echo esc_attr( $duration ); ?>">
						<p class="description"><?php esc_html_e( 'Live class duration (minutes). Used for scheduled classes only.', 'school-management' ); ?></p>
					</div>
				</div>
			</div>

			<div class="wlsm-form-section">
				<div class="form-row">
					<div class="form-group col-md-5">
						<label for="wlsm_password" class="wlsm-font-bold">
							<?php esc_html_e( 'Password', 'school-management' ); ?>:
						</label>
						<input type="text" name="password" class="form-control" id="wlsm_password" placeholder="<?php esc_attr_e( 'Enter password', 'school-management' ); ?>" value="<?php echo esc_attr( $password ); ?>">
						<p class="description"><?php esc_html_e( 'Password to join the live class. By default, password may only contain the following characters: [a-z A-Z 0-9 @ - _ *] and can have a maximum of 10 characters.', 'school-management' ); ?></p>

						<label for="wlsm_topic" class="wlsm-font-bold">
							<?php esc_html_e( 'Topic', 'school-management' ); ?>:
						</label>
						<input type="text" name="topic" class="form-control" id="wlsm_topic" placeholder="<?php esc_attr_e( 'Enter topic', 'school-management' ); ?>" value="<?php echo esc_attr( $topic ); ?>">
					</div>

					<div class="form-group col-md-7">
						<label for="wlsm_agenda" class="wlsm-font-bold">
							<?php esc_html_e( 'Agenda', 'school-management' ); ?>:
						</label>
						<textarea name="agenda" id="wlsm_agenda" class="form-control" rows="5"  placeholder="<?php esc_attr_e( 'Enter agenda', 'school-management' ); ?>"><?php echo esc_html( $agenda ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Live class description.', 'school-management' ); ?></p>
					</div>
				</div>
			</div>

			<div class="wlsm-form-section">
				<div class="form-row">
					<div class="form-group col-sm-12 col-md-4">
						<label for="wlsm_approval_type" class="wlsm-font-bold">
							<?php esc_html_e( 'Approval Type', 'school-management' ); ?>:
						</label>
						<select name="approval_type" class="form-control selectpicker" id="wlsm_approval_type" data-live-search="true" title="<?php esc_attr_e( 'Select Approval Type', 'school-management' ); ?>">
							<?php foreach ( $approval_types as $key => $type ) { ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $approval_type, true ); ?>>
								<?php echo esc_html( $type ); ?>
							</option>
							<?php } ?>
						</select>
					</div>

					<div class="form-group col-sm-12 col-md-8">
						<label for="wlsm_registration_type" class="wlsm-font-bold">
							<?php esc_html_e( 'Registration Type', 'school-management' ); ?>:
						</label>
						<select name="registration_type" class="form-control selectpicker" id="wlsm_registration_type" data-live-search="true" title="<?php esc_attr_e( 'Select Registration Type', 'school-management' ); ?>">
							<?php foreach ( $registration_types as $key => $type ) { ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $registration_type, true ); ?>>
								<?php echo esc_html( $type ); ?>
							</option>
							<?php } ?>
						</select>
						<p class="description"><?php esc_html_e( 'Registration type. Used for recurring class with fixed time only.', 'school-management' ); ?></p>
					</div>
				</div>
			</div>

			<div class="wlsm-form-section">
				<div class="form-row">
					<div class="col-sm-12 col-md-4">
						<div class="form-group">
							<label for="wlsm_recurrence_type" class="wlsm-font-bold">
								<?php esc_html_e( 'Recurrence Type', 'school-management' ); ?>:
							</label>
							<select name="recurrence_type" class="form-control selectpicker" id="wlsm_recurrence_type" data-live-search="true" title="<?php esc_attr_e( 'Select Recurrence Type', 'school-management' ); ?>">
								<?php foreach ( $recurrence_types as $key => $type ) { ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $recurrence_type, true ); ?>>
									<?php echo esc_html( $type ); ?>
								</option>
								<?php } ?>
							</select>
							<p class="description"><?php esc_html_e( 'Recurrence class types.', 'school-management' ); ?></p>
						</div>

						<div class="form-group">
							<label for="wlsm_repeat_interval" class="wlsm-font-bold">
								<?php esc_html_e( 'Repeat Interval', 'school-management' ); ?>:
							</label>
							<input type="number" step="1" name="repeat_interval" class="form-control" id="wlsm_repeat_interval" placeholder="<?php esc_attr_e( 'Enter repeat interval', 'school-management' ); ?>" value="<?php echo esc_attr( $repeat_interval ); ?>">
							<p class="description"><?php esc_html_e( 'Define the interval at which the class should recur. For instance, if you would like to schedule a class that recurs every two months, you must set the value of this field as 2 and the value of the recurrence type parameter as "Monthly".', 'school-management' ); ?></p>
							<p class="description"><?php esc_html_e( 'For a daily class, the maximum interval you can set is 90 days. For a weekly class the maximum interval that you can set is of 12 weeks. For a monthly class, there is a maximum of 3 months.', 'school-management' ); ?></p>
						</div>
					</div>

					<div class="col-sm-12 col-md-4">
						<div class="form-group">
							<label for="wlsm_weekly_days" class="wlsm-font-bold">
								<?php esc_html_e( 'Weekly Days', 'school-management' ); ?>:
							</label>
							<select name="weekly_days[]" multiple class="form-control selectpicker" id="wlsm_weekly_days" data-live-search="true" title="<?php esc_attr_e( 'Select Weekly Days', 'school-management' ); ?>">
								<?php foreach ( $meeting_weekly_days as $key => $days ) { ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, in_array( $key, $weekly_days ), true ); ?>>
									<?php echo esc_html( $days ); ?>
								</option>
								<?php } ?>
							</select>
							<p class="description"><?php esc_html_e( 'Use this field only if you’re scheduling a recurring class of type "Weekly" to state which day(s) of the week the class should repeat.', 'school-management' ); ?></p>
						</div>

						<div class="form-group">
							<label for="wlsm_monthly_day" class="wlsm-font-bold">
								<?php esc_html_e( 'Monthly Day', 'school-management' ); ?>:
							</label>
							<input type="number" step="1" max="31" name="monthly_day" class="form-control" id="wlsm_monthly_day" placeholder="<?php esc_attr_e( 'Enter monthly day', 'school-management' ); ?>" value="<?php echo esc_attr( $monthly_day ); ?>">
							<p class="description"><?php esc_html_e( 'Use this field only if you’re scheduling a recurring class of type "Monthly" to state which day in a month, the class should recur. The value range is from 1 to 31.', 'school-management' ); ?></p>
						</div>
					</div>

					<div class="col-sm-12 col-md-4">
						<div class="form-group">
							<label for="wlsm_end_times" class="wlsm-font-bold">
								<?php esc_html_e( 'End Times', 'school-management' ); ?>:
							</label>
							<input type="number" step="1" name="end_times" class="form-control" id="wlsm_end_times" placeholder="<?php esc_attr_e( 'Enter end times', 'school-management' ); ?>" value="<?php echo esc_attr( $end_times ); ?>">
							<p class="description"><?php esc_html_e( 'Select how many times the class should recur before it is canceled. (Cannot be used with "End Date / Time".', 'school-management' ); ?></p>
						</div>

						<div class="form-group">
							<label for="wlsm_end_at" class="wlsm-font-bold">
								<?php esc_html_e( 'End Date / Time', 'school-management' ); ?>:
							</label>
							<input type="text" name="end_at" class="form-control wlsm_at" id="wlsm_end_at" placeholder="<?php esc_attr_e( 'Enter end date and time', 'school-management' ); ?>" value="<?php echo esc_attr( $end_at ); ?>">
							<p class="description"><?php esc_html_e( 'Select the final date on which the class will recur before it is canceled. (Cannot be used with "End Times".)', 'school-management' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<div class="wlsm-form-section">
				<div class="form-row">
					<div class="col-sm-12 col-md-6">
						<div class="form-group">
							<input <?php checked( $join_before_host, 1, true ); ?> class="form-check-input mt-1" type="checkbox" name="join_before_host" id="wlsm_join_before_host" value="1">
							<label class="ml-4 mb-1 form-check-label" for="wlsm_join_before_host">
								<?php esc_html_e( 'Allow participants to join the class before the host starts the class. Only used for scheduled or recurring classes.', 'school-management' ); ?>
							</label>
						</div>

						<div class="form-group">
							<input <?php checked( $host_video, 1, true ); ?> class="form-check-input mt-1" type="checkbox" name="host_video" id="wlsm_host_video" value="1">
							<label class="ml-4 mb-1 form-check-label" for="wlsm_host_video">
								<?php esc_html_e( 'Start video when the host joins the class.', 'school-management' ); ?>
							</label>
						</div>

						<div class="form-group">
							<input <?php checked( $participant_video, 1, true ); ?> class="form-check-input mt-1" type="checkbox" name="participant_video" id="wlsm_participant_video" value="1">
							<label class="ml-4 mb-1 form-check-label" for="wlsm_participant_video">
								<?php esc_html_e( 'Start video when participants join the class.', 'school-management' ); ?>
							</label>
						</div>

						<div class="form-group">
							<input <?php checked( $mute_upon_entry, 1, true ); ?> class="form-check-input mt-1" type="checkbox" name="mute_upon_entry" id="wlsm_mute_upon_entry" value="1">
							<label class="ml-4 mb-1 form-check-label" for="wlsm_mute_upon_entry">
								<?php esc_html_e( 'Mute participants upon entry.', 'school-management' ); ?>
							</label>
						</div>
					</div>

					<?php
					if ( $meeting ) {
					?>
					<div class="col-sm-12 col-md-6">
						<a target="_blank" class="btn btn-outline-primary" href="<?php echo esc_url( $meeting->start_url ); ?>"><?php esc_html_e( 'Start Class', 'school-management' ); ?> <span class="fas fa-forward"></span></a>
						<hr>
						<a target="_blank" class="btn btn-outline-success" href="<?php echo esc_url( $meeting->join_url ); ?>"><?php esc_html_e( 'Join URL', 'school-management' ); ?> <span class="fas fa-forward"></span></a>
					</div>
					<?php
					}
					?>
				</div>
			</div>

			<div class="row mt-2">
				<div class="col-md-12 text-center">
					<button type="submit" class="btn btn-primary" id="wlsm-save-meeting-btn">
						<?php
						if ( $meeting ) {
							?>
							<i class="fas fa-save"></i>&nbsp;
							<?php
							esc_html_e( 'Update Live Class', 'school-management' );
						} else {
							?>
							<i class="fas fa-plus-square"></i>&nbsp;
							<?php
							esc_html_e( 'Add New Live Class', 'school-management' );
						}
						?>
					</button>
				</div>
			</div>

		</form>
	</div>
</div>
