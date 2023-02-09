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

use core\webapi\execution_context;
use totara_comment\comment;
use totara_reportedcontent\entity\review as review_entity;
use totara_reportedcontent\review;
use totara_webapi\graphql;

class totara_reportedcontent_remove_review_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_approve_review(): void {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $target_user = $this->getDataGenerator()->create_user();
        $this->setAdminUser();

        // Add a fake comment & review
        $comment = comment::create(
            22,
            'Some fake content',
            'comment',
            'test_component',
            FORMAT_PLAIN,
            $target_user->id
        );
        $review = review::create(
            $comment->get_id(),
            CONTEXT_SYSTEM,
            'test_component',
            'comment',
            'https://example.com',
            'this is a test comment',
            FORMAT_PLAIN,
            time(),
            $target_user->id,
            $user->id
        );

        // Check that the review is in a pending state
        $this->assertSame(
            review::DECISION_PENDING,
            (int) $DB->get_field(review_entity::TABLE, 'status', ['id' => $review->get_id()])
        );

        $variables = [
            'review_id' => $review->get_id(),
        ];

        $ec = execution_context::create('ajax', 'totara_reportedcontent_remove_review');
        $result = graphql::execute_operation($ec, $variables);

        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        // Make sure we return what's expected
        $this->assertArrayHasKey('review', $result->data);

        $review = $result->data['review'];
        $this->assertArrayHasKey('id', $review);
        $this->assertArrayHasKey('status', $review);

        $this->assertNotEmpty($review['id']);
        $this->assertSame(review::DECISION_REMOVE, $review['status']);
        $this->assertArrayHasKey('time_reviewed_description', $review);

        // Now check the stored review is what we expect
        $record = $DB->get_record(review_entity::TABLE, ['id' => $review['id']]);

        $this->assertSame(2, (int) $record->reviewer_id); // Admin user
        $this->assertSame(review::DECISION_REMOVE, (int) $record->status);
        $this->assertNotEmpty($record->time_reviewed);

        // Check that the comment was removed
        $removed_comment = comment::from_id($comment->get_id());
        $this->assertTrue($removed_comment->is_soft_deleted());
        $this->assertSame(comment::REASON_DELETED_REPORTED, $removed_comment->get_reason_deleted());
        $this->assertEmpty($removed_comment->get_content());
    }
}