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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_xapi
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use totara_xapi\entity\xapi_statement;
use totara_xapi\model\xapi_statement as xapi_statement_model;

/**
 * @group totara_xapi
 */
class totara_xapi_entity_xapi_statement_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_statement(): void  {
        $db = builder::get_db();
        self::assertEquals(0, $db->count_records(xapi_statement::TABLE));

        $entity = new xapi_statement();
        $entity->statement = json_encode(["data" => ["some_data"]]);
        $entity->user_id = 1234;

        self::assertFalse($entity->exists());
        $entity->save();

        self::assertTrue($entity->exists());
        self::assertEquals(1, $db->count_records(xapi_statement::TABLE));
    }

    /**
     * @return void
     */
    public function test_delete_statement(): void  {
        $entity = new xapi_statement();
        $entity->statement = json_encode(["data" => ["some_data"]]);

        self::assertFalse($entity->exists());
        $entity->save();

        self::assertTrue($entity->exists());

        $db = builder::get_db();
        self::assertEquals(1, $db->count_records(xapi_statement::TABLE));

        $entity->delete();
        self::assertFalse($entity->exists());
        self::assertEquals(0, $db->count_records(xapi_statement::TABLE));
    }

    /**
     * @return void
     */
    public function test_get_statement_as_array(): void {
        $entity = new xapi_statement();
        $entity->statement = json_encode(["data" => ["some_data"]]);
        $entity->save();

        $model = xapi_statement_model::load_by_entity($entity);
        $statement = $model->statement;
        self::assertEquals(["data" => ["some_data"]], $statement);
    }
}