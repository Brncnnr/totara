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

use totara_competency\achievement_configuration as achievement_configuration_model;
use totara_competency\entity\pathway;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_achievement_configuration_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_achievement_configuration';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/achievement_configuration objects are accepted/");

        $this->resolve_graphql_type(self::QUERY_TYPE,'id', new stdClass());
    }

    public function test_resolve_successful() {
        $data = $this->create_data();

        // resolve competency_id
        $result = $this->resolve_graphql_type(self::QUERY_TYPE,'competency_id', $data->config);
        $this->assertEquals($data->comp->id, $result);

        // resolve overall_aggregation
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'overall_aggregation', $data->config);
        $this->assertEquals(achievement_configuration_model::DEFAULT_AGGREGATION, $result->get_agg_type());

        // resolve paths
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'paths', $data->config,[]);
        $pathway = array_shift($result);
        $this->assertEquals($data->comp->id, $pathway->get_competency()->id);
    }

    private function create_data() {
        $this->setAdminUser();

        $data = new class() {
            /** @var competency $comp */
            public $comp;
            /** @var achievement_configuration_model $config */
            public $config;
            /** @var pathway */
            public $active_pathway;
        };

        /** @var \totara_hierarchy\testing\generator $hierarchy_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $data->comp = $competency_generator->create_competency();
        $data->config = new achievement_configuration_model($data->comp);

        // create an active pathway
        $data->active_pathway = new pathway();
        $data->active_pathway->competency_id = $data->comp->id;
        $data->active_pathway->sortorder = 2;
        $data->active_pathway->path_type = 'criteria_group';
        $data->active_pathway->path_instance_id = 0;
        $data->active_pathway->status = \totara_competency\pathway::PATHWAY_STATUS_ACTIVE;
        $data->active_pathway->save();

        return $data;
    }
}