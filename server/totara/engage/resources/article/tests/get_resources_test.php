<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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

use engage_article\totara_engage\resource\article;
use totara_engage\access\access;
use engage_article\local\loader;
use totara_engage\timeview\time_view;

class engage_article_get_resource_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_resources(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $data = [
            'name' => 'xx oo',
            'content' => 'bolobala',
            'visible' => access::PUBLIC,
            'timeview' => time_view::LESS_THAN_FIVE
        ];

        article::create($data);

        $paginator = loader::load_all_article_of_user($user->id);
        $this->assertEquals(1, $paginator->get_total());
    }

    /**
     * @return void
     */
    public function test_count_resources(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        for ($i = 0; $i < 5; $i++) {
            $data = [
                'name' => uniqid("xoxoxo"),
                'content' => 'lorem ipsum xvikokf g l[]pwq;dl ]d wqihjo iqd',
                'visible' => access::PUBLIC,
                'timeview' => time_view::LESS_THAN_FIVE
            ];

            article::create($data, $user->id);
        }

        $paginator = loader::load_all_article_of_user($user->id);
        $this->assertEquals(5, $paginator->get_total());
    }
}