<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

$functions = [
    'totara_competency_get_pathways' => [
        'classname' => \totara_competency\external::class,
        'methodname' => 'get_pathways',
        'description' => 'Return the pathways linked to the competency',
        'type' => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],

    'totara_competency_get_categories' => [
        'classname' => \totara_competency\external::class,
        'methodname'    => 'get_categories',
        'description'   => 'List categories',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true
    ],

    // List courses
    'totara_competency_get_courses' => [
        'classname' => \totara_competency\external::class,
        'methodname'    => 'get_courses',
        'description'   => 'List or search for courses',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true
    ],

    'totara_competency_get_linked_courses' => [
        'classname' => \totara_competency\external::class,
        'methodname'    => 'get_linked_courses',
        'description'   => 'Get courses that are linked to a competency',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true
    ],

    'totara_competency_set_linked_courses' => [
        'classname' => \totara_competency\external::class,
        'methodname'    => 'set_linked_courses',
        'description'   => 'Set courses that are linked to a competency',
        'type'          => 'update',
        'loginrequired' => true,
        'ajax'          => true
    ],

    'totara_competency_delete_pathways' => [
        'classname' => \totara_competency\external::class,
        'methodname'    => 'delete_pathways',
        'description'   => 'Delete pathways from the competency',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true
    ],

    'totara_competency_set_overall_aggregation' => [
        'classname' => \totara_competency\external::class,
        'methodname'    => 'set_overall_aggregation',
        'description'   => 'Save the overall aggregation',
        'type'          => 'push',
        'loginrequired' => true,
        'ajax'          => true
    ],
];

$assignments = [
    'totara_competency_assignment_index' => [
        'classname'     => \totara_competency\services\assignment::class,
        'methodname'    => 'index',
        'description'   => 'Returns a list of current assignments',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/competency:view_assignments',
    ],

    'totara_competency_assignment_show' => [
        'classname'     => \totara_competency\services\assignment::class,
        'methodname'    => 'index',
        'description'   => 'Return a single competency assignment',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/competency:view_assignments',
    ],

    'totara_competency_assignment_create' => [
        'classname'     => \totara_competency\services\assignment::class,
        'methodname'    => 'create',
        'description'   => 'Create a competency assignment(s) from a basket',
        'type'          => 'write',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/competency:manage_assignments',
    ],

    'totara_competency_assignment_create_from_baskets' => [
        'classname'     => \totara_competency\services\assignment::class,
        'methodname'    => 'create_from_baskets',
        'description'   => 'Create a competency assignment(s) from a basket',
        'type'          => 'write',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/competency:manage_assignments',
    ],

    'totara_competency_assignment_action' => [
        'classname'     => \totara_competency\services\assignment::class,
        'methodname'    => 'action',
        'description'   => 'Run action on one or multiple assignments (activate, archive, delete)',
        'type'          => 'write',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/competency:manage_assignments',
    ],

    // Expand user groups
    'totara_competency_expand_user_groups_index' => [
        'classname'     => \totara_competency\services\expand_user_groups::class,
        'methodname'    => 'index',
        'description'   => 'Expand user groups to preview what user groups do users have',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => implode(',', [
            'moodle/cohort:view',
            'moodle/cohort:manage',
            'totara/hierarchy:viewposition',
            'totara/hierarchy:vieworganisation',
        ]),
    ],

    // List users
    'totara_competency_user_index' => [
        'classname'     => \totara_competency\services\user::class,
        'methodname'    => 'index',
        'description'   => 'List or search for users',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/competency:manage_assignments',
    ],

    // List audiences
    'totara_competency_cohort_index' => [
        'classname'     => \totara_competency\services\cohort::class,
        'methodname'    => 'index',
        'description'   => 'List or search for audiences',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => implode(',', [
            'totara/competency:manage_assignments',
            'moodle/cohort:view',
            'moodle/cohort:manage'
        ]),
    ],
];

$competencies = [
    'totara_competency_competency_index' => [
        'classname'     => \totara_competency\services\competency::class,
        'methodname'    => 'index',
        'description'   => 'Returns a list of all competencies',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/competency:view_assignments',
    ],

    'totara_competency_competency_show' => [
        'classname'     => \totara_competency\services\competency::class,
        'methodname'    => 'show',
        'description'   => 'Returns one competency',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/competency:view_assignments',
    ],

    'totara_competency_get_frameworks' => [
        'classname' => \totara_competency\external::class,
        'methodname'    => 'get_frameworks',
        'description'   => 'List all frameworks',
        'type'          => 'read',
        'loginrequired' => true,
        'ajax'          => true,
        'capabilities'  => 'totara/competency:view_assignments',
    ],
];

$functions = array_merge($functions, $assignments, $competencies);
