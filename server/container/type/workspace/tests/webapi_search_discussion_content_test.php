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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\discussion\discussion;
use container_workspace\workspace;
use totara_webapi\phpunit\webapi_phpunit_helper;
use container_workspace\member\member;
use totara_comment\comment;

class container_workspace_webapi_search_discussion_content_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    public function test_search_discussion_content(): void {
        /** @var \core\testing\generator $generator */
        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();

        $search_term = 'SearchTerm';
        $this->setUser($user1);

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();

        member::join_workspace($workspace, $user2->id);
        member::join_workspace($workspace, $user3->id);
        
        // Combinatins are tested more thoroughly in container_workspace_discussion_loader_testcase.
        // Concentrating on graphql resolvers and formatters here
        
        $workspace_id = $workspace->get_id();

        $expected = [];
        
        $discussion1 = $workspace_generator->create_discussion(
            $workspace_id,
            'Discussion1. Not matching'
        );

        // Add comments and replies to the discussions.
        /** @var \totara_comment\testing\generator $comment_generator */
        $comment_generator = $generator->get_plugin_generator('totara_comment');
        $comment1_1 = $comment_generator->create_comment(
            $discussion1->get_id(),
            workspace::get_type(),
            discussion::AREA,
            "Comment 1-1 not matching",
            FORMAT_PLAIN,
            $user2->id
        );

        $reply1_1_1 = $comment_generator->create_reply(
            $comment1_1->get_id(),
            'Reply 1-1-1 not matching',
            FORMAT_PLAIN,
            $user2->id
        );


        $content = "Discussion2 containing {$search_term}";
        $discussion2 = $workspace_generator->create_discussion(
            $workspace_id,
            $content
        );
        $expected[] = [
            'workspace_id' => $workspace->get_id(),
            'discussion_id' => $discussion2->get_id(),
            'instance_id' => $discussion2->get_id(),
            'instance_type' => discussion::AREA,
            'content' => $content,
            'owner' => $user1->id,
        ];

        // Add comments and replies to the discussion.
        $content = "Comment 2-1 contains {$search_term}";
        $comment2_1 = $comment_generator->create_comment(
            $discussion2->get_id(),
            workspace::get_type(),
            discussion::AREA,
            $content,
            FORMAT_PLAIN,
            $user2->id
        );
        $expected[] = [
            'workspace_id' => $workspace->get_id(),
            'discussion_id' => $discussion2->get_id(),
            'instance_id' => $comment2_1->get_id(),
            'instance_type' => comment::COMMENT_AREA,
            'content' => $content,
            'owner' => $user2->id,
        ];
        
        $content ="Reply 2-1-1 contains {$search_term}";
        $reply2_1_1 = $comment_generator->create_reply(
            $comment2_1->get_id(),
            $content,
            FORMAT_PLAIN,
            $user3->id
        );
        $expected[] = [
            'workspace_id' => $workspace->get_id(),
            'discussion_id' => $discussion2->get_id(),
            'instance_id' => $reply2_1_1->get_id(),
            'instance_type' => comment::REPLY_AREA,
            'content' => $content,
            'owner' => $user3->id,
        ];

        $content = 'Reply 2-1-2 not matching';
        $reply2_1_2 = $comment_generator->create_reply(
            $comment2_1->get_id(),
            $content,
            FORMAT_PLAIN,
            $user3->id
        );

        $content = "Comment 2-2 not matching";
        $comment2_2 = $comment_generator->create_comment(
            $discussion2->get_id(),
            workspace::get_type(),
            discussion::AREA,
            $content,
            FORMAT_PLAIN,
            $user3->id
        );

        $content = "Reply 2-2-1 contains {$search_term}";
        $reply2_2_1 = $comment_generator->create_reply(
            $comment2_2->get_id(),
            $content,
            FORMAT_PLAIN,
            $user3->id
        );
        $expected[] = [
            'workspace_id' => $workspace->get_id(),
            'discussion_id' => $discussion2->get_id(),
            'instance_id' => $reply2_2_1->get_id(),
            'instance_type' => comment::REPLY_AREA,
            'content' => $content,
            'owner' => $user3->id,
        ];

        $content = 'Reply 2-2-2 not matching';
        $reply2_2_2 = $comment_generator->create_reply(
            $comment2_2->get_id(),
            $content,
            FORMAT_PLAIN,
            $user2->id
        );


        // Search for discussion content.
        $results = $this->execute_graphql_operation(
        'container_workspace_search_discussion_content',
            [
                'workspace_id' => $workspace->get_id(),
                'search_term' => strtolower($search_term),
            ]
        );
        
        $this->assertEmpty($results->errors);
        $this->verify_search_results($expected, $results->data['results']);
    }
    
    /**
     * @param array $expected
     * @param array $actual
     */
    private function verify_search_results(array $expected, array $actual): void {
        $this->assertSame(count($expected), count($actual));
        
        foreach ($expected as $idx => $exp_result) {
            foreach ($actual as $act_result) {
                if ($act_result['workspace_id'] == $exp_result['workspace_id']
                    && $act_result['discussion_id'] == $exp_result['discussion_id']
                    && $act_result['instance_id'] == $exp_result['instance_id']
                    && $act_result['instance_type'] == $exp_result['instance_type']
                    && $act_result['content'] == $exp_result['content']
                    && $act_result['owner']['id'] == $exp_result['owner']
                ) {
                    unset($expected[$idx]);
                    break;
                }
            }
        }
        
        $this->assertEmpty($expected);
    }

}