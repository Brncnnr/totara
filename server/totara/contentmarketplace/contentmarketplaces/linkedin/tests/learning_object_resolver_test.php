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
 * @package contentmarketplace_linkedin
 */
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use contentmarketplace_linkedin\learning_object\resolver;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_learning_object_resolver_testcase extends testcase {
    /**
     * @return void
     */
    public function test_find_learning_asset_record(): void {
        $generator = generator::instance();
        $learning_object_entity = $generator->create_learning_object('urn:li:lyndaCourse:252');

        $resolver = new resolver();
        $model = $resolver->find($learning_object_entity->id);

        self::assertEquals($learning_object_entity->id, $model->get_id());
        self::assertEquals($learning_object_entity->title, $model->get_name());
        self::assertEquals('contentmarketplace_linkedin', $model->get_marketplace_component());
        self::assertEquals('urn:li:lyndaCourse:252', $model->urn);

        self::assertNull($resolver->find(42));
    }
}