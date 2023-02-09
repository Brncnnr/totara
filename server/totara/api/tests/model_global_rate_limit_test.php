<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_api
 */

use core_phpunit\testcase;
use totara_api\model\global_rate_limit;
use totara_api\entity\global_rate_limit as entity;
use core\testing\generator;
use totara_api\testing\generator as api_generator;

/**
 * @group totara_api
 */
class totara_api_global_rate_limit_model_testcase extends testcase {

    /** @var generator */
    protected $generator;

    /** @var api_generator */
    protected $api_generator;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->generator = self::getDataGenerator();
        $this->api_generator = $this->generator->get_plugin_generator('totara_api');
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->api_generator = null;
        $this->generator = null;
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_create(): void {
        $this->setAdminUser();

        $model = global_rate_limit::create(
            1,
            2,
            3,
            4
        );

        self::assertNotNull($model);
        self::assertEquals(1, $model->prev_window_value);
        self::assertEquals(2, $model->current_window_value);
        self::assertEquals(3, $model->current_window_reset_time);
        self::assertEquals(4, $model->current_limit);
    }

    /**
     * @return void
     */
    public function test_update(): void {
        $this->setAdminUser();

        $entity = $this->api_generator->create_global_rate_limit([
            'prev_window_value' => 1010,
            'current_window_reset_time' => 2020,
            'current_window_value' => 3030,
            'current_limit' => 4040,
        ]);
        $model = global_rate_limit::load_by_entity($entity);
        $model->update(
            10,
            30
        );

        self::assertNotNull($model);
        self::assertEquals(10, $model->prev_window_value);
        self::assertEquals(30, $model->current_window_value);
        self::assertEquals(2020, $model->current_window_reset_time);
        self::assertEquals(4040, $model->current_limit);

        $model->update(
            10,
            30,
            20
        );

        self::assertNotNull($model);
        self::assertEquals(10, $model->prev_window_value);
        self::assertEquals(30, $model->current_window_value);
        self::assertEquals(20, $model->current_window_reset_time);
        self::assertEquals(4040, $model->current_limit);

        $model->update(
            10,
            30,
            null,
            40
        );

        self::assertNotNull($model);
        self::assertEquals(10, $model->prev_window_value);
        self::assertEquals(30, $model->current_window_value);
        self::assertEquals(null, $model->current_window_reset_time);
        self::assertEquals(40, $model->current_limit);
    }

    /**
     * @return void
     */
    public function test_delete(): void {
        $this->setAdminUser();

        $entity = $this->api_generator->create_global_rate_limit();
        $model = global_rate_limit::load_by_entity($entity);
        $model->delete();

        self::assertFalse(entity::repository()->where('id', '=', $model->id)->exists());
    }

}