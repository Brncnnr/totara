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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Block for displaying user profile details
 *
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package block_totara_user_profile
 */

/**
 * Block capabilities
 *
 */
$capabilities = array(

    'block/totara_user_profile:myaddinstance' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/totara_user_profile:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_PERSONAL,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW,
            'tenantdomainmanager' => CAP_ALLOW,
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

);

