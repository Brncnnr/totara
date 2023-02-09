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
 * @package totara_oauth2
 */

use core_phpunit\testcase;
use totara_oauth2\model\client_provider;

/**
 * @group totara_oauth2
 */
class totara_oauth2_client_provider_model_testcase extends testcase {

    public function test_generate_unique_value(): void {
        $class = new ReflectionClass(client_provider::class);
        $method = $class->getMethod("generate_unique_value");
        $method->setAccessible(true);

        $value = $method->invoke($class,'client_id');
        self::assertNotNull($value);
        self::assertNotNull(16, strlen($value));
    }

    public function test_create(): void {
        $model = client_provider::create(
            'test',
            'xapi:write',
            FORMAT_PLAIN,
            '123',
            1,
            true,
            null,
            'totara_oauth2'
        );

        self::assertNotNull($model);
        self::assertEquals('test', $model->name);
        self::assertEquals('xapi:write', $model->scope);
        self::assertEquals(FORMAT_PLAIN, $model->description_format);
        self::assertEquals('123', $model->description);
        self::assertEquals(1, $model->internal);
        self::assertEquals(true, $model->status);
        self::assertEquals('totara_oauth2', $model->component);
    }

    /**
     * @return void
     */
    public function test_create_non_internal_by_default(): void {
        $model = client_provider::create('test', 'xapi:write', FORMAT_PLAIN);

        self::assertNotNull($model);
        self::assertEquals(0, $model->internal);
    }
}