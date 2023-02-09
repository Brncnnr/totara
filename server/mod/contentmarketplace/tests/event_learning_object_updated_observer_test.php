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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package mod_contentmarketplace
 */

use core_phpunit\testcase;
use contentmarketplace_linkedin\sync_action\sync_learning_asset;
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
use mod_contentmarketplace\entity\content_marketplace;
use core\orm\entity\repository;

/**
 * @group totara_contentmarketplace
 */
class mod_contentmarketplace_event_learning_object_updated_observer_testcase extends testcase {
    /**
     * @var generator
     */
    private $generator;

    /**
     * @var string
     */
    private $urn;

    /**
     * @var repository
     */
    private $repository;

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        $this->setAdminUser();
        $this->generator = generator::instance();
        $this->generator->set_up_configuration();

        $token = new token('tokenone', time() + DAYSECS);
        $this->generator->set_token($token);

        $this->repository = content_marketplace::repository();
        $this->urn = 'urn:li:lyndaCourse:252';
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void {
        $this->generator = null;
        $this->urn = null;
        $this->repository = null;
    }

    /**
     * @return void
     */
    public function test_learning_object_observer(): void {
        if (!@get_headers('https://cdn.lynda.com/')) {
            $this->markTestSkipped("Couldn't connect to the LinkedIn/Lynda CDN, skipping test.");
        }

        [$course_id, $old_cm] = $this->create_learning_object();

        // Run regular sync.
        $this->run_regular_sync();

        /** @var entity $new_object */
        $new_object = entity::repository()->find_by_urn($this->urn);
        /** @var content_marketplace $new_cm **/
        $new_cm = $this->repository->where('course', $course_id)->one();
        self::assertEquals($new_cm->name, $new_object->title);

        // Make sure old cm entity is not equal to new updated learning object
        self::assertNotEquals($old_cm->name, $new_object->title);
    }

    /**
     * @return array
     */
    public function create_learning_object(): array {
        global $CFG;
        require_once("{$CFG->dirroot}/lib/filelib.php");

        self::assertEquals(0, $this->repository->count());

        $learning_object = new learning_object_model($this->generator->create_learning_object($this->urn, ['primary_image_url' => 'https://example.com/image_one.jpg']));

        curl::mock_response("This is image");
        $course_builder = new course_builder(
            $learning_object,
            helper::get_default_course_category_id(),
            new create_course_interactor(get_admin()->id)
        );

        $result = $course_builder->create_course();
        self::assertTrue($result->is_successful());
        self::assertEquals(1, $this->repository->count());

        $cm_entity = $this->repository->where('course', $result->get_course_id())->one();
        return [$result->get_course_id(), $cm_entity];
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