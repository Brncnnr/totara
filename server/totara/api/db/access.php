<?php
/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package totara_api
 *  @author Simon Coggins <simon.coggins@totaralearning.com>
 *
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'totara/api:manageclients' => [
        'captype' => 'write',
        'riskbitmask' => RISK_CONFIG|RISK_PERSONAL|RISK_DATALOSS,
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ],
    'totara/api:viewdocumentation' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
            'apiuser' => CAP_ALLOW,
        ]
    ],
    'totara/api:managesettings' => [
        'captype' => 'write',
        'riskbitmask' => RISK_CONFIG,
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ]
    ]
];