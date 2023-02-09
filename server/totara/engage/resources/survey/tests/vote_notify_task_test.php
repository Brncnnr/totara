<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @package engage_survey
 */

defined('MOODLE_INTERNAL') || die();

use core\task\manager;
use engage_survey\task\vote_notify_task;

class engage_survey_vote_notify_task_testcase extends advanced_testcase {
    use \core_phpunit\language_pack_faker_trait;

    public function test_recipients_language_setting_is_observed(): void {
        $generator = self::getDataGenerator();
        $fake_language = 'xo_ox';
        $this->add_fake_language_pack(
            $fake_language,
            [
                'engage_survey' => [
                    'vote_message_subject' => 'Fake language subject string'
                ]
            ]
        );

        $owner = $generator->create_user(['lang' => $fake_language]);
        $voter = $generator->create_user();

        $task =  new vote_notify_task();
        $task->set_custom_data([
            'url' => 'http://www.example.com',
            'owner' => $owner->id,
            'voter' => $voter->id,
            'name' => 'Survey test name',
        ]);

        manager::queue_adhoc_task($task);

        // Start the sink and execute the adhoc tasks.
        $message_sink = $this->redirectMessages();
        $this->executeAdhocTasks();

        $messages = $message_sink->get_messages();
        self::assertCount(1, $messages);
        $message = reset($messages);

        self::assertEquals($owner->id, $message->useridto);
        self::assertEquals('Fake language subject string', $message->subject);
    }
}