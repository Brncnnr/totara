<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_webapi
 */

require_once(__DIR__ . '/../../../../config.php');

// Behat test fixture only.
defined('BEHAT_SITE_RUNNING') || die('Only available on Behat test server');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('webview'); // No fancy UIs or navigation.
$PAGE->set_title('API Endpoint Client Emulator');

echo $OUTPUT->header();
echo '<div id="Output" style="padding: 50px;"><p>API endpoint emulator loading...</p></div>';
$url = $CFG->wwwroot . '/totara/webapi/tests/fixtures/js/endpoint_client_emulator.js';
echo '<script type="text/javascript" src="' . $url . '"></script>';
echo $OUTPUT->footer();
