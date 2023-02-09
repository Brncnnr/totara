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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die();

use core\webapi\execution_context;
use totara_engage\access\access;
use totara_webapi\graphql;

class totara_engage_shareto_graphql_testcase extends advanced_testcase {

    public function test_shareto_recipients() {
        global $CFG;

        $gen = $this->getDataGenerator();
        /** @var \totara_playlist\testing\generator $playlistgen */
        $engagegen = $gen->get_plugin_generator('totara_engage');

        // Create users.
        $users = $engagegen->create_users(3);
        $this->setUser($users[1]);

        // Get recipients via graphql.
        $ec = execution_context::create('ajax', 'totara_engage_shareto_recipients');
        $parameters = [
            'itemid' => 0,
            'component' => 'no_component',
            'search' => 'some1 a',
            'access' => access::get_code(access::RESTRICTED),
            'theme' => 'ventura',
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message: '');
        $this->assertNotEmpty($result->data);
        $this->assertArrayHasKey('recipients', $result->data);

        $recipients = $result->data['recipients'];
        $this->assertEquals(1, sizeof($recipients));
        $user = reset($recipients);
        $this->assertArrayHasKey('user', $user);
        $this->assertArrayHasKey('display_fields', $user['user']['card_display']);
        $this->assertEquals('Some1 Any1', $user['user']['card_display']['display_fields'][0]['value']);

        // Confirm that we get debug message when theme not passed.
        $ec = execution_context::create('ajax', 'totara_engage_shareto_recipients');
        $parameters = [
            'itemid' => 0,
            'component' => 'no_component',
            'search' => 'some1 a',
            'access' => access::get_code(access::RESTRICTED),
        ];

        $result = graphql::execute_operation($ec, $parameters);
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->message : '');
        $this->assertDebuggingCalled(
            "'theme' parameter not set. Falling back on {$CFG->theme}. The resolved assets "
            . "will be associated with {$CFG->theme}, which might not be the expected result."
        );
    }
}