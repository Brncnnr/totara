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
 * @package mod_perform
 */

use core\date_format;
use GraphQL\Deferred;
use totara_competency\entity\pathway_achievement as pathway_achievement_entity;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_pathway_achievement_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_pathway_achievement';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Please pass a pathway_achievement entity");

        $this->resolve_graphql_type(self::QUERY_TYPE, 'id', new stdClass());
    }

    public function test_resolve_successful() {
        $pathway_achievement = $this->create_data();
        // resolve id
        $this->assertEquals($pathway_achievement->id, $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $pathway_achievement));

        // resolve pathway
        $this->assertInstanceOf(Deferred::class, $this->resolve_graphql_type(self::QUERY_TYPE, 'pathway', $pathway_achievement));

        // resolve user
        $this->assertInstanceOf(Deferred::class, $this->resolve_graphql_type(self::QUERY_TYPE, 'user', $pathway_achievement));

        // resolve scale_value
        $this->assertInstanceOf(
            Deferred::class, $this->resolve_graphql_type(self::QUERY_TYPE, 'scale_value', $pathway_achievement)
        );

        // resolve achieved
        $this->assertEquals(
            $pathway_achievement->get_has_scale_value_attribute(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'achieved', $pathway_achievement)
        );

        // resolve date_achieved
        $this->assertEquals(
            $pathway_achievement->date_achieved,
            $this->resolve_graphql_type(
                self::QUERY_TYPE, 'date_achieved', $pathway_achievement, ['format' => date_format::FORMAT_TIMESTAMP]
            )
        );

        // resolve last_aggregated
        $this->assertEquals(
            $pathway_achievement->last_aggregated,
            $this->resolve_graphql_type(
                self::QUERY_TYPE, 'last_aggregated', $pathway_achievement, ['format' => date_format::FORMAT_TIMESTAMP]
            )
        );

        // resolve status
        $this->assertEquals('CURRENT', $this->resolve_graphql_type(self::QUERY_TYPE, 'status', $pathway_achievement));

        $pathway_achievement->status = 1;
        $this->assertEquals('ARCHIVED', $this->resolve_graphql_type(self::QUERY_TYPE, 'status', $pathway_achievement));

        // resolve related_info
        $this->assertEquals(
            $pathway_achievement->related_info, $this->resolve_graphql_type(self::QUERY_TYPE, 'related_info', $pathway_achievement)
        );

        // resolve has_scale_value
        $this->assertEquals(
            $pathway_achievement->get_has_scale_value_attribute(),
            $this->resolve_graphql_type(self::QUERY_TYPE, 'has_scale_value', $pathway_achievement)
        );
    }

    private function create_data() {
        $user = $this->getDataGenerator()->create_user();

        /** @var \totara_competency\testing\generator $competency_generator */
        $competency_generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $competency = $competency_generator->create_competency();
        $pathway = $competency_generator->create_test_pathway($competency, pathway_achievement_entity::STATUS_CURRENT);

        $this->setCurrentTimeStart();

        $pathway_achievement = pathway_achievement_entity::get_current($pathway, $user->id);

        return $pathway_achievement;
    }
}