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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

use totara_msteams\botfw\mini_output;
use totara_msteams\page_helper;

require_once(__DIR__ . '/../../../config.php');

\totara_core\advanced_feature::require('totara_msteams');

$redirect = optional_param('redirect', 0, PARAM_INT);
$rewind = optional_param('rewind', 0, PARAM_INT);
$redirecturl = new moodle_url('/totara/msteams/tabs/catalog.php', ['redirect' => 1]);

if (empty($redirect)) {
    page_helper::tab_page('catalog');
    exit; // Never reached.
}

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

$SESSION->theme = 'msteams';

require_login();
page_helper::override_language();

if ($CFG->catalogtype !== 'totara') {
    $PAGE->set_url($redirecturl);
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('noblocks');
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error:catalognotavailable', 'totara_msteams'), core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    die;
}

// Clear out $SESSION->wantsurl before any further redirection because manual the enrol page doesn't like it.
unset($SESSION->wantsurl);

$returnurl = new moodle_url('/totara/catalog/index.php');
// User has clicked the rewind link in the navigation bar.
if ($rewind) {
    redirect($returnurl);
}

// Let the front-end code handle the redirection of deep linking.
$PAGE->set_context(context_system::instance());
$renderer = new mini_output($PAGE);
echo $renderer->header();
echo $renderer->render_redirector($returnurl);
echo $renderer->footer();
