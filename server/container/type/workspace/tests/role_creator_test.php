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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Test the creation of the workspace creator & owner roles
 */
class container_workspace_role_testcase extends advanced_testcase {
    /**
     * Test the utility function creates the workspace roles if they're missing
     *
     * @return void
     */
    public function test_role_creation(): void {
        global $DB;

        // Make sure roles were created.
        $this->assertTrue($DB->record_exists('role', ['shortname' => 'workspacecreator']));
        $this->assertTrue($DB->record_exists('role', ['shortname' => 'workspaceowner']));
    }
}