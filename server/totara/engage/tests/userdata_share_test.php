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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_userdata\userdata\target_user;

class totara_engage_userdata_share_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_purge_share(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();
        $user_two = $gen->create_user();
        $this->setUser($user_one);

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        $article_one = $articlegen->create_article(['name' => 'article1']);
        $survey_one = $surveygen->create_survey('survey1?');

        $recipient_one = $gen->create_user();
        $recipient_two = $gen->create_user();
        $recipient_three = $gen->create_user();

        $article_recipient = $articlegen->create_user_recipients([$recipient_one, $recipient_two]);
        $survey_recipient = $surveygen->create_user_recipients([$recipient_one, $recipient_two, $recipient_three]);

        $articlegen->share_article($article_one, $article_recipient);
        $surveygen->share_survey($survey_one, $survey_recipient);

        $this->setUser($user_two);
        $article_two = $articlegen->create_article();
        $survey_two = $surveygen->create_survey();

        $articlegen->share_article($article_two, $article_recipient);
        $surveygen->share_survey($survey_two, $survey_recipient);

        // Share created.
        $this->assertTrue(
            $DB->record_exists('engage_share', ['ownerid' => $user_one->id])
        );
        $this->assertTrue(
            $DB->record_exists('engage_share', ['ownerid' => $user_two->id])
        );

        // Share recipient created.
        $this->assertTrue(
            $DB->record_exists('engage_share_recipient', ['sharerid' => $user_one->id])
        );
        $this->assertTrue(
            $DB->record_exists('engage_share_recipient', ['sharerid' => $user_two->id])
        );

        $user_one->deleted = 1;
        $DB->update_record('user', $user_one);

        $target_user = new target_user($user_one);
        $context = context_system::instance();

        $result = \totara_engage\userdata\share::execute_purge($target_user, $context);
        $this->assertEquals(\totara_engage\userdata\share::RESULT_STATUS_SUCCESS, $result);

        // User has to be purged.
        $this->assertFalse(
            $DB->record_exists('engage_share', ['ownerid' => $user_one->id])
        );
        $this->assertFalse(
            $DB->record_exists('engage_share_recipient', ['sharerid' => $user_one->id])
        );

        // Exiting user is still in the database.
        $this->assertTrue(
            $DB->record_exists('engage_share', ['ownerid' => $user_two->id])
        );
        $this->assertTrue(
            $DB->record_exists('engage_share_recipient', ['sharerid' => $user_two->id])
        );
    }

    /**
     * @return void
     */
    public function test_export_share(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $user_one = $gen->create_user();
        $this->setUser($user_one);

        /** @var \engage_article\testing\generator $articlegen */
        $articlegen = $gen->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $surveygen */
        $surveygen = $gen->get_plugin_generator('engage_survey');

        $article_one = $articlegen->create_article(['name' => 'article1']);
        $survey_one = $surveygen->create_survey('survey1?');

        $recipient_one = $gen->create_user();
        $recipient_two = $gen->create_user();
        $recipient_three = $gen->create_user();

        $article_recipient = $articlegen->create_user_recipients([$recipient_one, $recipient_two]);
        $survey_recipient = $surveygen->create_user_recipients([$recipient_one, $recipient_two, $recipient_three]);

        $articlegen->share_article($article_one, $article_recipient);
        $surveygen->share_survey($survey_one, $survey_recipient);

        // Share created
        $this->assertTrue(
            $DB->record_exists('engage_share', ['ownerid' => $user_one->id])
        );

        $target_user = new target_user($user_one);
        $context = context_system::instance();

        $export = \totara_engage\userdata\share::execute_export($target_user, $context);

        $this->assertNotEmpty($export->data);
        $this->assertCount(5, $export->data);

        foreach ($export->data as $record) {
            $this->assertIsArray($record);
            $this->assertArrayHasKey('name', $record);
            $this->assertArrayHasKey('timecreated', $record);
            $this->assertArrayHasKey('recipient', $record);
        }
    }
}