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
 * @package core_virtualmeeting
 */

use core\entity\user;
use core\plugininfo\virtualmeeting;
use totara_core\http\clients\curl_client;
use totara_core\http\util;
use totara_core\virtualmeeting\plugin\factory\auth_factory;

require_once(__DIR__ . '/../../config.php');
require_login();

$method = strtoupper($_SERVER['REQUEST_METHOD']);
$headers = util::get_request_headers();
if ($headers === false) {
    $headers = [];
}
$relativepath = get_file_argument();
$args = explode('/', ltrim($relativepath, '/'));
$body = @file_get_contents('php://input');
$query_get = $_GET;
$query_post = $_POST;

$pluginname = array_shift($args);

try {
    if (empty($pluginname)) {
        throw new Exception('unauthorised access');
    }

    $plugin = virtualmeeting::load($pluginname);
    $factory = $plugin->create_factory();
    if (!$factory->is_available()) {
        throw new Exception('unauthorised access');
    }

    if (!($factory instanceof auth_factory)) {
        throw new Exception('unauthorised access');
    }

    $client = new curl_client();
    try {
        $provider = $factory->create_auth_service_provider($client);
    } catch (\Throwable $ex) {
        throw new Exception('unauthorised access', 0, $ex);
    }
    $user = user::logged_in();
    $provider->authorise($user, $method, $headers, $body, $query_get, $query_post);

} catch (Throwable $ex) {
    header('Content-Type: text/html', true, 401);
    // TODO: user-friendly message
    echo get_string('error');
    debugging($ex->getMessage(), DEBUG_DEVELOPER);
    die;
}

?><!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title><?php echo s(get_string('login')); ?></title>
</head>
<body>
<script>
window.addEventListener('load', function() {
    // TODO: add more strict checks e.g. typeof window.opener.something !== 'undefined' && window.opener.something === 'blah'
    if (window.opener !== null) {
        window.opener.postMessage({
            sender: 'auth_callback',
            status: 'success',
        });
    } else {
        window.close();
    }
});
</script>
</body>
</html>
