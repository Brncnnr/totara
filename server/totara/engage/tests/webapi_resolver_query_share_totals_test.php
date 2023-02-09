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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_engage
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests the share_totals query within totara_engage.
 */
class totara_engage_webapi_resolver_query_share_totals_testcase extends advanced_testcase {

    use \totara_webapi\phpunit\webapi_phpunit_helper;

    private function execute_query(array $args) {
        return $this->resolve_graphql_query('totara_engage_share_totals', $args);
    }

    private function setup_user() {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        return $user;
    }

    private function create_article($name, $userid, $content = null): \engage_article\totara_engage\resource\article {
        /** @var \engage_article\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('engage_article');
        $params = [
            'name' => $name,
            'userid' => $userid,
        ];
        if ($content !== null) {
            $params['content'] = $content;
        }
        return $generator->create_article($params);
    }

    private function create_workspace($name, $userid, $summary = null, $private = false, $hidden = false): \container_workspace\workspace {
        /** @var \container_workspace\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        return $generator->create_workspace($name, $summary ?? "{$name} summary", FORMAT_PLAIN, $userid, $private, $hidden);
    }

    private function create_share(\totara_engage\share\shareable $item, int $fromuserid, array $recipients, $ownerid = null) {
        /** @var \totara_engage\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_engage');
        return $generator->share_item($item, $fromuserid, $recipients, $ownerid);
    }

    public function test_known_provides() {
        $classes = \core_component::get_namespace_classes(
            'totara_engage\\share',
            \totara_engage\share\provider::class
        );
        // If these change you'll need to update the tests in this file.
        self::assertContains('engage_article\\totara_engage\\share\\article_provider', $classes);
        self::assertContains('engage_survey\\totara_engage\\share\\survey_provider', $classes);
        self::assertContains('totara_playlist\\totara_engage\\share\\playlist_provider', $classes);
    }

    public function test_happy_path_no_shares() {
        $user = $this->setup_user();
        $article = $this->create_article('test', $user->id);
        self::assertSame(
            [
                'totalrecipients' => 0,
                'recipients' => []
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );
    }

    public function test_happy_path_one_user_share() {
        $user1 = $this->setup_user();
        $user2 = $this->getDataGenerator()->create_user();
        $article = $this->create_article('test', $user1->id);
        $recipient = new \core_user\totara_engage\share\recipient\user($user2->id);
        $this->create_share(
            $article,
            $user1->id,
            [$recipient]
        );
        self::assertSame(
            [
                'totalrecipients' => 1,
                'recipients' => [
                    [
                        'area' => 'user',
                        'label' => 'Users',
                        'total' => 1
                    ]
                ]
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );
    }

    public function test_happy_path_two_users_share() {
        $user1 = $this->setup_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $article = $this->create_article('test', $user1->id);
        $this->create_share(
            $article,
            $user1->id,
            [
                new \core_user\totara_engage\share\recipient\user($user2->id),
                new \core_user\totara_engage\share\recipient\user($user3->id),
            ]
        );
        self::assertSame(
            [
                'totalrecipients' => 2,
                'recipients' => [
                    [
                        'area' => 'user',
                        'label' => 'Users',
                        'total' => 2
                    ]
                ]
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );
    }

    public function test_happy_path_one_workspace_share() {
        $user1 = $this->setup_user();
        $user2 = $this->getDataGenerator()->create_user();
        $article = $this->create_article('test', $user1->id);
        $workspace = $this->create_workspace('test workspace', $user2->id);
        $recipient = new \container_workspace\totara_engage\share\recipient\library($workspace->id);
        $this->create_share(
            $article,
            $user1->id,
            [$recipient]
        );
        self::assertSame(
            [
                'totalrecipients' => 1,
                'recipients' => [
                    [
                        'area' => 'library',
                        'label' => 'Workspaces',
                        'total' => 1
                    ]
                ]
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );
    }

    public function test_happy_path_two_workspaces_share() {
        $user1 = $this->setup_user();
        $user2 = $this->getDataGenerator()->create_user();
        $article = $this->create_article('test', $user1->id);
        $workspace1 = $this->create_workspace('test workspace 1', $user1->id);
        $workspace2 = $this->create_workspace('test workspace 2', $user2->id);
        $this->create_share(
            $article,
            $user1->id,
            [
                new \container_workspace\totara_engage\share\recipient\library($workspace1->id),
                new \container_workspace\totara_engage\share\recipient\library($workspace2->id)
            ]
        );
        self::assertSame(
            [
                'totalrecipients' => 2,
                'recipients' => [
                    [
                        'area' => 'library',
                        'label' => 'Workspaces',
                        'total' => 2
                    ]
                ]
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );
    }

    public function test_happy_path_mixed_users_workspaces_share() {
        $user1 = $this->setup_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $article = $this->create_article('test', $user1->id);
        $workspace1 = $this->create_workspace('test workspace 1', $user1->id);
        $workspace2 = $this->create_workspace('test workspace 2', $user2->id);
        $this->create_share(
            $article,
            $user1->id,
            [
                new \container_workspace\totara_engage\share\recipient\library($workspace1->id),
                new \core_user\totara_engage\share\recipient\user($user2->id),
                new \container_workspace\totara_engage\share\recipient\library($workspace2->id),
                new \core_user\totara_engage\share\recipient\user($user3->id),
            ]
        );
        self::assertEqualsCanonicalizing(
            [
                'totalrecipients' => 4,
                'recipients' => [
                    [
                        'area' => 'library',
                        'label' => 'Workspaces',
                        'total' => 2
                    ],
                    [
                        'area' => 'user',
                        'label' => 'Users',
                        'total' => 2
                    ]
                ]
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );
    }

    public function test_happy_path_guest_mixed_users_workspaces_share() {
        $this->setGuestUser();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $article = $this->create_article('test', $user1->id);
        $this->create_workspace('test workspace 1', $user1->id);

        $this->setUser($user1);
        $workspace1 = $this->create_workspace('test workspace 1', $user1->id);
        $this->setUser($user2);
        $workspace2 = $this->create_workspace('test workspace 2', $user2->id);

        $this->setGuestUser();
        $this->create_share(
            $article,
            $user1->id,
            [
                new \container_workspace\totara_engage\share\recipient\library($workspace1->id),
                new \core_user\totara_engage\share\recipient\user($user2->id),
                new \container_workspace\totara_engage\share\recipient\library($workspace2->id),
                new \core_user\totara_engage\share\recipient\user($user3->id),
            ]
        );

        try {
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()]);
            $this->fail('The guest is not meant to be able to view this article, its not public.');
        } catch (moodle_exception $exception) {
            $this->assertStringContainsString('Permission denied', $exception->getMessage());
        }

        // Make it public so that the guest can see it.
        // TODO: TL-27420 we need to change to admin because of a global $USER bug.
        $this->setAdminUser();
        $article->update(['access' => \totara_engage\access\access::PUBLIC], get_admin()->id);
        $this->setGuestUser();


        // TODO: TL-27413 will see the need for this hack removed.
        $cap = 'totara/engage:viewlibrary';
        $context = \context_system::instance();
        self::assertFalse(has_capability($cap, $context, guest_user()->id));
        assign_capability($cap, CAP_ALLOW, get_guest_role()->id, $context);
        self::assertTrue(has_capability($cap, $context, guest_user()->id));

        self::assertEqualsCanonicalizing(
            [
                'totalrecipients' => 4,
                'recipients' => [
                    [
                        'area' => 'library',
                        'label' => 'Workspaces',
                        'total' => 2
                    ],
                    [
                        'area' => 'user',
                        'label' => 'Users',
                        'total' => 2
                    ]
                ]
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );
    }

    public function test_itemid_is_required() {
        $this->setup_user();
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('ItemID is a required field.');
        $this->execute_query(['component' => 'engage_article']);
    }

    public function test_component_is_required() {
        $this->setup_user();
        self::expectException(coding_exception::class);
        self::expectExceptionMessage('Component is a required field.');
        $this->execute_query(['itemid' => 1]);
    }

    public function test_no_arguments() {
        $this->setup_user();
        self::expectException(coding_exception::class);
        $this->execute_query([]);
    }

    public function test_invalid_component_not_accepted() {
        $this->setup_user();
        $course = $this->getDataGenerator()->create_course();
        self::expectException(coding_exception::class);
        $this->execute_query(['component' => 'core_course', 'itemid' => $course->id]);
    }

    public function test_invalid_itemid_not_accepted() {
        $this->setup_user();
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Permission denied');
        $this->execute_query(['component' => 'engage_article', 'itemid' => -1]);
    }

    public function test_non_existent_itemid_not_accepted() {
        $this->setup_user();
        self::expectException(moodle_exception::class);
        self::expectExceptionMessage('Permission denied');
        $this->execute_query(['component' => 'engage_article', 'itemid' => 42]);
    }

    public function test_query_checks_access() {
        $user1 = $this->setup_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $article = $this->create_article('test', $user1->id);
        $article->update(['access' => \totara_engage\access\access::RESTRICTED]);
        $recipient = new \core_user\totara_engage\share\recipient\user($user2->id);

        self::assertFalse($recipient::is_user_permitted($article, $user1->id));
        self::assertFalse($recipient::is_user_permitted($article, $user2->id));
        self::assertFalse($recipient::is_user_permitted($article, $user3->id));
        self::assertTrue(\totara_engage\access\access_manager::can_access($article, $user1->id));
        self::assertFalse(\totara_engage\access\access_manager::can_access($article, $user2->id));
        self::assertFalse(\totara_engage\access\access_manager::can_access($article, $user3->id));

        $this->create_share(
            $article,
            $user1->id,
            [$recipient],
            $user1->id
        );

        self::assertFalse($recipient::is_user_permitted($article, $user1->id));
        self::assertTrue($recipient::is_user_permitted($article, $user2->id));
        self::assertFalse($recipient::is_user_permitted($article, $user3->id));
        self::assertTrue(\totara_engage\access\access_manager::can_access($article, $user1->id));
        self::assertTrue(\totara_engage\access\access_manager::can_access($article, $user2->id));
        self::assertFalse(\totara_engage\access\access_manager::can_access($article, $user3->id));

        // I am currently user 1, and own the article, I can see the shares
        self::assertSame(
            [
                'totalrecipients' => 1,
                'recipients' => [
                    [
                        'area' => 'user',
                        'label' => 'Users',
                        'total' => 1
                    ]
                ]
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );

        // I am now user 2, the article was shared with me, so I can see its shares
        $this->setUser($user2);
        self::assertSame(
            [
                'totalrecipients' => 1,
                'recipients' => [
                    [
                        'area' => 'user',
                        'label' => 'Users',
                        'total' => 1
                    ]
                ]
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );

        // I am now user 3, I have no access to the article, I cannot access the shares.
        $this->setUser($user3);
        try {
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()]);
            $this->fail('Exception expected.');
        } catch (moodle_exception $ex) {
            self::assertInstanceOf(moodle_exception::class, $ex);
            self::assertStringContainsString('Permission denied', $ex->getMessage());
        }

        // I am now the guest user, I have no access o the article, I cannot access the shares.
        $this->setGuestUser();
        try {
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()]);
            $this->fail('Exception expected.');
        } catch (moodle_exception $ex) {
            self::assertInstanceOf(moodle_exception::class, $ex);
            self::assertStringContainsString('Permission denied', $ex->getMessage());
        }

        // Finally, I am the admin user, a diety of the TXP world. I can see all shares.
        $this->setAdminUser();
        self::assertSame(
            [
                'totalrecipients' => 1,
                'recipients' => [
                    [
                        'area' => 'user',
                        'label' => 'Users',
                        'total' => 1
                    ]
                ]
            ],
            $this->execute_query(['component' => 'engage_article', 'itemid' => $article->get_id()])
        );
    }

}