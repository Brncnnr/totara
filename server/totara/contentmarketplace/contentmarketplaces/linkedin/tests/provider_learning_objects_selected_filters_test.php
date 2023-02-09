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
use contentmarketplace_linkedin\data_provider\learning_objects_selected_filters;
use contentmarketplace_linkedin\dto\timespan;
use contentmarketplace_linkedin\testing\generator;
use core_phpunit\testcase;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_provider_learning_objects_selected_filters_testcase extends testcase {
    /**
     * @return void
     */
    public function test_get_filters_without_selected_filters(): void {
        $provider = new learning_objects_selected_filters([
            "language" => "en",
            "subjects" => [],
            "time_to_complete" => []
        ]);

        $result = $provider->get();
        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    /**
     * @return void
     */
    public function test_get_filters_with_selected_subjects(): void {
        $generator = generator::instance();
        $classification_1 = $generator->create_classification(
            "urn:li:category:155",
            [
                "type" => constants::CLASSIFICATION_TYPE_SUBJECT,
                "name" => "classification 1",
            ]
        );

        $classification_2 = $generator->create_classification(
            "urn:li:category:152",
            [
                "type" => constants::CLASSIFICATION_TYPE_SUBJECT,
                "name" => "classification 2",
            ]
        );

        $provider = new learning_objects_selected_filters([
            "language" => "en",
            "subjects" => [$classification_1->id],
            "time_to_complete" => []
        ]);

        $result = $provider->get();
        self::assertIsArray($result);
        self::assertNotEmpty($result);
        self::assertCount(1, $result);

        $first = reset($result);
        self::assertNotEquals($classification_2->name, $first);
        self::assertEquals($classification_1->name, $first);
    }

    /**
     * @return void
     */
    public function test_get_filters_with_selected_time_to_complete(): void {
        $timespan = timespan::minutes(10);

        $provider = new learning_objects_selected_filters([
            "language" => "en",
            "subjects" => [],
            "time_to_complete" => [json_encode(["max" => $timespan->get()])]
        ]);

        $result = $provider->get();
        self::assertIsArray($result);
        self::assertNotEmpty($result);

        self::assertCount(1, $result);
        $first = reset($result);

        self::assertEquals(
            get_string("catalog_filter_timespan_under_10_minutes", "contentmarketplace_linkedin"),
            $first
        );
    }
}