<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Notify.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Config.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_Payment.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Setting.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_School.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Session.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Role.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Invoice.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_General.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Class.php';
require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Accountant.php';

class WLSM_P_Invoice
{
	public static function get_students_with_pending_invoices()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'get-pending-invoices-students')) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$school_id  = isset($_POST['school_id']) ? absint($_POST['school_id']) : 0;
			$session_id = isset($_POST['session_id']) ? absint($_POST['session_id']) : 0;
			$class_id   = isset($_POST['class_id']) ? absint($_POST['class_id']) : 0;

			$name = isset($_POST['student_name']) ? sanitize_text_field($_POST['student_name']) : '';

			// Start validation.
			$errors = array();

			// Check if session exists.
			$session = WLSM_M_Session::get_session($session_id);

			if (!$session) {
				throw new Exception(esc_html__('Session not found.', 'school-management'));
			}

			if (empty($school_id)) {
				$errors['school_id'] = esc_html__('Please select a school.', 'school-management');
			} else {
				// Checks if school exists.
				$school = WLSM_M_School::get_active_school($school_id);

				if (!$school) {
					$errors['school_id'] = esc_html__('Please select a school.', 'school-management');
				}
			}

			if (count($errors) > 0) {
				wp_send_json_error($errors);
			}

			// Checks if class exists in the school.
			$class_school = WLSM_M_Staff_Class::get_class($school_id, $class_id);

			if (empty($class_id)) {
				if (!$class_school) {
					$errors['class_id'] = esc_html__('Please select a class.', 'school-management');
				}
			} else {
				if (!$class_school) {
					$errors['class_id'] = esc_html__('Class not found.', 'school-management');
				}
			}

			if (count($errors) > 0) {
				wp_send_json_error($errors);
			}

			$class_school_id = $class_school->ID;

			$name = trim($name);
			if (empty($name)) {
				$errors['student_name'] = esc_html__('Please specify the name.', 'school-management');
				wp_send_json_error($errors);
			} else if (strlen($name) < 2) {
				$errors['student_name'] = esc_html__('Please provide at least 2 characters.', 'school-management');
				wp_send_json_error($errors);
			}

			// Get class students in a session with the name provided.
			$students = $wpdb->get_results(
				$wpdb->prepare('SELECT sr.ID, sr.name, sr.enrollment_number, c.label as class_label, se.label as section_label, sr.roll_number FROM ' . WLSM_STUDENT_RECORDS . ' as sr
					JOIN ' . WLSM_SECTIONS . ' as se ON se.ID = sr.section_id
					JOIN ' . WLSM_CLASS_SCHOOL . ' as cs ON cs.ID = se.class_school_id
					JOIN ' . WLSM_CLASSES . ' as c ON c.ID = cs.class_id
					JOIN ' . WLSM_SESSIONS . ' as ss ON ss.ID = sr.session_id
					LEFT OUTER JOIN ' . WLSM_TRANSFERS . ' as tf ON tf.from_student_record = sr.ID
					WHERE sr.session_id = %d AND se.class_school_id = %d AND sr.name LIKE "%s" AND sr.is_active = 1 AND tf.ID IS NULL GROUP BY sr.ID', $session_id, $class_school_id, '%' . $wpdb->esc_like($name) . '%')
			);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = esc_html__('An unexpected error occurred!', 'school-management');
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		if (count($errors) < 1) {
			try {
				ob_start();

				if (count($students)) {
?>
					<!-- Students with pending invoices. -->
					<div class="wlsm-table-section">
						<div class="wlsm-table-caption wlsm-font-bold">
							<?php
							printf(
								wp_kses(
									/* translators: %s: number of students */
									_n('%d Student found.', '%d Students found.', count($students), 'school-management'),
									array('span' => array('class' => array()))
								),
								count($students)
							);
							?>
						</div>

						<div class="table-responsive w-100">
							<table class="table table-bordered wlsm-students-with-pending-invoices-table">
								<thead>
									<tr class="bg-primary text-white">
										<th><?php esc_html_e('Name', 'school-management'); ?></th>
										<th><?php esc_html_e('Enrollment Number', 'school-management'); ?></th>
										<th><?php esc_html_e('Class', 'school-management'); ?></th>
										<th><?php esc_html_e('Section', 'school-management'); ?></th>
										<th><?php esc_html_e('Roll Number', 'school-management'); ?></th>
										<th><?php esc_html_e('Fee Invoices', 'school-management'); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($students as $row) { ?>
										<tr>
											<td>
												<?php echo esc_html(WLSM_M_Staff_Class::get_name_text($row->name)); ?>
											</td>
											<td>
												<?php echo esc_html($row->enrollment_number); ?>
											</td>
											<td>
												<?php echo esc_html(WLSM_M_Class::get_label_text($row->class_label)); ?>
											</td>
											<td>
												<?php echo esc_html(WLSM_M_Staff_Class::get_section_label_text($row->section_label)); ?>
											</td>
											<td>
												<?php echo esc_html(WLSM_M_Staff_Class::get_roll_no_text($row->roll_number)); ?>
											</td>
											<td>
												<a class="wlsm-view-student-pending-invoices" data-student="<?php echo esc_attr($row->ID); ?>" data-nonce="<?php echo esc_attr(esc_attr(wp_create_nonce('view-student-invoices-' . $row->ID))); ?>" href="#">
													<?php esc_html_e('View', 'school-management'); ?>
												</a>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
					</div>

					<div class="wlsm-student-pending-invoices"></div>
				<?php
				} else {
				?>
					<div class="wlsm-alert wlsm-alert-warning wlsm-font-bold">
						<span class="wlsm-icon wlsm-icon-red">&#33;</span>
						<?php esc_html_e('There is no student with this name having pending fees.', 'school-management'); ?>
					</div>
			<?php
				}
				$html = ob_get_clean();

				wp_send_json_success(array('html' => $html));
			} catch (Exception $exception) {
				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					$response = esc_html__('An unexpected error occurred!', 'school-management');
				} else {
					$response = $exception->getMessage();
				}
				wp_send_json_error($response);
			}
		}
		wp_send_json_error($errors);
	}

	public static function get_student_pending_invoices()	{
		$student_id = isset($_POST['student_id']) ? absint($_POST['student_id']) : 0;

		if (!wp_verify_nonce($_POST['nonce'], 'view-student-invoices-' . $student_id)) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			// Checks if student exists.
			$student = WLSM_M_Staff_General::get_not_transferred_active_student($student_id);

			if (!$student) {
				die;
			}

			// Get student pending invoices.
			$invoices = WLSM_M_Staff_Accountant::get_student_pending_invoices($student_id);
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = esc_html__('An unexpected error occurred!', 'school-management');
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		try {
			ob_start();
			?>
			<!-- Student details -->
			<div class="wlsm-invoices-section">
				<span class="wlsm-student-section-title">
					<?php esc_html_e('Student Detail', 'school-management'); ?>
				</span>
				<ul class="wlsm-list-group">
					<li class="wlsm-list-item">
						<span class="wlsm-font-bold"><?php esc_html_e('Student Name', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($student->student_name)); ?></span>
					</li>
					<li class="wlsm-list-item">
						<span class="wlsm-font-bold"><?php esc_html_e('Enrollment Number', 'school-management'); ?>:</span>
						<span><?php echo esc_html($student->enrollment_number); ?></span>
					</li>
					<li class="wlsm-list-item">
						<span class="wlsm-font-bold"><?php esc_html_e('Class', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Class::get_label_text($student->class_label)); ?></span>
					</li>
					<li class="wlsm-list-item">
						<span class="wlsm-font-bold"><?php esc_html_e('Section', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Staff_Class::get_section_label_text($student->section_label)); ?></span>
					</li>
					<li class="wlsm-list-item">
						<span class="wlsm-font-bold"><?php esc_html_e('Roll Number', 'school-management'); ?>:</span>
						<span><?php echo esc_html(WLSM_M_Staff_Class::get_roll_no_text($student->roll_number)); ?></span>
					</li>
				</ul>
			</div>
		<?php
			require_once WLSM_PLUGIN_DIR_PATH . 'includes/partials/pending_fee_invoices.php';

			$html = ob_get_clean();

			wp_send_json_success(array('html' => $html));
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = esc_html__('An unexpected error occurred!', 'school-management');
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}
	}

	public static function get_student_pending_invoice()
	{
		$invoice_id = isset($_POST['invoice_id']) ? absint($_POST['invoice_id']) : 0;

		if (!wp_verify_nonce($_POST['nonce'], 'view-student-invoice-' . $invoice_id)) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			// Checks if pending invoice exists.
			$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

			if (!$invoice) {
				die;
			}

			$school_id = $invoice->school_id;

			$due = $invoice->payable - $invoice->paid;

			$invoice_partial_payment = $invoice->partial_payment;
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = esc_html__('An unexpected error occurred!', 'school-management');
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		$currency = WLSM_Config::currency();

		// Razorpay settings.
		$settings_razorpay      = WLSM_M_Setting::get_settings_razorpay($school_id);
		$school_razorpay_enable = $settings_razorpay['enable'];

		// Stripe settings.
		$settings_stripe      = WLSM_M_Setting::get_settings_stripe($school_id);
		$school_stripe_enable = $settings_stripe['enable'];

		// PayPal settings.
		$settings_paypal      = WLSM_M_Setting::get_settings_paypal($school_id);
		$school_paypal_enable = $settings_paypal['enable'];

		// Pesapal settings.
		$settings_pesapal      = WLSM_M_Setting::get_settings_pesapal($school_id);
		$school_pesapal_enable = $settings_pesapal['enable'];

		// Paystack settings.
		$settings_paystack      = WLSM_M_Setting::get_settings_paystack($school_id);
		$school_paystack_enable = $settings_paystack['enable'];

		// Paytm settings.
		$settings_paytm      = WLSM_M_Setting::get_settings_paytm($school_id);
		$school_paytm_enable = $settings_paytm['enable'];

		// Bank transfer settings.
		$settings_bank_transfer      = WLSM_M_Setting::get_settings_bank_transfer($school_id);
		$school_bank_transfer_enable = $settings_bank_transfer['enable'];
		$school_bank_transfer_branch  = $settings_bank_transfer['branch'];
		$school_bank_transfer_account = $settings_bank_transfer['account'];
		$school_bank_transfer_name    = $settings_bank_transfer['name'];
		$school_bank_transfer_message = $settings_bank_transfer['message'];

		try {
			ob_start();
		?>
			<!-- Invoice and student details -->
			<div class="wlsm-invoices-section wlsm-invoices-student-detail">
				<div class="wlsm-invoices-detail-section">
					<span class="wlsm-invoices-section-title">
						<strong><?php esc_html_e('Invoice Detail', 'school-management'); ?></strong>
					</span>
					<ul class="wlsm-list-group">
						<li class="wlsm-list-item">
							<span class="wlsm-font-bold"><?php esc_html_e('Invoice Title', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice->invoice_title)); ?></span>
						</li>
						<li class="wlsm-list-item">
							<span class="wlsm-font-bold"><?php esc_html_e('Invoice Number', 'school-management'); ?>:</span>
							<span><?php echo esc_html($invoice->invoice_number); ?></span>
						</li>
						<li class="wlsm-list-item">
							<span class="wlsm-font-bold"><?php esc_html_e('Date Issued', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_Config::get_date_text($invoice->date_issued)); ?></span>
						</li>
						<li class="wlsm-list-item">
							<span class="wlsm-font-bold"><?php esc_html_e('Due Date', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_Config::get_date_text($invoice->due_date)); ?></span>
						</li>
					</ul>
				</div>

				<div class="wlsm-invoices-detail-section">
					<span class="wlsm-invoices-section-title">
						<?php esc_html_e('Student Detail', 'school-management'); ?>
					</span>
					<ul class="wlsm-list-group">
						<li class="wlsm-list-item">
							<span class="wlsm-font-bold"><?php esc_html_e('Student Name', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Staff_Class::get_name_text($invoice->student_name)); ?></span>
						</li>
						<li class="wlsm-list-item">
							<span class="wlsm-font-bold"><?php esc_html_e('Enrollment Number', 'school-management'); ?>:</span>
							<span><?php echo esc_html($invoice->enrollment_number); ?></span>
						</li>
						<li class="wlsm-list-item">
							<span class="wlsm-font-bold"><?php esc_html_e('Class', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Class::get_label_text($invoice->class_label)); ?></span>
						</li>
						<li class="wlsm-list-item">
							<span class="wlsm-font-bold"><?php esc_html_e('Section', 'school-management'); ?>:</span>
							<span><?php echo esc_html(WLSM_M_Staff_Class::get_section_label_text($invoice->section_label)); ?></span>
						</li>
					</ul>
				</div>
			</div>

			<!-- Invoice status and payment -->
			<div id="wlsm-pay-invoice-amount-section" class="wlsm-pt-2">
				<input type="hidden" name="invoice_id" value="<?php echo esc_attr($invoice_id); ?>" id="wlsm_invoice_id">
				<div class="wlsm-form-group">
					<label for="wlsm_payment_amount" class="wlsm-font-bold">
						<?php esc_html_e('Fees Due', 'school-management'); ?>:
					</label>
					<?php
					echo esc_html(WLSM_Config::get_money_text($due));
					if ($invoice_partial_payment) {
					?>
						<br>
						<input type="number" step="any" min="0" name="payment_amount" class="wlsm-form-control" id="wlsm_payment_amount" placeholder="<?php esc_attr_e('Enter amount to pay', 'school-management'); ?>">
					<?php
					} else {
					?>
						<input type="hidden" name="payment_amount" id="wlsm_payment_amount" value="<?php echo esc_attr(WLSM_Config::sanitize_money($due)); ?>">
					<?php
					}
					?>
				</div>
				<div class="wlsm-form-group">
					<label class="wlsm-font-bold">
						<?php esc_html_e('Select Payment Method', 'school-management'); ?>:
					</label>
					<?php
					$payment_methods_count = 0;
					if ($school_razorpay_enable && WLSM_Payment::currency_supports_razorpay($currency)) { ?>
						<br>
						<label class="radio-inline wlsm-mr-3">
							<input type="radio" name="payment_method" class="wlsm-mr-2" value="razorpay" id="wlsm-payment-razorpay">
							<?php echo esc_html(WLSM_M_Invoice::get_payment_method_text('razorpay')); ?>
						</label>
					<?php
						$payment_methods_count++;
					}
					if ($school_stripe_enable && WLSM_Payment::currency_supports_stripe($currency)) { ?>
						<br>
						<label class="radio-inline wlsm-mr-3">
							<input type="radio" name="payment_method" class="wlsm-mr-2" value="stripe" id="wlsm-payment-stripe">
							<?php echo esc_html(WLSM_M_Invoice::get_payment_method_text('stripe')); ?>
						</label>
					<?php
						$payment_methods_count++;
					}
					if ($school_paypal_enable && WLSM_Payment::currency_supports_paypal($currency)) { ?>
						<br>
						<label class="radio-inline wlsm-mr-3">
							<input type="radio" name="payment_method" class="wlsm-mr-2" value="paypal" id="wlsm-payment-paypal">
							<?php echo esc_html(WLSM_M_Invoice::get_payment_method_text('paypal')); ?>
						</label>
					<?php
						$payment_methods_count++;
					}
					if ($school_pesapal_enable && WLSM_Payment::currency_supports_pesapal($currency)) { ?>
						<br>
						<label class="radio-inline wlsm-mr-3">
							<input type="radio" name="payment_method" class="wlsm-mr-2" value="pesapal" id="wlsm-payment-pesapal">
							<?php echo esc_html(WLSM_M_Invoice::get_payment_method_text('pesapal')); ?>
						</label>
					<?php
						$payment_methods_count++;
					}
					if ($school_paystack_enable && WLSM_Payment::currency_supports_paystack($currency)) { ?>
						<br>
						<label class="radio-inline wlsm-mr-3">
							<input type="radio" name="payment_method" class="wlsm-mr-2" value="paystack" id="wlsm-payment-paystack">
							<?php echo esc_html(WLSM_M_Invoice::get_payment_method_text('paystack')); ?>
						</label>
					<?php
						$payment_methods_count++;
					}
					if ($school_paytm_enable && WLSM_Payment::currency_supports_paytm($currency)) { ?>
						<br>
						<label class="radio-inline wlsm-mr-3">
							<input type="radio" name="payment_method" class="wlsm-mr-2" value="paytm" id="wlsm-payment-paytm">
							<?php echo esc_html(WLSM_M_Invoice::get_payment_method_text('paytm')); ?>
						</label>
					<?php
						$payment_methods_count++;
					}
					if ($school_bank_transfer_enable) { ?>
						<br>
						<label class="radio-inline wlsm-mr-3">
							<input type="radio" name="payment_method" class="wlsm-mr-2" value="bank-transfer" id="wlsm-payment-bank-transfer">
							<?php echo esc_html(WLSM_M_Invoice::get_payment_method_text('bank-transfer')); ?>
						</label>
						<?php
						$payment_methods_count++;
						?>
						<div class="wlsm-bank-transfer-detail">
							<?php if (!empty($school_bank_transfer_message)) { ?>
								<p>
									<?php echo esc_html($school_bank_transfer_message); ?>
								</p>
							<?php } ?>
							<div class="wlsm-form-group">
								<label class="wlsm-font-bold"><?php esc_html_e('Branch Code'); ?>:</label>
								<span><?php echo esc_html($school_bank_transfer_branch); ?></span>
							</div>
							<div class="wlsm-form-group">
								<label class="wlsm-font-bold"><?php esc_html_e('Account Number'); ?>:</label>
								<span><?php echo esc_html($school_bank_transfer_account); ?></span>
							</div>
							<div class="wlsm-form-group">
								<label class="wlsm-font-bold"><?php esc_html_e('Name'); ?>:</label>
								<span><?php echo esc_html($school_bank_transfer_name); ?></span>
							</div>
							<form id="wlsm-bank-tranfer-form">
								<div class="wlsm-form-group">
									<label class="wlsm-font-bold"><?php esc_html_e('Transaction ID'); ?>:</label>
									<br>
									<input type="text" name="bank_transfer_transaction_id" class="wlsm-form-control" id="wlsm_bank_transfer_transaction_id">
								</div>
								<div class="wlsm-form-group">
									<label class="wlsm-font-bold"><?php esc_html_e('Upload Payment Receipt'); ?>:</label>
									<br>
									<input type="file" name="bank_transfer_receipt" id="wlsm_bank_transfer_receipt">
								</div>
							</form>
						</div>
					<?php
					}
					?>
				</div>
				<?php if ($payment_methods_count > 0) { ?>
					<div class="wlsm-border-top wlsm-pt-2 wlsm-mt-2">
						<button class="button wlsm-btn btn btn-primary" type="button" id="wlsm-pay-invoice-amount-btn" data-nonce="<?php echo esc_attr(wp_create_nonce('pay-invoice-amount-' . $invoice_id)); ?>">
							<?php esc_html_e('Proceed to Pay', 'school-management'); ?>
						</button>
					</div>
					<div class="wlsm-pay-invoice-amount"></div>
				<?php } else { ?>
					<div class="wlsm-border-top wlsm-pt-2 wlsm-mt-2">
						<span class="wlsm-text-danger wlsm-font-bold"><?php esc_html_e('No payment method available right now.', 'school-management'); ?></span>
					</div>
				<?php } ?>
			</div>
		<?php
			$html = ob_get_clean();

			wp_send_json_success(array('html' => $html));
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = esc_html__('An unexpected error occurred!', 'school-management');
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}
	}

	public static function pay_invoice_amount()
	{
		$invoice_id = isset($_POST['invoice_id']) ? absint($_POST['invoice_id']) : 0;

		if (!wp_verify_nonce($_POST['nonce'], 'pay-invoice-amount-' . $invoice_id)) {
			die();
		}

		try {
			ob_start();
			global $wpdb;

			$payment_amount = isset($_POST['payment_amount']) ? WLSM_Config::sanitize_money($_POST['payment_amount']) : 0;
			$payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : '';

			$current_page_url = isset($_POST['current_page_url']) ? esc_url($_POST['current_page_url']) : '';

			// Start validation.
			$errors = array();

			// Checks if pending invoice exists.
			$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

			if (!$invoice) {
				throw new Exception(esc_html__('Invoice not found or already paid.', 'school-management'));
			}

			$school_id = $invoice->school_id;

			// Checks if school exists.
			$school = WLSM_M_School::get_active_school($school_id);

			if (!$school) {
				wp_send_json_error(esc_html__('Your school is currently inactive.', 'school-management'));
			}

			$school_name = WLSM_M_School::get_label_text($school->label);

			$due = $invoice->payable - $invoice->paid;

			$invoice_partial_payment = $invoice->partial_payment;

			if (!$payment_amount) {
				$errors['payment_amount'] = esc_html__('Please enter a valid amount.', 'school-management');
			} else {
				if ($payment_amount > $due) {
					$errors['payment_amount'] = esc_html__('Amount exceeded due amount.', 'school-management');
				} else {
					if (!$invoice_partial_payment) {
						$payment_amount = $due;
					}
				}
			}

			if (!$payment_method) {
				throw new Exception(esc_html__('Please select a payment method.', 'school-management'));
			}
		} catch (Exception $exception) {
			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				$response = esc_html__('An unexpected error occurred!', 'school-management');
			} else {
				$response = $exception->getMessage();
			}
			wp_send_json_error($response);
		}

		if (count($errors) < 1) {
			// Basic details.
			$name    = WLSM_M_Staff_Class::get_name_text($invoice->student_name);
			$phone   = $invoice->phone ? $invoice->phone : '';
			$email   = $invoice->login_email ? $invoice->login_email : $invoice->email;
			$address = $invoice->address;

			$description = sprintf(
				/* translators: 1: invoice title, 2: invoice number */
				__('Invoice: %1$s (%2$s)', 'school-management'),
				esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice->invoice_title)),
				esc_html($invoice->invoice_number)
			);

			$invoice_title_number = sprintf(
				/* translators: 1: invoice title, 2: invoice number */
				__('%1$s (%2$s)', 'school-management'),
				esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice->invoice_title)),
				esc_html($invoice->invoice_number)
			);

			$invoice_number = $invoice->invoice_number;

			// School details.
			$settings_general = WLSM_M_Setting::get_settings_general($school_id);
			$school_logo_url  = esc_url(wp_get_attachment_url($settings_general['school_logo']));

			// Currency.
			$currency = WLSM_Config::currency();

			try {
				ob_start();

				if ('razorpay' === $payment_method) {
					$settings_razorpay      = WLSM_M_Setting::get_settings_razorpay($school_id);
					$school_razorpay_enable = $settings_razorpay['enable'];
					$school_razorpay_key    = $settings_razorpay['razorpay_key'];

					if (!$school_razorpay_enable || !WLSM_Payment::currency_supports_razorpay($currency)) {
						wp_send_json_error(esc_html__('Razorpay payment method is currently unavailable.', 'school-management'));
					}

					$amount_in_paisa = $payment_amount * 100;
					$security        = wp_create_nonce('pay-with-razorpay');
					$razorpay_key    = $school_razorpay_key;

					$pay_with_razorpay_text = sprintf(
						/* translators: %s: amount to pay */
						__('Pay Amount %s using Razorpay', 'school-management'),
						esc_html(WLSM_Config::get_money_text($payment_amount))
					);

					$html = "<button class='wlsm-mt-2 float-md-right button btn btn-success' id='wlsm-razorpay-btn'>$pay_with_razorpay_text";

					$json = json_encode(
						array(
							'action'          => 'wlsm-p-pay-with-razorpay',
							'payment_method'  => esc_attr($payment_method),
							'razorpay_key'    => esc_attr($razorpay_key),
							'amount_in_paisa' => esc_attr($amount_in_paisa),
							'currency'        => esc_attr($currency),
							'school_name'     => esc_attr($school_name),
							'school_logo_url' => esc_attr($school_logo_url),
							'security'        => esc_attr($security),
							'name'            => esc_attr($name),
							'email'           => esc_attr($email),
							'address'         => esc_attr($address),
							'invoice_id'      => esc_attr($invoice_id),
							'invoice_number'  => esc_attr($invoice_number),
							'payment_amount'  => esc_attr($payment_amount),
							'description'     => esc_attr($description),
						)
					);
				} elseif ('stripe' === $payment_method) {
					$settings_stripe               = WLSM_M_Setting::get_settings_stripe($school_id);
					$school_stripe_enable          = $settings_stripe['enable'];
					$school_stripe_publishable_key = $settings_stripe['publishable_key'];

					if (!$school_stripe_enable || !WLSM_Payment::currency_supports_stripe($currency)) {
						wp_send_json_error(esc_html__('Stripe payment method is currently unavailable.', 'school-management'));
					}

					$amount_in_cents = $payment_amount * 100;
					$security        = wp_create_nonce('pay-with-stripe');
					$stripe_key      = $school_stripe_publishable_key;

					$pay_with_stripe_text = sprintf(
						/* translators: %s: amount to pay */
						__('Pay Amount %s using Stripe', 'school-management'),
						esc_html(WLSM_Config::get_money_text($payment_amount))
					);

					$html = "<button class='wlsm-mt-2 float-md-right button btn btn-success' id='wlsm-stripe-btn'>$pay_with_stripe_text</button>";

					$json = json_encode(
						array(
							'action'          => 'wlsm-p-pay-with-stripe',
							'payment_method'  => esc_attr($payment_method),
							'stripe_key'      => esc_attr($stripe_key),
							'amount_in_cents' => esc_attr($amount_in_cents),
							'currency'        => esc_attr($currency),
							'school_name'     => esc_attr($school_name),
							'school_logo_url' => esc_attr($school_logo_url),
							'security'        => esc_attr($security),
							'name'            => esc_attr($name),
							'email'           => esc_attr($email),
							'address'         => esc_attr($address),
							'invoice_id'      => esc_attr($invoice_id),
							'invoice_number'  => esc_attr($invoice_number),
							'payment_amount'  => esc_attr($payment_amount),
							'description'     => esc_attr($description),
						)
					);
				} elseif ('paypal' === $payment_method) {
					$settings_paypal              = WLSM_M_Setting::get_settings_paypal($school_id);
					$school_paypal_enable         = $settings_paypal['enable'];
					$school_paypal_business_email = $settings_paypal['business_email'];
					$school_paypal_payment_url    = $settings_paypal['payment_url'];
					$school_paypal_notify_url     = $settings_paypal['notify_url'];

					if (!$school_paypal_enable || !WLSM_Payment::currency_supports_paypal($currency)) {
						wp_send_json_error(esc_html__('PayPal payment method is currently unavailable.', 'school-management'));
					}

					$business_email = $school_paypal_business_email;
					$payment_url    = $school_paypal_payment_url;
					$notify_url     = $school_paypal_notify_url;

					$cancel_url = $current_page_url;
					$return_url = $current_page_url;

					$invoice_id  = esc_attr($invoice_id);

					$pay_with_paypal_text = sprintf(
						/* translators: %s: amount to pay */
						__('Pay Amount %s using PayPal', 'school-management'),
						esc_html(WLSM_Config::get_money_text($payment_amount))
					);

					$html = "<button class='wlsm-mt-2 float-md-right button btn btn-success' id='wlsm-stripe-btn'>$pay_with_paypal_text</button>";

					$html = <<<EOT
<form action="$payment_url" method="post">
	<input type="hidden" name="business" value="$business_email">
	<input type="hidden" name="cmd" value="_cart">
	<input type="hidden" name="upload" value="1">
	<input type="hidden" name="item_name_1" value="$invoice_title_number">
	<input type="hidden" name="item_number_1" value="1">
	<input type="hidden" name="amount_1" value="$payment_amount">
	<input type="hidden" name="currency_code" value="$currency">
	<input type='hidden' name='cancel_return' value='$cancel_url'>
	<input type='hidden' name='return' value='$return_url'>
	<input type="hidden" name="notify_url" value="$notify_url">
	<input type="hidden" name="custom" value="$invoice_id">
	<button type="submit" class='wlsm-mt-2 float-md-right button btn btn-success' id='wlsm-paypal-btn'>$pay_with_paypal_text</button>
</form>
EOT;

					$json = json_encode(
						array(
							'payment_method' => esc_attr($payment_method)
						)
					);
				} elseif ('pesapal' === $payment_method) {
					$settings_pesapal               = WLSM_M_Setting::get_settings_pesapal($school_id);
					$school_pesapal_enable          = $settings_pesapal['enable'];
					$school_pesapal_consumer_key    = $settings_pesapal['consumer_key'];
					$school_pesapal_consumer_secret = $settings_pesapal['consumer_secret'];
					$school_pesapal_mode            = $settings_pesapal['mode'];
					$school_pesapal_payment_url     = $settings_pesapal['payment_url'];
					$school_pesapal_notify_url      = $settings_pesapal['notify_url'];

					if (!$school_pesapal_enable || !WLSM_Payment::currency_supports_pesapal($currency)) {
						wp_send_json_error(esc_html__('Pesapal payment method is currently unavailable.', 'school-management'));
					}

					if ('live' === $school_pesapal_mode) {
						$hostname = 'www';
					} else {
						$hostname = 'demo';
					}

					require_once WLSM_PLUGIN_DIR_PATH . 'includes/libs/PesapalOAuth.php';

					$token = $params = NULL;

					$consumer_key    = $school_pesapal_consumer_key;
					$consumer_secret = $school_pesapal_consumer_secret;

					$signature_method = new PesapalOAuthSignatureMethod_HMAC_SHA1();

					$iframelink = $school_pesapal_payment_url;

					$amount = esc_attr($payment_amount);

					$type = 'MERCHANT';

					$invoice_id = esc_attr($invoice_id);
					$reference  = time() . '-' . $invoice_id . '-' . $payment_amount;

					$description = esc_attr($description);

					$name  = esc_attr($name);
					$names = explode(' ', $name);

					$first_name = $names[0];
					if (isset($names[1])) {
						$last_name = $names[1];
					} else {
						$last_name = '';
					}

					$email = esc_attr($email);
					$phone = esc_attr($phone);

					$callback_url = $school_pesapal_notify_url;

					$post_xml = '<?xml version="1.0" encoding="utf-8"?>';
					$post_xml .= '<PesapalDirectOrderInfo ';
					$post_xml .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
					$post_xml .= 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ';
					$post_xml .= 'Amount="' . $amount . '" ';
					$post_xml .= 'Description="' . $description . '" ';
					$post_xml .= 'Type="' . $type . '" ';
					$post_xml .= 'Reference="' . $reference . '" ';
					$post_xml .= 'FirstName="' . $first_name . '" ';
					$post_xml .= 'LastName="' . $last_name . '" ';
					$post_xml .= 'Email="' . $email . '" ';
					$post_xml .= 'PhoneNumber="' . $phone . '" ';
					$post_xml .= 'xmlns="http://www.pesapal.com" />';
					$post_xml = htmlentities($post_xml);

					$consumer = new PesapalOAuthConsumer($consumer_key, $consumer_secret);

					$iframe_src = PesapalOAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $iframelink, $params);

					$iframe_src->set_parameter('oauth_callback', $callback_url);
					$iframe_src->set_parameter('pesapal_request_data', $post_xml);
					$iframe_src->sign_request($signature_method, $consumer, $token);

					$iframe_failed = esc_html__('Browser unable to load iFrame', 'school-management');

					$html = <<<EOT
<iframe src="$iframe_src" width="100%" height="720px" referrerpolicy="origin" scrolling="auto" frameBorder="0">
	<p>$iframe_failed</p>
</iframe>
EOT;
					$json = json_encode(
						array(
							'payment_method' => esc_attr($payment_method)
						)
					);
				} elseif ('paytm' === $payment_method) {
					$settings_paytm                = WLSM_M_Setting::get_settings_paytm($school_id);
					$school_paytm_enable           = $settings_paytm['enable'];
					$school_paytm_merchant_id      = $settings_paytm['merchant_id'];
					$school_paytm_merchant_key     = $settings_paytm['merchant_key'];
					$school_paytm_industry_type_id = $settings_paytm['industry_type_id'];
					$school_paytm_website          = $settings_paytm['website'];
					$school_paytm_mode             = $settings_paytm['mode'];

					if (!$school_paytm_enable || !WLSM_Payment::currency_supports_paytm($currency)) {
						wp_send_json_error(esc_html__('Paytm payment method is currently unavailable.', 'school-management'));
					}

					$paytm_merchant_id      = $school_paytm_merchant_id;
					$paytm_merchant_key     = $school_paytm_merchant_key;
					$paytm_industry_type_id = $school_paytm_industry_type_id;
					$paytm_website          = $school_paytm_website;
					$paytm_mode             = $school_paytm_mode;

					require_once WLSM_PLUGIN_DIR_PATH . 'includes/libs/encdec_paytm.php';

					// Create unique transaction id for paytm.
					$order_id = $invoice_id . '-' . time();

					$post_params = array(
						'MID'              => $paytm_merchant_id,
						'WEBSITE'          => $paytm_website,
						'CHANNEL_ID'       => 'WEB',
						'INDUSTRY_TYPE_ID' => $paytm_industry_type_id,
						'ORDER_ID'         => $order_id,
						'TXN_AMOUNT'       => $payment_amount,
						'CUST_ID'          => $email,
						'EMAIL'            => $email,
						'CALLBACK_URL'     => $current_page_url,
					);

					$post_params['CHECKSUMHASH'] = WLSM_Paytm::getChecksumFromArray($post_params, $paytm_merchant_key);

					if ('production' === $paytm_mode) {
						$transaction_url = 'https://securegw.paytm.in/order/process';
					} else {
						$transaction_url = 'https://securegw-stage.paytm.in/order/process';
					}

					$transaction_url .= ('?orderid=' . $order_id);

					$pay_with_paytm_text = sprintf(
						/* translators: %s: amount to pay */
						__('Pay Amount %s using Paytm', 'school-management'),
						esc_html(WLSM_Config::get_money_text($payment_amount))
					);

					$form_id = 'wlsm-paytm-form';

					$html = ('<center><h4>' . esc_html__('Please do not refresh this page...', 'school-management') . '</h4></center>');

					$html .= ('<form method="post" action="' . esc_url($transaction_url) . '" name="' . esc_attr($form_id) . '" id="' . esc_attr($form_id) . '">');

					foreach ($post_params as $key => $value) {
						$html .= ('<input type="hidden" name="' . $key . '" value="' . $value . '">');
					}

					$html .= '</form>';

					$json = json_encode(
						array(
							'payment_method' => esc_attr($payment_method),
							'form_id'        => esc_attr($form_id),
						)
					);
				} elseif ('paystack' === $payment_method) {
					$settings_paystack          = WLSM_M_Setting::get_settings_paystack($school_id);
					$school_paystack_enable     = $settings_paystack['enable'];
					$school_paystack_public_key = $settings_paystack['paystack_public_key'];

					if (!$school_paystack_enable || !WLSM_Payment::currency_supports_paystack($currency)) {
						wp_send_json_error(esc_html__('Paystack payment method is currently unavailable.', 'school-management'));
					}

					$amount_x_100        = $payment_amount * 100;
					$security            = wp_create_nonce('pay-with-paystack');
					$paystack_public_key = $school_paystack_public_key;

					$pay_with_paystack_text = sprintf(
						/* translators: %s: amount to pay */
						__('Pay Amount %s using Paystack', 'school-management'),
						esc_html(WLSM_Config::get_money_text($payment_amount))
					);

					$html = "<button class='wlsm-mt-2 float-md-right button btn btn-success' id='wlsm-paystack-btn'>$pay_with_paystack_text";

					$json = json_encode(
						array(
							'action'              => 'wlsm-p-pay-with-paystack',
							'payment_method'      => esc_attr($payment_method),
							'paystack_public_key' => esc_attr($paystack_public_key),
							'amount_x_100'        => esc_attr($amount_x_100),
							'currency'            => esc_attr($currency),
							'school_name'         => esc_attr($school_name),
							'school_logo_url'     => esc_attr($school_logo_url),
							'security'            => esc_attr($security),
							'name'                => esc_attr($name),
							'email'               => esc_attr($email),
							'phone'               => esc_attr($phone),
							'address'             => esc_attr($address),
							'invoice_id'          => esc_attr($invoice_id),
							'invoice_number'      => esc_attr($invoice_number),
							'payment_amount'      => esc_attr($payment_amount),
							'description'         => esc_attr($description),
						)
					);
				} elseif ('bank-transfer' === $payment_method) {
					$settings_bank_transfer      = WLSM_M_Setting::get_settings_bank_transfer($school_id);
					$school_bank_transfer_enable = $settings_bank_transfer['enable'];

					if (!$school_bank_transfer_enable) {
						wp_send_json_error(esc_html__('Bank transfer payment method is currently unavailable.', 'school-management'));
					}

					$bank_transfer_transaction_id = isset($_POST['bank_transfer_transaction_id']) ? sanitize_text_field($_POST['bank_transfer_transaction_id']) : '';
					$bank_transfer_receipt        = (isset($_FILES['bank_transfer_receipt']) && is_array($_FILES['bank_transfer_receipt'])) ? $_FILES['bank_transfer_receipt'] : NULL;

					if (isset($bank_transfer_receipt['tmp_name']) && !empty($bank_transfer_receipt['tmp_name'])) {
						if (!WLSM_Helper::is_valid_file($bank_transfer_receipt, 'attachment')) {
							$errors['bank_transfer_receipt'] = esc_html__('This file type is not allowed.', 'school-management');
							wp_send_json_error($errors);
						}
					} else {
						$errors['bank_transfer_receipt'] = esc_html__('Please upload the payment receipt.', 'school-management');
						wp_send_json_error($errors);
					}

					$wpdb->query('BEGIN;');

					$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);

					// Pending payment data.
					$pending_payment_data = array(
						'receipt_number'    => $receipt_number,
						'amount'            => $payment_amount,
						'payment_method'    => $payment_method,
						'transaction_id'    => $bank_transfer_transaction_id,
						'invoice_label'     => $invoice->invoice_title,
						'invoice_payable'   => $due,
						'student_record_id' => $invoice->student_id,
						'invoice_id'        => $invoice_id,
						'school_id'         => $school_id,
					);

					$bank_transfer_receipt = media_handle_upload('bank_transfer_receipt', 0);
					if (is_wp_error($bank_transfer_receipt)) {
						throw new Exception($bank_transfer_receipt->get_error_message());
					}
					$pending_payment_data['attachment'] = $bank_transfer_receipt;

					$pending_payment_data['created_at'] = current_time('Y-m-d H:i:s');

					$success = $wpdb->insert(WLSM_PENDING_PAYMENTS, $pending_payment_data);

					$buffer = ob_get_clean();
					if (!empty($buffer)) {
						throw new Exception($buffer);
					}

					if (false === $success) {
						throw new Exception($wpdb->last_error);
					}

					$wpdb->query('COMMIT;');

					$message = esc_html__('Your payment receipt is submitted and waiting for confirmation.', 'school-management');

					$json = json_encode(
						array(
							'message'        => esc_html($message),
							'payment_method' => esc_attr($payment_method)
						)
					);

					$html = '';
				} else {
					wp_send_json_error(esc_html__('Please select a valid payment method.', 'school-management'));
				}

				wp_send_json_success(array('html' => $html, 'json' => $json));
			} catch (Exception $exception) {
				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					$response = esc_html__('An unexpected error occurred!', 'school-management');
				} else {
					$response = $exception->getMessage();
				}
				wp_send_json_error($response);
			}
		}
		wp_send_json_error($errors);
	}

	public static function process_razorpay()
	{
		if (!wp_verify_nonce($_POST['security'], 'pay-with-razorpay')) {
			die();
		}

		$unexpected_error_message = esc_html__('An unexpected error occurred!', 'school-management');
		if (!isset($_POST['payment_id']) || !isset($_POST['amount'])) {
			wp_send_json_error($unexpected_error_message);
		}

		$invoice_id = absint($_POST['invoice_id']);

		// Checks if pending invoice exists.
		$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

		if (!$invoice) {
			wp_send_json_error(esc_html__('Invoice not found or already paid.', 'school-management'));
		}

		$school_id  = $invoice->school_id;
		$session_id = $invoice->session_id;

		$settings_razorpay      = WLSM_M_Setting::get_settings_razorpay($school_id);
		$school_razorpay_key    = $settings_razorpay['razorpay_key'];
		$school_razorpay_secret = $settings_razorpay['razorpay_secret'];

		$payment_id      = sanitize_text_field($_POST['payment_id']);
		$amount_in_paisa = WLSM_Config::sanitize_money($_POST['amount']);
		$razorpay_key    = $school_razorpay_key;
		$razorpay_secret = $school_razorpay_secret;
		$url             = "https://$razorpay_key:$razorpay_secret@api.razorpay.com/v1/payments";

		$response = wp_remote_post(
			"$url/$payment_id/capture",
			array(
				'method'  => 'POST',
				'headers' => array(),
				'body'    => array('amount' => $amount_in_paisa),
				'cookies' => array(),
			)
		);

		if (is_wp_error($response)) {
			wp_send_json_error($response->get_error_message());
		}

		$data = json_decode($response['body']);

		if (!$data->captured) {
			wp_send_json_error(esc_html__('Unable to capture the payment.', 'school-management'));
		}

		global $wpdb;

		$payment_amount = ($data->amount) / 100;

		$partial_payment = $invoice->partial_payment;

		$due = $invoice->payable - $invoice->paid;

		if (($payment_amount <= 0) || ($payment_amount > $due) || (!$partial_payment && ($payment_amount != $due))) {
			wp_send_json_error($unexpected_error_message);
		}

		$transaction_id = $payment_id;

		try {
			$wpdb->query('BEGIN;');

			$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);

			// Payment data.
			$payment_data = array(
				'receipt_number'    => $receipt_number,
				'amount'            => $payment_amount,
				'transaction_id'    => $transaction_id,
				'payment_method'    => 'razorpay',
				'invoice_label'     => $invoice->invoice_title,
				'invoice_payable'   => $invoice->payable,
				'student_record_id' => $invoice->student_id,
				'invoice_id'        => $invoice_id,
				'school_id'         => $school_id,
			);

			$payment_data['created_at'] = current_time('Y-m-d H:i:s');

			$success = $wpdb->insert(WLSM_PAYMENTS, $payment_data);

			$new_payment_id = $wpdb->insert_id;

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

			$wpdb->query('COMMIT;');

			if (isset($new_payment_id)) {
				// Notify for online fee submission.
				$data = array(
					'school_id'  => $school_id,
					'session_id' => $session_id,
					'payment_id' => $new_payment_id,
				);

				wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission', $data);
				wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission_to_parent', $data);
			}

			wp_send_json_success(array('message' => esc_html__('Payment made successfully.', 'school-management')));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($unexpected_error_message);
		}
	}

	public static function process_stripe()
	{
		// var_dump($_POST); die;

		if (!wp_verify_nonce($_POST['security'], 'pay-with-stripe')) {
			die();
		}

		$unexpected_error_message = esc_html__('An unexpected error occurred!', 'school-management');
		if (!isset($_POST['stripeToken']) || !isset($_POST['amount'])) {
			wp_send_json_error($unexpected_error_message);
		}

		require_once WLSM_PLUGIN_DIR_PATH . 'includes/vendor/autoload.php';

		// Currency.
		$currency = WLSM_Config::currency();

		$stripe_token    = sanitize_text_field($_POST['stripeToken']);
		$amount_in_cents = WLSM_Config::sanitize_money($_POST['amount']);

		$invoice_id = absint($_POST['invoice_id']);

		// Checks if pending invoice exists.
		$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

		if (!$invoice) {
			wp_send_json_error(esc_html__('Invoice not found or already paid.', 'school-management'));
		}

		$partial_payment = $invoice->partial_payment;

		$due = $invoice->payable - $invoice->paid;

		$school_id  = $invoice->school_id;
		$session_id = $invoice->session_id;

		$description = sprintf(
			/* translators: 1: invoice title, 2: invoice number */
			__('Invoice: %1$s (%2$s)', 'school-management'),
			esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice->invoice_title)),
			esc_html($invoice->invoice_number)
		);

		global $wpdb;

		$payment_amount = $amount_in_cents / 100;

		if (($payment_amount <= 0) || ($payment_amount > $due) || (!$partial_payment && ($payment_amount != $due))) {
			wp_send_json_error($unexpected_error_message);
		}

		$settings_stripe          = WLSM_M_Setting::get_settings_stripe($school_id);
		$school_stripe_secret_key = $settings_stripe['secret_key'];

		$secret_key = $school_stripe_secret_key;

		try {
			\Stripe\Stripe::setApiKey($secret_key);
			$charge = \Stripe\Charge::create(
				array(
					'amount'      => $amount_in_cents,
					'currency'    => $currency,
					'description' => $description,
					'source'      => $stripe_token
				)
			);
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($exception->getMessage());
		}

		if (!($charge && $charge->captured && ($charge->amount == $amount_in_cents))) {
			wp_send_json_error(esc_html__('Unable to capture the payment.', 'school-management'));
		}

		$transaction_id = $charge->id;

		try {
			$wpdb->query('BEGIN;');

			$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);

			// Payment data.
			$payment_data = array(
				'receipt_number'    => $receipt_number,
				'amount'            => $payment_amount,
				'transaction_id'    => $transaction_id,
				'payment_method'    => 'stripe',
				'invoice_label'     => $invoice->invoice_title,
				'invoice_payable'   => $invoice->payable,
				'student_record_id' => $invoice->student_id,
				'invoice_id'        => $invoice_id,
				'school_id'         => $school_id,
			);

			$payment_data['created_at'] = current_time('Y-m-d H:i:s');

			$success = $wpdb->insert(WLSM_PAYMENTS, $payment_data);

			$new_payment_id = $wpdb->insert_id;

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

			$wpdb->query('COMMIT;');

			if (isset($new_payment_id)) {
				// Notify for online fee submission.
				$data = array(
					'school_id'  => $school_id,
					'session_id' => $session_id,
					'payment_id' => $new_payment_id,
				);

				wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission', $data);
				wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission_to_parent', $data);
			}

			wp_send_json_success(array('message' => esc_html__('Payment made successfully.', 'school-management')));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($unexpected_error_message);
		}
	}

	public static function process_paypal()
	{
		if (!(!isset($_POST["txn_id"]) && !isset($_POST["txn_type"]))) {

			$payment_amount = isset($_POST['mc_gross_1']) ? sanitize_text_field($_POST['mc_gross_1']) : '';

			$data = array(
				'payment_status'   => $_POST['payment_status'],
				'payment_currency' => $_POST['mc_currency'],
				'txn_id'           => $_POST['txn_id'],
				'receiver_email'   => $_POST['receiver_email'],
				'payer_email'      => $_POST['payer_email'],
				'invoice_id'       => $_POST['custom'],
				'payment_amount'   => $payment_amount
			);

			if (self::check_paypal_txnid($data['txn_id'])) {
				self::save_paypal_payment($data);
			}
		}
	}

	public static function process_paystack()
	{
		if (!wp_verify_nonce($_POST['security'], 'pay-with-paystack')) {
			die();
		}

		$unexpected_error_message = esc_html__('An unexpected error occurred!', 'school-management');
		if (!isset($_POST['reference']) || !isset($_POST['amount'])) {
			wp_send_json_error($unexpected_error_message);
		}

		$invoice_id = absint($_POST['invoice_id']);

		// Checks if pending invoice exists.
		$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

		if (!$invoice) {
			wp_send_json_error(esc_html__('Invoice not found or already paid.', 'school-management'));
		}

		$school_id  = $invoice->school_id;
		$session_id = $invoice->session_id;

		$settings_paystack          = WLSM_M_Setting::get_settings_paystack($school_id);
		$school_paystack_public_key = $settings_paystack['paystack_public_key'];
		$school_paystack_secret_key = $settings_paystack['paystack_secret_key'];

		$payment_id          = sanitize_text_field($_POST['reference']);
		$amount_x_100        = WLSM_Config::sanitize_money($_POST['amount']);
		$paystack_public_key = $school_paystack_public_key;
		$paystack_secret_key = $school_paystack_secret_key;
		$url                 = "https://api.paystack.co/transaction/verify/$payment_id";

		$response = wp_remote_get(
			$url,
			array(
				'headers' => array('Authorization' => 'Bearer ' . $paystack_secret_key)
			)
		);

		if (is_wp_error($response)) {
			wp_send_json_error($response->get_error_message());
		}

		$data = json_decode($response['body']);

		if (!$data->status || ('success' !== $data->data->status)) {
			wp_send_json_error(esc_html__('Unable to verify the transaction.', 'school-management'));
		}

		global $wpdb;

		$payment_amount = ($data->data->amount) / 100;

		$partial_payment = $invoice->partial_payment;

		$due = $invoice->payable - $invoice->paid;

		if (($payment_amount <= 0) || ($payment_amount > $due) || (!$partial_payment && ($payment_amount != $due))) {
			wp_send_json_error($unexpected_error_message);
		}

		$transaction_id = $payment_id;

		try {
			$wpdb->query('BEGIN;');

			$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);

			// Payment data.
			$payment_data = array(
				'receipt_number'    => $receipt_number,
				'amount'            => $payment_amount,
				'transaction_id'    => $transaction_id,
				'payment_method'    => 'paystack',
				'invoice_label'     => $invoice->invoice_title,
				'invoice_payable'   => $invoice->payable,
				'student_record_id' => $invoice->student_id,
				'invoice_id'        => $invoice_id,
				'school_id'         => $school_id,
			);

			$payment_data['created_at'] = current_time('Y-m-d H:i:s');

			$success = $wpdb->insert(WLSM_PAYMENTS, $payment_data);

			$new_payment_id = $wpdb->insert_id;

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

			$wpdb->query('COMMIT;');

			if (isset($new_payment_id)) {
				// Notify for online fee submission.
				$data = array(
					'school_id'  => $school_id,
					'session_id' => $session_id,
					'payment_id' => $new_payment_id,
				);

				wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission', $data);
				wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission_to_parent', $data);
			}

			wp_send_json_success(array('message' => esc_html__('Payment made successfully.', 'school-management')));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($unexpected_error_message);
		}
	}

	// Check if payment is not done already.
	private static function check_paypal_txnid($txnid)
	{
		global $wpdb;

		$payment = $wpdb->get_row($wpdb->prepare('SELECT p.ID FROM ' . WLSM_PAYMENTS . ' as p WHERE p.payment_method = "paypal" AND p.transaction_id = %s', $txnid));

		return !$payment;
	}

	private static function save_paypal_payment($data)
	{
		$unexpected_error_message = esc_html__('An unexpected error occurred!', 'school-management');

		$payment_status   = $data['payment_status'];
		$payment_currency = $data['payment_currency'];
		$transaction_id   = $data['txn_id'];
		$receiver_email   = $data['receiver_email'];
		$payer_email      = $data['payer_email'];
		$invoice_id       = $data['invoice_id'];
		$payment_amount   = $data['payment_amount'];

		// Checks if pending invoice exists.
		$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

		if (!$invoice) {
			wp_send_json_error(esc_html__('Invoice not found or already paid.', 'school-management'));
		}

		$partial_payment = $invoice->partial_payment;

		$due = $invoice->payable - $invoice->paid;

		$school_id  = $invoice->school_id;
		$session_id = $invoice->session_id;

		$description = sprintf(
			/* translators: 1: invoice title, 2: invoice number */
			__('Invoice: %1$s (%2$s)', 'school-management'),
			esc_html(WLSM_M_Staff_Accountant::get_invoice_title_text($invoice->invoice_title)),
			esc_html($invoice->invoice_number)
		);

		global $wpdb;

		if (($payment_amount <= 0) || ($payment_amount > $due) || (!$partial_payment && ($payment_amount != $due))) {
			wp_send_json_error($unexpected_error_message);
		}

		try {
			$wpdb->query('BEGIN;');

			$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);

			// Payment data.
			$payment_data = array(
				'receipt_number'    => $receipt_number,
				'amount'            => $payment_amount,
				'transaction_id'    => $transaction_id,
				'payment_method'    => 'paypal',
				'invoice_label'     => $invoice->invoice_title,
				'invoice_payable'   => $invoice->payable,
				'student_record_id' => $invoice->student_id,
				'invoice_id'        => $invoice_id,
				'school_id'         => $school_id,
			);

			$payment_data['created_at'] = current_time('Y-m-d H:i:s');

			$success = $wpdb->insert(WLSM_PAYMENTS, $payment_data);

			$new_payment_id = $wpdb->insert_id;

			$buffer = ob_get_clean();
			if (!empty($buffer)) {
				throw new Exception($buffer);
			}

			if (false === $success) {
				throw new Exception($wpdb->last_error);
			}

			$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

			$wpdb->query('COMMIT;');

			if (isset($new_payment_id)) {
				// Notify for online fee submission.
				$data = array(
					'school_id'  => $school_id,
					'session_id' => $session_id,
					'payment_id' => $new_payment_id,
				);

				wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission', $data);
				wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission_to_parent', $data);
			}

			wp_send_json_success(array('message' => esc_html__('Payment made successfully.', 'school-management')));
		} catch (Exception $exception) {
			$wpdb->query('ROLLBACK;');
			wp_send_json_error($unexpected_error_message);
		}
	}

	public static function process_pesapal()
	{
		$unexpected_error_message = esc_html__('An unexpected error occurred!', 'school-management');

		$reference               = isset($_REQUEST['pesapal_merchant_reference']) ? sanitize_text_field($_REQUEST['pesapal_merchant_reference']) : 0;
		$transaction_tracking_id = isset($_REQUEST['pesapal_transaction_tracking_id']) ? sanitize_text_field($_REQUEST['pesapal_transaction_tracking_id']) : '';
		$notification_type       = isset($_REQUEST['pesapal_notification_type']) ? sanitize_text_field($_REQUEST['pesapal_notification_type']) : '';

		$invoice_payment = explode('-', $reference);
		$timestamp       = isset($invoice_payment[0]) ? absint($invoice_payment[0]) : 0;
		$invoice_id      = isset($invoice_payment[1]) ? absint($invoice_payment[1]) : 0;
		$payment_amount  = isset($invoice_payment[2]) ? $invoice_payment[2] : 0;

		if (empty($timestamp) || empty($invoice_id) || empty($payment_amount) || empty($transaction_tracking_id)) {
			die;
		}

		if (('CHANGE' !== $notification_type)) {
		?>
			<h1>
				<?php esc_html_e('Your transaction has been processed and is waiting for confirmation.', 'school-management'); ?>
			</h1>
<?php
			die;
		}

		// Checks if pending invoice exists.
		$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

		if (!$invoice) {
			wp_send_json_error(esc_html__('Invoice not found or already paid.', 'school-management'));
		}

		$partial_payment = $invoice->partial_payment;

		$due = $invoice->payable - $invoice->paid;

		if (($payment_amount <= 0) || ($payment_amount > $due) || (!$partial_payment && ($payment_amount != $due))) {
			wp_send_json_error($unexpected_error_message);
		}

		$school_id  = $invoice->school_id;
		$session_id = $invoice->session_id;

		$settings_pesapal               = WLSM_M_Setting::get_settings_pesapal($school_id);
		$school_pesapal_consumer_key    = $settings_pesapal['consumer_key'];
		$school_pesapal_consumer_secret = $settings_pesapal['consumer_secret'];
		$school_pesapal_status_url      = $settings_pesapal['status_url'];

		require_once WLSM_PLUGIN_DIR_PATH . 'includes/libs/PesapalOAuth.php';

		$token = $params = NULL;

		$consumer_key    = $school_pesapal_consumer_key;
		$consumer_secret = $school_pesapal_consumer_secret;

		$status_request_api = $school_pesapal_status_url;

		$signature_method = new PesapalOAuthSignatureMethod_HMAC_SHA1();

		$consumer = new PesapalOAuthConsumer($consumer_key, $consumer_secret);

		$request_status = PesapalOAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $status_request_api, $params);

		$request_status->set_parameter('pesapal_merchant_reference', $reference);
		$request_status->set_parameter('pesapal_transaction_tracking_id', $transaction_tracking_id);
		$request_status->sign_request($signature_method, $consumer, $token);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $request_status);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		if (defined('CURL_PROXY_REQUIRED') && (CURL_PROXY_REQUIRED == 'True')) {
			$proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, $proxy_tunnel_flag);
			curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			curl_setopt($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
		}

		$response = curl_exec($ch);

		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$raw_header  = substr($response, 0, $header_size - 4);
		$headerArray = explode("\r\n\r\n", $raw_header);
		$header      = $headerArray[count($headerArray) - 1];

		$elements = preg_split('/=/', substr($response, $header_size));
		$status   = $elements[1];

		curl_close($ch);

		if ('COMPLETED' === $status) {
			try {
				$wpdb->query('BEGIN;');

				$created_at = date('Y-m-d H:i:s', $timestamp);

				// Checks if payment already exists.
				$payment = $wpdb->get_row($wpdb->prepare('SELECT p.ID FROM ' . WLSM_PAYMENTS . ' as p WHERE p.school_id = %d AND p.created_at = %s AND p.invoice_id = %d', $school_id, $created_at, $invoice_id));

				if ($payment) {
					die;
				}

				$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);

				// Payment data.
				$payment_data = array(
					'receipt_number'    => $receipt_number,
					'amount'            => $payment_amount,
					'transaction_id'    => $transaction_tracking_id,
					'payment_method'    => 'pesapal',
					'invoice_label'     => $invoice->invoice_title,
					'invoice_payable'   => $invoice->payable,
					'student_record_id' => $invoice->student_id,
					'invoice_id'        => $invoice_id,
					'school_id'         => $school_id,
					'created_at'        => $created_at
				);

				$payment_data['created_at'] = current_time('Y-m-d H:i:s');

				$success = $wpdb->insert(WLSM_PAYMENTS, $payment_data);

				$new_payment_id = $wpdb->insert_id;

				$buffer = ob_get_clean();
				if (!empty($buffer)) {
					throw new Exception($buffer);
				}

				if (false === $success) {
					throw new Exception($wpdb->last_error);
				}

				$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

				$wpdb->query('COMMIT;');

				if (isset($new_payment_id)) {
					// Notify for online fee submission.
					$data = array(
						'school_id'  => $school_id,
						'session_id' => $session_id,
						'payment_id' => $new_payment_id,
					);

					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission', $data);
					wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission_to_parent', $data);
				}

				wp_send_json_success(array('message' => esc_html__('Payment made successfully.', 'school-management')));
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				wp_send_json_error($unexpected_error_message);
			}
		}

		exit;
	}

	public static function process_paytm()
	{
		if (!empty($_POST) && isset($_POST['ORDERID'])) {

			$current_page_url = ((isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$current_page_url = esc_url($current_page_url);

			$unexpected_error_message = esc_html__('An unexpected error occurred!', 'school-management');

			$error   = '';
			$success = '';

			require_once WLSM_PLUGIN_DIR_PATH . 'includes/libs/encdec_paytm.php';

			global $wpdb;

			try {
				$wpdb->query('BEGIN;');

				$order_id = sanitize_text_field($_POST['ORDERID']);

				$order_id_parts = explode('-', $order_id);

				if (2 !== count($order_id_parts)) {
					throw new Exception($unexpected_error_message);
				}

				$invoice_id = absint($order_id_parts[0]);

				// Checks if pending invoice exists.
				$invoice = WLSM_M_Staff_Accountant::get_student_pending_invoice($invoice_id);

				if (!$invoice) {
					throw new Exception(esc_html__('Invoice not found or already paid.', 'school-management'));
				}

				$school_id  = $invoice->school_id;
				$session_id = $invoice->session_id;

				$settings_paytm = WLSM_M_Setting::get_settings_paytm($school_id);

				$school_paytm_merchant_id      = $settings_paytm['merchant_id'];
				$school_paytm_merchant_key     = $settings_paytm['merchant_key'];
				$school_paytm_industry_type_id = $settings_paytm['industry_type_id'];
				$school_paytm_website          = $settings_paytm['website'];
				$school_paytm_mode             = $settings_paytm['mode'];

				$paytm_merchant_id      = $school_paytm_merchant_id;
				$paytm_merchant_key     = $school_paytm_merchant_key;
				$paytm_industry_type_id = $school_paytm_industry_type_id;
				$paytm_website          = $school_paytm_website;
				$paytm_mode             = $school_paytm_mode;

				if ('production' === $paytm_mode) {
					$transaction_status_url = 'https://securegw.paytm.in/order/status';
				} else {
					$transaction_status_url = 'https://securegw-stage.paytm.in/order/status';
				}

				if (WLSM_Paytm::verifychecksum_e($_POST, $paytm_merchant_key, 'TRUE' === $_POST['CHECKSUMHASH'])) {

					if ('01' === $_POST['RESPCODE']) {

						$payment_amount = WLSM_Config::sanitize_money($_POST['TXNAMOUNT']);

						$partial_payment = $invoice->partial_payment;

						$due = $invoice->payable - $invoice->paid;

						if (($payment_amount <= 0) || ($payment_amount > $due) || (!$partial_payment && ($payment_amount != $due))) {
							throw new Exception($unexpected_error_message);
						}

						$transaction_id = $order_id;

						$request_param_list = array('MID' => $paytm_merchant_id, 'ORDERID' => $_POST['ORDERID']);

						$checksum_status = WLSM_Paytm::getChecksumFromArray($request_param_list, $paytm_merchant_key);

						$request_param_list['CHECKSUMHASH'] = $checksum_status;

						$request_param_list = WLSM_Paytm::callNewAPI($transaction_status_url, $request_param_list);

						if ('TXN_SUCCESS' === $request_param_list['STATUS'] && $request_param_list['TXNAMOUNT'] === $_POST['TXNAMOUNT']) {

							$receipt_number = WLSM_M_Invoice::get_receipt_number($school_id);

							// Payment data.
							$payment_data = array(
								'receipt_number'    => $receipt_number,
								'amount'            => $payment_amount,
								'transaction_id'    => $transaction_id,
								'payment_method'    => 'paytm',
								'invoice_label'     => $invoice->invoice_title,
								'invoice_payable'   => $invoice->payable,
								'student_record_id' => $invoice->student_id,
								'invoice_id'        => $invoice_id,
								'school_id'         => $school_id,
							);

							$payment_data['created_at'] = current_time('Y-m-d H:i:s');

							$success = $wpdb->insert(WLSM_PAYMENTS, $payment_data);

							$new_payment_id = $wpdb->insert_id;

							$buffer = ob_get_clean();
							if (!empty($buffer)) {
								throw new Exception($buffer);
							}

							if (false === $success) {
								throw new Exception($wpdb->last_error);
							}

							$invoice_status = WLSM_M_Staff_Accountant::refresh_invoice_status($invoice_id);

							$wpdb->query('COMMIT;');

							if (isset($new_payment_id)) {
								// Notify for online fee submission.
								$data = array(
									'school_id'  => $school_id,
									'session_id' => $session_id,
									'payment_id' => $new_payment_id,
								);

								wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission', $data);
								wp_schedule_single_event(time() + 30, 'wlsm_notify_for_online_fee_submission_to_parent', $data);
							}

							$success = esc_html__('Payment made successfully.', 'school-management');
						} else {
							throw new Exception(esc_html__('It seems some issue in server to server communication. Please connect with administrator.', 'school-management'));
						}
					} else {
						throw new Exception($unexpected_error_message);
					}
				} else {
					throw new Exception(
						sprintf(
							/* translators: %s reason for transaction failed via paytm */
							esc_html__('The transaction has been failed for reason: %s', 'school-management'),
							esc_html($_POST['RESPMSG'])
						)
					);
				}
			} catch (Exception $exception) {
				$wpdb->query('ROLLBACK;');
				$error = $exception->getMessage();
			}

			wp_register_script('wlsm-paytm-status', '');
			wp_enqueue_script('wlsm-paytm-status');

			if (!empty($error)) {
				$js = 'window.alert("' . esc_attr($error) . '");';
			} else {
				$js = 'window.alert("' . esc_attr($success) . '");';
			}

			wp_add_inline_script('wlsm-paytm-status', $js);
		}
	}
}
