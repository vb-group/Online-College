<?php
defined( 'ABSPATH' ) || die();
?>
<div class="wlsm-st-attendance-section table-responsive w-100 wlsm-w-100">
	<table class="wlsm-st-attendance-table table table-hover table-bordered wlsm-w-100 wlsm-text-left">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Month', 'school-management' ); ?></td>
				<th><?php esc_html_e( 'Total Attendance', 'school-management' ); ?></td>
				<th><?php esc_html_e( 'Total Present', 'school-management' ); ?></td>
				<th><?php esc_html_e( 'Total Absent', 'school-management' ); ?></td>
			</tr>
		</thead>
		<tbody>
			<?php
			$total_attendance = 0;
			$total_present    = 0;
			$total_absent     = 0;
			foreach ( $attendance as $monthly ) {
				$month = new DateTime();
				$month->setDate( $monthly->year, $monthly->month, 1 );
				$total_attendance += $monthly->total_attendance;
				$total_present    += $monthly->total_present;
				$total_absent     += $monthly->total_absent;
			?>
			<tr>
				<td><?php echo esc_html( $month->format( 'F Y' ) ); ?></td>
				<td><?php echo esc_html( $monthly->total_attendance ); ?></td>
				<td><?php echo esc_html( $monthly->total_present ); ?></td>
				<td><?php echo esc_html( $monthly->total_absent ); ?></td>
			</tr>
			<?php
			}
			?>
		</tbody>
		<tfoot>
			<tr>
				<th><?php esc_html_e( 'Overall', 'school-management' ); ?></td>
				<th><?php echo esc_html( $total_attendance ); ?></td>
				<th><?php echo esc_html( $total_present ); ?></td>
				<th><?php echo esc_html( $total_absent ); ?></td>
			</tr>
		</tfoot>
	</table>
</div>
