<?php
/*
 * This file is part of Totara LMS
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
 * @author Qingyang liu <qingyang.liu@totaralearning.com>
 * @package totara_engage
 */

use engage_article\totara_engage\resource\article;
use totara_engage\access\access;

defined('MOODLE_INTERNAL') || die();

class totara_engage_webapi_shareto_recipients_testcase extends advanced_testcase {
    use \totara_webapi\phpunit\webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_shareto_recipients_without_search(): void {
        $this->setAdminUser();
        $article = $this->create_article();

        $gen = $this->getDataGenerator();
        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $user3 = $gen->create_user();

        $result = $this->execute_query([
            'itemid' => $article->get_id(),
            'component' => $article::get_resource_type(),
            'access' => access::get_code($article->get_access()),
            'theme' => 'ventura',
        ]);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $ids = array_map(
            function ($recipient): int {
                return $recipient['instanceid'];
            },
            $result
        );

        $this->assertContainsEquals($user1->id, $ids);
        $this->assertContainsEquals($user2->id, $ids);
        $this->assertContainsEquals($user3->id, $ids);
    }

    /**
     * @return void
     */
    public function test_shareto_recipients_with_search(): void {
        $this->setAdminUser();
        $article = $this->create_article();

        $gen = $this->getDataGenerator();
        $gen->create_user(['firstname' => 'aaa', 'lastname' => 'aaa']);
        $gen->create_user(['firstname' => 'bbb', 'lastname' => 'bbb']);
        $user3 = $gen->create_user(['firstname' => 'ccc', 'lastname' => 'ccc']);

        $result = $this->execute_query([
            'itemid' => $article->get_id(),
            'component' => $article::get_resource_type(),
            'access' => access::get_code($article->get_access()),
            'search' => 'cc',
            'theme' => 'ventura',
        ]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);

        $ids = array_map(
            function ($recipient): int {
                return $recipient['instanceid'];
            },
            $result
        );
        $this->assertEquals($user3->id, $ids[0]);
    }

    /**
     * @return void
     */
    public function test_shareto_recipients_with_invalid_component(): void {
        $this->setAdminUser();
        $article = $this->create_article();

        $this->expectException('coding_exception');
        $this->expectExceptionMessage("No provider found for component 'engage_aaaarticle'");
        $this->execute_query([
            'itemid' => $article->get_id(),
            'component' => 'engage_aaaarticle',
            'access' => access::get_code($article->get_access()),
            'theme' => 'ventura',
        ]);
    }

    /**
     * @return void
     */
    public function test_shareto_recipients_with_invalid_itemid(): void {
        $this->setAdminUser();
        $article = $this->create_article();
        $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_user();

        $this->expectException(moodle_exception::class);
        $this->execute_query([
            'itemid' => '111',
            'component' => $article::get_resource_type(),
            'access' => access::get_code($article->get_access()),
            'theme' => 'ventura',
        ]);
    }

    /**
     * @return void
     */
    public function test_shareto_recipients_with_different_logged_user(): void {
        $this->setAdminUser();
        $article = $this->create_article();

        $gen = $this->getDataGenerator();
        $current_user = $gen->create_user();

        // Login as other user
        $this->setUser($current_user);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('Permission denied');
        $this->execute_query([
            'itemid' => $article->get_id(),
            'component' => $article::get_resource_type(),
            'access' => access::get_code($article->get_access()),
            'theme' => 'ventura',
        ]);
    }

    /**
     * @return void
     */
    public function test_shareto_recipients_when_workspace_deleted(): void {
        $this->setAdminUser();

        $gen = $this->getDataGenerator();
        /** @var \container_workspace\testing\generator $workspacegen */
        $workspacegen = $gen->get_plugin_generator('container_workspace');

        $user1 = $gen->create_user();
        $user2 = $gen->create_user();
        $w1 = $workspacegen->create_workspace();
        $w2 = $workspacegen->create_workspace('search workspace');

        $result = $this->execute_query([
            'itemid' => 0,
            'search' => '',
            'component' => 'engage_article',
            'access' => access::get_code(access::PUBLIC),
            'theme' => 'ventura',
        ]);

        $this->assertIsArray($result);

        $ids = array_map(
            function ($recipient): int {
                return $recipient['instanceid'];
            },
            $result
        );

        $this->assertContainsEquals($user1->id, $ids);
        $this->assertContainsEquals($user2->id, $ids);
        $this->assertContainsEquals($w1->id, $ids);
        $this->assertContainsEquals($w2->id, $ids);

        // Remove the workspace.
        $w2->delete();

        $result = $this->execute_query([
            'itemid' => 0,
            'search' => '',
            'component' => 'engage_article',
            'access' => access::get_code(access::PUBLIC),
            'theme' => 'ventura',
        ]);

        $ids = array_map(
            function ($recipient): int {
                return $recipient['instanceid'];
            },
            $result
        );

        $this->assertContainsEquals($user1->id, $ids);
        $this->assertContainsEquals($user2->id, $ids);
        $this->assertContainsEquals($w1->id, $ids);
        $this->assertNotContainsEquals($w2->id, $ids);
    }

    /**
     * @param array $args
     * @return mixed|null
     */
    private function execute_query(array $args) {
        return $this->resolve_graphql_query('totara_engage_shareto_recipients', $args);
    }

    /**
     * @param string|null $name
     * @param int|null $access
     * @return article
     */
    private function create_article(?string $name = null, ?int $access = access::RESTRICTED ): article {
        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');

        $params = ['access' => $access];
        if (isset($name)) {
            $params['name'] = $name;
        }
        return $generator->create_article($params);
    }
}