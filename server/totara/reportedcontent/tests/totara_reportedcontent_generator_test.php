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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_reportedcontent
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\discussion\discussion;
use engage_article\totara_engage\resource\article;
use engage_survey\totara_engage\resource\survey;
use totara_engage\answer\answer_type;

/**
 * Test the reportedcontent review generator
 */
class totara_reportedcontent_generator_test extends advanced_testcase {

    /**
     * Test generating resource reviews
     */
    public function test_generate_resource_reviews(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        /** @var \engage_article\testing\generator $article_gen */
        $article_gen = $this->getDataGenerator()->get_plugin_generator('engage_article');

        $user1 = $gen->create_user();
        $this->setUser($user1);

        /** @var article[] $articles */
        $articles = [];
        for ($i = 1; $i <= 5; $i++) {
            $article = $article_gen->create_article(['name' => 'A#' . $i, 'userid' => $user1->id]);
            $articles[$article->get_id()] = $article;
            unset($article);
        }
        $this->assertCount(5, $articles);

        $existing = $DB->get_records('totara_reportedcontent', ['component' => 'engage_article', 'area' => '']);
        $this->assertEmpty($existing);

        $user2 = $gen->create_user();

        /** @var \totara_reportedcontent\testing\generator $review_gen */
        $review_gen = $gen->get_plugin_generator('totara_reportedcontent');

        foreach ($articles as $article) {
            $review_gen->create_resource_review_from_params([
                'component' => 'engage_article',
                'name' => $article->get_name(FORMAT_PLAIN),
                'username' => $user2->username
            ]);
        }

        // Now check each review exists
        $existing = $DB->get_records('totara_reportedcontent', ['component' => 'engage_article', 'area' => '']);
        $this->assertCount(5, $existing);

        foreach ($existing as $review) {
            $item_id = $review->item_id;
            $this->assertArrayHasKey($item_id, $articles);
            $article = $articles[$item_id];

            $this->assertSame($article->get_name(FORMAT_PLAIN), $review->content);
            $this->assertNull($review->time_reviewed);
            $this->assertEquals(0, $review->status);
        }
    }

    /**
     * Test generating survey reviews
     */
    public function test_generate_survey_reviews(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        /** @var \engage_survey\testing\generator $survey_gen */
        $survey_gen = $this->getDataGenerator()->get_plugin_generator('engage_survey');

        $user1 = $gen->create_user();
        $this->setUser($user1);

        /** @var survey[] $surveys */
        $surveys = [];
        for ($i = 1; $i <= 5; $i++) {
            $survey = $survey_gen->create_survey('S#' . $i, ['A', 'B', 'C'], answer_type::MULTI_CHOICE, ['userid' => $user1->id]);
            $surveys[$survey->get_id()] = $survey;
            unset($survey);
        }
        $this->assertCount(5, $surveys);

        $existing = $DB->get_records('totara_reportedcontent', ['component' => 'engage_survey', 'area' => '']);
        $this->assertEmpty($existing);

        $user2 = $gen->create_user();

        /** @var \totara_reportedcontent\testing\generator $review_gen */
        $review_gen = $gen->get_plugin_generator('totara_reportedcontent');

        foreach ($surveys as $survey) {
            $review_gen->create_resource_review_from_params([
                'component' => 'engage_survey',
                'name' => $survey->get_name(FORMAT_PLAIN),
                'username' => $user2->username
            ]);
        }

        // Now check each review exists
        $existing = $DB->get_records('totara_reportedcontent', ['component' => 'engage_survey', 'area' => '']);
        $this->assertCount(5, $existing);

        foreach ($existing as $review) {
            $item_id = $review->item_id;
            $this->assertArrayHasKey($item_id, $surveys);
            $survey = $surveys[$item_id];

            $this->assertSame($survey->get_name(FORMAT_PLAIN), $review->content);
            $this->assertNull($review->time_reviewed);
            $this->assertEquals(0, $review->status);
        }
    }

    /**
     * Test generating workspace discussion reviews
     */
    public function test_generate_workspace_discussion_reviews(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $this->getDataGenerator()->get_plugin_generator('container_workspace');

        $user1 = $gen->create_user();
        $this->setUser($user1);

        $workspace = $workspace_gen->create_workspace();

        /** @var discussion[] $discussions */
        $discussions = [];
        for ($i = 1; $i <= 5; $i++) {
            $discussion = $workspace_gen->create_discussion($workspace->get_id(), 'D#' . $i, null, FORMAT_PLAIN, $user1->id);
            $discussions[$discussion->get_id()] = $discussion;
            unset($discussion);
        }
        $this->assertCount(5, $discussions);

        $existing = $DB->get_records('totara_reportedcontent', ['component' => 'container_workspace', 'area' => 'discussion']);
        $this->assertEmpty($existing);

        $user2 = $gen->create_user();

        /** @var \totara_reportedcontent\testing\generator $review_gen */
        $review_gen = $gen->get_plugin_generator('totara_reportedcontent');

        foreach ($discussions as $discussion) {
            $review_gen->create_discussion_review_from_params([
                'workspace' => $workspace->get_name(),
                'username' => $user2->username,
                'discussion' => $discussion->get_content_text(),
            ]);
        }

        // Now check each review exists
        $existing = $DB->get_records('totara_reportedcontent', ['component' => 'container_workspace', 'area' => 'discussion']);
        $this->assertCount(5, $existing);

        foreach ($existing as $review) {
            $item_id = $review->item_id;
            $this->assertArrayHasKey($item_id, $discussions);
            $discussion = $discussions[$item_id];
            $this->assertSame($discussion->get_content(), $review->content);
            $this->assertEquals($discussion->get_content_format(), $review->format);
            $this->assertNull($review->time_reviewed);
            $this->assertEquals(0, $review->status);
        }
    }

    /**
     * Test generating comment reviews
     */
    public function test_generate_comment_reviews(): void {
        global $DB;

        $gen = $this->getDataGenerator();

        // We're going to comment on a workspace discussion, resource and playlist.
        /** @var \engage_article\testing\generator $article_gen */
        $article_gen = $this->getDataGenerator()->get_plugin_generator('engage_article');
        /** @var \container_workspace\testing\generator $workspace_gen */
        $workspace_gen = $this->getDataGenerator()->get_plugin_generator('container_workspace');
        /** @var \totara_playlist\testing\generator $playlist_gen */
        $playlist_gen = $this->getDataGenerator()->get_plugin_generator('totara_playlist');
        /** @var \totara_comment\testing\generator $comment_gen */
        $comment_gen = $this->getDataGenerator()->get_plugin_generator('totara_comment');
        /** @var \totara_reportedcontent\testing\generator $review_gen */
        $review_gen = $gen->get_plugin_generator('totara_reportedcontent');

        $user1 = $gen->create_user();
        $this->setUser($user1);

        $article = $article_gen->create_article(['name' => 'Test Article']);
        $playlist = $playlist_gen->create_playlist(['name' => 'Test Playlist']);
        $workspace = $workspace_gen->create_workspace('Test Workspace');
        $discussion = $workspace_gen->create_discussion($workspace->get_id(), 'Test Discussion', null, FORMAT_PLAIN);

        // Create the comments on each
        $parent_items = [
            [
                'id' => $article->get_id(),
                'component' => $article::get_resource_type(),
                'area' => $article::COMMENT_AREA,
                'name' => $article->get_name(FORMAT_PLAIN),
            ],
            [
                'id' => $playlist->get_id(),
                'component' => $playlist::get_resource_type(),
                'area' => $playlist::COMMENT_AREA,
                'name' => $playlist->get_name(FORMAT_PLAIN),
            ],
            [
                'id' => $discussion->get_id(),
                'component' => $workspace::get_type(),
                'area' => $discussion::AREA,
                'name' => $discussion->get_content_text(),
                'extra' => [
                    'workspace' => $workspace->get_name(),
                ]
            ],
        ];

        $comments = [];
        foreach ($parent_items as $i => $parent_item) {
            $comment = $comment_gen->create_comment(
                $parent_item['id'],
                $parent_item['component'],
                $parent_item['area'],
                'C#' . $i,
                FORMAT_PLAIN,
                $user1->id
            );
            $reply = $comment_gen->create_reply(
                $comment->get_id(),
                'R#' . $i,
                FORMAT_PLAIN,
                $user1->id
            );

            $comments[$comment->get_id()] = [$comment, $i];
            $comments[$reply->get_id()] = [$reply, $i];
        }

        $existing = $DB->get_records('totara_reportedcontent');
        $this->assertEmpty($existing);

        $user2 = $gen->create_user();
        $this->setUser($user2);

        // Create our reviews
        foreach ($comments as [$comment, $i]) {
            $parent_item = $parent_items[$i];

            $params = [
                'component' => $comment->get_component(),
                'name' => $parent_item['name'],
                'area' => $comment->get_area(),
                'username' => $user2->username,
                'comment' => $comment->get_content_text(),
            ];

            if (isset($parent_item['extra'])) {
                $params = array_merge($params, $parent_item['extra']);
            }

            $review_gen->create_comment_review_from_params($params);
        }

        // Now check each review exists
        $existing = $DB->get_records('totara_reportedcontent');
        $this->assertCount(6, $existing); // Comment & reply on each

        foreach ($existing as $review) {
            $item_id = $review->item_id;
            $this->assertArrayHasKey($item_id, $comments);
            [$comment_or_reply] = $comments[$item_id];
            /** @var \totara_comment\comment $comment_or_reply */

            $this->assertSame($comment_or_reply->get_content(), $review->content);
            $this->assertEquals($comment_or_reply->get_format(), $review->format);
            $this->assertNull($review->time_reviewed);
            $this->assertEquals(0, $review->status);
        }
    }
}