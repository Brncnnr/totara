<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_catalog
 */
defined('MOODLE_INTERNAL') || die();

use totara_catalog\totara_engage\link\catalog_source;
use totara_catalog\local\filter_handler;
use totara_catalog\totara_engage\link\catalog_destination;
use totara_engage\link\builder;

class totara_catalog_totara_engage_catalog_source_link_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_build_catalog_source_without_data_filter(): void {
        $source = catalog_source::make([]);

        $expected = "ct.orderbykey=featured&itemstyle=narrow";
        self::assertEquals($expected, $source->build_source());
    }

    /**
     * @return void
     */
    public function test_build_catalog_source_with_data_filter(): void {
        $filter_handler = filter_handler::instance();
        $filters = $filter_handler->get_learning_type_filters();

        // Set the learning type filter.
        foreach ($filters as $filter) {
            $filter->selector->set_current_data([
                'catalog_learning_type_panel' => ['engage_article', 'certification']
            ]);
        }

        $source = catalog_source::make([]);
        $expected = "ct." . implode('&',
            [
                'catalog_learning_type_panel%5B0%5D=engage_article&catalog_learning_type_panel%5B1%5D=certification',
                'orderbykey=featured',
                'itemstyle=narrow'
            ]
        );

        self::assertEquals($expected, $source->build_source());
    }

    /**
     * @return void
     */
    public function test_build_catalog_source_with_full_text_search(): void {
        $filter_handler = filter_handler::instance();
        $fts_filter = $filter_handler->get_full_text_search_filter();

        $fts_filter->selector->set_current_data([
            $fts_filter->key => 'darth vader'
        ]);

        $source = catalog_source::make([]);

        $expected = "ct.{$fts_filter->key}=darth+vader&orderbykey=featured&itemstyle=narrow";
        self::assertEquals($expected, $source->build_source());
    }

    /**
     * @return void
     */
    public function test_build_catalog_source_with_full_text_search_for_and_sign(): void {
        $filter_handler = filter_handler::instance();
        $fts_filter = $filter_handler->get_full_text_search_filter();

        $fts_filter->selector->set_current_data([
            $fts_filter->key => 'darth&vader'
        ]);

        $source = catalog_source::make([]);

        $expected = "ct.{$fts_filter->key}=darth%26vader&orderbykey=featured&itemstyle=narrow";
        self::assertEquals($expected, $source->build_source());
    }

    /**
     * This is to make sure that the dot in catalog fts is not treated as field separator
     * for the link builder.
     *
     * @return void
     */
    public function test_build_catalog_source_with_full_text_search_for_dot(): void {
        $filter_handler = filter_handler::instance();
        $fts_filter = $filter_handler->get_full_text_search_filter();

        $fts_filter->selector->set_current_data([
            $fts_filter->key => 'darth.vader'
        ]);

        $source = catalog_source::make([]);
        $params = [
            $fts_filter->key => 'darth%2Evader',
            'orderbykey' => 'featured',
            'itemstyle' => 'narrow'
        ];

        $expected = "ct." . http_build_query($params, '', '&');
        self::assertEquals($expected, $source->build_source());

        // Now check the destination
        $destination = builder::from_source($source->build_source());
        self::assertInstanceOf(catalog_destination::class, $destination);

        $back_button = $destination->back_button_attributes();

        self::assertIsArray($back_button);
        self::assertArrayHasKey('url', $back_button);
        self::assertStringContainsString("darth.vader", $back_button['url']);
    }

    /**
     * @return void
     */
    public function test_build_catalog_source_with_full_text_search_for_equal_sign(): void {
        $filter_handler = filter_handler::instance();
        $fts_filter = $filter_handler->get_full_text_search_filter();

        $fts_filter->selector->set_current_data([
            $fts_filter->key => 'darth=vader'
        ]);

        $source = catalog_source::make([]);
        $params = [
            $fts_filter->key => 'darth=vader',
            'orderbykey' => 'featured',
            'itemstyle' => 'narrow'
        ];

        $expected = "ct." . http_build_query($params, '', '&');

        self::assertEquals($expected, $source->build_source());

        $destination = builder::from_source($source->build_source());
        self::assertInstanceOf(catalog_destination::class, $destination);

        $back_button = $destination->back_button_attributes();

        self::assertIsArray($back_button);
        self::assertArrayHasKey('url', $back_button);
        self::assertStringContainsString("darth%3Dvader", $back_button['url']);
    }

    /**
     * @return void
     */
    public function test_convert_source_to_attributes(): void {
        $default = [
            'orderbykey' => 'featured',
            'itemstyle' => 'narrow'
        ];
        // Always default to orderbykey and featured.
        self::assertEquals(
           $default,
            catalog_source::convert_source_to_attributes(['darth.vader'])
        );

        self::assertEquals(
            $default,
            catalog_source::convert_source_to_attributes(
                [http_build_query(['darth' => 'va-super-der'], '', '&')]
            )
        );

        self::assertEquals(
            array_merge(['catalog_fts' => 'super darth'], $default),
            catalog_source::convert_source_to_attributes(
                [http_build_query(['catalog_fts' => 'super darth'], '', '&')]
            )
        );
    }

    /**
     * @return void
     */
    public function test_to_back_button_from_source_with_multiple_learning_type(): void {
        $filter_handler = filter_handler::instance();
        $filters = $filter_handler->get_active_filters();

        foreach ($filters as $filter) {
            $filter->selector->set_current_data([
                'catalog_learning_type_panel' => ['totara_engage', 'certification'],
            ]);
        }

        $source =  catalog_source::make([]);
        $destination = builder::from_source($source->build_source());

        self::assertInstanceOf(catalog_destination::class, $destination);
        $back_button = $destination->back_button_attributes();

        self::assertIsArray($back_button);
        self::assertArrayHasKey('url', $back_button);

        self::assertStringContainsString('totara_engage', $back_button['url']);
        self::assertStringContainsString('certification', $back_button['url']);
    }
}