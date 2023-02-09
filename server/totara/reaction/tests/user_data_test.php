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
 * @package totara_reaction
 */
defined('MOODLE_INTERNAL') || die();

use totara_reaction\resolver\resolver_factory;
use totara_reaction\reaction_helper;
use totara_userdata\userdata\target_user;
use totara_reaction\userdata\reaction as user_data_reaction;
use totara_reaction\loader\reaction_loader;

class totara_reaction_user_data_testcase extends advanced_testcase {
    /**
     * Test to make sure that the purge is only purging deleted user's related record(s).
     *
     * @return void
     */
    public function test_purge_reaction_records(): void {
        global $DB, $CFG;

        $generator = $this->getDataGenerator();

        // We need two users in order to see the purge is valid.
        $user_one = $generator->create_user();
        $user_two = $generator->create_user();

        require_once("{$CFG->dirroot}/totara/reaction/tests/fixtures/default_reaction_resolver.php");
        $resolver = new default_reaction_resolver();
        $resolver->set_component('dota_pudge');

        resolver_factory::phpunit_set_resolver($resolver);

        // Start creating 20 records for the instance of both user.
        for ($i = 1; $i <= 20; $i++) {
            reaction_helper::create_reaction($i, 'dota_pudge', 'fresh_meat', $user_one->id);
            reaction_helper::create_reaction($i, 'dota_pudge', 'fresh_meat', $user_two->id);
        }

        $this->assertEquals(20, $DB->count_records('reaction', ['userid' => $user_one->id]));
        $this->assertEquals(20, $DB->count_records('reaction', ['userid' => $user_two->id]));

        $user_two->deleted = 1;
        $DB->update_record('user', $user_two);

        $context = context_system::instance();
        $target_user = new target_user($user_two);

        $result = user_data_reaction::execute_purge($target_user, $context);

        $this->assertEquals(user_data_reaction::RESULT_STATUS_SUCCESS, $result);
        $this->assertFalse($DB->record_exists('reaction', ['userid' => $user_two->id]));
    }

    /**
     * This is to assure that when user is deleted, the result for existing check should return
     * false, as the record should not be found.
     *
     * @return void
     */
    public function test_check_existing_when_user_deleted(): void {
        global $CFG;
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();

        $this->setUser($user);
        require_once("{$CFG->dirroot}/totara/reaction/tests/fixtures/default_reaction_resolver.php");

        $resolver = new default_reaction_resolver();
        $resolver->set_component('dota_pudge');

        resolver_factory::phpunit_set_resolver($resolver);
        $reaction = reaction_helper::create_reaction(42, 'dota_pudge', 'fresh_meat', $user->id);

        $this->assertTrue($reaction->exists());
        // Start deleting the user.
        $result = delete_user($user);
        $this->assertTrue($result);

        $this->assertFalse(
            reaction_loader::exist(
                42,
                'dota_pudge',
                'fresh_meat',
                $user->id
            )
        );
    }
}