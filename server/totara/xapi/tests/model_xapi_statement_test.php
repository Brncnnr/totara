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

use core_phpunit\testcase;
use totara_xapi\entity\xapi_statement;
use totara_xapi\model\xapi_statement as xapi_statement_model;

/**
 * @group totara_xapi
 */
class totara_xapi_model_xapi_statement_testcase extends testcase {
    /**
     * @return void
     */
    public function test_create_statement(): void  {

        $entity = new xapi_statement();
        $entity->statement = json_encode(["data" => ["some_data"]]);
        $entity->user_id = 1234;
        $entity->client_id = "123456";
        $entity->save();

        $model = xapi_statement_model::load_by_entity($entity);

        $this->assertEquals('{"data":["some_data"]}', $model->get_raw_statement());
        $this->assertEquals(["data" => ["some_data"]], $model->get_statement());
        $this->assertEquals(1234, $model->user_id);
        $this->assertEquals('123456', $model->client_id);
    }
}