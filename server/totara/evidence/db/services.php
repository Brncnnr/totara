<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_evidence
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'totara_evidence_item_info' => [
        'classname' => \totara_evidence\services\item::class,
        'methodname' => 'info',
        'description' => 'Returns information about the item with a given id',
        'type' => 'read',
        'loginrequired' => true,
        'ajax' => true,
        'capabilities' => 'totara/evidence:managetype',
    ],
    'totara_evidence_item_delete' => [
        'classname' => \totara_evidence\services\item::class,
        'methodname' => 'delete',
        'description' => 'Deletes the item with a given id',
        'type' => 'write',
        'loginrequired' => true,
        'ajax' => true,
        'capabilities' => 'totara/evidence:managetype',
    ],
    'totara_evidence_type_data' => [
        'classname' => \totara_evidence\services\type::class,
        'methodname' => 'data',
        'description' => 'Returns all data about a type with a given id',
        'type' => 'read',
        'loginrequired' => true,
        'ajax' => true,
        'capabilities' => 'totara/evidence:managetype',
    ],
    'totara_evidence_type_details' => [
        'classname' => \totara_evidence\services\type::class,
        'methodname' => 'details',
        'description' => 'Returns limited details about a type with a given id',
        'type' => 'read',
        'loginrequired' => true,
        'ajax' => true,
        'capabilities' => implode(', ', [
            'totara/evidence:viewanyevidenceonself',
            'totara/evidence:manageownevidenceonself',
            'totara/evidence:manageanyevidenceonself',
            'totara/evidence:viewanyevidenceonothers',
            'totara/evidence:manageownevidenceonothers',
            'totara/evidence:manageanyevidenceonothers',
        ]),
    ],
    'totara_evidence_type_search' => [
        'classname' => \totara_evidence\services\type::class,
        'methodname' => 'search',
        'description' => 'Searches for evidence types with a given name',
        'type' => 'read',
        'loginrequired' => true,
        'ajax' => true,
        'capabilities' => implode(', ', [
            'totara/evidence:manageownevidenceonself',
            'totara/evidence:manageanyevidenceonself',
            'totara/evidence:manageownevidenceonothers',
            'totara/evidence:manageanyevidenceonothers',
        ]),
    ],
    'totara_evidence_type_set_visibility' => [
        'classname' => \totara_evidence\services\type::class,
        'methodname' => 'set_visibility',
        'description' => 'Set the visibility of a type with a given id',
        'type' => 'write',
        'loginrequired' => true,
        'ajax' => true,
        'capabilities' => 'totara/evidence:managetype',
    ],
    'totara_evidence_type_delete' => [
        'classname' => \totara_evidence\services\type::class,
        'methodname' => 'delete',
        'description' => 'Deletes the type with a given id',
        'type' => 'write',
        'loginrequired' => true,
        'ajax' => true,
        'capabilities' => 'totara/evidence:managetype',
    ],
    'totara_evidence_type_import_fields' => [
        'classname' => \totara_evidence\services\type::class,
        'methodname' => 'get_import_fields',
        'description' => 'Returns list of custom fields that may be imported',
        'type' => 'read',
        'loginrequired' => true,
        'ajax' => true,
        'capabilities' => '',
    ],
];
