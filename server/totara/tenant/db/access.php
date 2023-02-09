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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_tenant
 */

$capabilities = array(
    /* View tenant list and tenant details */
    'totara/tenant:view' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'read',
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'tenantusermanager' => CAP_ALLOW,
        ),
    ),
    /* View list of tenant participants via tenant course category context */
    'totara/tenant:viewparticipants' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ),
    ),
    /* Change settings and create, update and delete tenants */
    'totara/tenant:config' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM, // Cannot be delegated in tenant contexts intentionally.
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        ),
    ),
    /* Add new tenant member users */
    'totara/tenant:usercreate' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'tenantusermanager' => CAP_ALLOW,
            'apiuser' => CAP_ALLOW,
        ),
    ),
    /* Add and remove other tenant participants, migrate users to and from tenants */
    'totara/tenant:manageparticipants' => array(
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
        ),
    ),
    /* Upload users to a tenant */
    'totara/tenant:userupload' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_TENANT,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'tenantusermanager' => CAP_ALLOW,
        ),
    ),
);
