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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_playlist
 */
define('CLI_SCRIPT', true);
require_once(__DIR__ . "/../../server/config.php");

use totara_engage\resource\resource_factory;
use totara_engage\access\access_manager;
use totara_playlist\query\playlist_query;
use totara_playlist\loader\playlist_loader;
use totara_playlist\pagination\cursor;
use totara_playlist\playlist;

global $CFG, $DB, $USER;
require_once("{$CFG->dirroot}/lib/clilib.php");

[$options, $un_recognized] = cli_get_params(
    [
        'help' => false,
        'resource_id' => null,
        'user_id' => null
    ],
    [
        'h' => 'help',
        'i' => 'resource_id',
        'u' => 'user_id'
    ]
);

if ($options['help']) {
    echo "
A script to generate the record of resources be added into multiple playlists.
Note that this script will only look for the resource that the user is able to see
and add it into multiple playlists that this user is able to control or own.

Usage: 
    php dev/engage/add_resource_to_playlists.php -u=2 - i=15

Options:
    -h, --help              Print out this help.
    -i, --resource_id       The resource's id that we are going to add into the database.
    -u, --user_id           The user's id that should have access to the resources.
";

    return 0;
}

if (!isset($options['resource_id']) || !isset($options['user_id'])) {
    echo "There is no 'resource_id' or 'user_id' provided\n";
    return 1;
}

$user_id = $options['user_id'];
$resource_id = $options['resource_id'];

// Setup the user to execute the script.
$user = $DB->get_record('user', ['id' => $user_id], '*', MUST_EXIST);
$USER = $user;

$resource = resource_factory::create_instance_from_id($resource_id);

if (!$resource->is_public() && !access_manager::can_access($resource, $user_id)) {
    echo "User with id '{$user_id}' is not able to access to resource with id '{$resource_id}'\n";
    return 1;
}

$query = new playlist_query($user_id);

// Unlimited cursor
$cursor = new cursor();
$cursor->set_limit(0);

$query->set_cursor($cursor);

$result = playlist_loader::get_playlists($query);
$playlists = $result->get_items()->all();

/** @var playlist $playlist */
foreach ($playlists as $playlist) {
    echo "Adding resource '{$resource_id}' into playlist '{$playlist->get_id()}'\n";
    $playlist->add_resource($resource, $user_id);
}