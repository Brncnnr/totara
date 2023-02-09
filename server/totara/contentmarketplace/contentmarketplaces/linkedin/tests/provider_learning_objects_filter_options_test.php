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
use contentmarketplace_linkedin\data_provider\learning_objects_filter_options;
use core_phpunit\testcase;
use contentmarketplace_linkedin\testing\generator;
use totara_core\tui\tree\tree_node;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_provider_learning_objects_filter_options_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_without_subjects(): void {
        $provider = new learning_objects_filter_options("en");
        $result = $provider->get();

        self::assertIsArray($result);
        self::assertArrayHasKey("subjects", $result);
        self::assertArrayHasKey("asset_type", $result);
        self::assertArrayHasKey("time_to_complete", $result);
        self::assertArrayHasKey("in_catalog", $result);

        // There should be only one root tree with empty nodes.
        self::assertIsArray($result["subjects"]);
        self::assertNotEmpty($result["subjects"]);
        self::assertCount(1, $result["subjects"]);

        /** @var tree_node $root */
        $root = reset($result["subjects"]);
        self::assertTrue($root->is_root());
        self::assertEmpty($root->get_children());
    }

    /**
     * @return void
     */
    public function test_get_subjects(): void {
        $generator = generator::instance();
        $library = $generator->create_classification(
            "lib",
            ["type" => constants::CLASSIFICATION_TYPE_LIBRARY]
        );

        $course = $generator->create_classification(
            "course",
            ["type" => constants::CLASSIFICATION_TYPE_SUBJECT]
        );

        $generator->create_classification_relationship(
            $library->id,
            $course->id
        );

        $provider = new learning_objects_filter_options("en");
        $result = $provider->get();

        self::assertIsArray($result);
        self::assertArrayHasKey("subjects", $result);
        self::assertIsArray($result["subjects"]);
        self::assertCount(1, $result["subjects"]);

        /** @var tree_node $root */
        $root = reset($result["subjects"]);
        self::assertTrue($root->is_root());

        $nodes = $root->get_children();
        self::assertNotEmpty($nodes);
        self::assertCount(1, $nodes);

        /** @var tree_node $node */
        $node = reset($nodes);
        self::assertFalse($node->is_root());
        self::assertEmpty($node->get_children());
        self::assertEquals($library->id, $node->get_id());
        self::assertEquals($library->name, $node->get_label());

        $content = $node->get_content();
        self::assertNotEmpty($content);
        self::assertCount(1, $content);

        /** @var tree_node $node */
        $node = reset($content);
        self::assertEquals($course->id, $node->get_id());
        self::assertEquals($course->name, $node->get_label());
    }

    /**
     * @return void
     */
    public function test_get_asset_types(): void {
        $provider = new learning_objects_filter_options("en");
        $result = $provider->get();

        self::assertIsArray($result);
        self::assertArrayHasKey("asset_type", $result);
        self::assertIsArray($result["asset_type"]);
        self::assertCount(1, $result["asset_type"]);

        /** @var tree_node $root */
        $root = reset($result["asset_type"]);
        self::assertTrue($root->is_root());
        self::assertEmpty($root->get_children());

        $content = $root->get_content();
        self::assertNotEmpty($content);
        self::assertCount(2, $content);

        /** @var tree_node $first */
        $first = reset($content);
        self::assertEquals(constants::ASSET_TYPE_COURSE, $first->get_id());
        self::assertEquals(
            get_string("asset_type_course_plural", "contentmarketplace_linkedin"),
            $first->get_label()
        );

        /** @var tree_node $last */
        $last = end($content);
        self::assertEquals(constants::ASSET_TYPE_VIDEO, $last->get_id());
        self::assertEquals(
            get_string("asset_type_video_plural", "contentmarketplace_linkedin"),
            $last->get_label()
        );
    }


    /**
     * @return void
     */
    public function test_get_time_to_complete(): void {
        $provider = new learning_objects_filter_options("en");
        $result = $provider->get();

        self::assertIsArray($result);
        self::assertArrayHasKey("time_to_complete", $result);
        self::assertIsArray($result["time_to_complete"]);
        self::assertCount(1, $result["time_to_complete"]);

        /** @var tree_node $root */
        $root = reset($result["time_to_complete"]);
        self::assertTrue($root->is_root());
        self::assertEmpty($root->get_children());

        $content = $root->get_content();
        self::assertNotEmpty($content);
        self::assertCount(6, $content);

        $node_data_in_order = [
            [
                "id" => json_encode(["max" => 10 * MINSECS]),
                "label" => get_string("catalog_filter_timespan_under_10_minutes", "contentmarketplace_linkedin"),
            ],
            [
                "id" => json_encode([
                    "min" => 10 * MINSECS,
                    "max" => 30 * MINSECS
                ]),
                "label" => get_string(
                    "catalog_filter_timespan_10_to_30_minutes",
                    "contentmarketplace_linkedin"
                )
            ],
            [
                "id" => json_encode([
                    "min" => 30 * MINSECS,
                    "max" => 60 * MINSECS
                ]),
                "label" => get_string(
                    "catalog_filter_timespan_30_to_60_minutes",
                    "contentmarketplace_linkedin"
                ),
            ],
            [
                "id" => json_encode([
                    "min" => 1 * HOURSECS,
                    "max" => 2 * HOURSECS
                ]),
                "label" => get_string(
                    "catalog_filter_timespan_1_to_2_hours",
                    "contentmarketplace_linkedin"
                )
            ],
            [
                "id" => json_encode([
                    "min" => 2 * HOURSECS,
                    "max" => 3 * HOURSECS
                ]),
                "label" => get_string(
                    "catalog_filter_timespan_2_to_3_hours",
                    "contentmarketplace_linkedin"
                ),
            ],
            [
                "id" => json_encode(["min" => 3 * HOURSECS]),
                "label" => get_string(
                    "catalog_filter_timespan_over_3_hours",
                    "contentmarketplace_linkedin"
                )
            ]
        ];

        foreach ($content as $i => $node) {
            $expected = $node_data_in_order[$i];

            self::assertEquals($expected["id"], $node->get_id());
            self::assertEquals($expected["label"], $node->get_label());
        }
    }

    /**
     * @return void
     */
    public function test_get_in_catalog(): void {
        $provider = new learning_objects_filter_options('en');
        $result = $provider->get();

        self::assertIsArray($result);
        self::assertArrayHasKey('in_catalog', $result);
        self::assertIsArray($result['in_catalog']);
        self::assertCount(1, $result['in_catalog']);

        /** @var tree_node $root */
        $root = reset($result['in_catalog']);
        self::assertTrue($root->is_root());
        self::assertEmpty($root->get_children());

        $content = $root->get_content();
        self::assertNotEmpty($content);
        self::assertCount(2, $content);

        /** @var tree_node $first */
        $first = reset($content);
        self::assertEquals('yes', $first->get_id());
        self::assertEquals(
            get_string('catalog_filter_in_catalog', 'contentmarketplace_linkedin'),
            $first->get_label()
        );

        /** @var tree_node $last */
        $last = end($content);
        self::assertEquals('no', $last->get_id());
        self::assertEquals(
            get_string('catalog_filter_not_in_catalog', 'contentmarketplace_linkedin'),
            $last->get_label()
        );
    }
}
