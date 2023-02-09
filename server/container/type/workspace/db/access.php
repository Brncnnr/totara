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
 * @package container_workspace
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Create public workspaces
    'container/workspace:create' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'user' => CAP_ALLOW,
            'workspacecreator' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    // Create private workspaces
    'container/workspace:createprivate' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'user' => CAP_ALLOW,
            'workspacecreator' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    // Create hidden workspaces
    'container/workspace:createhidden' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'user' => CAP_ALLOW,
            'workspacecreator' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    // Edit the workspace description/title/picture
    'container/workspace:update' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    // Delete the workspace
    'container/workspace:delete' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    // Invite other users to join the workspace
    'container/workspace:invite' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'student' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    // Can join public workspaces
    'container/workspace:joinpublic' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'user' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    // Can request to join private workspaces
    'container/workspace:joinprivate' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'user' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],

    // Can add other members to a workspace (without confirmation)
    'container/workspace:addmember' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ],
    ],

    // Can remove members from a workspace
    'container/workspace:removemember' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ],
    ],

    // Can share resources with a workspace
    'container/workspace:libraryadd' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
            'student' => CAP_ALLOW,
        ],
    ],

    // Can unshare resources with a workspace
    'container/workspace:libraryremove' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ],
    ],

    // Can manage/moderate all discussions
    'container/workspace:discussionmanage' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ],
    ],

    // Can create discussions
    'container/workspace:discussioncreate' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
            'student' => CAP_ALLOW,
        ],
    ],

    // Can perform almost any ability in a workspace
    'container/workspace:manage' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'workspaceowner' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ],
    ],

    // Can administrate any workspace
    'container/workspace:administrate' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ],
    ],

    // Can view workspaces in general (used to limit workspaces to just some users)
    // It's not called "view" so it's not confused as a course-level view
    'container/workspace:workspacesview' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'tenantdomainmanager' => CAP_ALLOW,
            'user' => CAP_ALLOW,
        ],
    ],
];