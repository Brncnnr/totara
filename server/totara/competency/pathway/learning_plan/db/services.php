<?php
/*
 * This file is part of Totara Learn
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
 * @author Brendan Cox <brendan.cox@totaralearning.com>
 * @package totara_pathway
 */

$functions = [

    'pathway_learning_plan_create' => [
        'classname' => \pathway_learning_plan\external::class,
        'methodname' => 'create',
        'description' => "Create a new lp pathway",
        'type' => 'post',
        'loginrequired' => true,
        'ajax' => true,
    ],

    'pathway_learning_plan_update' => [
        'classname' => \pathway_learning_plan\external::class,
        'methodname' => 'update',
        'description' => "Update the lp pathway detail",
        'type' => 'post',
        'loginrequired' => true,
        'ajax' => true,
    ],
];
