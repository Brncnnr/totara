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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use totara_webapi\graphql;
use core\webapi\execution_context;

class editor_weka_search_user_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_finding_users(): void {
        // Create a list of users that start with a letter 'G'
        $generator = $this->getDataGenerator();

        for ($i = 0; $i < 5; $i++) {
            $generator->create_user([
                'firstname' => uniqid('G_'),
                'lastname' => uniqid('G_')
            ]);
        }

        // Act like an admin, so that we don't have to resolve the field visibility.
        $this->setAdminUser();

        // Try to search for the users.
        $ec = execution_context::create('ajax', 'editor_weka_find_users_by_pattern');
        $result = graphql::execute_operation($ec, ['pattern' => 'G_']);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('users', $result->data);

        $this->assertNotEmpty($result->data['users']);
        $this->assertCount(5, $result->data['users']);
    }
}