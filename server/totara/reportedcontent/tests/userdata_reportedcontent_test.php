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
 * @package totara_reportedcontent
 */
defined('MOODLE_INTERNAL') || die();

use totara_userdata\userdata\target_user;
use totara_comment\comment;
use totara_reportedcontent\entity\review as review_entity;
use totara_reportedcontent\review;
use totara_reportedcontent\userdata\reportedcontent;

class totara_reportedcontent_userdata_reportedcontent_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_purge_reportedcontent(): void {
        global $DB;

        $gen = $this->getDataGenerator();
        $complainer = $gen->create_user();
        $target_user = $gen->create_user();
        $this->setAdminUser();

        $comments = $this->create_comments(6, $target_user->id);

        $reviews = [];
        foreach ($comments as $index => $comment) {
            $reviews[] = review::create(
                $comment->get_id(),
                CONTEXT_SYSTEM,
                'test_component',
                'comment',
                'https://example.com',
                'this is a test comment'.$index,
                FORMAT_PLAIN,
                time(),
                $target_user->id,
                $complainer->id
            );
        }

        foreach ($reviews as $review) {
            // Check that the review is in a pending state
            $this->assertSame(
                review::DECISION_PENDING,
                (int) $DB->get_field(review_entity::TABLE, 'status', ['id' => $review->get_id()])
            );
        }

        // Review created
        $this->assertTrue(
            $DB->record_exists(review_entity::TABLE, ['complainer_id' => $complainer->id])
        );

        // Delete target user
        $complainer->deleted = 1;
        $DB->update_record('user', $complainer);

        $target_user = new target_user($complainer);
        $context = context_system::instance();

        $result = reportedcontent::execute_purge($target_user, $context);
        $this->assertEquals(reportedcontent::RESULT_STATUS_SUCCESS, $result);

        foreach ($reviews as $review) {
            $this->assertNull($DB->get_field(
                review_entity::TABLE,
                'complainer_id',
                ['id' => $review->get_id()]
            ));
        }
    }

    /**
     * @param int $len
     * @param int $user_id
     * @return array
     */
    private function create_comments(int $len, int $user_id): array {
        $list = [];
        for ($i = 1; $i < $len; $i++) {
            $list[] = comment::create(
                 $i,
                'Some fake content'. $i,
                'comment',
                'test_component',
                FORMAT_PLAIN,
                $user_id
            );
        }

        return $list;
    }
}