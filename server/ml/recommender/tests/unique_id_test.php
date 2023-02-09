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
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use ml_recommender\local\unique_id;

class ml_recommender_unique_id_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_component_from_unique_id(): void {
        $valid_data = [
            ['data_xoz', 45, 'data_xoz'],
            ['data_dc', 44, 'data_dc'],
            ['data_cc', 43, 'data_cc'],
            ['data_dd', 42, 'data_dd'],
            ['data_juiji', 41, 'data_juiji'],
            ['engage_article', 55, 'engage_microlearning'],
        ];

        foreach ($valid_data as $valid_datum) {
            $valid_str = implode('', [$valid_datum[2], $valid_datum[1]]);
            self::assertEquals($valid_datum, unique_id::normalise_unique_id($valid_str));
        }
    }

    /**
     * @return void
     */
    public function test_get_component_from_unique_id_with_exception(): void {
        $invalid_samples = [
            'kaboom',
            '8929_kar',
            'kar_78200',
            'wow_apple_pie'
        ];

        foreach ($invalid_samples as $sample) {
            try {
                unique_id::normalise_unique_id($sample);
            } catch (coding_exception $e) {
                self::assertStringContainsString(
                    "Cannot extract the component name from unique id string",
                    $e->getMessage()
                );
                continue;
            }

            $this->fail("Expecting an exception to be thrown for sample '{$sample}'");
        }
    }
}