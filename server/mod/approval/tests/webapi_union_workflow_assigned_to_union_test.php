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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package mod_approval
 */

use core_phpunit\testcase;
use GraphQL\Type\Definition\ResolveInfo;
use hierarchy_organisation\entity\organisation;
use hierarchy_position\entity\position;
use core\entity\cohort;
use mod_approval\testing\approval_workflow_test_setup;
use mod_approval\webapi\resolver\union\workflow_assigned_to_union;
use totara_webapi\phpunit\webapi_phpunit_helper;
use core\webapi\resolver\type\cohort as cohort_resolver;
use totara_hierarchy\webapi\resolver\type\organisation as organisation_resolver;
use totara_hierarchy\webapi\resolver\type\position as position_resolver;

/**
 * @coversDefaultClass mod_approval\webapi\resolver\union\workflow_assigned_to_union
 *
 * @group approval_workflow
 */
class mod_approval_webapi_union_workflow_assigned_to_union_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    /**
     * @covers ::resolve_type
     */
    public function test_returns_the_right_type() {
        /** @var GraphQL\Type\Definition\ResolveInfo $resolve_info_mock */
        $resolve_info_mock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $organisation = new organisation();
        $random_class = new stdClass();
        $cohort = new cohort();
        $position = new position();

        $organisation_type = workflow_assigned_to_union::resolve_type($organisation, context_system::instance(), $resolve_info_mock);
        $this->assertEquals(organisation_resolver::class, $organisation_type);

        $cohort_type = workflow_assigned_to_union::resolve_type($cohort, context_system::instance(), $resolve_info_mock);
        $this->assertEquals(cohort_resolver::class, $cohort_type);

        $position_type = workflow_assigned_to_union::resolve_type($position, context_system::instance(), $resolve_info_mock);
        $this->assertEquals(position_resolver::class, $position_type);

        // Test random type fails.
        $this->expectException(coding_exception::class);
        workflow_assigned_to_union::resolve_type($random_class, context_system::instance(), $resolve_info_mock);
    }
}
