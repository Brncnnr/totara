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

use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\data_provider\classifications;
use contentmarketplace_linkedin\entity\classification_relationship;
use contentmarketplace_linkedin\model\classification;
use contentmarketplace_linkedin\testing\generator;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_provider_classifications_testcase extends testcase {
    /**
     * @return void
     */
    public function test_fetch_items(): void {
        $generator = generator::instance();

        $classification_1 = $generator->create_classification('urn:li:organization:442', ['name' => 'Admin']);
        $classification_2 = $generator->create_classification('urn:li:organization:443', ['name' => 'Badmin']);

        $provider = new classifications();
        $collection = $provider->fetch()->get();

        self::assertEquals(2, $collection->count());

        /**
         * @var classification $first
         * @var classification $last
         */
        $first = $collection->first();
        $last = $collection->last();

        self::assertInstanceOf(classification::class, $first);
        self::assertInstanceOf(classification::class, $last);

        self::assertEquals($classification_1->urn, $first->urn);
        self::assertEquals($classification_1->name, $first->name);
        self::assertEquals($classification_1->locale_language, $first->locale_language);
        self::assertEquals($classification_1->type, $first->type);

        self::assertEquals($classification_2->urn, $last->urn);
        self::assertEquals($classification_2->name, $last->name);
        self::assertEquals($classification_2->locale_language, $last->locale_language);
        self::assertEquals($classification_2->type, $last->type);
    }

    /**
     * @return void
     */
    public function test_fetch_items_by_locale_language_filter(): void {
        $generator = generator::instance();
        $classification_1 = $generator->create_classification(
            'urn:li:organization:496',
            [
                'locale_language' => 'en',
                'name' => 'classification_1',
                'type' => constants::CLASSIFICATION_TYPE_LIBRARY
            ]
        );

        $classification_2 = $generator->create_classification(
            'urn:li:organization:458',
            [
                'locale_language' => 'de',
                'name' => 'classification_2'
            ]
        );

        $provider = new classifications();
        $provider->add_filters(['locale_language' => 'en']);

        $collection = $provider->fetch()->get();
        self::assertEquals(1, $collection->count());

        /** @var classification $item */
        $item = $collection->current();

        self::assertEquals($classification_1->id, $item->id);
        self::assertEquals($classification_1->urn, $item->urn);
        self::assertEquals($classification_1->name, $item->name);
        self::assertEquals($classification_1->type, $item->type);
        self::assertEquals($classification_1->locale_language, $item->locale_language);

        self::assertNotEquals($classification_2->urn, $item->urn);
        self::assertNotEquals($classification_2->id, $item->id);
        self::assertNotEquals($classification_2->name, $item->name);
        self::assertNotEquals($classification_2->type, $item->type);
        self::assertNotEquals($classification_2->locale_language, $item->locale_language);
    }

    /**
     * @return void
     */
    public function test_fetch_items_with_and_without_parent_id(): void {
        $generator = generator::instance();

        $classification_2 = $generator->create_classification(null, ['name' => 'Badmin']);
        $classification_1 = $generator->create_classification(
            null,
            [
                'type' => constants::CLASSIFICATION_TYPE_LIBRARY,
                'name' => 'Admin'
            ]
        );

        $relationship = new classification_relationship();
        $relationship->parent_id = $classification_1->id;
        $relationship->child_id = $classification_2->id;
        $relationship->save();

        $provider = new classifications();
        $provider->sort_by(classifications::SORT_BY_ALPHABETICAL);
        $collection = $provider->get();

        self::assertEquals(2, $collection->count());

        /**
         * @var classification $parent
         * @var classification $child
         */
        $parent = $collection->first();
        $child = $collection->last();

        self::assertEquals($classification_1->id, $parent->id);
        self::assertEmpty($parent->parents);
        self::assertCount(1, $parent->children);
        self::assertEquals($classification_2->id, $parent->children->first()->id);

        self::assertEquals($classification_2->id, $child->id);
        self::assertEmpty($child->children);
        self::assertCount(1, $child->parents);
        self::assertEquals($classification_1->id, $child->parents->first()->id);
    }
}