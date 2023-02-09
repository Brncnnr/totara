<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use totara_engage\access\access;
use ml_recommender\entity\interaction;
use engage_article\event\article_viewed;
use totara_engage\timeview\time_view;
use engage_article\totara_engage\resource\article;

class engage_article_event_article_viewed_testcase extends advanced_testcase {

    public function test_view_private_article_interaction(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $data = [
            'name' => 'private article',
            'content' => 'test',
            'access' => access::PRIVATE,
            'timeview' => time_view::LESS_THAN_FIVE
        ];

        $article = article::create($data);
        $event = article_viewed::from_article($article);
        $this->assertFalse($event->is_public());
        $event->trigger();

        $interactions = interaction::repository()->get();
        $this->assertEmpty($interactions);
    }

    public function test_view_public_article_interaction(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $data = [
            'name' => 'public article',
            'content' => 'test',
            'access' => access::PUBLIC,

            'timeview' => time_view::LESS_THAN_FIVE
        ];

        $article = article::create($data);
        $event = article_viewed::from_article($article);
        $this->assertTrue($event->is_public());
        $event->trigger();

        $interactions = interaction::repository()->get();
        $this->assertNotEmpty($interactions);
    }
}