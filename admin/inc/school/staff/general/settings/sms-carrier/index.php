<?php
defined('ABSPATH') || die();

// SMS settings.
$settings_sms       = WLSM_M_Setting::get_settings_sms($school_id);
$school_sms_carrier = $settings_sms['carrier'];

// SMS carriers.
$sms_carriers = WLSM_SMS::sms_carriers();

// SMS Striker settings.
$settings_smsstriker         = WLSM_M_Setting::get_settings_smsstriker($school_id);
$school_smsstriker_username  = $settings_smsstriker['username'];
$school_smsstriker_password  = $settings_smsstriker['password'];
$school_smsstriker_sender_id = $settings_smsstriker['sender_id'];

// MsgClub settings.
$settings_msgclub                = WLSM_M_Setting::get_settings_msgclub($school_id);
$school_msgclub_auth_key         = $settings_msgclub['auth_key'];
$school_msgclub_sender_id        = $settings_msgclub['sender_id'];
$school_msgclub_route_id         = $settings_msgclub['route_id'];
$school_msgclub_sms_content_type = $settings_msgclub['sms_content_type'];

// Point SMS settings.
$settings_pointsms         = WLSM_M_Setting::get_settings_pointsms($school_id);
$school_pointsms_username  = $settings_pointsms['username'];
$school_pointsms_sender_id = $settings_pointsms['sender_id'];
$school_pointsms_channel   = $settings_pointsms['channel'];
$school_pointsms_route     = $settings_pointsms['route'];
$school_pointsms_peid      = $settings_pointsms['peid'];

// vinuthan SMS settings.
$settings_vinuthansms = WLSM_M_Setting::get_settings_vinuthan($school_id);
$school_vinuthansms_username = $settings_vinuthansms['username'];
$school_vinuthansms_sender_id = $settings_vinuthansms['sender_id'];
$school_vinuthansms_channel = $settings_vinuthansms['channel'];
$school_vinuthansms_route = $settings_vinuthansms['route'];

// pob SMS settings.
$settings_pob         = WLSM_M_Setting::get_settings_pob($school_id);
$school_pob_username  = $settings_pob['username'];
$school_pob_password  = $settings_pob['password'];
$school_pob_sender_id = $settings_pob['sender_id'];


// Nexmo settings.
$settings_nexmo          = WLSM_M_Setting::get_settings_nexmo($school_id);
$school_nexmo_api_key    = $settings_nexmo['api_key'];
$school_nexmo_api_secret = $settings_nexmo['api_secret'];
$school_nexmo_from       = $settings_nexmo['from'];

// Twilio settings.
$settings_twilio     = WLSM_M_Setting::get_settings_twilio($school_id);
$school_twilio_sid   = $settings_twilio['sid'];
$school_twilio_token = $settings_twilio['token'];
$school_twilio_from  = $settings_twilio['from'];

// Msg91 settings.
$settings_msg91       = WLSM_M_Setting::get_settings_msg91($school_id);
$school_msg91_authkey = $settings_msg91['authkey'];
$school_msg91_route   = $settings_msg91['route'];
$school_msg91_sender  = $settings_msg91['sender'];
$school_msg91_country = $settings_msg91['country'];

// Textlocal settings.
$settings_textlocal       = WLSM_M_Setting::get_settings_textlocal($school_id);
$school_textlocal_api_key = $settings_textlocal['api_key'];
$school_textlocal_sender  = $settings_textlocal['sender'];

// EBulkSMS settings.
$settings_ebulksms        = WLSM_M_Setting::get_settings_ebulksms($school_id);
$school_ebulksms_username = $settings_ebulksms['username'];
$school_ebulksms_api_key  = $settings_ebulksms['api_key'];
$school_ebulksms_sender   = $settings_ebulksms['sender'];
?>
<div class="tab-pane fade" id="wlsm-school-sms-carrier" role="tabpanel" aria-labelledby="wlsm-school-sms-carrier-tab">

	<div class="row">
		<div class="col-md-9">
			<form action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" method="post" id="wlsm-save-school-sms-carrier-settings-form">
				<?php
				$nonce_action = 'save-school-sms-carrier-settings';
				$nonce        = wp_create_nonce($nonce_action);
				?>
				<input type="hidden" name="<?php echo esc_attr($nonce_action); ?>" value="<?php echo esc_attr($nonce); ?>">

				<input type="hidden" name="action" value="wlsm-save-school-sms-carrier-settings">

				<div class="row">
					<div class="col-md-3">
						<label for="wlsm_sms_carrier" class="wlsm-font-bold">
							<?php esc_html_e('SMS Carrier', 'school-management'); ?>:
						</label>
					</div>
					<div class="col-md-9">
						<div class="form-group">
							<select name="sms_carrier" id="wlsm_sms_carrier" class="form-control">
								<?php foreach ($sms_carriers as $key => $sms_carrier) { ?>
									<option <?php selected($key, $school_sms_carrier, true); ?> value="<?php echo esc_attr($key); ?>"><?php echo esc_attr($sms_carrier); ?></option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_smsstriker">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_smsstriker_sender_id" class="wlsm-font-bold"><?php esc_html_e('Sender ID', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="smsstriker_sender_id" type="text" id="wlsm_smsstriker_sender_id" value="<?php echo esc_attr($school_smsstriker_sender_id); ?>" class="form-control" placeholder="<?php esc_attr_e('SMSStriker Sender ID', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_smsstriker">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_smsstriker_username" class="wlsm-font-bold"><?php esc_html_e('Username', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="smsstriker_username" type="text" id="wlsm_smsstriker_username" value="<?php echo esc_attr($school_smsstriker_username); ?>" class="form-control" placeholder="<?php esc_attr_e('SMSStriker Username', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_smsstriker">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_smsstriker_password" class="wlsm-font-bold"><?php esc_html_e('Password', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="smsstriker_password" type="password" id="wlsm_smsstriker_password" class="form-control" placeholder="<?php esc_attr_e('SMSStriker Password', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_msgclub">
					<div class="row">
						<div class="col-md-3">
							<label class="wlsm-font-bold"><?php esc_html_e('SMS Package', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<a class="wlsm-font-bold" target="_blank" href="https://infigosoftware.in/bulk-sms-service/">
									<?php esc_html_e('Click for SMS Package Features and Pricing', 'school-management'); ?>
								</a>
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_msgclub">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_msgclub_auth_key" class="wlsm-font-bold"><?php esc_html_e('Auth Key', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="msgclub_auth_key" type="text" id="wlsm_msgclub_auth_key" value="<?php echo esc_attr($school_msgclub_auth_key); ?>" class="form-control" placeholder="<?php esc_attr_e('Infigo Msg Auth Key', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_msgclub">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_msgclub_sender_id" class="wlsm-font-bold"><?php esc_html_e('Sender ID', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="msgclub_sender_id" type="text" id="wlsm_msgclub_sender_id" value="<?php echo esc_attr($school_msgclub_sender_id); ?>" class="form-control" placeholder="<?php esc_attr_e('Infigo Msg Sender ID', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_msgclub">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_msgclub_route_id" class="wlsm-font-bold"><?php esc_html_e('Route ID', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="msgclub_route_id" type="text" id="wlsm_msgclub_route_id" value="<?php echo esc_attr($school_msgclub_route_id); ?>" class="form-control" placeholder="<?php esc_attr_e('Infigo Msg Route ID', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_msgclub">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_msgclub_sms_content_type" class="wlsm-font-bold"><?php esc_html_e('SMS Content Type', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="msgclub_sms_content_type" type="text" id="wlsm_msgclub_sms_content_type" value="<?php echo esc_attr($school_msgclub_sms_content_type); ?>" class="form-control" placeholder="<?php esc_attr_e('Infigo Msg SMS Content Type', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_pointsms">
					<div class="row">
						<div class="col-md-3">
							<label class="wlsm-font-bold"><?php esc_html_e('SMS Package', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<a class="wlsm-font-bold" target="_blank" href="https://infigosoftware.in/bulk-sms-service/">
									<?php esc_html_e('Click for SMS Package Features and Pricing', 'school-management'); ?>
								</a>
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_pointsms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_pointsms_sender_id" class="wlsm-font-bold"><?php esc_html_e('Sender ID', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="pointsms_sender_id" type="text" id="wlsm_pointsms_sender_id" value="<?php echo esc_attr($school_pointsms_sender_id); ?>" class="form-control" placeholder="<?php esc_attr_e('Infigo Point Sender ID', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_pointsms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_pointsms_username" class="wlsm-font-bold"><?php esc_html_e('Username', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="pointsms_username" type="text" id="wlsm_pointsms_username" value="<?php echo esc_attr($school_pointsms_username); ?>" class="form-control" placeholder="<?php esc_attr_e('Infigo Point Username', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_pointsms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_pointsms_password" class="wlsm-font-bold"><?php esc_html_e('Password', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="pointsms_password" type="password" id="wlsm_pointsms_password" class="form-control" placeholder="<?php esc_attr_e('Infigo Point Password', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_pointsms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_pointsms_channel" class="wlsm-font-bold"><?php esc_html_e('Channel', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="pointsms_channel" type="text" id="wlsm_pointsms_channel" value="<?php echo esc_attr($school_pointsms_channel); ?>" class="form-control" placeholder="<?php esc_attr_e('Infigo Point Channel: Trans or Promo', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_pointsms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_pointsms_route" class="wlsm-font-bold"><?php esc_html_e('Route', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="pointsms_route" type="text" id="wlsm_pointsms_route" value="<?php echo esc_attr($school_pointsms_route); ?>" class="form-control" placeholder="<?php esc_attr_e('Infigo Point Route', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>
				<div class="wlsm_sms_carrier wlsm_pointsms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_pointsms_peid" class="wlsm-font-bold"><?php esc_html_e('Peid', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="pointsms_peid" type="text" id="wlsm_pointsms_peid" value="<?php echo esc_attr($school_pointsms_peid); ?>" class="form-control" placeholder="<?php esc_attr_e('Infigo Point peid', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_vinuthansms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_vinuthansms_sender_id" class="wlsm-font-bold"><?php esc_html_e('Sender ID', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="vinuthansms_sender_id" type="text" id="wlsm_vinuthansms_sender_id" value="<?php echo esc_attr($school_vinuthansms_sender_id); ?>" class="form-control" placeholder="<?php esc_attr_e('Vinuthan sms Sender ID', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_vinuthansms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_vinuthansms_username" class="wlsm-font-bold"><?php esc_html_e('Authkey', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="vinuthansms_username" type="text" id="wlsm_vinuthansms_username" value="<?php echo esc_attr($school_vinuthansms_username); ?>" class="form-control" placeholder="<?php esc_attr_e('Vinuthan sms Authkey', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>


				<div class="wlsm_sms_carrier wlsm_vinuthansms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_vinuthansms_channel" class="wlsm-font-bold"><?php esc_html_e('Type', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="vinuthansms_channel" type="text" id="wlsm_vinuthansms_channel" value="<?php echo esc_attr($school_vinuthansms_channel); ?>" class="form-control" placeholder="<?php esc_attr_e('Vinuthan sms Type', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_vinuthansms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_vinuthansms_route" class="wlsm-font-bold"><?php esc_html_e('Route', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="vinuthansms_route" type="text" id="wlsm_vinuthansms_route" value="<?php echo esc_attr($school_vinuthansms_route); ?>" class="form-control" placeholder="<?php esc_attr_e('Vinuthan sms Route', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>
				<div class="wlsm_sms_carrier wlsm_pob">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_pob_username" class="wlsm-font-bold"><?php esc_html_e('Username', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="pob_username" type="text" id="wlsm_pob_username" value="<?php echo esc_attr($school_pob_username); ?>" class="form-control" placeholder="<?php esc_attr_e('Pob Talk Username', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_pob">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_pob_password" class="wlsm-font-bold"><?php esc_html_e('Password', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="pob_password" type="password" id="wlsm_pob_password" class="form-control" placeholder="<?php esc_attr_e('Pob Talk Password', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_pob">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_pob_sender_id" class="wlsm-font-bold"><?php esc_html_e('Sender ID', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="pob_sender_id" type="text" id="wlsm_pob_sender_id" value="<?php echo esc_attr($school_pob_sender_id); ?>" class="form-control" placeholder="<?php esc_attr_e('Pob Talk Sender ID', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>



				<div class="wlsm_sms_carrier wlsm_nexmo">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_nexmo_api_key" class="wlsm-font-bold"><?php esc_html_e('API Key', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="nexmo_api_key" type="text" id="wlsm_nexmo_api_key" value="<?php echo esc_attr($school_nexmo_api_key); ?>" class="form-control" placeholder="<?php esc_attr_e('Nexmo API Key', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_nexmo">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_nexmo_api_secret" class="wlsm-font-bold"><?php esc_html_e('API Secret', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="nexmo_api_secret" type="text" id="wlsm_nexmo_api_secret" value="<?php echo esc_attr($school_nexmo_api_secret); ?>" class="form-control" placeholder="<?php esc_attr_e('Nexmo API Secret', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_nexmo">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_nexmo_from" class="wlsm-font-bold"><?php esc_html_e('From', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="nexmo_from" type="text" id="wlsm_nexmo_from" value="<?php echo esc_attr($school_nexmo_from); ?>" class="form-control" placeholder="<?php esc_attr_e('Nexmo From', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_twilio">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_twilio_sid" class="wlsm-font-bold"><?php esc_html_e('SID', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="twilio_sid" type="text" id="wlsm_twilio_sid" value="<?php echo esc_attr($school_twilio_sid); ?>" class="form-control" placeholder="<?php esc_attr_e('Twilio Account SID', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_twilio">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_twilio_token" class="wlsm-font-bold"><?php esc_html_e('Auth Token', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="twilio_token" type="text" id="wlsm_twilio_token" value="<?php echo esc_attr($school_twilio_token); ?>" class="form-control" placeholder="<?php esc_attr_e('Twilio Auth Token', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_twilio">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_twilio_from" class="wlsm-font-bold"><?php esc_html_e('From', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="twilio_from" type="text" id="wlsm_twilio_from" value="<?php echo esc_attr($school_twilio_from); ?>" class="form-control" placeholder="<?php esc_attr_e('A Twilio phone number you purchased at twilio.com/console', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_msg91">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_msg91_authkey" class="wlsm-font-bold"><?php esc_html_e('Auth Key', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="msg91_authkey" type="text" id="wlsm_msg91_authkey" value="<?php echo esc_attr($school_msg91_authkey); ?>" class="form-control" placeholder="<?php esc_attr_e('Msg91 Auth Key', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_msg91">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_msg91_route" class="wlsm-font-bold"><?php esc_html_e('Route', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="msg91_route" type="text" id="wlsm_msg91_route" value="<?php echo esc_attr($school_msg91_route); ?>" class="form-control" placeholder="<?php esc_attr_e('Msg91 Route', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_msg91">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_msg91_sender" class="wlsm-font-bold"><?php esc_html_e('Sender', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="msg91_sender" type="text" id="wlsm_msg91_sender" value="<?php echo esc_attr($school_msg91_sender); ?>" class="form-control" placeholder="<?php esc_attr_e('Msg91 Sender', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_msg91">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_msg91_country" class="wlsm-font-bold"><?php esc_html_e('Country Code', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="msg91_country" type="text" id="wlsm_msg91_country" value="<?php echo esc_attr($school_msg91_country); ?>" class="form-control" placeholder="<?php esc_attr_e('Msg91 Country Code', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_textlocal">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_textlocal_api_key" class="wlsm-font-bold"><?php esc_html_e('API Key', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="textlocal_api_key" type="text" id="wlsm_textlocal_api_key" value="<?php echo esc_attr($school_textlocal_api_key); ?>" class="form-control" placeholder="<?php esc_attr_e('Textlocal API Key', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_textlocal">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_textlocal_sender" class="wlsm-font-bold"><?php esc_html_e('Sender', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="textlocal_sender" type="text" id="wlsm_textlocal_sender" value="<?php echo esc_attr($school_textlocal_sender); ?>" class="form-control" placeholder="<?php esc_attr_e('Textlocal Sender', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_ebulksms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_ebulksms_username" class="wlsm-font-bold"><?php esc_html_e('Username', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="ebulksms_username" type="text" id="wlsm_ebulksms_username" value="<?php echo esc_attr($school_ebulksms_username); ?>" class="form-control" placeholder="<?php esc_attr_e('EBulkSMS Username', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_ebulksms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_ebulksms_api_key" class="wlsm-font-bold"><?php esc_html_e('API Key', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="ebulksms_api_key" type="text" id="wlsm_ebulksms_api_key" value="<?php echo esc_attr($school_ebulksms_api_key); ?>" class="form-control" placeholder="<?php esc_attr_e('EBulkSMS API Key', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="wlsm_sms_carrier wlsm_ebulksms">
					<div class="row">
						<div class="col-md-3">
							<label for="wlsm_ebulksms_sender" class="wlsm-font-bold"><?php esc_html_e('Sender', 'school-management'); ?>:</label>
						</div>
						<div class="col-md-9">
							<div class="form-group">
								<input name="ebulksms_sender" type="text" id="wlsm_ebulksms_sender" value="<?php echo esc_attr($school_ebulksms_sender); ?>" class="form-control" placeholder="<?php esc_attr_e('EBulkSMS Sender', 'school-management'); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-md-12 text-center">
						<button type="submit" class="btn btn-primary" id="wlsm-save-school-sms-carrier-settings-btn">
							<i class="fas fa-save"></i>&nbsp;
							<?php esc_html_e('Save', 'school-management'); ?>
						</button>
					</div>
				</div>
			</form>
		</div>
	</div>

</div>