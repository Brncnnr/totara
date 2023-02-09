<?php
/**
 * This file is part of Totara Core
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\entity\learning_object;
use contentmarketplace_linkedin\model\user_progress;
use contentmarketplace_linkedin\testing\generator as linkedin_generator;
use core\entity\user;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_model_user_progress_testcase extends testcase {
    /**
     * @var user
     */
    private $user;

    /**
     * @var learning_object
     */
    private $learning_object;

    /**
     * @return void
     */
    protected function setUp(): void {
        $this->user = new user(self::getDataGenerator()->create_user());
        $this->learning_object = linkedin_generator::instance()->create_learning_object('1234');
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->user = null;
        $this->learning_object = null;
    }

    /**
     * @return void
     */
    public function test_load(): void {
        $this->assertNull(
            user_progress::load_for_user_and_learning_object_urn($this->user->id, $this->learning_object->urn)
        );
        $this->assertNull(
            user_progress::load_for_user_and_learning_object_id($this->user->id, $this->learning_object->id)
        );

        $progress = user_progress::set_progress($this->user->id, $this->learning_object->urn, 0, time());

        $this->assertEquals(
            $progress->get_entity_copy()->to_array(),
            user_progress::load_for_user_and_learning_object_urn($this->user->id, $this->learning_object->urn)
                ->get_entity_copy()->to_array()
        );
        $this->assertEquals(
            $progress->get_entity_copy()->to_array(),
            user_progress::load_for_user_and_learning_object_id($this->user->id, $this->learning_object->id)
                ->get_entity_copy()->to_array()
        );
    }

    /**
     * @return void
     */
    public function test_set_progress(): void {
        $time = 1;
        $progress = user_progress::set_progress($this->user->id, $this->learning_object->urn, 0, $time);

        $this->assertEquals($this->user->id, $progress->user_id);
        $this->assertEquals($this->learning_object->id, $progress->learning_object->id);
        $this->assertEquals('1234', $progress->learning_object_urn);
        $this->assertEquals(0, $progress->progress);
        $this->assertEquals($time, $progress->time_created);
        $this->assertEquals($time, $progress->time_updated);
        $this->assertFalse($progress->started);
        $this->assertFalse($progress->completed);
        $this->assertNull($progress->time_completed);

        // Progress is newer, so it updates the values
        $new_time = 3;
        $progress = user_progress::set_progress($this->user->id, $this->learning_object->urn, 66, $new_time);

        $this->assertEquals($this->user->id, $progress->user_id);
        $this->assertEquals($this->learning_object->id, $progress->learning_object->id);
        $this->assertEquals('1234', $progress->learning_object_urn);
        $this->assertEquals(66, $progress->progress);
        $this->assertEquals($time, $progress->time_created);
        $this->assertEquals($new_time, $progress->time_updated);
        $this->assertTrue($progress->started);
        $this->assertFalse($progress->completed);
        $this->assertNull($progress->time_completed);

        // Progress is older, so it doesn't update the existing values
        $newer_time = 2;
        $progress = user_progress::set_progress($this->user->id, $this->learning_object->urn, 55, $newer_time);

        $this->assertEquals($this->user->id, $progress->user_id);
        $this->assertEquals($this->learning_object->id, $progress->learning_object->id);
        $this->assertEquals('1234', $progress->learning_object_urn);
        $this->assertEquals(66, $progress->progress);
        $this->assertEquals($time, $progress->time_created);
        $this->assertEquals($new_time, $progress->time_updated);
        $this->assertTrue($progress->started);
        $this->assertFalse($progress->completed);
        $this->assertNull($progress->time_completed);
    }

    public function test_set_complete(): void {
        $time = 123;
        $progress = user_progress::set_completed($this->user->id, $this->learning_object->urn, $time);

        $this->assertEquals($this->user->id, $progress->user_id);
        $this->assertEquals($this->learning_object->id, $progress->learning_object->id);
        $this->assertEquals('1234', $progress->learning_object->urn);
        $this->assertEquals(user_progress::PROGRESS_COMPLETE, $progress->progress);
        $this->assertEquals($time, $progress->time_created);
        $this->assertEquals($time, $progress->time_updated);
        $this->assertEquals($time, $progress->time_completed);
        $this->assertTrue($progress->started);
        $this->assertTrue($progress->completed);
    }

    public function invalid_progress_provider(): array {
        return [[-1], [101]];
    }

    /**
     * @param $progress
     * @dataProvider invalid_progress_provider
     */
    public function test_set_invalid_progress($progress): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Invalid progress percentage of $progress was specified - must be in a range of 0-100");
        user_progress::set_progress($this->user->id, $this->learning_object->urn, $progress, time());
    }

}