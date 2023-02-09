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

class editor_weka_query_editor_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_query_editor_via_graphql(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $ec = execution_context::create('ajax', 'editor_weka_weka');
        $result = graphql::execute_operation($ec, [
            'component' => 'editor_weka',
            'area' => 'phpunit'
        ]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
    }

    /**
     * Make sure the query can be run without a user in session.
     */
    public function test_query_editor_nosession_via_graphql(): void {
        $ec = execution_context::create('ajax', 'editor_weka_weka');
        $result = graphql::execute_operation($ec, [
            'component' => 'editor_weka',
            'area' => 'phpunit'
        ]);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);
    }

}