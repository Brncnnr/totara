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
 * @package totara_pathway
 */

$functions = [
    'pathway_manual_get_roles' => [
        'classname' => \pathway_manual\external::class,
        'methodname' => 'get_roles',
        'description' => 'Return all available roles',
        'type' => 'read',
        'loginrequired' => true,
        'ajax' => true,
    ],

    'pathway_manual_create' => [
        'classname' => \pathway_manual\external::class,
        'methodname' => 'create',
        'description' => "Create a new manual pathway",
        'type' => 'post',
        'loginrequired' => true,
        'ajax' => true,
    ],

    'pathway_manual_update' => [
        'classname' => \pathway_manual\external::class,
        'methodname' => 'update',
        'description' => "Update the manual pathway detail",
        'type' => 'post',
        'loginrequired' => true,
        'ajax' => true,
    ],
];
