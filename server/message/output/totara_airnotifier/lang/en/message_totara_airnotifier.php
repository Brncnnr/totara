<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package message_totara_airnotifier
 */

$string['config_airnotifier_appcode'] = 'The AirNotifier app code token issued for your Totara instance.';
$string['config_airnotifier_appcode_registered'] = 'The AirNotifier app code token issued for your Totara instance.<br><a href="{$a->url}">Request an app code token from push.totaralearning.com</a>';
$string['config_airnotifier_appname'] = 'The AirNotifier app name for the mobile app which should receive the notifications.';
$string['config_airnotifier_host'] = 'Homepage URL of the AirNotifier server to use for sending push notifications.';
$string['delivery_channel_label'] = 'Mobile app notifications';
$string['airnotifier_appcode'] = 'AirNotifier App Code';
$string['airnotifier_appname'] = 'AirNotifier App Name';
$string['airnotifier_host'] = 'AirNotifier Server URL';
$string['event_fcmtoken_rejected'] = 'FCM Token rejected';
$string['event_pushnotification_sent'] = 'Push notification sent';
$string['pluginname'] = 'Totara AirNotifier';
$string['request_appcode_error:notdefault'] = 'App code can only be requested with default settings';
$string['request_appcode_error:notregistered'] = 'App code can only be requested by registered Totara sites';
$string['request_appcode_error:requestfail'] = 'Unable to retrieve App Code from Totara registration server ({$a->error})';
$string['request_appcode_success'] = 'App code request successful, settings have been saved';
