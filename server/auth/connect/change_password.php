<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package auth_connect
 */

require(__DIR__ . '/../../config.php');

require_login();

if (!is_enabled_auth('connect') || $USER->auth !== 'connect') {
    redirect(new moodle_url('/'));
}

// There is no way to force password change from the Totara Connect client,
// remove the flag to prevent inifnite loop here.
unset_user_preference('auth_forcepasswordchange');

$connectuser = $DB->get_record('auth_connect_users', ['userid' => $USER->id], '*', MUST_EXIST);
$server = $DB->get_record('auth_connect_servers', ['id' => $connectuser->serverid], '*', MUST_EXIST);

redirect(new moodle_url($server->serverurl . '/login/change_password.php', ['returnto' => 'tc_' . $server->clientidnumber]));
