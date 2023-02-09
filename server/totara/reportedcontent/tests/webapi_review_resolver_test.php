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

use totara_comment\comment;
use totara_reportedcontent\review;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * Test for the totara_reportedcontent review graphql resolver type
 */
class totara_reportedcontent_webapi_review_resolver_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    /**
     * @var review
     */
    protected $review;

    /**
     * @var comment
     */
    protected $comment;

    /**
     * Test a regular created review type has the expected fields
     * and that deprecated fields throw a warning
     */
    public function test_review_type_default(): void {
        // Basic test, check that the default fields match what's expected
        $fields = $this->get_fields();
        $this->check_fields($fields);
    }

    /**
     * Test an approved review looks like what we expect
     */
    public function test_review_type_approved(): void {
        // We need to approve the review and check the fields
        $reviewer = $this->getDataGenerator()->create_user();
        $this->review->do_review(review::DECISION_APPROVE, $reviewer->id);

        $fields = $this->get_fields();
        $fields['reviewer'] = $reviewer;
        $this->check_fields($fields);
    }

    /**
     * Test a removed review looks like what we expect
     */
    public function test_review_type_removed(): void {
        // We need to approve the review and check the fields
        $reviewer = $this->getDataGenerator()->create_user();
        $this->review->do_review(review::DECISION_REMOVE, $reviewer->id);

        $fields = $this->get_fields();
        $fields['reviewer'] = $reviewer;
        $this->check_fields($fields);
    }

    /**
     * Create the reviews
     */
    protected function setUp(): void {
        $resource_user = $this->getDataGenerator()->create_user();
        $reporter_user = $this->getDataGenerator()->create_user();

        // Create a comment that'll be reported
        $this->comment = comment::create(
            42,
            'My Content',
            'comment',
            'test_component',
            FORMAT_PLAIN,
            $resource_user->id
        );
        $this->review = review::create(
            $this->comment->get_id(),
            CONTEXT_SYSTEM,
            'test_component',
            'comment',
            'https://example.com',
            $this->comment->get_content(),
            $this->comment->get_format(),
            time(),
            $this->comment->get_userid(),
            $reporter_user->id
        );

        parent::setUp();
    }

    /**
     * Clean up after the tests finish
     */
    protected function tearDown(): void {
        $this->comment = null;
        $this->review = null;
        parent::tearDown();
    }

    /**
     * Creates the arrays of expected field types and values.
     *
     * @return array
     */
    protected function get_fields(): array {
        $review = $this->review;
        $comment = $this->comment;

        return [
            'id' => $review->get_id(),
            'url' => $review->get_url(),
            'approved' => $review->get_status() === review::DECISION_APPROVE,
            'removed' => $review->get_status() === review::DECISION_REMOVE,
            'status' => $review->get_status(),
            'time_created' => $review->get_time_created(),
            'time_content' => $review->get_time_content(),
            'time_reviewed_description' => [$review->get_time_reviewed(), ['format' => 'TIMESTAMP']],
            'item_id' => $review->get_item_id(),
            'context_id' => $review->get_context_id(),
            'component' => $review->get_component(),
            'area' => $review->get_area(),
            'target_user' => $comment->get_user(),
            'complainer' => $review->get_complainer(),
            'reviewer' => null,
            // This field is deprecated
            'time_reviewed' => $review->get_time_reviewed(),
        ];
    }

    /**
     * @param array $fields
     */
    protected function check_fields(array $fields): void {
        foreach ($fields as $field => $expected_value) {
            $variables = [];
            if (is_array($expected_value)) {
                $variables = $expected_value[1];
                $expected_value = $expected_value[0];
            }

            $actual_value = $this->resolve_graphql_type(
                'totara_reportedcontent_review',
                $field,
                $this->review,
                $variables
            );

            if ($expected_value instanceof stdClass && isset($expected_value->username)) {
                $this->assertNotNull($actual_value);
                $this->assertObjectHasAttribute('id', $expected_value);
                $this->assertEquals($actual_value->id, $expected_value->id);
            } else {
                $this->assertEquals(
                    $expected_value,
                    $actual_value,
                    "'$field' field does not have expected value '$expected_value', instead has '$actual_value'"
                );
            }
        }
    }
}