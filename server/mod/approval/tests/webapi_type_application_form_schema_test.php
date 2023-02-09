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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

use container_approval\approval;
use core_phpunit\testcase;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_approval\webapi\resolver\type\application_form_schema
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_application_form_schema_testcase extends testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'mod_approval_application_form_schema';

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $context = approval::get_default_category_context();

        // Incorrect source, so field is unknown
        $schema_object = new stdClass();
        try {
            $this->resolve_graphql_type(self::TYPE, 'form_schema', $schema_object, [''], $context);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString("Unknown field form_schema", $ex->getMessage());
        }

        $schema_object = (object)['form_schema' => '{"key":"ora"}', 'form_data' => '{}'];
        try {
            $this->resolve_graphql_type(self::TYPE, 'unknown', $schema_object, [], $context);
            $this->fail('coding_exception expected');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString("Unknown field unknown", $ex->getMessage());
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        $context = approval::get_default_category_context();

        // NOTE: the type resolver can take a wrong JSON format as they are just unverified raw strings
        $schema_object = (object)['form_schema' => 'form schema', 'form_data' => 'form data'];

        $value = $this->resolve_graphql_type(self::TYPE, 'form_schema', $schema_object, [], $context);
        $this->assertEquals('form schema', $value);

        $value = $this->resolve_graphql_type(self::TYPE, 'form_data', $schema_object, [], $context);
        $this->assertEquals('form data', $value);
    }
}
