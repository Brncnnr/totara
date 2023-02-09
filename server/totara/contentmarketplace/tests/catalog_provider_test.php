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
 * @package totara_contentmarketplace
 */

use core_phpunit\testcase;
use totara_catalog\catalog_retrieval;
use totara_catalog\provider_handler;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use totara_contentmarketplace\testing\generator;
use totara_contentmarketplace\course\course_builder;
use totara_contentmarketplace\testing\mock\create_course_interactor;
use totara_contentmarketplace\testing\helper;
use totara_contentmarketplace\totara_catalog\provider as provider_filer;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_catalog_provider_testcase extends testcase {
    /**
     * @var array
     */
    protected $data;

    /**
     * @var string
     */
    protected const PANEL_FILTER_KEY = 'contentmarketplace_provider_multi';

    /**
     * @var string
     */
    protected const BROWSE_FILTER_KEY = 'contentmarketplace_provider_tree';

    /**
     * @inheritDoc
     */
    protected function setUp(): void {
        self::setAdminUser();
        $generator = self::getDataGenerator();
        $this->data[] = ($generator->create_course())->id;
        $this->data[] = ($generator->create_course())->id;

        $admin = get_admin();
        $marketplace_generator = generator::instance();
        $learning_object = $marketplace_generator->create_learning_object('contentmarketplace_linkedin');

        $course_builder = new course_builder(
            $learning_object,
            helper::get_default_course_category_id(),
            new create_course_interactor($admin->id)
        );

        $this->data[] = ($course_builder->create_course())->get_course_id();
        $plugin = contentmarketplace::plugin('linkedin');
        $plugin->enable();
        parent::setUp();
    }

    /**
     * @return void
     */
    public function test_provider_disabled_when_marketplace_disabled(): void {
        set_config('enablecontentmarketplaces', 0);
        $filters = provider_handler::instance()->get_provider('course')->get_filters();

        $keys = array_map(
            function ($filter) {
                return $filter->key;
            },
            $filters
        );

        self::assertTrue(!in_array(self::PANEL_FILTER_KEY, $keys));
        self::assertTrue(!in_array(self::BROWSE_FILTER_KEY, $keys));
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void {
        $this->data = null;
        parent::tearDown();
    }

    /**
     * @return void
     */
    public function test_catalog_provider_panel_filter(): void {
        [$browse_filter, $panel_filter] = $this->get_filters();

        $catalog = new catalog_retrieval();
        $filter_data = $panel_filter->datafilter;
        $filter_data->set_current_data([provider_filer::INTERNAL]);
        $result = $catalog->get_page_of_objects(10, 0);

        self::assertCount(2, $result->objects);

        $ids = array_map(
            function ($obj) {
                return $obj->objectid;
            },
            $result->objects
        );

        self::assertTrue(in_array($this->data[0], $ids));
        self::assertTrue(in_array($this->data[1], $ids));

        $filter_data->set_current_data(['contentmarketplace_linkedin']);
        $result = $catalog->get_page_of_objects(10, 0);

        self::assertCount(1, $result->objects);
        self::assertEquals($this->data[2], $result->objects[0]->objectid);
    }

    /**
     * @return void
     */
    public function test_catalog_provider_browse_filter(): void {
        [$browse_filter, $panel_filter] = $this->get_filters();

        $catalog = new catalog_retrieval();
        $filter_data = $browse_filter->datafilter;
        $filter_data->set_current_data(provider_filer::INTERNAL);
        $result = $catalog->get_page_of_objects(10, 0);

        self::assertCount(2, $result->objects);

        $ids = array_map(
            function ($obj) {
                return $obj->objectid;
            },
            $result->objects
        );

        self::assertTrue(in_array($this->data[0], $ids));
        self::assertTrue(in_array($this->data[1], $ids));

        $filter_data->set_current_data('contentmarketplace_linkedin');
        $result = $catalog->get_page_of_objects(10, 0);

        self::assertCount(1, $result->objects);
        self::assertEquals($this->data[2], $result->objects[0]->objectid);
    }

    /**
     * @return array|null[]
     */
    private function get_filters(): array {
        $browse_filter = null;
        $panel_filter = null;
        $filters = provider_handler::instance()->get_provider('course')->get_filters();
        foreach ($filters as $filter) {
            if ($filter->key === self::PANEL_FILTER_KEY) {
                $panel_filter = $filter;
            }

            if ($filter->key === self::BROWSE_FILTER_KEY) {
                $browse_filter = $filter;
            }

            if ($panel_filter && $browse_filter) {
                break;
            }
        }

        return [$browse_filter, $panel_filter];
    }
}