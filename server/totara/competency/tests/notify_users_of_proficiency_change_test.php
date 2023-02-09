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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package totara_competency
 */

use core_phpunit\testcase;
use totara_competency\hook\competency_achievement_updated_bulk;
use totara_competency\watcher\notify_users_of_proficiency_change;
use totara_hierarchy\entity\competency;
use core\orm\query\builder;

/**
 * Class notify_users_of_proficiency_change_test
 *
 * @group totara_competency
 */
class totara_competency_notify_users_of_proficiency_change_test extends testcase {

    /**
     * @var int
     */
    private $user_count = 10;

    /**
     * @var array
     */
    private $user_ids;

    /**
     * @var competency
     */
    private $competency;

    /**
     * Test notify users of competency change watcher sends message.
     *
     * @return void
     */
    public function test_message_is_sent_to_user(): void {
        $hook = new competency_achievement_updated_bulk($this->competency);

        foreach ($this->user_ids as $user_id) {
            $hook->add_user_id(
                $user_id,
                [
                    'is_proficient' => 0,
                    'new_scale_value' => [
                        'id' => 15,
                        'name' => 'Newcomer',
                    ],
                    'proficiency_changed' => true,
                ]
            );
        }

        notify_users_of_proficiency_change::send_notification($hook);
        $messages_to_users = builder::table('notifications')->get()->pluck('useridto');
        $this->assertEqualsCanonicalizing($messages_to_users, $this->user_ids);
    }

    /**
     * Test proficiency change message is sent on hook execution.
     *
     * @return void
     */
    public function test_message_is_sent_on_hook_execution(): void {
        $hook = new competency_achievement_updated_bulk($this->competency);

        foreach ($this->user_ids as $user_id) {
            $hook->add_user_id(
                $user_id,
                [
                    'is_proficient' => 0,
                    'new_scale_value' => [
                        'id' => 15,
                        'name' => 'Newcomer',
                    ],
                    'proficiency_changed' => true,
                ]
            );
        }

        $hook->execute();
        $messages_to_users = builder::table('notifications')->get()->pluck('useridto');
        $this->assertEqualsCanonicalizing($messages_to_users, $this->user_ids);
    }

    /**
     * Setup users for test.
     */
    protected function setUp(): void {
        $generator = $this->getDataGenerator();
        $competency_generator = $generator->get_plugin_generator('totara_competency');
        $framework = $competency_generator->create_framework();
        $this->competency = $competency_generator->create_competency('My Competency', $framework);

        for ($i = 0; $i < $this->user_count; $i++) {
            $user = $generator->create_user();
            $this->user_ids[] = $user->id;
        }
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void {
        $this->user_count = null;
        $this->user_ids = null;
        $this->competency = null;
    }
}