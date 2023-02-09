<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package mod_facetoface
 */

$functions = [
    // Render the session list table.
    'mod_facetoface_render_session_list' => [
        'classname'         => 'mod_facetoface\external',
        'methodname'        => 'render_session_list',
        'classpath'         => 'mod/facetoface/classes/external.php',
        'description'       => 'Render the session list in tabular format',
        'type'              => 'read',
        'loginrequired'     => true,
        'ajax'              => true,
        'capabilities'      => ''
    ],

    // Get the user's profile from external sources for virtual meetings.
    'mod_facetoface_user_profile' => [
        'classname'         => 'mod_facetoface\external',
        'methodname'        => 'user_profile',
        'classpath'         => 'mod/facetoface/classes/external.php',
        'description'       => 'Get the user profile from external sources',
        'type'              => 'read',
        'loginrequired'     => true,
        'ajax'              => true,
        'capabilities'      => ''
    ],
];
