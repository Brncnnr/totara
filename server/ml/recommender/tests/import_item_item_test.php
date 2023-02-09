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
 * @author Vernon Denny <vernon.denny@totaralearning.com>
 * @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package ml_recommender
 */

defined('MOODLE_INTERNAL') || die();

use ml_recommender\local\import\item_item;

/**
 * Test item_item import
 */
class ml_recommender_import_item_item_testcase extends advanced_testcase {

    public function test_import_item_item() {
        global $DB;

        $i2i = [
            ['target_iid' => 'engage_article1', 'similar_iid' => 'totara_playlist7', 'ranking' => 0.743599951267],
            ['target_iid' => 'engage_article1', 'similar_iid' => 'engage_article267', 'ranking' => 0.633537650108],
            ['target_iid' => 'totara_playlist1', 'similar_iid' => 'engage_article96', 'ranking' => 0.629082918167],
            ['target_iid' => 'totara_playlist1', 'similar_iid' => 'totara_playlist70', 'ranking' => 0.627931892872],
            ['target_iid' => 'engage_article2', 'similar_iid' => 'engage_article24', 'ranking' => 0.602713346481],
            ['target_iid' => 'engage_microlearning1', 'similar_iid' => 'engage_microlearning70', 'ranking' => 0.627931892872],
            ['target_iid' => 'engage_microlearning2', 'similar_iid' => 'engage_microlearning24', 'ranking' => 0.602713346481],
            ['target_iid' => 'engage_article2', 'similar_iid' => 'engage_article47', 'ranking' => 0.599358260632]
        ];

        // Upload items per item.
        $item_recommendations = new item_item();
        $item_recommendations->import(new ArrayIterator($i2i));

        // Check count of uploaded records.
        $count_i2i = $DB->count_records_sql("SELECT COUNT(mri.id) FROM  {ml_recommender_items} mri");
        $this->assertCount($count_i2i, $i2i);
    }
}