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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_webapi
 */

use core\output\notification;
use totara_tui\output\component;

require(__DIR__ . '/../../../../config.php');

$title = 'GraphQL session check';

$context = context_system::instance();

require_login();
require_capability('moodle/site:config', $context);
$PAGE->set_url('/totara/webapi/tests/fixtures/test_session.php');
$PAGE->set_context($context);
$PAGE->set_pagelayout('noblocks');
$PAGE->set_title($title);

$component = new component('totara_webapi/pages/TestSession');
$component->register($PAGE);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

// This is intentionally hard coded - this page is not in the navigation and should only ever be used by developers.
$msg = 'This page only exists to facilitate acceptance testing, if you are here for any other reason please file an improvement request.';
echo $OUTPUT->notification($msg, notification::NOTIFY_SUCCESS);
// We display a developer debug message as well to ensure that this doesn't not get shown during behat testing.
debugging('This is a developer resource, please contact your system admin if you have arrived here by mistake.', DEBUG_DEVELOPER);

echo $OUTPUT->render($component);
echo $OUTPUT->footer();
