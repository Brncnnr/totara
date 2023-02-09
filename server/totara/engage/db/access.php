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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // View resource content
    'totara/engage:view_resource_reporting' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantusermanager' => CAP_ALLOW,
        ],
    ],
    // View engaged users content
    'totara/engage:view_engagedusers_reporting' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantusermanager' => CAP_ALLOW,
        ],
    ],

    /* View workspace content */
    'totara/engage:view_engagedworkspace_reporting' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantusermanager' => CAP_ALLOW,
        ],
    ],

    // View engage library
    'totara/engage:viewlibrary' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ],
    ],
    // Manage engage. Ability to access and do many things
    'totara/engage:manage' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantusermanager' => CAP_ALLOW,
        ],
    ],
];