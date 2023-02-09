<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_criteria
 */

use criteria_onactivate\onactivate_display;

/**
 * @group totara_competency
 */
class criteria_onactivate_display_testcase extends advanced_testcase {

     /**
      * Test configuration display
      */
    public function test_configuration() {

        /** @var \totara_competency\testing\generator $competency_generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_criteria');

        $cc = $generator->create_onactivate(['competency' => 1]);
        $display_configuration = (new onactivate_display($cc))->get_configuration();

        $expected = (object)[
            'item_type' => get_string('pluginname', 'criteria_onactivate'),
            'item_aggregation' => get_string('complete_all', 'totara_criteria'),
            'items' => [],
        ];

        $this->assertEqualsCanonicalizing($expected, $display_configuration);
    }
}
