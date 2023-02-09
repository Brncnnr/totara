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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package container_perform
 */

$capabilities = [
    // Create a performance container.
    // Additionally requires 'mod/perform:view_manage_activities' to access the page where creation is done.
    'container/perform:create' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
            'performanceactivitycreator' => CAP_ALLOW,
        ]
    ],
    // Backup a performance container.
    // Requires course context as the container course the activity is in must be backed up.
    // This and 'container/perform:restore' are required for cloning,
    // and 'mod/perform:view_manage_activities' to access the page where cloning is done.
    'container/perform:backup' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
            'performanceactivitycreator' => CAP_ALLOW,
        ]
    ],
    // Restore a performance container.
    // Requires course context as the container course the activity is in must be restored up.
    // This and 'container/perform:backup' are required for cloning,
    // and 'mod/perform:view_manage_activities' to access the page where cloning is done.
    'container/perform:restore' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
            'performanceactivitycreator' => CAP_ALLOW,
        ]
    ],
];
