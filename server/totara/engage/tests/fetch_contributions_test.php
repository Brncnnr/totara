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
 * @package totara_engage
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\answer\answer_type;
use totara_engage\access\access;
use totara_engage\query\query;
use totara_engage\card\card_loader;
use core_user\totara_engage\share\recipient\user;
use totara_engage\card\card;

class totara_engage_fetch_contributions_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_fetch_cards_exclude_deleted_users(): void {
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $user_three_recipient = new user($user_three->id);

        // Create 5 items for both user one and two.
        $resource_ids = [];
        for ($i = 0; $i < 5; $i++) {
            if (0 === $i % 2) {
                // Create survey.
                $user_one_survey = $survey_generator->create_survey(
                    "Question for user one {$i}",
                    [],
                    answer_type::MULTI_CHOICE,
                    [
                        'userid' => $user_one->id,
                        'access' => access::PUBLIC
                    ]
                );


                $user_two_survey = $survey_generator->create_survey(
                    "Question for user two {$i}",
                    [],
                    answer_type::MULTI_CHOICE,
                    [
                        'userid' => $user_two->id,
                        'access' => access::PUBLIC
                    ]
                );

                // Share the surveys to user three.
                $survey_generator->share_survey($user_one_survey, [$user_three_recipient]);
                $survey_generator->share_survey($user_two_survey, [$user_three_recipient]);

                $resource_ids[] = $user_one_survey->get_id();
                $resource_ids[] = $user_two_survey->get_id();

                continue;
            }

            // Create public articles
            $user_one_article = $article_generator->create_article([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);

            $user_two_article = $article_generator->create_article([
                'userid' => $user_two->id,
                'access' => access::PUBLIC
            ]);

            $article_generator->share_article($user_one_article, [$user_three_recipient]);
            $article_generator->share_article($user_two_article, [$user_three_recipient]);

            $resource_ids[] = $user_one_article->get_id();
            $resource_ids[] = $user_two_article->get_id();
        }

        // Fetching all the resources as user three.
        $query = new query();
        $query->set_share_recipient_id($user_three_recipient->get_id());

        $card_loader = new card_loader($query);
        $before_delete_result = $card_loader->fetch_shared($user_three_recipient);

        // 5 articles/surveys from user one are shared to user three.
        // 5 articles/surveys from user two are shared to user three.
        // In total we should have 10 of the items.
        self::assertEquals(count($resource_ids), $before_delete_result->get_total());
        $before_delete_items = $before_delete_result->get_items()->all();

        self::assertCount(count($resource_ids), $before_delete_items);

        /** @var card $item */
        foreach ($before_delete_items as $item) {
            self::assertInstanceOf(card::class, $item);
            self::assertContainsEquals($item->get_userid(), [$user_one->id, $user_two->id]);
            self::assertContainsEquals($item->get_instanceid(), $resource_ids);
        }

        // Now delete the second user and fetch shared again, the result should go down to.
        delete_user($user_two);
        $after_delete_result = $card_loader->fetch_shared($user_three_recipient);

        // 5 articles/surveys from user one are shared to user three.
        // It is excluding the 5 articles/surveys from user two that are shared to user three.
        self::assertEquals(5, $after_delete_result->get_total());
        $after_delete_items = $after_delete_result->get_items()->all();

        self::assertCount(5, $after_delete_items);

        /** @var card $item */
        foreach ($after_delete_items as $item) {
            self::assertInstanceOf(card::class, $item);
            self::assertNotEquals($user_two->id, $item->get_userid());
            self::assertEquals($user_one->id, $item->get_userid());
        }
    }

    /**
     * Suspend users should not exclude their content.
     * @return void
     */
    public function test_fetch_cards_should_not_exclude_suspended_users(): void {
        global $CFG;
        $generator = $this->getDataGenerator();

        $user_one = $generator->create_user();
        $user_two = $generator->create_user();
        $user_three = $generator->create_user();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');

        /** @var \engage_survey\testing\generator $survey_generator */
        $survey_generator = $generator->get_plugin_generator('engage_survey');
        $user_three_recipient = new user($user_three->id);

        // Create 5 items for both user one and two.
        $resource_ids = [];
        for ($i = 0; $i < 5; $i++) {
            if (0 === $i % 2) {
                // Create survey.
                $user_one_survey = $survey_generator->create_survey(
                    "Question for user one {$i}",
                    [],
                    answer_type::MULTI_CHOICE,
                    [
                        'userid' => $user_one->id,
                        'access' => access::PUBLIC
                    ]
                );


                $user_two_survey = $survey_generator->create_survey(
                    "Question for user two {$i}",
                    [],
                    answer_type::MULTI_CHOICE,
                    [
                        'userid' => $user_two->id,
                        'access' => access::PUBLIC
                    ]
                );

                // Share the surveys to user three.
                $survey_generator->share_survey($user_one_survey, [$user_three_recipient]);
                $survey_generator->share_survey($user_two_survey, [$user_three_recipient]);

                $resource_ids[] = $user_one_survey->get_id();
                $resource_ids[] = $user_two_survey->get_id();

                continue;
            }

            // Create public articles
            $user_one_article = $article_generator->create_article([
                'userid' => $user_one->id,
                'access' => access::PUBLIC
            ]);

            $user_two_article = $article_generator->create_article([
                'userid' => $user_two->id,
                'access' => access::PUBLIC
            ]);

            $article_generator->share_article($user_one_article, [$user_three_recipient]);
            $article_generator->share_article($user_two_article, [$user_three_recipient]);

            $resource_ids[] = $user_one_article->get_id();
            $resource_ids[] = $user_two_article->get_id();
        }

        // Fetching all the resources as user three.
        $query = new query();
        $query->set_share_recipient_id($user_three_recipient->get_id());

        $card_loader = new card_loader($query);
        $before_suspend_result = $card_loader->fetch_shared($user_three_recipient);

        // 5 articles/surveys from user one are shared to user three.
        // 5 articles/surveys from user two are shared to user three.
        // In total we should have 10 of the items.
        self::assertEquals(count($resource_ids), $before_suspend_result->get_total());
        $before_suspend_items = $before_suspend_result->get_items()->all();

        self::assertCount(count($resource_ids), $before_suspend_items);

        /** @var card $item */
        foreach ($before_suspend_items as $item) {
            self::assertInstanceOf(card::class, $item);
            self::assertContainsEquals($item->get_userid(), [$user_one->id, $user_two->id]);
            self::assertContainsEquals($item->get_instanceid(), $resource_ids);
        }

        // Now suspend the second user and fetch shared again, the result should go down to 5.
        require_once("{$CFG->dirroot}/user/lib.php");
        user_suspend_user($user_two->id);
        $after_suspend_result = $card_loader->fetch_shared($user_three_recipient);

        // 5 articles/surveys from user one are shared to user three.
        // It should NOT excluding the 5 articles/surveys from user two that are shared to user three.
        self::assertEquals(10, $after_suspend_result->get_total());
        $after_suspend_items = $after_suspend_result->get_items()->all();

        self::assertCount(10, $after_suspend_items);

        /** @var card $item */
        foreach ($after_suspend_items as $item) {
            self::assertInstanceOf(card::class, $item);
            self::assertContainsEquals($item->get_userid(), [$user_two->id, $user_one->id]);
        }
    }
}