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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */
use totara_msteams\botfw\auth\default_authoriser;
use totara_msteams\botfw\mini_output;

global $CFG;

if (empty($CFG)) {
    require_once(__DIR__ . '/../../config.php');
}

if (isset($USER) && isset($PAGE)) {
    // Turn off editing.
    $USER->editing = false;
    $PAGE->set_url(new moodle_url('/totara/msteams/botlogin.php'));

    // Render as minimum HTML as possible to speed up the login process.
    $PAGE->set_context(context_system::instance());
    $renderer = new mini_output($PAGE);

    if (($userstate = default_authoriser::continue_login()) !== null) {
        echo $renderer->header();
        echo $renderer->render_post_process($userstate);
        echo $renderer->footer();
    } else {
        $returnurl = default_authoriser::initiate_login();
        echo $renderer->header();
        echo $renderer->render_sso_login($returnurl, true);
        echo $renderer->footer();
    }
}