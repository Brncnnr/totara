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

/**
 * Test item_user import
 */
class ml_recommender_import_item_user_testcase extends advanced_testcase {

    public function test_import_item_item() {
        global $DB;

        $i2u = [
            ['uid' => 1, 'iid' => 'engage_article277', 'ranking' => 2.203594684601],
            ['uid' => 1, 'iid' => 'engage_article1', 'ranking' => 1.936550498009],
            ['uid' => 2, 'iid' => 'engage_article242', 'ranking' => 1.892577528954],
            ['uid' => 2, 'iid' => 'totara_playlist1', 'ranking' => 1.879106402397],
            ['uid' => 1, 'iid' => 'engage_article271', 'ranking' => 1.845853328705],
            ['uid' => 1, 'iid' => 'engage_microlearning1', 'ranking' => 1.879106402397],
            ['uid' => 2, 'iid' => 'engage_microlearning1', 'ranking' => 1.845853328705],
            ['uid' => 1, 'iid' => 'engage_article122', 'ranking' => 1.669565081596]
        ];

        // Upload items per user.
        $user_recommendations = new \ml_recommender\local\import\item_user();
        $user_recommendations->import(new ArrayIterator($i2u));

        $count_i2u = $DB->count_records_sql("SELECT COUNT(mru.id) FROM  {ml_recommender_users} mru");
        $this->assertCount($count_i2u, $i2u);
    }
}