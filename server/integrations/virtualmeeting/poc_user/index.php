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
 * @package virtualmeeting_poc_user
 */

use core\plugininfo\virtualmeeting;

/**
 * The PoC identity provider endpoint aka a login page.
 */

// @codeCoverageIgnoreStart

global $CFG;
if (empty($CFG)) {
    require_once(__DIR__ . '/../../../config.php');
}

if (!isset($USER) || !isset($PAGE) || !virtualmeeting::is_poc_available()) {
    die;
}

$redirect_uri = required_param('redirect_uri', PARAM_LOCALURL);

if ($formdata = data_submitted()) {
    require_sesskey();
    if (empty($formdata->username) || empty($formdata->password)) {
        echo 'Incorrect username/password';
        die;
    }
    $user = authenticate_user_login($formdata->username, $formdata->password);
    if (!$user || empty($user->id)) {
        echo 'Login failure';
        die;
    }
    $url = new moodle_url($redirect_uri, ['username' => $user->username]);
    redirect($url);
    die;
}

?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Log in</title>
<style>
h1 { text-align: center }
form { display: flex; flex-direction: column; margin: auto; max-width: 400px }
input,button { line-height: 2; margin-bottom: 1rem; padding: 0 .5rem }
</style>
</head>
<body>
<h1>Log in</h1>
<form method="post" autocomplete="off">
<input type="hidden" name="sesskey" value="<?php echo s(sesskey()); ?>">
<label for="username">Username</label>
<input type="text" id="username" name="username" required>
<label for="password">Password</label>
<input type="password" id="password" name="password" required>
<button>Log in</button>
</form>
</body>
</html>
