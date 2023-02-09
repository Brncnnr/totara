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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_evidence
 */

use core\format;
use totara_customfield\field\field_data;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_evidence\formatter\evidence_item as evidence_item_formatter;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

class webapi_resolver_type_evidence_item_field_testcase extends totara_evidence_testcase {
    use webapi_phpunit_helper;

    /** @var string */
    private const TYPE = 'totara_evidence_evidence_item_field';

    /**
     * @return void
     */
    public function test_invalid_source(): void {
        try {
            $this->resolve_graphql_type(self::TYPE, '', new stdClass());
            $this->fail('Expected a coding exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertEquals(
                'Coding error detected, it must be fixed by a programmer: Expected field data',
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_invalid_field(): void {
        global $CFG;
        require_once($CFG->dirroot . '/totara/customfield/field/field_data.php');
        try {
            $this->resolve_graphql_type(
                self::TYPE,
                'unknown',
                new field_data('', '', [])
            );
            $this->fail('Expected a coding exception');
        } catch (Exception $e) {
            $this->assertInstanceOf(coding_exception::class, $e);
            $this->assertEquals(
                'Coding error detected, it must be fixed by a programmer: Unknown field unknown',
                $e->getMessage()
            );
        }
    }

    /**
     * @return void
     */
    public function test_valid_fields(): void {
        $this->setAdminUser();

        // Allow creation of actual files.
        $this->generator()->set_create_files(true);

        // Create evidence type.
        $evidence_type = $this->generator()->create_evidence_type([
            'name' => 'Completion',
            'field_types' => [
                'file',
                'checkbox',
            ]
        ]);

        // Create evidence bank item.
        $evidence_item = $this->generator()->create_evidence_item([
            'typeid' => $evidence_type->get_id(),
            'name' => 'Conference attendance',
        ]);

        // Get fields.
        $context = context_system::instance();
        $item_formatter = new evidence_item_formatter($evidence_item, $context);

        /** @var field_data[] $fields */
        $fields = $item_formatter->format('fields', format::FORMAT_HTML);

        // Validate each field.
        foreach ($fields as $field_data) {
            $label = $this->resolve_graphql_type(self::TYPE, 'label', $field_data);
            $this->assertEquals($field_data->get_label(), $label, "Wrong value for label");
            $type = $this->resolve_graphql_type(self::TYPE, 'type', $field_data);
            $this->assertEquals($field_data->get_type(), $type, "Wrong value for type");
            $content = $this->resolve_graphql_type(self::TYPE, 'content', $field_data);
            $this->assertEquals($field_data->extra_to_json(), $content, "Wrong value for content");

            // Validate content.
            $json = json_decode($content, true);
            $this->assertEquals(JSON_ERROR_NONE, json_last_error());
            $this->assertArrayHasKey('html', $json);

            // Validate file type.
            if ($field_data->get_type() === 'file') {
                $this->assertArrayHasKey('file_name', $json);
                $this->assertArrayHasKey('file_size', $json);
                $this->assertArrayHasKey('url', $json);
            }
        }
    }

}
