<?php
/**
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_perform_rating
 */

use pathway_learning_plan\achievement_detail;
use pathway_manual\manual;
use pathway_perform_rating\perform_rating;
use totara_competency\pathway_factory;
use totara_core\advanced_feature;

require_once(__DIR__ . '/perform_rating_base_testcase.php');

/**
 * @group pathway_perform_rating
 * @group totara_competency
 */
class pathway_perform_rating_pathway_test extends perform_rating_base_testcase {

    public function test_is_enabled(): void {
        $perform_pathway = new perform_rating();
        $other_pathway = new manual();

        $this->assertTrue($perform_pathway->is_enabled());
        $this->assertTrue($other_pathway->is_enabled());
        $this->assertContainsEquals('perform_rating', pathway_factory::get_pathway_types());

        advanced_feature::disable('performance_activities');

        $this->assertFalse($perform_pathway->is_enabled());
        $this->assertTrue($other_pathway->is_enabled());
        $this->assertNotContainsEquals('perform_rating', pathway_factory::get_pathway_types());
    }

}
