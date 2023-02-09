<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_notification
 */
defined('MOODLE_INTERNAL') || die();

$string['actions_for'] = 'Actions for {$a}';
$string['actions_for_event'] = 'Actions for {$a} event';
$string['actions_for_notification'] = 'Actions for {$a} notification';
$string['amended'] = 'Amended';
$string['body'] = 'Body';
$string['cachedef_access'] = 'Notification capabilities map cache';
$string['cachedef_notifiable_resolver_map'] = 'Notification resolver map cache';
$string['create_notification'] = 'Create notification';
$string['create_notification_for_event'] = 'Create notification for event {$a}';
$string['create_notification_select_placeholder'] = 'Select...';
$string['create_custom_notification_title'] = 'Create notification';
$string['custom'] = 'Custom';
$string['default_delivery_preferences_helptext'] = 'These are the default delivery channels for all notifications based on this trigger. At least one channel should be selected in order for notifications to be sent. Users can change the default channels in their notification preferences. Notifications with forced delivery ignore user preferences.';
$string['delivery_label'] = 'Delivery channels';
$string['delivery_channel'] = 'Delivery channel';
$string['delivery_channels'] = 'Default delivery channels';
$string['delete_confirm_title'] = "Delete notification: {\$a}";
$string['delete_confirm_message'] = 'Are you sure? Deleting this notification will remove its instances in other contexts, such as categories and courses. This action cannot be undone.';
$string['delete_notification_name'] = "Delete notification {\$a}";
$string['delete_notification_logs'] = 'Delete notification logs';
$string['delete_success'] = 'Successfully deleted notification';
$string['delivery_preferences'] = 'Delivery preferences';
$string['delivery_preferences_for_event'] = 'Update delivery preferences for event {$a}';
$string['delivery_preferences_helptext'] = 'Select the default channels for all the notifications triggered by this event. Please note that mobile app notifications, tasks and alerts require site notifications to be selected.';
$string['delivery_preferences_helptext_aria'] = 'Help text for the delivery channels';
$string['delivery_preferences_override_helptext'] = 'Use this setting to override the default delivery channels in notifications using this notification trigger. Please note that mobile app notifications, tasks and alerts require site notifications to be selected.';
$string['delivery_preferences_override_helptext_aria'] = 'Help text to override the delivery channels';
$string['delivery_view_all'] = 'View all delivery logs';
$string['disabled'] = 'Disabled';
$string['disable'] = 'Disable notification';
$string['disable_all_helptext'] = 'Disable all notifications, including forced and custom notifications.';
$string['edit_delivery_preferences'] = 'Edit delivery channels';
$string['edit_notification'] = "Edit notification";
$string['edit_notification_name'] = "Edit notification {\$a}";
$string['enabled'] = 'Enabled';
$string['enable_custom_additional_criteria'] = 'Enable customising additional configuration properties';
$string['enable_custom_body'] = 'Enable customising field body';
$string['enable_custom_forced_delivery_channels'] = 'Enable customising forced delivery channels';
$string['enable_custom_schedule'] = 'Enable customising field schedule';
$string['enable_custom_subject'] = 'Enable customising field subject';
$string['enable_custom_recipient'] = 'Enable customising field recipient';
$string['enable_custom_status'] = 'Enable customising notification status';
$string['enablenotificationlogs'] = 'Enable notification logs';
$string['enablenotificationlogs_help'] = 'When enabled, notification logs are aggregated for auditing and troubleshooting purposes. Legacy notifications and private messages are not included and notification content is not recorded.';
$string['enabled_helptext'] = "When enabled, a trigger and all its notifications appear in lower contexts such as categories and courses. An enabled trigger can hold disabled notifications too. These notifications are disabled in all lower contexts, but they can be enabled manually in any context. Also, new notifications can be created for the enabled trigger in any context.
When disabled, the trigger and its notifications are effectively disabled and hidden in lower contexts. No new notifications can be created for the disabled trigger in lower contexts, since it is hidden.";
$string['enabled_helptext_aria'] = 'Help text for the enabled status';
$string['enabled_status'] = 'Is enabled';
$string['event_create_custom_notification_preference'] = 'Create custom notification preference';
$string['event_create_override_notification_preference'] = 'Create an Override of the notification preference';
$string['event_update_custom_notification_preference'] = 'Update custom notification preference';
$string['event_update_overridden_notification_preference'] = 'Update inherited notification preference';
$string['error_audit_notification'] = 'You are not allowed to audit notification preference';
$string['error_manage_notification'] = 'You are not allowed to manage notification preference';
$string['error_preference_id_missing'] = 'You are not allowed to manage notification preference with an empty id';
$string['error_user_preference_permission'] = 'You are not allowed to update notification preferences';
$string['enable_status'] = '{$a} notification status';
$string['factory'] = 'Factory';
$string['forced'] = 'Forced';
$string['forced_delivery_help'] = 'This setting forces delivery of important notifications through selected channels, regardless of user preferences';
$string['forced_info'] = 'Forced delivery information';
$string['force_channel_x'] = 'Force channel {$a}';
$string['inherited'] = 'Inherited';
$string['invalid_input'] = 'Invalid value';
$string['log_delivery_title'] = 'Delivery log';
$string['log_delivery_title_for'] = 'Delivery log for {$a}';
$string['log_delivery_title_context'] = 'Delivery log: {$a}';
$string['log_event_back_to'] = 'Back to {$a}';
$string['log_event_title'] = 'Notification events';
$string['log_event_title_for'] = 'Notification events for {$a}';
$string['log_event_title_context'] = 'Notification events: {$a}';
$string['log_title'] = 'Sent notifications';
$string['log_title_for'] = 'Sent notifications for {$a}';
$string['log_title_context'] = 'Sent notifications: {$a}';
$string['messaging_and_notification'] = 'Messaging and notifications';
$string['more_actions_for'] = 'More actions for {$a}';
$string['no_available_data_for_key'] = '<no available data for {$a}>';
$string['no_notifications'] = 'No notifications found.';
$string['notifiable_event_triggered'] = '{$a->resolver_title}';
$string['notifiable_events'] = 'Notification trigger';
$string['notification'] = "Notification";
$string['notifications'] = "Notifications";
$string['notifications_for'] = 'Notifications for {$a}';
$string['notifications_view_all'] = 'View all sent notifications';
$string['notification:auditnotifications'] = "Audit notifications";
$string['notification:auditownnotifications'] = "Audit own notifications";
$string['notification:managenotifications'] = "Manage notifications";
$string['notification_body_label'] = 'Body';
$string['notification_body_label_help'] = "[content-placeholder]: When editing the subject, type [ to see a list of all content placeholders that are available in this notification. You can browse the list or type ahead to filter it, and click the placeholder you wish to add.";
$string['notification_delivery_channel_default_label'] = 'Default';
$string['notification_delivery_channel_label'] = 'Delivery channel';
$string['notification_delivery_channels_label'] = 'Delivery channels';
$string['notification_include_ical_attachment_label'] = 'Include iCal attachment';
$string['notification_schedule_label'] = 'Schedule';
$string['notification_status'] = 'Notification status';
$string['notification_status_label'] = 'Status';
$string['notification_subject_label'] = 'Subject';
$string['notification_subject_label_help'] = '[content-placeholder]: When editing the subject, type [ to see a list of all content placeholders that are available in this notification. You can browse the list or type ahead to filter it, and click the placeholder you wish to add. Use keyboard shortcut Ctrl-m to insert Multilang block.';
$string['notification_title_label'] = 'Name';
$string['notification_trigger'] = 'Notification trigger: {$a}';
$string['notification_warning'] = 'Warning: Disabled notification trigger. Click for details.';
$string['totara_notification_log_days_to_keep'] = 'Days to keep notification logs';
$string['totara_notification_log_days_to_keep_help'] = 'Keeping notification logs for longer might cause performance issues in large sites with a lot of users and notifications. Enter \'0\' to keep indefinitely (not recommended).';
$string['override'] = 'Override';
$string['override_delivery_channels_aria'] = 'Override delivery channels';
$string['placeholder_group_appraiser'] = 'Appraiser {$a}';
$string['placeholder_group_manager'] = 'All managers {$a}';
$string['placeholder_group_recipient'] = 'Recipient {$a}';
$string['placeholder_group_subject'] = 'Subject {$a}';
$string['pluginname'] = 'Centralised notification';
$string['process_event_queue_task'] = 'Queue event scheduled task';
$string['process_notification_queue_task'] = 'Queue notification scheduled task';
$string['process_scheduled_event_task'] = 'Queue scheduled event task';
$string['recipient'] = 'Recipient';
$string['recipient_appraiser'] = 'Appraiser';
$string['recipient_direct_report'] = 'Direct report';
$string['recipient_manager'] = 'Manager';
$string['recipient_managers_manager'] = 'Manager\'s manager';
$string['recipient_subject'] = 'Subject';
$string['required'] = 'Required';
$string['saved_notification'] = 'Notification saved';
$string['schedule'] = 'Schedule';
$string['schedule_label_after_event'] = '{$a} days after';
$string['schedule_label_after_event_singular'] = '{$a} day after';
$string['schedule_label_before_event'] = '{$a} days before';
$string['schedule_label_before_event_singular'] = '{$a} day before';
$string['schedule_label_on_event'] = 'On event';
$string['schedule_form_label_after_event'] = 'Days after';
$string['schedule_form_label_before_event'] = 'Days before';
$string['schedule_form_label_on_event'] = 'On notification trigger event';
$string['subject'] = 'Subject';
$string['type'] = 'Type';
$string['updated_notification'] = 'Notification updated';
$string['user_preferences_page_title'] = 'Notification preferences';
$string['userdata_notification_logs'] = 'Centralised notification logs';
$string['unavailable'] = 'Unavailable';
$string['viewnotificationlogs'] = 'View notification logs';
