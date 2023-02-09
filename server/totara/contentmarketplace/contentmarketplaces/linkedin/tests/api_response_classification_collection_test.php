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

use contentmarketplace_linkedin\api\v2\service\learning_classification\response\collection;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_api_response_classification_collection_testcase extends testcase {
    /**
     * @return void
     */
    public function test_instantiate_collection(): void {
        $generator = generator::instance();
        $json_data = $generator->get_json_content_from_fixtures('classification_response_1');

        $json = json_decode($json_data, false, 512, JSON_THROW_ON_ERROR);
        $collection = collection::create($json);

        self::assertIsObject($json);
        self::assertObjectHasAttribute('elements', $json);
        self::assertIsArray($json->elements);

        $elements = $collection->get_elements();
        self::assertSameSize($json->elements, $elements);

        self::assertObjectHasAttribute('paging', $json);
        $paging = $json->paging;

        self::assertIsObject($paging);
        self::assertObjectHasAttribute('count', $paging);
        self::assertObjectHasAttribute('start', $paging);
        self::assertObjectHasAttribute('links', $paging);
        self::assertObjectHasAttribute('total', $paging);
        self::assertIsArray($paging->links);
        self::assertEmpty($paging->links);

        $pagination = $collection->get_paging();
        self::assertEquals($paging->count, $pagination->get_count());
        self::assertEquals($paging->start, $pagination->get_start());
        self::assertEquals($paging->total, $pagination->get_total());

        self::assertFalse($pagination->has_next());
        self::assertNull($pagination->get_next_link());
        self::assertNull($pagination->get_previous_link());
    }
}