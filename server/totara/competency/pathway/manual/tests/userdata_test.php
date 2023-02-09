<?php
/*
 * This file is part of Totara Learn
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 * @subpackage test
 */

use core_phpunit\testcase;
use pathway_manual\entity\rating;
use pathway_manual\userdata\manual_rating_other;
use pathway_manual\userdata\manual_rating_self;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale_value;
use totara_userdata\userdata\target_user;

/**
 * @group totara_competency
 */
class pathway_manual_userdata_testcase extends testcase {

    /**
     * @var stdClass
     */
    private $user1;

    /**
     * @var stdClass
     */
    private $user2;

    /**
     * @var competency
     */
    private $competency;

    /**
     * @var scale_value
     */
    private $scale_value;

    protected function setUp(): void {
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $this->competency = $generator->create_competency();
        $this->scale_value = scale_value::repository()->order_by('id', 'desc')->first();

        $this->create_rating($this->user1, $this->user1, 'Rating On Self 1');
        $this->create_rating($this->user1, $this->user2, 'Rating On Other 1');
        $this->create_rating($this->user2, $this->user2, 'Rating On Self 2');
        $this->create_rating($this->user2, $this->user1, 'Rating On Other 2');
    }

    protected function tearDown(): void {
        $this->user1 = null;
        $this->user2 = null;
        $this->ratings = null;
        $this->competency = null;
        $this->scale_value = null;
    }

    public function test_purge_self() {
        $this->assertTrue(rating::repository()->where('comment', 'Rating On Self 1')->exists());

        $this->purge_self($this->user1);

        $this->assertEquals(3, rating::repository()->count());
        $this->assertFalse(rating::repository()->where('comment', 'Rating On Self 1')->exists());
    }

    public function test_purge_others() {
        $expected_ratings = array_map(function ($rating) {
            if ($rating['assigned_by'] == $this->user2->id && $rating['user_id'] != $this->user2->id) {
                $rating['assigned_by'] = null;
            }
            return $rating;
        }, rating::repository()->order_by('id')->get()->to_array());

        /** @var rating $other_rating */
        $other_rating = rating::repository()->where('comment', 'Rating On Other 1')->one();
        $this->assertTrue($other_rating->exists());
        $this->assertNotNull($other_rating->assigned_by);

        $this->purge_others($this->user2);

        $this->assertEquals($expected_ratings, rating::repository()->order_by('id')->get()->to_array());
        $this->assertNull($other_rating->refresh()->assigned_by);
    }

    public function test_export_self() {
        $expected = array_merge(
            rating::repository()->where('comment', 'Rating On Self 1')->one()->to_array(),
            [
                'competency_name' => $this->competency->fullname,
                'scale_value_name' => $this->scale_value->name,
            ]
        );
        unset($expected['user_id']);

        $this->assertEquals([$expected], $this->export_self($this->user1));

        $expected = array_merge(
            rating::repository()->where('comment', 'Rating On Self 2')->one()->to_array(),
            [
                'competency_name' => $this->competency->fullname,
                'scale_value_name' => $this->scale_value->name,
            ]
        );
        unset($expected['user_id']);

        $this->assertEquals([$expected], $this->export_self($this->user2));
    }

    public function test_export_others() {
        $export_other_1 = $this->export_others($this->user2);
        $expected = array_merge(
            rating::repository()->where('comment', 'Rating On Other 1')->one()->to_array(),
            [
                'competency_name' => $this->competency->fullname,
                'scale_value_name' => $this->scale_value->name,
            ]
        );
        unset($expected['user_id']);

        $this->assertEquals([$expected], $export_other_1);

        $export_other_2 = $this->export_others($this->user1);
        $expected = array_merge(
            rating::repository()->where('comment', 'Rating On Other 2')->one()->to_array(),
            [
                'competency_name' => $this->competency->fullname,
                'scale_value_name' => $this->scale_value->name,
            ]
        );
        unset($expected['user_id']);

        $this->assertEquals([$expected], $export_other_2);
    }

    public function test_count() {
        $this->assertEquals(1, $this->count_self($this->user1));
        $this->assertEquals(1, $this->count_self($this->user2));
        $this->assertEquals(1, $this->count_others($this->user1));
        $this->assertEquals(1, $this->count_others($this->user2));
    }

    public function test_count_after_purge() {
        $this->purge_others($this->user1);
        $this->assertEquals(0, $this->count_others($this->user1));

        $this->purge_self($this->user1);
        $this->assertEquals(0, $this->count_self($this->user1));
    }

    private function create_rating(stdClass $user_for, stdClass $assigned_by, string $comment = null): rating {
        $rating = new rating();
        $rating->competency_id = $this->competency->id;
        $rating->user_id = $user_for->id;
        $rating->scale_value_id = $this->scale_value->id;
        $rating->date_assigned = time();
        $rating->assigned_by = $assigned_by->id;
        $rating->comment = $comment;
        $rating->save();
        return $rating;
    }

    private function purge_self(stdClass $user) {
        manual_rating_self::execute_purge(new target_user($user), context_system::instance());
    }

    private function purge_others(stdClass $user) {
        manual_rating_other::execute_purge(new target_user($user), context_system::instance());
    }

    private function export_self(stdClass $user): array {
        return manual_rating_self::execute_export(new target_user($user), context_system::instance())->data['manual_rating_self'];
    }

    private function export_others(stdClass $user): array {
        return manual_rating_other::execute_export(new target_user($user), context_system::instance())->data['manual_rating_other'];
    }

    private function count_self(stdClass $user): int {
        return manual_rating_self::execute_count(new target_user($user), context_system::instance());
    }

    private function count_others(stdClass $user): int {
        return manual_rating_other::execute_count(new target_user($user), context_system::instance());
    }

}
