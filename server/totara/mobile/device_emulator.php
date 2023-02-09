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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

/*
 * This page uses JavaScript to simulate the requests made by a Totata Mobile App,
 * for development and testing purposes.
 *
 * Developers can set $CFG->mobile_device_emulator = 1 to enable manual use.
 */

use totara_mobile\local\util;

require('../../config.php');

// Only allow behat or intentional development use.
if (!defined('BEHAT_SITE_RUNNING') && empty($CFG->mobile_device_emulator)) {
    die();
}

$syscontext = context_system::instance();

$PAGE->set_context($syscontext);
$PAGE->set_pagelayout('webview'); // No fancy UIs or navigation.
$PAGE->set_title('Mobile Device Emulator');

if (!get_config('totara_mobile', 'enable')) {
    util::webview_error(get_string('errormobileunavailable', 'totara_mobile'));
}

echo $OUTPUT->header();
echo '<iframe id="WebView" style="width: 320px; height: 480px; float: right; position: sticky; top: 50px; margin-right: 50px;"><p>Webview Window</p></iframe>';
echo '<div id="Output" style="padding: 50px;"><p>Device emulator loading...</p></div>';
$url = $CFG->wwwroot . '/totara/mobile/js/device_emulator.js';
echo '<script type="text/javascript" src="' . $url . '"></script>';
echo $OUTPUT->footer();
