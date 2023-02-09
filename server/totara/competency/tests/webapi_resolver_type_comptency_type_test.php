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

use core\date_format;
use core\format;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_comptency_type_testcase extends advanced_testcase {
    use webapi_phpunit_helper;

    private const QUERY_TYPE = 'totara_competency_competency_type';

    public function test_resolve_invalid_object() {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Accepting only competency type entities.");

        $this->resolve_graphql_type(self::QUERY_TYPE, 'id', new stdClass());
    }

    public function test_resolve_successful() {
        $comp_type = $this->create_data();

        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'id', $comp_type);
        $this->assertEquals($comp_type->id, $result);

        // resolve idnumber
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'idnumber', $comp_type);
        $this->assertEquals($comp_type->idnumber, $result);

        $this->assertEquals(
            'type shortname',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $comp_type, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            '<p>type shortname</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $comp_type, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'type shortname',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'shortname', $comp_type, ['format' => format::FORMAT_PLAIN])
        );

        // resolve fullname
        $this->assertEquals(
            'Comp type one',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $comp_type, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            '<p>Comp type one</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $comp_type, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'Comp type one',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'fullname', $comp_type, ['format' => format::FORMAT_PLAIN])
        );

        // resolve display_name
        $this->assertEquals(
            'Comp type one',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'display_name', $comp_type, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            '<p>Comp type one</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'display_name', $comp_type, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            'Comp type one',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'display_name', $comp_type, ['format' => format::FORMAT_PLAIN])
        );

        // resolve description
        $this->assertEquals(
            '<p>Description</p>',
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $comp_type, ['format' => format::FORMAT_HTML])
        );
        $this->assertEquals(
            "<p>Description</p><script>alert('This shouldn\'t be here\')</script>",
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $comp_type, ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals(
            "Description\n",
            $this->resolve_graphql_type(self::QUERY_TYPE, 'description', $comp_type, ['format' => format::FORMAT_PLAIN])
        );

        // resolve timecreated
        $result = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'timecreated', $comp_type, ['format' => date_format::FORMAT_TIMESTAMP]
        );
        $this->assertEquals($comp_type->timecreated, $result);

        // resolve timemodified
        $result = $this->resolve_graphql_type(
            self::QUERY_TYPE, 'timemodified', $comp_type, ['format' => date_format::FORMAT_TIMESTAMP]
        );
        $this->assertEquals($comp_type->timemodified, $result);

        // resolve usermodified
        $result = $this->resolve_graphql_type(self::QUERY_TYPE, 'usermodified', $comp_type);
        $this->assertEquals($comp_type->usermodified, $result);
    }

    private function create_data() {
        /** @var \totara_competency\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');
        $comp_type = $generator
            ->create_type(
                [
                    'fullname' => '<p>Comp type one</p>',
                    'shortname' => '<p>type shortname</p>',
                ]
            );

        $comp_type->description = "<p>Description</p><script>alert('This shouldn\'t be here\')</script>";

        return $comp_type;
    }
}