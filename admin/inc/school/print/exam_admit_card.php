<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Setting.php';

if ( isset( $from_front ) ) {
	$print_button_classes = 'button btn-sm btn-success';
} else {
	$print_button_classes = 'btn btn-sm btn-success';
}

$exam_title = $exam->exam_title;
$start_date = $exam->start_date;
$end_date   = $exam->end_date;

$class_names = array();
foreach ( $exam_classes as $exam_class ) {
	array_push( $class_names, WLSM_M_Class::get_label_text( $exam_class->label ) );
}

$class_names = implode( ', ', $class_names );

$photo_id = $admit_card->photo_id;
?>

<!-- Print exam admit card. -->
<div class="wlsm-container wlsm d-flex mt-2 mb-2">
	<div class="col-md-12 wlsm-text-center">
		<?php
		printf(
			wp_kses(
				/* translators: 1: exam title, 2: start date, 3: end date, 4: exam classes */
				__( '<span class="wlsm-font-bold">Admit Card:</span> <span class="text-dark">%1$s (%2$s - %3$s)<br><span class="wlsm-font-bold">Class:</span> %4$s</span>', 'school-management' ),
				array( 'span' => array( 'class' => array() ), 'br' => array() )
			),
			esc_html( WLSM_M_Staff_Examination::get_exam_label_text( $exam_title ) ),
			esc_html( WLSM_Config::get_date_text( $start_date ) ),
			esc_html( WLSM_Config::get_date_text( $end_date ) ),
			esc_html( $class_names )
		);
		?>
		<br>
		<button type="button" class="<?php echo esc_attr( $print_button_classes ); ?> mt-2" id="wlsm-print-exam-admit-card-btn" data-styles='["<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/bootstrap.min.css' ); ?>","<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/wlsm-school-header.css' ); ?>","<?php echo esc_url( WLSM_PLUGIN_URL . 'assets/css/print/wlsm-exam-admit-card.css' ); ?>"]' data-title="<?php
			printf(
				/* translators: 1: exam title, 2: start date, 3: end date, 4: exam classes */
				esc_attr__( 'Admit Card: %1$s (%2$s - %3$s), Class: %4$s', 'school-management' ),
				esc_html( WLSM_M_Staff_Examination::get_exam_label_text( $exam_title ) ),
				esc_html( WLSM_Config::get_date_text( $start_date ) ),
				esc_html( WLSM_Config::get_date_text( $end_date ) ),
				esc_html( $class_names )
			);
			?>"><?php esc_html_e( 'Print Admit Card', 'school-management' ); ?>
		</button>
	</div>
</div>

<!-- Print exam admit card section. -->
<div class="wlsm-container wlsm wlsm-form-section" id="wlsm-print-exam-admit-card">
	<div class="wlsm-print-exam-admit-card-container">

		<?php require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/print/partials/school_header.php'; ?>

		<div class="wlsm-heading wlsm-admit-card-heading h5 wlsm-text-center">
			<span><?php esc_html_e( 'STUDENT ADMIT CARD', 'school-management' ); ?></span>
		</div>

		<div class="row wlsm-student-details">
			<div class="col-9 wlsm-student-details-right">
				<ul class="wlsm-list-group">
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e( 'Student Name', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $admit_card->name ) ); ?></span>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e( 'Enrollment Number', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( $admit_card->enrollment_number ); ?></span>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e( 'Session', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( WLSM_M_Session::get_label_text( $admit_card->session_label ) ); ?></span>
					</li>
					<li>
						<span class="wlsm-pr-3 pr-3">
							<span class="wlsm-font-bold"><?php esc_html_e( 'Class', 'school-management' ); ?>:</span>
							<span><?php echo esc_html( WLSM_M_Class::get_label_text( $admit_card->class_label ) ); ?></span>
						</span>
						<span class="wlsm-pl-3 pl-3">
							<span class="wlsm-font-bold"><?php esc_html_e( 'Section', 'school-management' ); ?>:</span>
							<span><?php echo esc_html( WLSM_M_Class::get_label_text( $admit_card->section_label ) ); ?></span>
						</span>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e( 'Exam Roll Number', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( WLSM_M_Staff_Class::get_roll_no_text( $admit_card->roll_number ) ); ?></span>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e( 'Phone', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( WLSM_M_Staff_Class::get_phone_text( $admit_card->phone ) ); ?></span>
					</li>
					<li>
						<span class="wlsm-font-bold"><?php esc_html_e( 'Email', 'school-management' ); ?>:</span>
						<span><?php echo esc_html( WLSM_M_Staff_Class::get_name_text( $admit_card->email ) ); ?></span>
					</li>
				</ul>
			</div>

			<div class="col-3 wlsm-student-details-left">
				<div class="wlsm-student-photo-box">
				<?php if ( ! empty ( $photo_id ) ) { ?>
					<img src="<?php echo esc_url( wp_get_attachment_url( $photo_id ) ); ?>" class="wlsm-student-photo">
				<?php } ?>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-12">
				<span>
				<?php
				printf(
					wp_kses(
						/* translators: 1: exam title, 2: start date, 3: end date */
						__( '<span class="wlsm-font-bold">Exam:</span> <span class="text-dark">%1$s (%2$s - %3$s)</span>', 'school-management' ),
						array( 'span' => array( 'class' => array() ) )
					),
					esc_html( WLSM_M_Staff_Examination::get_exam_label_text( $exam_title ) ),
					esc_html( WLSM_Config::get_date_text( $start_date ) ),
					esc_html( WLSM_Config::get_date_text( $end_date ) )
				);
				?>
				</span>
				<span class="float-md-right">
				<?php
				printf(
					wp_kses(
						/* translators: %s: exam classes */
						__( '<span class="wlsm-font-bold">Class:</span> %s</span>', 'school-management' ),
						array( 'span' => array( 'class' => array() ) )
					),
					esc_html( $class_names )
				);
				?>
				</span>
			</div>
		</div>
		<div class="table-responsive w-100">
			<table class="table table-bordered wlsm-view-exam-time-table">
				<?php require_once WLSM_PLUGIN_DIR_PATH . 'includes/partials/exam_time_table.php'; ?>
			</table>
		</div>

	</div>
</div>
