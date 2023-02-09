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

use totara_mobile\local\device;

require('../../config.php');

$syscontext = context_system::instance();
require_login();
require_capability('totara/mobile:use', $syscontext);

if (!get_config('totara_mobile', 'enable')) {
    redirect(new moodle_url('/'));
}
$returnurl = new moodle_url('/totara/mobile/index.php');

$PAGE->set_context($syscontext);
$PAGE->set_url('/totara/mobile/device_register_universal_link.php');
$PAGE->set_cacheable(false);

if (!data_submitted()) {
    redirect($returnurl);
}
require_sesskey();

// We are looking at our own profile.
$myprofilenode = $PAGE->settingsnav->find('myprofile', null);
$mobilenode = $myprofilenode->add(get_string('profilecategory', 'totara_mobile'));
$mydevicesnode = $mobilenode->add(get_string('managedevices', 'totara_mobile'));
$mydevicesnode->make_active();

echo $OUTPUT->header();

$setupsecret = device::request();
$url = device::get_universal_link_register_url($setupsecret);

$button = new single_button($url, get_string('apploginopen', 'totara_mobile'), 'get', true);
$button->class = 'mobileappregister';
echo $OUTPUT->render($button);

$button = new single_button($returnurl, get_string('devices', 'totara_mobile'), 'post', false);
$button->class = 'continuebutton';
echo $OUTPUT->render($button);

echo $OUTPUT->footer();
