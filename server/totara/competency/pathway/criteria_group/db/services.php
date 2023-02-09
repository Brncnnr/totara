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
 * @package pathway_criteria_group
 */

$functions = [
    'pathway_criteria_group_update' => [
        'classname' => \pathway_criteria_group\external::class,
        'methodname' => 'update',
        'description' => 'Update this criteria group.',
        'type' => 'post',
        'loginrequired' => true,
        'ajax' => true,
    ],

    'pathway_criteria_group_create' => [
        'classname' => \pathway_criteria_group\external::class,
        'methodname' => 'create',
        'description' => 'Create a new criteria group.',
        'type' => 'post',
        'loginrequired' => true,
        'ajax' => true,
    ],

    'pathway_criteria_group_get_criteria_types' => [
        'classname' => \pathway_criteria_group\external::class,
        'methodname' => 'get_criteria_types',
        'description' => 'Get the available criteria types.',
        'type' => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],
];
