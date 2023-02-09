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

use totara_msteams\page_helper;

require_once(__DIR__ . '/../../../config.php');

\totara_core\advanced_feature::require('totara_msteams');

$redirect = optional_param('redirect', 0, PARAM_INT);
$redirecturl = new moodle_url('/totara/msteams/tabs/mylearning.php', ['redirect' => 1]);

if (empty($redirect)) {
    page_helper::tab_page('mylearning');
    exit; // Never reached.
}

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

$SESSION->theme = 'msteams';

require_login();

$PAGE->set_url($redirecturl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('noblocks');
$PAGE->set_title(get_string('tab:mylearning', 'totara_msteams'));

echo $OUTPUT->header();
$renderer = $PAGE->get_renderer('totara_msteams');

if (page_helper::is_tab_available('/totara/msteams/tabs/mylearning.php')) {
    /** @var totara_msteams_renderer $renderer */
    echo $renderer->render_my_learning();
} else {
    echo $OUTPUT->notification(get_string('error:mylearningnotavailable', 'totara_msteams'), core\output\notification::NOTIFY_INFO);
}

echo $OUTPUT->footer();
