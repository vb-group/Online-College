<?php
defined('ABSPATH') || die();

$subjects = WLSM_M_Staff_Class::get_class_subjects($school_id, $class_id);

$exams = WLSM_M_Staff_Examination::get_class_school_exams($school_id, $class_school_id);

$exam_groups = WLSM_M_Staff_Examination::get_class_school_exam_groups($school_id, $class_school_id);

$exam_without_group = WLSM_M_Staff_Examination::exam_without_group($school_id, $class_school_id);

$total_exam_groups = count($exam_groups);
$total_exams       = count($exams);
if (!$exam_without_group && ($total_exam_groups > 1) && ($total_exam_groups < $total_exams)) {
	$show_exam_groups = true;
} else {
	$show_exam_groups = false;
}
?>
<thead>
	<?php
	if ($show_exam_groups) {
		$exams = array();
	?>
		<tr class="wlsm-text-center text-center">
			<th></th>
			<?php
			foreach ($exam_groups as $exam_group) {
				$exam_group_exams = WLSM_M_Staff_Examination::get_exam_group_exams($school_id, $class_school_id, $exam_group);

				$exam_groups_colspan = count($exam_group_exams);

				$exams = array_merge($exams, $exam_group_exams);
			?>
				<th colspan="<?php echo esc_html($exam_groups_colspan); ?>"><?php echo esc_html(stripslashes($exam_group)); ?></th>
			<?php
			}
			?>
			<th></th>
		</tr>
	<?php
	}
	?>
	<tr>
		<th><?php esc_html_e('Subject', 'school-management'); ?></th>
		<?php
		foreach ($exams as $key => $exam) {
		?>
			<th><?php echo esc_html(stripslashes($exam->exam_title)); ?></th>
		<?php
		}
		?>
		<th><?php esc_html_e('Total', 'school-management'); ?></th>
		<th><?php esc_html_e('Remarks', 'school-management'); ?></th>
	</tr>
</thead>
<tbody>
	<?php
	foreach ($subjects as $subject) {
	?>
		<tr>
			<td>
				<?php
				printf(
					wp_kses(
						/* translators: 1: subject label, 2: subject code */
						_x('%1$s (%2$s)', 'Subject', 'school-management'),
						array('span' => array('class' => array()))
					),
					esc_html(WLSM_M_Staff_Class::get_subject_label_text($subject->label)),
					esc_html($subject->code)
				);
				?>
			</td>
			<?php
			$total_maximum_marks_subject  = 0;
			$total_obtained_marks_subject = 0;
			foreach ($exams as $key => $exam) {
				// Get exam paper with this subject code.
				$exam_result = WLSM_M_Staff_Examination::get_exam_result_by_subject_code($school_id, $exam->ID, $student_id, $subject->code);
			?>
				<td>
					<?php
					if ($exam_result) {
						$maximum_marks  = $exam_result->maximum_marks;
						$obtained_marks = $exam_result->obtained_marks;
						$remark         = $exam_result->remark;

						$total_maximum_marks_subject  += $exam_result->maximum_marks;
						$total_obtained_marks_subject += $exam_result->obtained_marks;
					?>
						<span class="wlsm-font-bold">
							<?php echo esc_html($obtained_marks); ?>
						</span>
					<?php
						echo ' / ';
						echo esc_html($maximum_marks);
					} else {
						echo '-';
					}
					?>
				</td>


			<?php
			}
			?>

			<td>
				<?php
				if ($total_maximum_marks_subject) {
				?>
					<span class="wlsm-font-bold">
						<?php echo esc_html($total_obtained_marks_subject); ?>
					</span>
				<?php
					echo ' / ';
					echo esc_html($total_maximum_marks_subject);
				} else {
					echo '-';
				}
				?>
			</td>
			<td> <?php  echo esc_html($remark);?></td>
		</tr>
	<?php
	}
	?>
	<tr>
		<th><?php esc_html_e('Total', 'school-management'); ?></th>
		<?php
		$percentage_data           = array();
		$total_percentage_obtained = 0;
		$total_percentage_maximum  = 0;
		foreach ($exams as $key => $exam) {
			$exam_result = WLSM_M_Staff_Examination::get_exam_results_total_by_student_id($school_id, $exam->ID, $student_id);
			$percentage_row = array();
		?>
			<td>
				<?php
				if ($exam_result->total_marks) {
					$total_marks    = $exam_result->total_marks;
					$obtained_marks = $exam_result->obtained_marks;

					$percentage_row['value'] = WLSM_Config::sanitize_percentage($total_marks, $obtained_marks);
					$percentage_row['text']  = WLSM_Config::get_percentage_text($total_marks, $obtained_marks);

					$total_percentage_obtained += $obtained_marks;
					$total_percentage_maximum  += $total_marks;
				?>
					<span class="wlsm-font-bold">
						<?php echo esc_html($exam_result->obtained_marks); ?>
					</span>
				<?php
					echo ' / ';
					echo esc_html($exam_result->total_marks);
				} else {
					$percentage_row['value'] = 0;
					$percentage_row['text']  = '-';
					echo '-';
				}

				array_push($percentage_data,  $percentage_row);
				?>
			</td>

		<?php
		}
		?>
		<th>
			<?php
			if ($total_percentage_maximum) {
				echo esc_html($total_percentage_obtained);
				echo ' / ';
				echo esc_html($total_percentage_maximum);
			} else {
				echo '-';
			}
			?>
		</th>
	</tr>
	<tr>
		<th><?php esc_html_e('Percentage', 'school-management'); ?></th>
		<?php
		foreach ($percentage_data as $percentage) {
		?>
			<td>
				<span class="wlsm-font-bold"><?php echo esc_html($percentage['text']); ?></span>
			</td>
		<?php
		}
		if ($total_percentage_maximum) {
			$total_percentage_value = WLSM_Config::sanitize_percentage($total_percentage_maximum, $total_percentage_obtained);
			$total_percentage_text  = WLSM_Config::get_percentage_text($total_percentage_maximum, $total_percentage_obtained);
		}
		?>
		<th>
			<?php
			if ($total_percentage_value) {
				echo esc_html($total_percentage_text);
			} else {
				echo '-';
			}
			?>
		</th>
	</tr>
	<tr>
		<th><?php esc_html_e('Rank', 'school-management'); ?></th>
		<?php
		foreach ($exams as $key => $exam) {
			$student_rank = '-';
			$admit_card   = WLSM_M_Staff_Examination::get_admit_card_by_exam_student($school_id, $exam->ID, $student_id);

			if ($admit_card) {
				$student_rank = WLSM_M_Staff_Examination::calculate_exam_ranks($school_id, $exam->ID, array(), $admit_card->ID);
			}
		?>
			<td>
				<span class="wlsm-font-bold"><?php echo esc_html($student_rank); ?></span>
			</td>
		<?php
		}
		?>
		<td></td>
	</tr>
</tbody>