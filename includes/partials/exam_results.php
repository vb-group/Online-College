<?php
defined( 'ABSPATH' ) || die();

$show_marks_grades = count( $marks_grades );

$student_rank = WLSM_M_Staff_Examination::calculate_exam_ranks( $school_id, $exam_id, array(), $admit_card->ID );
?>
<thead>
	<tr>
		<th><?php esc_html_e( 'Paper Code', 'school-management' ); ?></th>
		<th><?php esc_html_e( 'Subject Name', 'school-management' ); ?></th>
		<th><?php esc_html_e( 'Subject Type', 'school-management' ); ?></th>
		<th><?php esc_html_e( 'Maximum Marks', 'school-management' ); ?></th>
		<th><?php esc_html_e( 'Obtained Marks', 'school-management' ); ?></th>
		<?php if ( $show_marks_grades ) { ?>
		<th><?php esc_html_e( 'Grade', 'school-management' ); ?></th>
		<?php } ?>
	</tr>
</thead>
<tbody>
	<?php
	$total_maximum_marks  = 0;
	$total_obtained_marks = 0;

	foreach ( $exam_papers as $key => $exam_paper ) {
		if ( $admit_card && isset( $exam_results[ $exam_paper->ID ] ) ) {
			$exam_result    = $exam_results[ $exam_paper->ID ];
			$obtained_marks = $exam_result->obtained_marks;
		} else {
			$obtained_marks = '';
		}

		$percentage = WLSM_Config::sanitize_percentage( $exam_paper->maximum_marks, WLSM_Config::sanitize_marks( $obtained_marks ) );

		$total_maximum_marks  += $exam_paper->maximum_marks;
		$total_obtained_marks += WLSM_Config::sanitize_marks( $obtained_marks );
	?>
	<tr>
		<td><?php echo esc_html( $exam_paper->paper_code ); ?></td>
		<td><?php echo esc_html( stripcslashes( $exam_paper->subject_label ) ); ?></td>
		<td><?php echo esc_html( WLSM_Helper::get_subject_type_text( $exam_paper->subject_type ) ); ?></td>
		<td><?php echo esc_html( $exam_paper->maximum_marks ); ?></td>
		<td><?php echo esc_html( $obtained_marks ); ?></td>
		<?php if ( $show_marks_grades ) { ?>
		<td><?php echo esc_html( WLSM_Helper::calculate_grade( $marks_grades, $percentage ) ); ?></td>
		<?php } ?>
	</tr>
	<?php
	}

	$total_percentage = WLSM_Config::sanitize_percentage( $total_maximum_marks, $total_obtained_marks )
	?>
	<tr>
		<th colspan="3"><?php esc_html_e( 'Total', 'school-management' ); ?></th>
		<th><?php echo esc_html( $total_maximum_marks ); ?></th>
		<th><?php echo esc_html( $total_obtained_marks ); ?></th>
		<?php if ( $show_marks_grades ) { ?>
		<th></th>
		<?php } ?>
	</tr>
	<tr>
		<th colspan="4"><?php esc_html_e( 'Percentage', 'school-management' ); ?></th>
		<th><?php echo esc_html( WLSM_Config::get_percentage_text( $total_maximum_marks, $total_obtained_marks ) ); ?></th>
		<?php if ( $show_marks_grades ) { ?>
		<th>
		<?php
		if ( $enable_overall_grade ) {
			echo esc_html( WLSM_Helper::calculate_grade( $marks_grades, $total_percentage ) );
		}
		?>
		</th>
		<?php } ?>
	</tr>
	<tr>
		<th colspan="4"><?php esc_html_e( 'Rank', 'school-management' ); ?></th>
		<th colspan="<?php echo esc_html( $show_marks_grades ? '2' : '1' ); ?>"><?php echo esc_html( $student_rank ); ?></th>
	</tr>
</tbody>
