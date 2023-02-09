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

defined('MOODLE_INTERNAL') || die();

/**
 * Tests changes in db/access.php files.
 */
class totara_tenant_access_testcase extends advanced_testcase {
    public function test_change_to_user_level() {
        $capabilities = [
            'moodle/site:sendmessage',
            'moodle/site:deleteownmessage',
            'moodle/user:changeownpassword',
            'moodle/user:delete',
            'moodle/user:editownmessageprofile',
            'moodle/user:editownprofile',
            'moodle/user:manageownblocks',
            'moodle/user:manageownfiles',
            'moodle/user:viewhiddendetails',
            'totara/core:editownquickaccessmenu',
            'totara/hierarchy:assignselfposition',
            'totara/hierarchy:assignuserposition',
        ];

        foreach ($capabilities as $capname) {
            $capinfo = get_capability_info($capname);
            $this->assertSame(strval(CONTEXT_USER), $capinfo->contextlevel, "Capability $capname is supposed to be using CONTEXT_USER level");
        }
    }
}
