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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package engage_article
 */
defined('MOODLE_INTERNAL') || die();

use engage_article\totara_engage\resource\article;
use totara_engage\access\access;

class engage_article_webapi_get_article_testcase extends advanced_testcase {
    use \totara_webapi\phpunit\webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_get_private_article(): void {
        $user = $this->setup_user();
        $article = $this->create_article(['userid' => $user->id]);

        $result = $this->execute_query(['id' => $article->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($article->get_id(), $result->get_id());

        $user = $this->setup_user();
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("User with id '{$user->id}' does not have access to this article");
        $this->execute_query(['id' => $article->get_id()]);
    }

    /**
     * @return void
     */
    public function test_get_public_article(): void {
        $user = $this->setup_user();
        $article = $this->create_article(['userid' => $user->id, 'access' => access::PUBLIC]);

        $this->setup_user();
        $result = $this->execute_query(['id' => $article->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($article->get_id(), $result->get_id());
    }

    /**
     * @return void
     */
    public function test_get_restricted_article(): void {
        $user = $this->setup_user();
        $article = $this->create_article(['userid' => $user->id, 'access' => access::RESTRICTED]);

        $result = $this->execute_query(['id' => $article->get_id()]);
        self::assertNotEmpty($result);
        self::assertEquals($article->get_id(), $result->get_id());
    }

    /**
     * @return void
     */
    public function test_get_non_exist_article(): void {
        $this->setup_user();
        $this->create_article();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("No article found");
        $this->execute_query(['id' => 3]);
    }

    private function execute_query(array $args = []) {
        return $this->resolve_graphql_query('engage_article_get_article', $args);
    }

    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        return $user;
    }

    private function create_article(?array $params = []): article {
        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');
        return $generator->create_article($params);
    }
}