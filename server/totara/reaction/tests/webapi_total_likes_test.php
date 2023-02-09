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
 * @package totara_reaction
 */
defined('MOODLE_INTERNAL') || die();

use totara_reaction\reaction_helper;
use totara_webapi\phpunit\webapi_phpunit_helper;
use engage_article\totara_engage\resource\article;

class totara_reaction_webapi_total_likes_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_total_likes(): void {
        $user = $this->setup_user();
        $article = $this->create_article($user->id);
        $users = $this->create_users(5);

        foreach ($users as $user) {
            reaction_helper::create_reaction(
                $article->get_id(),
                $article::get_resource_type(),
                $article::REACTION_AREA,
                $user->id
            );
        }

        $result = $this->execute_query([
            'component' => $article::get_resource_type(),
            'area' => $article::REACTION_AREA,
            'instanceid' => $article->get_id()
        ]);

        self::assertNotEmpty($result);
        self::assertEquals(5, $result);
    }

    private function execute_query(array $args) {
        return $this->resolve_graphql_query('totara_reaction_total', $args);
    }

    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        return $user;
    }

    private function create_users(int $number): array {
        $users = [];
        for ($i = 0; $i < $number; $i++) {
            $users[] = $this->getDataGenerator()->create_user();
        }

        return $users;
    }

    private function create_article(int $user_id): article {
        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');
        return $generator->create_public_article(['userid' => $user_id]);
    }
}