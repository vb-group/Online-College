<?php
defined( 'ABSPATH' ) || die();
?>
<div class="wlsm-print-id-card-container">

	<?php require WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/partials/school_header.php'; ?>

	<div class="row wlsm-print-id-card-details mt-1 mobile-id-card">
		<div class="col-8 wlsm-print-id-card-right">
			<ul>
				<li>
					<span class="wlsm-font-bold"><?php esc_html_e( 'Student Name', 'school-management' ); ?>:</span>
					<span><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $student->student_name ) ); ?></span>
				</li>
				<li>
					<span class="wlsm-font-bold"><?php esc_html_e( 'Enrollment Number', 'school-management' ); ?>:</span>
					<span><?php echo esc_html( $student->enrollment_number ); ?></span>
				</li>

				<li>
					<span class="pr-3">
						<span class="wlsm-font-bold"><?php esc_html_e( 'Class', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( WLSM_M_Class::get_label_text( $student->class_label ) ); ?></span>
					</span>
					<span class="pl-3">
						<span class="wlsm-font-bold"><?php esc_html_e( 'Section', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( WLSM_M_Class::get_label_text( $student->section_label ) ); ?></span>
					</span>
				</li>
				<li>
					<span class="pr-3">
						<span class="wlsm-font-bold"><?php esc_html_e( 'Roll Number', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( WLSM_M_Staff_Class::get_roll_no_text( $student->roll_number ) ); ?></span>
					</span>
					<span class="pl-3">
						<span class="wlsm-font-bold"><?php esc_html_e( 'Blood Group', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( $student->blood_group ); ?></span>
					</span>
				</li>
				<li>
					<span class="wlsm-font-bold"><?php esc_html_e( 'Father Name', 'school-management' ); ?>:</span>
					<span><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $student->father_name ) ); ?></span>
				</li>
				<li>
					<span class="wlsm-font-bold"><?php esc_html_e( 'Phone', 'school-management' ); ?>:</span>
					<span><?php echo esc_html( WLSM_M_Staff_Class::get_phone_text( $student->phone ) ); ?></span>
				</li>
				<li>
					<span class="wlsm-font-bold"><?php esc_html_e( 'Address', 'school-management' ); ?>:</span>
					<span><?php echo esc_html( $student->address) ; ?></span>
				</li>
			</ul>
		</div>

		<div class="col-3 wlsm-print-id-card-left">
			<div class="wlsm-print-id-card-photo-box">
			<?php if ( ! empty ( $photo_id ) ) { ?>
				<img src="<?php echo esc_url( wp_get_attachment_url( $photo_id ) ); ?>" class="wlsm-print-id-card-photo">
			<?php } ?>
			</div>
			<div class="wlsm-print-id-card-authorized-by">
				<?php if ( ! empty ( $school_signature ) ) { ?>
					<img src="<?php echo esc_url( wp_get_attachment_url( $school_signature ) ); ?>" class="wlsm-print-id-card-signature">
				<?php } ?>
				<span><?php esc_html_e( 'Authorized By', 'school-management' ); ?></span>
			</div>
		</div>
	</div>

</div>
