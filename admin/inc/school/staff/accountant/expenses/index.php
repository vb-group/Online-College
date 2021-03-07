<?php
defined( 'ABSPATH' ) || die();

$page_url = WLSM_M_Staff_Accountant::get_expenses_page_url();
?>

<div class="row">
	<div class="col-md-12">
		<div class="text-center wlsm-section-heading-block">
			<span class="wlsm-section-heading">
				<i class="fas fa-file-invoice"></i>
				<?php esc_html_e( 'Expenses', 'school-management' ); ?>
			</span>
			<span class="float-md-right">
				<a href="<?php echo esc_url( $page_url . '&action=category' ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-tag"></i>&nbsp;
					<?php echo esc_html( 'Expense Categories', 'school-management' ); ?>
				</a>&nbsp;
				<a href="<?php echo esc_url( $page_url . '&action=save' ); ?>" class="btn btn-sm btn-outline-light">
					<i class="fas fa-plus-square"></i>&nbsp;
					<?php echo esc_html( 'Add New Expense', 'school-management' ); ?>
				</a>
			</span>
		</div>
		<div class="wlsm-table-block">
			<table class="table table-hover table-bordered" id="wlsm-expenses-table">
				<thead>
					<tr class="text-white bg-primary">
						<th scope="col"><?php esc_html_e( 'Title', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Category', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Amount', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Invoice Number', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Date', 'school-management' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Note', 'school-management' ); ?></th>
						<th scope="col" class="text-nowrap"><?php esc_html_e( 'Action', 'school-management' ); ?></th>
					</tr>
				</thead>
			</table>
			<?php require_once WLSM_PLUGIN_DIR_PATH . 'admin/inc/school/staff/partials/export.php'; ?>
		</div>
	</div>
</div>
