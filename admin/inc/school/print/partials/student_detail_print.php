<?php
defined('ABSPATH') || die();
?>
<div class="wlsm-student-detail-container">

	<?php require WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/partials/school_header.php'; ?>

	<div class="row">
		<div class="col mx-auto">
			<div class="wlsm-student-detail-details "><?php esc_html_e('Basic Details', 'school-management'); ?></div>
		</div>
	</div>
	<div class="row mt-3">
		<div class="col-9 mx-auto">
			<ul>
				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('Student Name', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)); ?></span>
				</li>
				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('Enrollment Number', 'school-management'); ?>:</span>
					<span><?php echo esc_html($student->enrollment_number); ?></span>
				</li>

				<li class="student-detail_list">
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Class', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->class_label)); ?></span>
					</span>
					<span class="pl-3">
						<span class="wlsm-font-bold"><?php esc_html_e('Section', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->section_label)); ?></span>
					</span>
					<span class="pl-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Date Of Birth', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->dob)); ?></span>
					</span>
				</li>
				<li class="student-detail_list">
					<span class="pr-3">
						<span class="wlsm-font-bold"><?php esc_html_e('Roll Number', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)); ?></span>
					</span>
					<span class="pl-3">
						<span class="wlsm-font-bold"><?php esc_html_e('Blood Group', 'school-management'); ?>:</span>
						<span><?php echo esc_html($student->blood_group); ?></span>
					</span>
				</li>
				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('Father Name', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($student->father_name)); ?></span>
				</li>
				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('Phone', 'school-management'); ?>:</span>
					<span><?php echo esc_html(WLSM_M_Staff_Class::get_phone_text($student->phone)); ?></span>
				</li>
			</ul>
		</div>

		<div class="col-3 wlsm-student-detail-left">
			<div class="wlsm-student-detail-photo-box">
				<?php if (!empty($photo_id)) { ?>
					<img src="<?php echo esc_url(wp_get_attachment_url($photo_id)); ?>" class="wlsm-student-detail-photo">
				<?php } ?>
			</div>
		</div>

	</div>
	<!-- Student Admission Detail -->
	<div class="row">
		<div class="col mx-auto mt-3">
			<div class="wlsm-student-detail-details "><?php esc_html_e('Admission Detail', 'school-management'); ?></div>
		</div>
	</div>
	<div class="row mt-3">
		<div class="col mx-auto">
			<ul>
				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('Admission Number', 'school-management'); ?>:</span>
					<span><?php echo esc_html($student->admission_number); ?></span>
				</li>
				<li class="student-detail_list">
					<span class="pr-3">
						<span class="wlsm-font-bold"><?php esc_html_e('Admission Date', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->admission_date)); ?></span>
					</span>
				</li>
				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('Address', 'school-management'); ?>:</span>
					<span><?php echo esc_html($student->address); ?></span>
				</li>
				<li class="student-detail_list">
					<span class="wlsm-font-bold"><?php esc_html_e('Email', 'school-management'); ?>:</span>
					<span><?php echo esc_html($student->email); ?></span>
				</li>
				<li class="student-detail_list">
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('City', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->city)); ?></span>
					</span>
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('State', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->state)); ?></span>
					</span>
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Country', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->country)); ?></span>
					</span>
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Religion', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->religion)); ?></span>
					</span>
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Caste', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->caste)); ?></span>
					</span>
				</li>
			</ul>
		</div>
	</div>

	<!-- Parents Details -->
	<div class="row">
		<div class="col mx-auto mt-3">
			<div class="wlsm-student-detail-details "><?php esc_html_e('Parents Details', 'school-management'); ?></div>
		</div>
	</div>
	<div class="row mt-3">
		<div class="col mx-auto">
			<ul>
				<li class="student-detail_list">
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Father Name', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->father_name)); ?></span>
					</span>
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Father Phone', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->father_phone)); ?></span>
					</span>
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Father Occupation', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->father_occupation)); ?></span>
					</span>
				</li>
				<li class="student-detail_list">
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Mother Name', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->mother_name)); ?></span>
					</span>
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Mother Phone', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->mother_phone)); ?></span>
					</span>
					<span class="pr-5">
						<span class="wlsm-font-bold"><?php esc_html_e('Mother Occupation ', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->mother_occupation)); ?></span>
					</span>
				</li>

			</ul>
		</div>
	</div>
</div>