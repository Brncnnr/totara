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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_competency
 */

use core\format;
use pathway_manual\models\roles\self_role;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_pathway_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_pathway';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/pathway objects are accepted/");

        $this->resolve_graphql_type(self::QUERY_TYPE, 'id', new stdClass());
    }

    public function test_resolve_successful() {
        [$manual_pathway, $learning_plan_pathway] = $this->create_pathway();
        // resolve id
        $this->assertEquals($manual_pathway->get_id(), $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $manual_pathway));
        $this->assertEquals(
            $learning_plan_pathway->get_id(), $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $learning_plan_pathway)
        );

        // resolve pathway_type
        $this->assertEquals(
            $manual_pathway->get_path_type(), $this->resolve_graphql_type(self::QUERY_TYPE, 'pathway_type', $manual_pathway)
        );
        $this->assertEquals(
            $learning_plan_pathway->get_path_type(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'pathway_type', $learning_plan_pathway)
        );

        // resolve instance_id
        $this->assertEquals(
            $manual_pathway->get_path_instance_id(), $this->resolve_graphql_type(self::QUERY_TYPE, 'instance_id', $manual_pathway)
        );
        $this->assertEquals(
            $learning_plan_pathway->get_path_instance_id(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'instance_id', $learning_plan_pathway)
        );

        // resolve title
        $this->assertEquals($manual_pathway->get_title(), $this->resolve_graphql_type(self::QUERY_TYPE, 'title', $manual_pathway));
        $this->assertEquals(
            $learning_plan_pathway->get_title(), $this->resolve_graphql_type(self::QUERY_TYPE, 'title', $learning_plan_pathway)
        );

        // resolve sortorder
        $this->assertEquals(
            $manual_pathway->get_sortorder(), $this->resolve_graphql_type(self::QUERY_TYPE, 'sortorder', $manual_pathway)
        );
        $this->assertEquals(
            $learning_plan_pathway->get_sortorder(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'sortorder', $learning_plan_pathway)
        );

        // resolve status
        $this->assertEquals(
            $manual_pathway->get_status_name(), $this->resolve_graphql_type(self::QUERY_TYPE, 'status', $manual_pathway)
        );
        $this->assertEquals(
            $learning_plan_pathway->get_status_name(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'status', $learning_plan_pathway)
        );

        // resolve classification
        $this->assertEquals(
            $manual_pathway->get_classification_name(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'classification', $manual_pathway)
        );
        $this->assertEquals(
            $learning_plan_pathway->get_classification_name(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'classification', $learning_plan_pathway)
        );

        // resolve scale_value
        $this->assertEquals(
            $manual_pathway->get_scale_value(), $this->resolve_graphql_type(self::QUERY_TYPE, 'scale_value', $manual_pathway)
        );
        $this->assertEquals(
            $learning_plan_pathway->get_scale_value(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'scale_value', $learning_plan_pathway)
        );

        // resolve error
        $error = get_string('error_invalid_configuration', 'totara_competency');
        $this->assertEquals(null, $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $manual_pathway));

        $manual_pathway->set_valid(false);
        $this->assertEquals(
            $error, $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $manual_pathway, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            $error, $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $manual_pathway, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            $error, $this->resolve_graphql_type(self::QUERY_TYPE, 'error', $manual_pathway, ['format' => format::FORMAT_PLAIN])
        );

        // resolve criteria_summary
        $this->assertEquals(
            $manual_pathway->get_summarized_criteria_set(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'criteria_summary', $manual_pathway)
        );
        $this->assertEquals(
            $learning_plan_pathway->get_summarized_criteria_set(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'criteria_summary', $learning_plan_pathway)
        );
    }

    public function test_resolve_unknown_field() {
        [$manual_pathway] = $this->create_pathway();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/Unknown field/");
        $this->resolve_graphql_type(self::QUERY_TYPE, 'unknown_field', $manual_pathway);
    }

    private function create_pathway() {
        $this->setAdminUser();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();

        $manual_pathway = $competency_generator->create_manual($competency, [self_role::class]);
        $learning_plan_pathway = $competency_generator->create_learning_plan_pathway($competency);

        return [$manual_pathway, $learning_plan_pathway];
    }
}