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
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use core\json_editor\node\paragraph;
use container_workspace\discussion\discussion_helper;
use container_workspace\query\discussion\query as discussion_query;
use container_workspace\loader\discussion\loader as discussion_loader;
use container_workspace\discussion\discussion;
use container_workspace\member\member;
use totara_comment\comment_helper;
use totara_comment\comment;
use container_workspace\workspace;
use container_workspace\query\discussion\sort as discussion_sort;

class container_workspace_discussion_loader_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_finding_discussions_with_like(): void {
        /** @var \core\testing\generator $generator */
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Login as second users and adding discussions to the workspace.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);

        // Create a first discussion that has the search term.
        $special_discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [
                    paragraph::create_json_node_from_text("This is the special text")
                ]
            ]),
            null,
            FORMAT_JSON_EDITOR
        );

        // Now create the several not-a-like discussions.
        for ($i = 0; $i < 5; $i++) {
            discussion_helper::create_discussion(
                $workspace,
                json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text(uniqid())]
                ]),
                null,
                FORMAT_JSON_EDITOR
            );
        }

        $query = new discussion_query($workspace->get_id());
        $query->set_search_term("this is");

        $paginator = discussion_loader::get_discussions($query);
        $this->assertEquals(1, $paginator->get_total());

        /** @var discussion[] $discussions */
        $discussions = $paginator->get_items()->all();
        $this->assertCount(1, $discussions);

        $discussion = reset($discussions);
        $this->assertEquals($special_discussion->get_id(), $discussion->get_id());
        $this->assertEquals($special_discussion->get_content(), $discussion->get_content());
    }

    /**
     * @return void
     */
    public function test_finding_discussion_with_like_from_comment(): void {
        /** @var \core\testing\generator $generator */
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Log in as user two and check if you are able to search for the discussions base on the comment.
        $user_two = $generator->create_user();
        $this->setUser($user_two);

        member::join_workspace($workspace, $user_two->id);
        $special_discussion = discussion_helper::create_discussion(
            $workspace,
            json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text(uniqid())]
            ]),
            null,
            FORMAT_JSON_EDITOR
        );

        for ($i = 0; $i < 5; $i++) {
            discussion_helper::create_discussion(
                $workspace,
                json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text(uniqid())]
                ]),
                null,
                FORMAT_JSON_EDITOR
            );
        }

        // Now create a several comments for the special discussion.
        $workspace_type = workspace::get_type();
        comment_helper::create_comment(
            $workspace_type,
            discussion::AREA,
            $special_discussion->get_id(),
            json_encode([
                'type' => 'doc',
                'content' => [paragraph::create_json_node_from_text('Parent is THE discussion')]
            ]),
            FORMAT_JSON_EDITOR
        );

        for ($i = 0; $i < 5; $i++) {
            comment_helper::create_comment(
                $workspace_type,
                discussion::AREA,
                $special_discussion->get_id(),
                json_encode([
                    'type' => 'doc',
                    'content' => [paragraph::create_json_node_from_text(uniqid())]
                ]),
                FORMAT_JSON_EDITOR
            );
        }

        $query = new discussion_query($workspace->get_id());
        $query->set_search_term('the discussion');

        $paginator = discussion_loader::get_discussions($query);
        $this->assertEquals(1, $paginator->get_total());

        /** @var discussion[] $discussions */
        $discussions = $paginator->get_items()->all();
        $this->assertCount(1, $discussions);

        $discussion = reset($discussions);

        $this->assertEquals($special_discussion->get_id(), $discussion->get_id());
        $this->assertEquals($special_discussion->get_id(), $discussion->get_id());
    }

    /**
     * @return void
     */
    public function test_fetching_discussions_with_recent_update_sort_order(): void {
        /** @var \core\testing\generator $generator */
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();

        $this->setUser($user_one);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        // Create two discussions, and fetch with the recently updated before adding comment to one and another.
        $workspace_id = $workspace->get_id();
        $discussion_one = $workspace_generator->create_discussion($workspace_id);
        $discussion_two = $workspace_generator->create_discussion($workspace_id);

        // Both discussions need to have different timestamps, so we can test the sorting.
        // We set a past time here so when the comment below is created the timestamp will absolutely be different
        discussion::get_entity_repository()->update_record([
            'id' => $discussion_one->get_id(),
            'timestamp' => 584217720,
            'time_created' => 584217720,
            'time_modified' => 584217720,
        ]);
        discussion::get_entity_repository()->update_record([
            'id' => $discussion_two->get_id(),
            'timestamp' => 584217800,
            'time_created' => 584217800,
            'time_modified' => 584217800,
        ]);

        // Load the discussion base on the recently updated which the second discussion should be there.
        $query = new discussion_query($workspace_id);
        $query->set_sort(discussion_sort::RECENT);

        $before_result = discussion_loader::get_discussions($query);
        self::assertEquals(2, $before_result->get_total());

        $before_result_discussions = $before_result->get_items()->all();
        self::assertCount(2, $before_result_discussions);

        // The discussion two will be at the top.
        $first_before_result_discussion = reset($before_result_discussions);
        self::assertEquals($discussion_two->get_id(), $first_before_result_discussion->get_id());

        // The discussion one will be at the bottom.
        $second_before_result_discussion = end($before_result_discussions);
        self::assertEquals($discussion_one->get_id(), $second_before_result_discussion->get_id());

        // Add a comment to discussion one - which it will move the discussion one to the top
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment_generator->create_comment(
            $discussion_one->get_id(),
            workspace::get_type(),
            discussion::AREA
        );

        $after_result = discussion_loader::get_discussions($query);
        self::assertEquals(2, $after_result->get_total());

        $after_result_discussions = $after_result->get_items()->all();
        self::assertCount(2, $after_result_discussions);

        // Discussion one should be at the top.
        $first_after_result_discussion = reset($after_result_discussions);
        self::assertEquals($discussion_one->get_id(), $first_after_result_discussion->get_id());

        // Discussion two should be at the bottom - since discussion one was added with the comment.
        $second_after_result_discussion = end($after_result_discussions);
        self::assertEquals($discussion_two->get_id(), $second_after_result_discussion->get_id());
    }


    /**
     * Data provider for test_search_conternt
     */
    public function data_provider_test_search_content() {
        return [
            [
                'discussions' => [
                    [
                        'with_term' => false,
                    ],
                ],
            ],
            [
                'discussions' => [
                    [
                        'with_term' => true,
                    ],
                ],
            ],
            [
                'discussions' => [
                    [
                        'with_term' => false,
                        'comments' => [
                            [
                                'with_term' => true,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'discussions' => [
                    [
                        'with_term' => false,
                        'comments' => [
                            [
                                'with_term' => false,
                                'replies' => [
                                    [
                                        'with_term' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'discussions' => [
                    [
                        'with_term' => true,
                        'comments' => [
                            [
                                'with_term' => true,
                                'replies' => [
                                    [
                                        'with_term' => true,
                                    ],
                                    [
                                        'with_term' => false,
                                    ],
                                ],
                            ],
                            [
                                'with_term' => false,
                                'replies' => [
                                    [
                                        'with_term' => true,
                                    ],
                                    [
                                        'with_term' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'with_term' => false,
                        'comments' => [
                            [
                                'with_term' => true,
                                'replies' => [
                                    [
                                        'with_term' => true,
                                    ],
                                    [
                                        'with_term' => false,
                                    ],
                                ],
                            ],
                            [
                                'with_term' => false,
                                'replies' => [
                                    [
                                        'with_term' => true,
                                    ],
                                    [
                                        'with_term' => false,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return void
     * @dataProvider data_provider_test_search_content
     */
    public function test_search_content(array $discussions): void {
        /** @var \core\testing\generator $generator */
        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $this->setUser($user1);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');

        $workspace = $workspace_generator->create_workspace();

        member::join_workspace($workspace, $user2->id);
        member::join_workspace($workspace, $user3->id);

        $search_term = 'SearchTerm';
        $expected = [];

        foreach ($discussions as $d_cnt => $discussion_to_add) {
            $text = "Discussion {$d_cnt}";
            if ($discussion_to_add['with_term']) {
                $text .= " containing {$search_term}";
            }

            $discussion = discussion_helper::create_discussion(
                $workspace,
                $text,
                null,
                FORMAT_PLAIN,
                $user1->id
            );

            if ($discussion_to_add['with_term']) {
                $expected[] = [
                    'workspace_id' => $workspace->get_id(),
                    'discussion_id' => $discussion->get_id(),
                    'instance_type' => discussion::AREA,
                    'instance_id' => $discussion->get_id(),
                    'content' => $text,
                ];
            }

            if (!isset($discussion_to_add['comments'])) {
                continue;
            }

            foreach ($discussion_to_add['comments'] as $c_cnt => $comment_to_add) {
                $text = "Comment {$d_cnt} - {$c_cnt}";
                if ($comment_to_add['with_term']) {
                    $text .= " containing {$search_term}";
                }

                $comment = $comment_generator->create_comment(
                    $discussion->get_id(),
                    workspace::get_type(),
                    discussion::AREA,
                    $text,
                    FORMAT_PLAIN,
                    $user2->id
                );

                if ($comment_to_add['with_term']) {
                    $expected[] = [
                        'workspace_id' => $workspace->get_id(),
                        'discussion_id' => $discussion->get_id(),
                        'instance_type' => comment::COMMENT_AREA,
                        'instance_id' => $comment->get_id(),
                        'content' => $text,
                    ];
                }

                if (!isset($comment_to_add['replies'])) {
                    continue;
                }

                foreach ($comment_to_add['replies'] as $r_cnt => $reply_to_add) {
                    $text = "Reply {$d_cnt} - {$c_cnt} - {$r_cnt}";
                    if ($reply_to_add['with_term']) {
                        $text .= " containing {$search_term}";
                    }

                    $reply = $comment_generator->create_reply(
                        $comment->get_id(),
                        $text,
                        FORMAT_PLAIN,
                        $user3->id
                    );
                    
                    if ($reply_to_add['with_term']) {
                        $expected[] = [
                            'workspace_id' => $workspace->get_id(),
                            'discussion_id' => $discussion->get_id(),
                            'instance_type' => comment::REPLY_AREA,
                            'instance_id' => $reply->get_id(),
                            'content' => $text,
                        ];
                    }
                }
            }
        }

        $query = new discussion_query($workspace->get_id());
        $query->set_search_term(strtolower($search_term));

        $paginator = discussion_loader::search_discussion_content($query);
        $total = $paginator->get_total();
        $items = $paginator->get_items()->to_array();
        $this->verify_search_results($expected, $items);
    }
    
    /**
     * @param array $expected
     * @param array $items
     */
    private function verify_search_results(array $expected, array $actual): void {
        $this->assertSame(count($expected), count($actual));
        foreach ($expected as $idx => $expected_result) {
            foreach ($actual as $actual_result) {
                // Ids returned from db are strings
                if ($expected_result['workspace_id'] == $actual_result['workspace_id']
                    && $expected_result['discussion_id'] == $actual_result['discussion_id']
                    && $expected_result['instance_id'] == $actual_result['instance_id']
                    && $expected_result['instance_type'] == $actual_result['instance_type']
                    && $expected_result['content'] == $actual_result['content_text']
                ) {
                    unset($expected[$idx]);
                    break;
                }
            }
        }
        
        $this->assertEmpty($expected);
    }
}