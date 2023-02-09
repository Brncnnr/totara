<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_mobile
 */

use \core\output\flex_icon;
use totara_mobile\local\device;

require('../../config.php');

$deletedevice = optional_param('delete', 0, PARAM_INT);
$logoutall = optional_param('logoutall', 0, PARAM_INT);

$syscontext = context_system::instance();
require_login();
require_capability('totara/mobile:use', $syscontext);

if (!get_config('totara_mobile', 'enable')) {
    redirect(new moodle_url('/'));
}

$PAGE->set_context($syscontext);
$PAGE->set_url('/totara/mobile/index.php');
$PAGE->requires->js_call_amd('totara_mobile/device_management', 'init');

// We are looking at our own profile.
$myprofilenode = $PAGE->settingsnav->find('myprofile', null);
$mobilenode = $myprofilenode->add(get_string('profilecategory', 'totara_mobile'));
$mydevicesnode = $mobilenode->add(get_string('managedevices', 'totara_mobile'));
$mydevicesnode->make_active();

// Show only own devices!!!
$devices = $DB->get_records('totara_mobile_devices', ['userid' => $USER->id]);

if ($deletedevice and confirm_sesskey()) {
    if (isset($devices[$deletedevice])) {
        device::delete($USER->id, $deletedevice);
        \core\notification::success(get_string('device_loggedout', 'totara_mobile'));
        redirect($PAGE->url);
    }
}

if ($logoutall and confirm_sesskey()) {
    foreach ($devices as $device) {
        device::delete($USER->id, $deletedevice);
    }
    \core\notification::success(get_string('device_loggedout', 'totara_mobile'));
    redirect($PAGE->url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('devices', 'totara_mobile'));

if (core_useragent::is_safari_ios() or core_useragent::is_webkit_android()) {
    $registerurl = new moodle_url('/totara/mobile/device_register_universal_link.php', ['sesskey' => sesskey()]);
    $button = new single_button($registerurl, get_string('applogin', 'totara_mobile'), 'post', false);
    echo $OUTPUT->render($button);
}

if (!$devices) {
    echo 'No devices yet';
    echo $OUTPUT->footer();
    die;
}

// Print table header.
$table = new html_table();
$table->id = 'totaramobiledevices';
$table->attributes['class'] = 'admintable generaltable';

$table->head = [
    get_string('devicetable_index', 'totara_mobile'),
    get_string('devicetable_registered', 'totara_mobile'),
    get_string('devicetable_accessed', 'totara_mobile'),
    get_string('devicetable_logout', 'totara_mobile'),
];

$table->colclasses = array(
    'leftalign name',
    'leftalign',
    'leftalign',
    'leftalign actions'
);
$table->data = array();

$index = 1;
foreach ($devices as $device) {
    $icons = [];

    $deleteurl = new \moodle_url('/totara/mobile/index.php', array('delete' => $device->id, 'sesskey' => sesskey()));
    $icons[] = $OUTPUT->action_icon($deleteurl, new flex_icon('sign-out', array('alt' => get_string('logout'))));

    $row = new html_table_row(array(
        new html_table_cell($index++),
        new html_table_cell($device->timeregistered ? userdate($device->timeregistered) : '-'),
        new html_table_cell($device->timelastaccess ? userdate($device->timelastaccess) : '-'),
        new html_table_cell(join(' ', $icons)),
    ));
    $table->data[] = $row;
}

echo html_writer::table($table);

$logoutallurl = new \moodle_url('/totara/mobile/index.php', array('logoutall' => 1, 'sesskey' => sesskey()));
$button = new single_button($logoutallurl, get_string('devices_logoutall', 'totara_mobile'));
echo $OUTPUT->render($button);

echo $OUTPUT->footer();
