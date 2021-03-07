<?php
defined('ABSPATH') || die();


require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Setting.php';

$student_name = WLSM_M_Staff_Class::get_name_text($student->student_name);

$notices = WLSM_M_Staff_Class::get_school_notices($school_id, 7, $class_school_id);

$section = WLSM_M_Staff_Class::get_school_section($school_id, $student->section_id);

$class_label   = $section->class_label;
$section_label = $section->label;

$attendance = WLSM_M_Staff_General::get_student_attendance_stats($student->ID);
$invoices = WLSM_M_Staff_Accountant::get_student_pending_invoices($student->ID);
$vehicle_id = $student->route_vehicle_id;
if ($vehicle_id) {
	$query = 'SELECT  ro.name, ro.fare, v.vehicle_number, v.driver_name, v.driver_phone FROM ' . WLSM_ROUTE_VEHICLE . ' as rov
				JOIN ' . WLSM_ROUTES . ' as ro ON ro.ID = rov.route_id
				JOIN ' . WLSM_VEHICLES . ' as v ON v.ID = rov.vehicle_id
				JOIN ' . WLSM_STUDENT_RECORDS . ' as sr ON sr.route_vehicle_id = rov.ID
				JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
				JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
				JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
				JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
				WHERE rov.ID = ' . $vehicle_id . '';

	$transportation_details = $wpdb->get_results($wpdb->prepare($query));
}
$invoices = WLSM_M_Staff_Accountant::get_student_pending_invoices_paid($student->ID, 1);
$check_dashboard_display = WLSM_M_Setting::get_dash($invoices);

// School Information 
$schools = $wpdb->get_results('SELECT s.ID, s.label, s.phone, s.email, s.address, s.is_active, s.is_active FROM ' . WLSM_SCHOOLS . ' as s WHERE s.ID = ' . $school_id . '');
?>

<?php if ($invoices and 'paid' !== $check_dashboard_display) {
	require_once WLSM_PLUGIN_DIR_PATH . 'includes/partials/pending_fee_invoices.php';
} else {
	require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/student/partials/navigation.php'; ?>
	<div class="wlsm-content-area wlsm-section-dashboard wlsm-student-dashboard">

		<span class="wlsm-pl-2">
			<?php
			/* translators: %s: student name */
			printf(
				wp_kses(
					'Student Name: <span class="wlsm-font-bold">%s</span>',
					array('span' => array('class' => array()))
				),
				esc_html($student_name)
			);
			?>
		</span>

		<!-- College Name information -->
		<?php foreach ($schools as $school) : ?><br>
			<span class=" wlsm-pl-2">
				<?php esc_html_e('College Name:', 'school-management') ?>
				<strong> <?php esc_html_e($school->label, 'school-management') ?></strong>
			</span>
			<br>
			<span class="wlsm-pl-2">
				<?php esc_html_e('School Email:', 'school-management') ?>
				<strong> <?php esc_html_e($school->email, 'school-management') ?></strong>
			</span>
			<span class=" wlsm-pl-2">
				<?php esc_html_e('School Phone:', 'school-management') ?>
				<strong> <?php esc_html_e($school->phone, 'school-management') ?></strong>
			</span>
			<br>
			<span class="wlsm-pl-2">
				<?php esc_html_e('School Address:', 'school-management') ?>
				<strong> <?php esc_html_e($school->address, 'school-management') ?></strong>
			</span>
		<?php endforeach ?>
		<!-- College Name information -->
		<br>
		<a style="float: right;" class="" href="<?php echo esc_url(add_query_arg(array('action' => 'settings'), $current_page_url)); ?>"><?php esc_html_e('Account Settings', 'school-management'); ?></a>
		<br>

		<div class="wlsm-flex-between">
			<div class="wlsm-flex-item wlsm-l-w-50 wlsm-mt-3">
				<div class="wlsm-st-details-heading wlsm-mt-3">
					<span><?php esc_html_e('Noticeboard', 'school-management'); ?></span>
				</div>
				<div class="wlsm-st-recent-notices-section">
					<?php
					if (count($notices)) {
						$today = new DateTime();
						$today->setTime(0, 0, 0);
					?>
						<ul class="wlsm-st-recent-notices">
							<?php
							foreach ($notices as $key => $notice) {
								$link_to = $notice->link_to;
								$link    = '#';

								if ('url' === $link_to) {
									if (!empty($notice->url)) {
										$link = $notice->url;
									}
								} else if ('attachment' === $link_to) {
									if (!empty($notice->attachment)) {
										$attachment = $notice->attachment;
										$link       = wp_get_attachment_url($attachment);
									}
								} else {
									$link = '#';
								}

								$notice_date = DateTime::createFromFormat('Y-m-d H:i:s', $notice->created_at);
								$notice_date->setTime(0, 0, 0);

								$interval = $today->diff($notice_date);
							?>
								<li>
									<span>
										<a target="_blank" href="<?php echo esc_url($link); ?>"><?php echo esc_html(stripslashes($notice->title)); ?> <span class="wlsm-st-notice-date wlsm-font-bold"><?php echo esc_html(WLSM_Config::get_date_text($notice->created_at)); ?></span></a>
										<?php if ($interval->days < 7) { ?>
											<img class="wlsm-st-notice-new" src="<?php echo esc_url(WLSM_PLUGIN_URL . 'assets/images/newicon.gif'); ?>">
										<?php } ?>
									</span>
								</li>
							<?php
							}
							?>
						</ul>
					<?php
					} else {
					?>
						<div>
							<span class="wlsm-font-medium wlsm-font-bold">
								<?php esc_html_e('There is no notice.', 'school-management'); ?>
							</span>
						</div>
					<?php
					}
					?>
				</div>
			</div>
			<div class="wlsm-flex-item wlsm-l-w-48 wlsm-mt-2">
				<div class="wlsm-st-details wlsm-border-bottom-0">
					<div class="wlsm-st-details-heading">
						<span><?php esc_html_e('Your Attendance', 'school-management'); ?></span>
					</div>
					<span class="wlsm-st-details-list wlsm-st-attendance-section">
						<ul class="wlsm-st-attendance-stats">
							<li><?php echo esc_html($attendance['percentage_text']); ?></li>
						</ul>
					</span>
				</div>

				<div class="wlsm-st-details">
					<div class="wlsm-st-details-heading">
						<span><?php esc_html_e('Your Details', 'school-management'); ?></span>
					</div>
					<ul class="wlsm-st-details-list">
						<li>
							<span class="wlsm-st-details-list-key"><?php esc_html_e('Name'); ?>:</span>
							<span class="wlsm-st-details-list-value"><?php echo esc_html($student_name); ?></span>
						</li>
						<li>
							<span class="wlsm-st-details-list-key"><?php esc_html_e('Enrollment Number', 'school-management'); ?>:</span>
							<span class="wlsm-st-details-list-value"><?php echo esc_html($student->enrollment_number); ?></span>
						</li>
						<li>
							<span class="wlsm-st-details-list-key"><?php esc_html_e('Session', 'school-management'); ?>:</span>
							<span class="wlsm-st-details-list-value"><?php echo esc_html(WLSM_M_Session::get_label_text($student->session_label)); ?></span>
						</li>
						<li>
							<span class="wlsm-st-details-list-key"><?php esc_html_e('Class', 'school-management'); ?>:</span>
							<span class="wlsm-st-details-list-value"><?php echo esc_html(WLSM_M_Class::get_label_text($student->class_label)); ?></span>
						</li>
						<li>
							<span class="wlsm-st-details-list-key"><?php esc_html_e('Section', 'school-management'); ?>:</span>
							<span class="wlsm-st-details-list-value"><?php echo esc_html(WLSM_M_Class::get_label_text($student->section_label)); ?></span>
						</li>
						<li>
							<span class="wlsm-st-details-list-key"><?php esc_html_e('Roll Number', 'school-management'); ?>:</span>
							<span class="wlsm-st-details-list-value"><?php echo esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)); ?></span>
						</li>
						<li>
							<span class="wlsm-st-details-list-key"><?php esc_html_e('Father Name', 'school-management'); ?>:</span>
							<span class="wlsm-st-details-list-value"><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($student->father_name)); ?></span>
						</li>
						<li>
							<span class="wlsm-st-details-list-key"><?php esc_html_e('ID Card', 'school-management'); ?>:</span>
							<span class="wlsm-st-details-list-value">
								<a class="wlsm-st-print-id-card" data-id-card="<?php echo esc_attr($user_id); ?>" data-nonce="<?php echo esc_attr(wp_create_nonce('st-print-id-card-' . $user_id)); ?>" href="#" data-message-title="<?php echo esc_attr__('Print ID Card', 'school-management'); ?>">
									<?php esc_html_e('Print', 'school-management'); ?>
								</a>
							</span>
						</li>
					</ul>
					<br>
					<?php if ($vehicle_id) : ?>
						<div class="wlsm-st-details-heading">
							<span><?php esc_html_e('Transportation Details', 'school-management'); ?></span>
						</div>
						<ul class="wlsm-st-details-list">
							<li>
								<span class="wlsm-st-details-list-key"><?php esc_html_e('Route Name', 'school-management'); ?>:</span>
								<span class="wlsm-st-details-list-value"><?php foreach ($transportation_details as $transport) {
																				if ($transport->name) {
																					echo esc_html($transport->name);
																				} else {
																					echo '-';
																				}
																			} ?></span>
							</li>
							<li>
								<span class="wlsm-st-details-list-key"><?php esc_html_e('Vehicle Number', 'school-management'); ?>:</span>
								<span class="wlsm-st-details-list-value">
									<?php foreach ($transportation_details as $transport) {
										if ($transport->vehicle_number) {
											echo esc_html($transport->vehicle_number);
										} else {
											echo '-';
										}
									} ?></span>
							</li>
							<li>
								<span class="wlsm-st-details-list-key"><?php esc_html_e('Fare', 'school-management'); ?>:</span>
								<span class="wlsm-st-details-list-value"><?php foreach ($transportation_details as $transport) {
																				if ($transport->fare) {
																					echo esc_html($transport->fare);
																				} else {
																					echo '-';
																				}
																			} ?></span>
							</li>
							<li>
								<span class="wlsm-st-details-list-key"><?php esc_html_e("Driver's Name", 'school-management'); ?>:</span>
								<span class="wlsm-st-details-list-value"><?php foreach ($transportation_details as $transport) {
																				if ($transport->driver_name) {
																					echo esc_html($transport->driver_name);
																				} else {
																					echo '-';
																				}
																			} ?></span>
							</li>
							<li>
								<span class="wlsm-st-details-list-key"><?php esc_html_e("Driver's Mobile", 'school-management'); ?>:</span>
								<span class="wlsm-st-details-list-value"><?php foreach ($transportation_details as $transport) {
																				if ($transport->driver_phone) {
																					echo esc_html($transport->driver_phone);
																				} else {
																					echo '-';
																				}
																			} ?></span>
							</li>
						</ul>
					<?php endif ?>
				</div>
			</div>
		</div>
	</div>
<?php } ?>