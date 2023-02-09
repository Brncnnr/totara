<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *
 * @package auth_oauth2
 */

use \auth_oauth2\api;
use auth_oauth2\linked_login;

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$returnurl = optional_param('returnurl', '/', PARAM_LOCALURL);
if ($returnurl === '') {
    $returnurl = '/';
}

$syscontext = context_system::instance();

$PAGE->set_context($syscontext);
$PAGE->set_url('/auth/oauth2/linkedlogindelete.php', ['id' => $id, 'confirm' => $confirm, 'returnurl' => $returnurl]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('deletelinkedlogin', 'auth_oauth2'));

require_login();

// NOTE: do not use is_enabled_auth('oauth2') here because the report is supposed to work even if auth is disabled.

$linkedlogin = linked_login::get_record(['id' => $id]);
if (!$linkedlogin || !api::can_delete_linked_login($linkedlogin)) {
    redirect(new moodle_url($returnurl));
}

if ($confirm) {
    require_sesskey();

    api::delete_linked_login($linkedlogin->get('id'));
    redirect(new moodle_url($returnurl));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deletelinkedlogin', 'auth_oauth2'));

$issuers = api::get_login_issuers_menu();
$user = $DB->get_record('user', ['id' => $linkedlogin->get('userid')]);
$a = new stdClass();
$a->issuername = $issuers[$linkedlogin->get('issuerid')];
$a->fullname = fullname($user);
$message = get_string('confirmdeletelinkedlogin', 'auth_oauth2', $a);

$yesurl = new moodle_url('/auth/oauth2/linkedlogindelete.php',
    ['id' => $linkedlogin->get('id'), 'confirm' => 1, 'sesskey' => sesskey(), 'returnurl' => $returnurl]);
$yebutton = new single_button($yesurl, get_string('delete'), 'post', true);
echo $OUTPUT->confirm($message, $yebutton, new moodle_url($returnurl));

echo $OUTPUT->footer();
