<?php
defined( 'ABSPATH' ) || die();

$page_url = WLSM_M_Staff_Accountant::get_invoices_page_url();

$can_delete_payments = WLSM_M_Role::check_permission( array( 'delete_payments' ), $current_school['permissions'] );
?>

<!-- Payment History -->
<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-file-invoice"></i>
				<?php esc_html_e( 'Payment History', 'school-management' ); ?>
			</span>
			<span class="float-md-right">
				<a href="<?php echo esc_url( $page_url . '&action=pending_payments' ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-file-invoice"></i>&nbsp;
					<?php echo esc_html( 'Pending Payments', 'school-management' ); ?>
				</a>&nbsp;
				<a href="<?php echo esc_url( $page_url . '&action=save' ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-plus-square"></i>&nbsp;
					<?php echo esc_html( 'Add New Fee Invoice', 'school-management' ); ?>
				</a>&nbsp;
				<a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-file-invoice"></i>&nbsp;
					<?php echo esc_html( 'View Invoices', 'school-management' ); ?>
				</a>
			</span>
		</div>
		<div class="wlsm-table-block">
			<table class="table table-hover table-bordered" id="wlsm-payments-table">
				<thead>
					<tr class="text-white bg-primary">
						<th><?php esc_html_e( 'Receipt Number', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Payment Method', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Transaction ID', 'school-management' ); ?></th>
						<th class="text-nowrap"><?php esc_html_e( 'Date', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Note', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Invoice', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Student Name', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Admission Number', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Class', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Section', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Enrollment Number', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Phone', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Fahter Name', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Fahter Phone', 'school-management' ); ?></th>
						<th><?php esc_html_e( 'Print', 'school-management' ); ?></th>
						<?php if ( $can_delete_payments ) { ?>
						<th class="text-nowrap"><?php esc_html_e( 'Delete', 'school-management' ); ?></th>
						<?php } ?>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>
