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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\data_providers\activity\activity_type;
use mod_perform\models\activity\activity_type as activity_type_model;

/**
 * @coversDefaultClass \mod_perform\data_providers\activity\activity_type
 *
 * @group perform
 */
class mod_perform_activity_type_data_provider_testcase extends advanced_testcase {
    /**
     * @covers ::fetch
     * @covers ::get
     */
    public function test_get(): void {
        $out_of_the_box_types = [
            'appraisal',
            'check-in',
            'feedback'
        ];

        $data_provider = new activity_type();
        $types = $data_provider->get()->pluck("name");
        $this->assertEquals($out_of_the_box_types, $types, 'wrong types');

        $new_types = [
            "aaa",
            "ddd",
            "zzz"
        ];

        foreach ($new_types as $name) {
            activity_type_model::create($name);
        }

        // Even though the database has new types, the data provider will not
        // pick it up until fetch() is called.
        $types = $data_provider->get()->pluck("name");
        $this->assertEquals($out_of_the_box_types, $types, 'wrong types');

        $after_fetch_types = array_merge($out_of_the_box_types, $new_types);
        $types = $data_provider->fetch()->get()->pluck("name");
        $this->assertEquals($after_fetch_types, $types, 'wrong types');
    }
}
