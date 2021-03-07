<?php
defined( 'ABSPATH' ) || die();

// General settings.
$settings_general                   = WLSM_M_Setting::get_settings_general( $school_id );
$school_logo                        = $settings_general['school_logo'];
$school_signature                   = $settings_general['school_signature'];
$school_student_logout_redirect_url = $settings_general['student_logout_redirect_url'];
$school_hide_transport              = $settings_general['hide_transport'];
$school_hide_library                = $settings_general['hide_library'];
?>
<div class="tab-pane fade show active" id="wlsm-school-general" role="tabpanel" aria-labelledby="wlsm-school-general-tab">
	<div class="row">
		<div class="col-md-9">
			<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" id="wlsm-save-school-general-settings-form">
				<?php
				$nonce_action = 'save-school-general-settings';
				$nonce        = wp_create_nonce( $nonce_action );
				?>
				<input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">

				<input type="hidden" name="action" value="wlsm-save-school-general-settings">

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_school_logo" class="wlsm-font-bold mt-1">
							<?php esc_html_e( 'Upload School Logo', 'school-management' ); ?>:
						</label>
					</div>
					<div class="col-md-9">
						<div class="wlsm-school-logo-box">
							<div class="wlsm-school-logo-section">
								<div class="form-group">
									<div class="custom-file mb-3">
										<input type="file" class="custom-file-input" id="wlsm_school_logo" name="school_logo">
										<label class="custom-file-label" for="wlsm_school_logo">
											<?php esc_html_e( 'Choose File', 'school-management' ); ?>
										</label>
									</div>
								</div>

								<?php if ( ! empty ( $school_logo ) ) { ?>
								<img src="<?php echo esc_url( wp_get_attachment_url( $school_logo ) ); ?>" class="img-responsive wlsm-school-logo">

								<div class="form-group">
									<input class="form-check-input mt-2" type="checkbox" name="remove_school_logo" id="wlsm_school_remove_logo" value="1">
									<label class="ml-4 mb-1 mt-1 form-check-label wlsm-font-bold text-danger" for="wlsm_school_remove_logo">
										<?php esc_html_e( 'Remove School Logo?', 'school-management' ); ?>
									</label>
								</div>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_school_signature" class="wlsm-font-bold mt-1">
							<?php esc_html_e( 'Upload School signature', 'school-management' ); ?>:
						</label>
					</div>
					<div class="col-md-9">
						<div class="wlsm-school-signature-box">
							<div class="wlsm-school-signature-section">
								<div class="form-group">
									<div class="custom-file mb-3">
										<input type="file" class="custom-file-input" id="wlsm_school_signature" name="school_signature">
										<label class="custom-file-label" for="wlsm_school_signature">
											<?php esc_html_e( 'Choose File', 'school-management' ); ?>
										</label>
									</div>
								</div>

								<?php if ( ! empty ( $school_signature ) ) { ?>
								<img src="<?php echo esc_url( wp_get_attachment_url( $school_signature ) ); ?>" class="img-responsive wlsm-school-signature">
								<?php } ?>
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_student_logout_redirect_url" class="wlsm-font-bold"><?php esc_html_e( 'Redirect URL after Logout', 'school-management' ); ?>:</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<input name="student_logout_redirect_url" type="text" id="wlsm_student_logout_redirect_url" value="<?php echo esc_attr( $school_student_logout_redirect_url ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Redirect URL after Logout', 'school-management' ); ?>">
							<p class="description">
								<?php esc_html_e( 'Enter URL where to redirect the student after logout. Leave blank for same page URL.', 'school-management' ); ?>
							</p>
						</div>
					</div>
				</div>

				<div class="row mt-2">
					<div class="col-md-12 text-center">
						<button type="submit" class="btn btn-primary" id="wlsm-save-school-general-settings-btn">
							<i class="fas fa-save"></i>&nbsp;
							<?php esc_html_e( 'Save', 'school-management' ); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>

</div>
