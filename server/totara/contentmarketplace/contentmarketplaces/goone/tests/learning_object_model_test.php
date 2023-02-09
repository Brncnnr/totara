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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_goone
 */

use contentmarketplace_goone\entity\learning_object as learning_object_entity;
use contentmarketplace_goone\model\learning_object;
use contentmarketplace_goone\testing\generator;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_goone_learning_object_model_testcase extends testcase {

    public function test_load_from_external_id(): void {
        $external_id1 = '1234';
        $this->assertFalse(learning_object_entity::repository()->where('external_id', $external_id1)->exists());

        $learning_object1 = learning_object::load_by_external_id($external_id1);
        $this->assertTrue(learning_object_entity::repository()->where('external_id', $external_id1)->exists());
        $this->assertEquals($external_id1, $learning_object1->external_id);

        $learning_object2 = learning_object::load_by_external_id($external_id1, null, false);
        $this->assertEquals($external_id1, $learning_object2->external_id);
        $this->assertEquals($learning_object1->external_id, $learning_object2->external_id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage(
            "The Go1 learning object with ID 5678 does not exist in the marketplace_goone_learning_object table."
        );
        learning_object::load_by_external_id('5678', null, false);
    }

    /**
     * Since the model properties such as the name aren't actually stored within a Totara database,
     * we need to ensure that they get fetched from the cache/Go1's API when requested.
     *
     * @param int $id
     * @dataProvider mocked_go1_learning_objects_provider
     */
    public function test_properties_fetched_from_api(int $id): void {
        $generator = generator::instance();
        $mock_json = $generator->get_mock_learning_object($id);
        $api = $generator->get_mock_api();

        $model = learning_object::load_by_external_id($id, $api);

        $this->assertEquals($mock_json['id'], $model->external_id);
        $this->assertEquals($mock_json['title'], $model->name);
        $this->assertEquals($mock_json['description'], $model->description->get_raw_value());
        $this->assertEquals($mock_json['image'], $model->image_url);
        $this->assertEquals($mock_json['language'], $model->language);
    }

    /**
     * @return string[]
     */
    public function mocked_go1_learning_objects_provider(): array {
        return [
            'Learning object ID 29271' => [29271],
            'Learning object ID 191657' => [1916572],
            'Learning object ID 1868492' => [1868492],
        ];
    }

}
