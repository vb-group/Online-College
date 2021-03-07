<?php
defined( 'ABSPATH' ) || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/WLSM_M_Setting.php';

// Zoom settings.
$settings_zoom            = WLSM_M_Setting::get_settings_zoom( $school_id );
$settings_zoom_api_key    = $settings_zoom['api_key'];
$settings_zoom_api_secret = $settings_zoom['api_secret'];
?>
<div class="tab-pane fade" id="wlsm-school-zoom" role="tabpanel" aria-labelledby="wlsm-school-zoom-tab">

	<div class="row">
		<div class="col-md-7">
			<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" id="wlsm-save-school-zoom-settings-form">
				<?php
				$nonce_action = 'save-school-zoom-settings';
				$nonce        = wp_create_nonce( $nonce_action );
				?>
				<input type="hidden" name="<?php echo esc_attr( $nonce_action ); ?>" value="<?php echo esc_attr( $nonce ); ?>">

				<input type="hidden" name="action" value="wlsm-save-school-zoom-settings">

				<div class="wlsm_zoom">
					<div class="row">
						<div class="col-md-4">
							<label for="wlsm_zoom_api_key" class="wlsm-font-bold"><?php esc_html_e( 'Zoom API Key', 'school-management' ); ?>:</label>
						</div>
						<div class="col-md-8">
							<div class="form-group">
								<input name="zoom_api_key" type="text" id="wlsm_zoom_api_key" value="<?php echo esc_attr( $settings_zoom_api_key ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Zoom API Key (JWT)', 'school-management' ); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_zoom">
					<div class="row">
						<div class="col-md-4">
							<label for="wlsm_zoom_api_secret" class="wlsm-font-bold"><?php esc_html_e( 'Zoom API Secret', 'school-management' ); ?>:</label>
						</div>
						<div class="col-md-8">
							<div class="form-group">
								<input name="zoom_api_secret" type="text" id="wlsm_zoom_api_secret" value="<?php echo esc_attr( $settings_zoom_api_secret ); ?>" class="form-control" placeholder="<?php esc_attr_e( 'Zoom API Secret (JWT)', 'school-management' ); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12 text-center">
						<button type="submit" class="btn btn-primary" id="wlsm-save-school-zoom-settings-btn">
							<i class="fas fa-save"></i>&nbsp;
							<?php esc_html_e( 'Save', 'school-management' ); ?>
						</button>
					</div>
				</div>
			</form>
		</div>

		<div class="col-md-5">
			<h5><?php esc_html_e( 'Accessing your Zoom API Key & Secret', 'school-management' ); ?></h5>
			<p>
				<?php esc_html_e( 'To access the API Key and Secret, Create a JWT App on the Marketplace. After providing basic information about your app, locate your API Key and Secret in the App Credentials page.', 'school-management' ); ?>
				<a target="_blank" href="https://marketplace.zoom.us/docs/guides/auth/jwt"><?php esc_html_e( 'Click here for more information', 'school-management' ); ?></a>
			</p>
		</div>
	</div>

</div>
