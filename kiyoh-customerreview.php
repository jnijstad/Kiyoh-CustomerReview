<?php
/**
 * @package kiyoh_customerreview
 */
/*
Plugin Name: Kiyoh Customerreview (Jeffrey Edit)
Plugin URI: http://www.interactivated.me/ (http://jnijstad.com)
Description: KiyOh.nl-gebruikers kunnen met deze plug-in automatisch klantbeoordelingen verzamelen, publiceren en delen in social media. Wanneer een klant een bestelling heeft gemaakt in uw Magento Shop, wordt een e-mail uitnodiging automatisch na een paar dagen verstuurd om u te beoordelen. De e-mail wordt uit naam en e-mailadres van uw organisatie gestuurd, zodat uw klanten u herkennen. De e-mail tekst is aanpasbaar en bevat een persoonlijke en veilige link naar de pagina om te beoordelen. Vanaf nu worden de beoordelingen dus automatisch verzameld, gepubliceerd en gedeeld. Dat is nog eens handig!
Version: 1.0.2
Author: kiyoh & jnijstad
Author URI: http://www.interactivated.me/webshop-modules/kiyoh-magento.html
License: GPLv2 or later
Text Domain: kiyoh_customerreview
Domain Path: /i18n/languages/
*/

define( 'KIYOH__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'KIYOH__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once( KIYOH__PLUGIN_DIR . 'functions.php' );
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if (is_plugin_active('woocommerce/woocommerce.php')) {
	$kiyoh_options = kiyoh_getOption();
	if ($kiyoh_options['enable'] == 'Yes') {
		$delay = time() + $kiyoh_options['delay'] * 24 * 3600;
		if ( !is_admin() ) {
			$url = trim(strip_tags($_SERVER['REQUEST_URI']));
			if ($kiyoh_options['event'] == 'Purchase') {
				$order_id = 0;
				if (count($_GET) >= 1) {
					if (strpos($url, 'order-received') == true && strpos($url, 'wc_order') == true) {
						require (ABSPATH . WPINC . '/pluggable.php');
                        $current_user = wp_get_current_user();
						if ($current_user) {
							if (isset($current_user->ID)) {
								$user_id = $current_user->ID;
							}
						}
						if (kiyoh_checkExculeGroups($kiyoh_options['excule_groups'], $user_id) == true) {
							if (count($_GET) == 1) {
								$url = explode('order-received/', $url);
								$url = $url[1];
								$url = explode("/", $url);
								$order_id = (int)$url[0];
							}else{
								$order_id = strip_tags($_GET['order-received']);
							}
							if ($order_id > 0) {
								require_once plugin_dir_path( dirname(__FILE__) ) . '/woocommerce/includes/abstracts/abstract-wc-order.php';
								require_once plugin_dir_path( dirname(__FILE__) ) . '/woocommerce/includes/class-wc-order.php';

								$order = new WC_Order($order_id);
								$email = $order->billing_email;
                                if(!$email) return;
								$optionsSendMail = array('option' => $kiyoh_options, 'email' => $email);
								kiyoh_createTableKiyoh();
								global $wpdb;
								$table_name = $wpdb->prefix . 'kiyoh';
                                if($kiyoh_options['send_method']=='kiyoh'){
                                    kiyoh_sendMail($optionsSendMail);
                                } else if (!kiyoh_checkSendedMail($table_name, $order_id, 'Purchase')) {
									kiyoh_insertRow($table_name, $order_id, 'Purchase');
									if ($kiyoh_options['delay'] == 0) {
										kiyoh_sendMail($optionsSendMail);
									}else{
										wp_schedule_single_event($delay, 'kiyoh_sendMail', array('optionsSendMail' => $optionsSendMail) );
									}
								}
							}
						}
					}
				}
			}
		}
		add_action("save_post", "check_kiyoh_review", 10, 1);
	}//if ($kiyoh_options['enable'] == 'Yes')
}

function check_kiyoh_review($post_id) {
	$kiyoh_options = kiyoh_getOption();
	$order = new WC_Order($post_id);
	$status = $order->get_status();
	$email = $order->billing_email;
    if(!$email) return;
	$status_old = trim(strip_tags($_POST['post_status']));
	$status_old = str_replace('wc-', '', $status_old);

	if ($status    == 'pending'	  || $status == 'processing' 	|| $status == 'on-hold'
		|| $status == 'completed' || $status == 'cancelled' 	|| $status == 'fraud'
		|| $status == 'refunded'  || $status == 'failed') {

		//check change status, check excule_groups
        $corect_event = false;
        if (is_array($kiyoh_options['event'])){
            if(in_array($status,$kiyoh_options['event'])){
                $corect_event = true;
            }
        } else {
            $corect_event = ($status == $kiyoh_options['event']);
        }
		if ($corect_event && $status_old != $status) {
			$user_id = trim(strip_tags($_POST['customer_user'] ));
			$user_id = (int)$user_id;
			if (kiyoh_checkExculeGroups($kiyoh_options['excule_groups'], $user_id) == true) {
				$optionsSendMail = array('option' => $kiyoh_options, 'email' => $email);

				kiyoh_createTableKiyoh();
				global $wpdb;
				$table_name = $wpdb->prefix . 'kiyoh';
                if($kiyoh_options['send_method']=='kiyoh'){
                    kiyoh_sendMail($optionsSendMail);
                } else	if (!kiyoh_checkSendedMail($table_name, $order->id, $status)) {
					kiyoh_insertRow($table_name, $order->id, $status);
					if ($kiyoh_options['delay'] == 0) {
						kiyoh_sendMail($optionsSendMail);
					}else{
                        $delay = time() + $kiyoh_options['delay'] * 24 * 3600;
						wp_schedule_single_event($delay, 'kiyoh_sendMail', array('optionsSendMail' => $optionsSendMail) );
					}
				}
			}
		}
	}
}

function enqueue_my_scripts()
{
	wp_enqueue_script('kiyoh-script', KIYOH__PLUGIN_URL . 'js/script.js');
}
add_action('admin_init', 'enqueue_my_scripts');

function kiyoh_register_settings() {
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_enable' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_link' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_email' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_delay' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_event' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_order_status' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_server' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_excule_groups' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_tmpl_en' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_tmpl_du' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_excule' );
	register_setting( 'kiyoh-settings-group', 'kiyoh_option_company_name' );
    register_setting( 'kiyoh-settings-group', 'kiyoh_option_send_method' );
    register_setting( 'kiyoh-settings-group', 'kiyoh_option_connector' );
    register_setting( 'kiyoh-settings-group', 'kiyoh_option_custom_user' );
    register_setting( 'kiyoh-settings-group', 'kiyoh_option_email_template_language' );
    //register_setting( 'kiyoh-settings-group', 'kiyoh_option_enable_microdata' );
    //register_setting( 'kiyoh-settings-group', 'kiyoh_option_company_id' );
}

function kiyoh_create_menu() {
	add_options_page('Kiyoh Customerreview Settings', 'Kiyoh Settings', 'administrator', __FILE__, 'kiyoh_settings_page','', 90);
	add_action( 'admin_init', 'kiyoh_register_settings' );
}
add_action('admin_menu', 'kiyoh_create_menu');

function kiyoh_settings_page() {
?>
<div class="wrap">
<?php if(is_plugin_active('woocommerce/woocommerce.php')) : ?>
	<h2>Kiyoh Customerreview Settings</h2>
	<?php if( isset($_GET['settings-updated']) ) { ?>
		<div id="message" class="updated">
			<p><strong><?php _e('Settings saved.') ?></strong></p>
		</div>
	<?php } ?>
	<form method="post" action="options.php">
		<?php settings_fields( 'kiyoh-settings-group' ); ?>
		<table class="form-table">
            <tr valign="top">
                <th scope="row">Module Version</th>
                <td>
                    <p>1.0.2</p>
                </td>
            </tr>
			<tr valign="top">
				<th scope="row">Enable</th>
				<td>
					<select name="kiyoh_option_enable">
						<option value="Yes" <?php selected(get_option('kiyoh_option_enable'), 'Yes'); ?>>Yes</option>
						<option value="No" <?php selected(get_option('kiyoh_option_enable'), 'No'); ?>>No</option>
					</select>
					<p>Recommended Value is Yes. On setting it to NO, module ll stop sending email invites to customers.</p>
				</td>
			</tr>
            <tr valign="top">
                <th scope="row">Email send method</th>
                <td>
                    <select name="kiyoh_option_send_method" required>
                        <option value="" <?php selected(get_option('kiyoh_option_send_method'), false); ?>></option>
                        <option value="my" <?php selected(get_option('kiyoh_option_send_method'), 'my'); ?>>Send emails from my server</option>
                        <option value="kiyoh" <?php selected(get_option('kiyoh_option_send_method'), 'kiyoh'); ?>>Send emails from Kiyoh server</option>
                    </select>
                </td>
            </tr>
			<tr valign="top" class="myserver">
				<th scope="row">Company Name</th>
				<td><input type="text" name="kiyoh_option_company_name" value="<?php echo get_option('kiyoh_option_company_name'); ?>" /></td>
			</tr>
			<tr valign="top" class="myserver">
				<th scope="row">Link rate</th>
				<td><input type="text" name="kiyoh_option_link" value="<?php echo get_option('kiyoh_option_link'); ?>" />
					<p>Enter here the link to the review (Easy Invite Link). Please contact Kiyoh and they provide you the correct link.</p>
				</td>
			</tr>
			<tr valign="top" class="myserver">
				<th scope="row">Sender Email</th>
				<td><input type="email" name="kiyoh_option_email" value="<?php echo get_option('kiyoh_option_email'); ?>" /></td>
			</tr>
			<tr valign="top">
				<th scope="row">Enter delay</th>
				<td><input type="text" name="kiyoh_option_delay" value="<?php echo get_option('kiyoh_option_delay'); ?>" />
					<p>Enter here the delay(number of days) after which you would like to send review invite email to your customer. This delay applies after customer event (to be selected at next option). You may enter 0 to send review invite email immediately after customer event. Cron should be configured for values>0</p>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Select Event</th>
				<td>
					<select name="kiyoh_option_event">
						<option value="" <?php selected(get_option('kiyoh_option_event'), ''); ?>></option>
						<option value="Purchase" <?php selected(get_option('kiyoh_option_event'), 'Purchase'); ?>>Purchase</option>
						<option value="Orderstatus" <?php selected(get_option('kiyoh_option_event'), 'Orderstatus'); ?>>Order status change</option>
					</select>
					<p>Enter here the event after which you would like to send review invite email to your customer.</p>
				</td>
			</tr>
			<tr valign="top" style="display: none;" id="status">
				<th scope="row">Order Status</th>
				<td>
					<select name="kiyoh_option_order_status[]" multiple>
                        <?php
                        $statuses = get_option('kiyoh_option_order_status');
                        ?>
						<option value="pending" <?php if(in_array('pending',$statuses)) echo " selected"; ?>>Pending Payment</option>
						<option value="processing" <?php if(in_array('processing',$statuses)) echo ' selected'; ?>>Processing</option>
						<option value="on-hold" <?php if(in_array('on-hold',$statuses)) echo ' selected'; ?>>On Hold</option>
						<option value="completed" <?php if(in_array('completed',$statuses)) echo ' selected'; ?>>Completed</option>
						<option value="cancelled" <?php if(in_array('cancelled',$statuses)) echo ' selected'; ?>>Cancelled</option>
						<option value="refunded" <?php if(in_array('refunded',$statuses)) echo ' selected'; ?>>Refunded</option>
						<option value="failed" <?php if(in_array('failed',$statuses)) echo ' selected'; ?>>Failed</option>
					</select>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row">Select Server</th>
				<td>
					<select name="kiyoh_option_server">
						<option value="kiyoh.nl" <?php selected(get_option('kiyoh_option_server'), 'kiyoh.nl'); ?>>Kiyoh Netherlands(kiyoh.nl)</option>
						<option value="kiyoh.com" <?php selected(get_option('kiyoh_option_server'), 'kiyoh.com'); ?>>Kiyoh International(kiyoh.com)</option>
					</select>
				</td>
			</tr>
            <tr valign="top" class="kiyohserver">
                <th scope="row">Enter Connector</th>
                <td>
                    <p><input type="text" name="kiyoh_option_connector" value="<?php echo get_option('kiyoh_option_connector'); ?>" required/></p>
                    <p>Enter here the Kiyoh Connector Code from your Kiyoh Account.</p>
                </td>
            </tr>
            <tr valign="top" class="kiyohserver">
                <th scope="row">Company Email</th>
                <td>
                    <p><input type="text" name="kiyoh_option_custom_user" value="<?php echo get_option('kiyoh_option_custom_user'); ?>" required/></p>
                    <p>Enter here your "company email address" as registered in your KiyOh account. Not the "user email address"! </p>
                </td>
            </tr>
            <tr valign="top" class="kiyohserver dependsonkiyohserver">
                <th scope="row">Language email template</th>
                <td>
                    <select name="kiyoh_option_email_template_language">
                        <option value="" <?php selected(get_option('kiyoh_option_email_template_language'), ''); ?>></option>
                        <?php $languges = array(
                            '' => '',
                            '1' => ('Dutch (BE)'),
                            '2' => ('French'),
                            '3' => ('German'),
                            '4' => ('English'),
                            '5' => ('Netherlands'),
                            '6' => ('Danish'),
                            '7' => ('Hungarian'),
                            '8' => ('Bulgarian'),
                            '9' => ('Romanian'),
                            '10' => ('Croatian'),
                            '11' => ('Japanese'),
                            '12' => ('Spanish'),
                            '13' => ('Italian'),
                            '14' => ('Portuguese'),
                            '15' => ('Turkish'),
                            '16' => ('Norwegian'),
                            '17' => ('Swedish'),
                            '18' => ('Finnish'),
                            '20' => ('Brazilian Portuguese'),
                            '21' => ('Polish'),
                            '22' => ('Slovenian'),
                            '23' => ('Chinese'),
                            '24' => ('Russian'),
                            '25' => ('Greek'),
                            '26' => ('Czech'),
                            '29' => ('Estonian'),
                            '31' => ('Lithuanian'),
                            '33' => ('Latvian'),
                            '35' => ('Slovak')
                        );
                        foreach ($languges as $lang_id => $languge):?>
                            <option value="<?php echo $lang_id;?>" <?php selected(get_option('kiyoh_option_email_template_language'), $lang_id); ?>><?php echo $languge;?></option>
                        <?php endforeach;?>
                    </select>
                </td>
            </tr>
            <!--<tr valign="top" class="kiyohserver">
                <th scope="row">Enable Microdata functionality</th>
                <td>
                    <select name="kiyoh_option_enable_microdata">
                        <option value="Yes" <?php /*selected(get_option('kiyoh_option_enable_microdata'), 'Yes'); */?>>Yes</option>
                        <option value="No" <?php /*selected(get_option('kiyoh_option_enable_microdata'), 'No'); */?>>No</option>
                    </select>
                    <p>Enable a microdata rating widget.</p>
                </td>
            </tr>-->
            <!--<tr valign="top" class="kiyohserver">
                <th scope="row">Company Id</th>
                <td>
                    <p><input type="text" name="kiyoh_option_company_id" value="<?php /*echo get_option('kiyoh_option_company_id'); */?>"/></p>
                    <p>Enter here your "Company Id" as registered in your KiyOh account. </p>
                </td>
            </tr>-->
			<?php if (kiyoh_checkExistsTable('groups_group') && is_plugin_active('groups/groups.php')) : ?>
			<tr valign="top">
				<th scope="row">Exclude customer groups</th>
				<td><?php kiyoh_selectExculeGroups(); ?></td>
			</tr>
			<?php endif; ?>
			<tr valign="top" class="myserver">
				<th scope="row">Email template (English)</th>
				<td>
					<?php wp_editor(str_replace("\n", '<br />', get_option('kiyoh_option_tmpl_en')), 'kiyoh_option_tmpl_en', array( 'media_buttons' => true,'quicktags' => false ) ); ?>
				</td>
			</tr>
			<tr valign="top" class="myserver">
				<th scope="row">Email template (Dutch)</th>
				<td><?php wp_editor(str_replace("\n", '<br />', get_option('kiyoh_option_tmpl_du')), 'kiyoh_option_tmpl_du', array( 'media_buttons' => true,'quicktags' => false, 'editor_css' => true ) ); ?></td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
<?php else: ?>
	<h2>You need install and activate WooCommerce plugin</h2>
<?php endif; ?>
</div>
<?php
}
//widget kiyoh_review
require_once KIYOH__PLUGIN_DIR . 'widget.php';
function register_kiyoh_review() {
    register_widget( 'kiyoh_review' );
}
add_action( 'widgets_init', 'register_kiyoh_review' );
