<?php
/*
Plugin Name: Restrict Content Pro Slack Notifications
Plugin URL: http://creativeg.gr
Description: Restrict Content Pro Slack Notifications
Version: 1.0.0
Author: creativeG
Author URI: http://creativeg.gr
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function rcp_slack_activation() {
    if (!is_plugin_active('restrict-content-pro/restrict-content-pro.php')) {
        // display notice
        add_action('admin_notices', 'rcp_slack_admin_notices');
        return;
    }
}
add_action('admin_init', 'rcp_slack_activation');

function rcp_slack_admin_notices() {
    if (!is_plugin_active('restrict-content-pro/restrict-content-pro.php')) {
        echo '<div class="error"><p>You must install & activate <a href="https://wordpress.org/plugins/restrict-content/" title="Restrict Content Pro" target="_blank"><strong>Restrict Content Pro</strong></a> to use <strong>Restrict Content Pro Slack Notifications</strong></p></div>';
    }
}

function rcp_slack_misc_settings($rcp_options) {
    ?>
    <table class="form-table">
    <tbody>
    <tr valign="top">
    	<th colspan="2">
    		<label style="text-decoration: underline;font-size:18px;">Slack notification settings</label>	
    	</th>
    </tr>
	<tr valign="top">
		<th>
			<label for="rcp_settings[enable_slack_notification]"><?php _e('Enable Slack Notification');?></label>
		</th>
		<td>
			<input type="checkbox" id="rcp_settings[enable_slack_notification]" class="regular-text" <?php if(isset($rcp_options['enable_slack_notification']) && $rcp_options['enable_slack_notification'] == 1)echo 'checked="checked"'; ?> name="rcp_settings[enable_slack_notification]" value="1">
			<span class="description"><?php _e('Check this to turn on Slack notifications');?></span>
		</td>
	</tr>
	<tr valign="top">
		<th>
			<label for="rcp_settings[bot_name]"><?php _e('Bot name');?></label>
		</th>
		<td>
			<input type="text" id="rcp_settings[bot_name]" class="regular-text" name="rcp_settings[bot_name]" value="<?php echo isset($rcp_options['bot_name']) ? esc_attr($rcp_options['bot_name']) : ''; ?>">
			<p class="description"><?php _e('Enter the name of your Bot, the default is: ' . get_bloginfo('name') . ' Subscription Bot');?></p>
		</td>
	</tr>
	<tr valign="top">
		<th>
			<label for="rcp_settings[bot_icon]"><?php _e('Bot Icon');?></label>
		</th>
		<td>
			<input type="text" id="rcp_settings[bot_icon]" class="regular-text" name="rcp_settings[bot_icon]" value="<?php echo isset($rcp_options['bot_icon']) ? esc_attr($rcp_options['bot_icon']) : ''; ?>">
			<p class="description"><?php _e('Enter the emoji icon for your bot. Click <a href="http://emoji-cheat-sheet.com" target="_blank">here</a> to view the list of available emoji icon. You are to enter only a single emoji icon. The default is :moneybag:');?></p>
		</td>
	</tr>
	<tr valign="top">
		<th>
			<label for="rcp_settings[channel_name]"><?php _e('Channel Name');?></label>
		</th>
		<td>
			<input type="text" id="rcp_settings[channel_name]" class="regular-text" name="rcp_settings[channel_name]" value="<?php echo isset($rcp_options['channel_name']) ? esc_attr($rcp_options['channel_name']) : ''; ?>">
			<p class="description"><?php _e('Enter the name of the Channel notifications should be sent to e.g. #rcp_sales');?></p>
		</td>
	</tr>
	<tr valign="top">
		<th>
			<label for="rcp_settings[webhook_url]"><?php _e('Webhook Url');?></label>
		</th>
		<td>
			<input type="text" id="rcp_settings[webhook_url]" class="regular-text" name="rcp_settings[webhook_url]" value="<?php echo isset($rcp_options['webhook_url']) ? esc_attr($rcp_options['webhook_url']) : ''; ?>">
			<p class="description"><?php _e('Enter the url of the webhook created for the channel above. This can be created <a href="https://my.slack.com/services/new/incoming-webhook/" target="_blank">here</a>');?></p>
		</td>
	</tr>
	</tbody>
	</table>
	<?php
}
add_action('rcp_misc_settings', 'rcp_slack_misc_settings');

function rcp_slack_set_status($new_status, $user_id, $old_status, $member)
{
    global $rcp_options;
    $enable_slack  = isset($rcp_options['enable_slack_notification']) ? $rcp_options['enable_slack_notification'] : '';
    $slack_channel = isset($rcp_options['channel_name']) ? $rcp_options['channel_name'] : '';
    $webhook_url   = isset($rcp_options['webhook_url']) ? $rcp_options['webhook_url'] : '';
    if (!($enable_slack && $slack_channel && $webhook_url)) {
        return;
    }
    $emoji    = !empty($rcp_options['bot_icon']) ? $rcp_options['bot_icon'] : ':moneybag:';
    $bot_name = !empty($rcp_options['bot_name']) ? $rcp_options['bot_name'] : get_bloginfo('name') . ' Sales Bot';

    $userData = get_userdata($user_id);

    $site_name = stripslashes_deep(html_entity_decode(get_bloginfo('name'), ENT_COMPAT, 'UTF-8'));

    $message = "User status has changed on $site_name \n\n";
    $message = "Status changed from ".$old_status." to ".$new_status."\n\n";
    $message .= " Name: " . $userData->first_name . ' ' . $userData->last_name . "\n";
    $message .= " Email: " . $userData->user_email . "\n";
    $message .= " Subscription: " . rcp_get_subscription( $userData->ID ) . "\n";
    $attachment   = array();
    $attachment[] = array(
        'title'     => 'User status change notification',
        'text'      => $message,
        'color'     => 'good',
        'mrkdwn_in' => array('text'),
    );
    $payload = array(
        'username'    => $bot_name,
        'attachments' => $attachment,
        'icon_emoji'  => $emoji,
        'channel'     => $slack_channel,
    );
    $args = array(
        'body'    => json_encode($payload),
        'timeout' => 30,
    );
    $response = wp_remote_post($webhook_url, $args);
    return;
}
add_action('rcp_set_status', 'rcp_slack_set_status', 10, 4);
