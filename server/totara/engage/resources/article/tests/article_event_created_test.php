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
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use engage_article\event\article_created;
use engage_article\totara_engage\resource\article;
use engage_article\entity\article as article_entity;
use totara_engage\entity\engage_resource as engage_resource_entity;
use totara_engage\access\access;

class engage_article_event_article_created_testcase extends advanced_testcase {
    /**
     * @param int $user_id
     * @return article
     */
    private function create_article(int $user_id): article {
        $article_entity = new article_entity();
        $article_entity->content = 'Hello world';
        $article_entity->format = FORMAT_PLAIN;
        $article_entity->save();

        $resource_entity = new engage_resource_entity();
        $resource_entity->userid = $user_id;
        $resource_entity->name = 'This is article';
        $resource_entity->instanceid = $article_entity->id;
        $resource_entity->access = access::PRIVATE;
        $resource_entity->resourcetype = article::get_resource_type();

        $resource_entity->save();
        return article::from_entity($article_entity, $resource_entity);
    }

    /**
     * @return void
     */
    public function test_create_event_without_actor_id(): void {
        global $USER;
        $this->setGuestUser();

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $article = $this->create_article($user_one->id);
        self::assertEquals($user_one->id, $article->get_userid());

        // Now check the creation of event, that it does not fallback to global $USER.
        $event = article_created::from_article($article);

        self::assertNotEquals($USER->id, $event->get_user_id());
        self::assertEquals($user_one->id, $event->get_user_id());
    }

    /**
     * This test is to emulate the scenario when the creator of the article is
     * a completely different user from the user who created the event.
     *
     * By the look of the code, we allow it to do so. However, it is up to the developers
     * to pick the right arguments in their own implementation.
     *
     * This is to prevent the developers from changing the logic of code only.
     *
     * @return void
     */
    public function test_create_event_with_different_actor_id(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $article = $this->create_article($user_one->id);
        self::assertEquals($user_one->id, $article->get_userid());

        $user_two = $generator->create_user();
        $event = article_created::from_article($article, $user_two->id);


        self::assertNotEquals($user_one->id, $event->get_user_id());
        self::assertEquals($user_two->id, $event->get_user_id());

        self::assertEquals($user_one->id, $event->relateduserid);
    }
}