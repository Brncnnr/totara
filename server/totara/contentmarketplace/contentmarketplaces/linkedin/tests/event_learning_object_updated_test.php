<?php
/**
 * This file is part of Totara Learn
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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\sync_action\sync_learning_asset;
use core_phpunit\testcase;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\testing\mock\create_course_interactor;
use totara_contentmarketplace\testing\helper;
use contentmarketplace_linkedin\config;
use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\testing\generator;
use totara_contentmarketplace\token\token;
use totara_core\http\clients\simple_mock_client;
use contentmarketplace_linkedin\model\learning_object as learning_object_model;
use contentmarketplace_linkedin\entity\learning_object as entity;


/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_event_learning_object_updated_testcase extends testcase {
    /**
     * @var generator
     */
    private $generator;

    /**
     * @var array
     */
    private $urns;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        $this->setAdminUser();
        $this->generator = generator::instance();
        $this->generator->set_up_configuration();

        $token = new token('tokenone', time() + DAYSECS);
        $this->generator->set_token($token);

        $this->urns = [
            'urn:li:lyndaCourse:252',
            'urn:li:lyndaCourse:260'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void {
        $this->generator = null;
        $this->urns = null;
    }

    /**
     * @return void
     */
    public function test_linkedin_learning_object_updated_observer(): void {
        if (!@get_headers('https://cdn.lynda.com/')) {
            $this->markTestSkipped("Couldn't connect to the LinkedIn/Lynda CDN, skipping test.");
        }

        [$course_ids, $old_objects] = $this->create_learning_objects();
        $this->run_regular_sync();

        $new_objects = [
            entity::repository()->find_by_urn($this->urns[0]),
            entity::repository()->find_by_urn($this->urns[1]),
        ];

        foreach ($course_ids as $i => $course_id) {
            /** @var entity $new_object */
            $new_object = $new_objects[$i];
            $course = get_course($course_id);
            self::assertEquals($new_object->description_include_html, $course->summary);
            self::assertEquals($new_object->title, $course->fullname);
        }

        // Make sure the old learning object is not equal to new one
        foreach ($old_objects as $i => $old_object) {
            $new_object = $new_objects[$i];
            self::assertNotEquals($old_object->description_include_html, $new_object->description_include_html);
            self::assertNotEquals($old_object->title, $new_object->title);
        }
    }

    /**
     * @return array
     */
    public function create_learning_objects(): array {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        $learning_objects = [
            new learning_object_model($this->generator->create_learning_object($this->urns[0], ['primary_image_url' => 'https://example.com/image_one.jpg'])),
            new learning_object_model($this->generator->create_learning_object($this->urns[1], ['primary_image_url' => 'https://example.com/image_two.jpg'])),
        ];

        $course_ids = [];
        foreach ($learning_objects as $i => $learning_object) {
            curl::mock_response("This is image" . $i);

            $course_builder = new course_builder(
                $learning_object,
                helper::get_default_course_category_id(),
                new create_course_interactor(get_admin()->id)
            );

            $result = $course_builder->create_course();
            $course_ids[] = $result->get_course_id();
            self::assertTrue($result->is_successful());
        }

        self::assertCount(2, $course_ids);
        return [$course_ids, $learning_objects];
    }

    /**
     * @return void
     */
    private function run_regular_sync(): void {
        $client = new simple_mock_client();
        $client->mock_queue($this->generator->create_json_response_from_fixture('response_2'));

        // Adding empty queue as the sync will try to push for extra sync.
        $client->mock_queue($this->generator->create_json_response_from_fixture("empty_response"));

        $time_now = time();
        config::save_completed_initial_sync_learning_asset(true);
        config::save_last_time_sync_learning_asset($time_now - DAYSECS);

        self::assertNotEquals($time_now, config::last_time_sync_learning_asset());
        self::assertEquals($time_now - DAYSECS, config::last_time_sync_learning_asset());

        $sync = new sync_learning_asset(false, $time_now);
        $sync->set_api_client($client);
        $sync->set_asset_types(constants::ASSET_TYPE_COURSE);
        $sync->invoke();

        self::assertNotEquals($time_now - DAYSECS, config::last_time_sync_learning_asset());
        self::assertEquals($time_now, config::last_time_sync_learning_asset());
    }
}