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
use totara_evidence\models\evidence_item;
use totara_evidence\models\evidence_type;
use totara_evidence\formatter\evidence_item as evidence_item_formatter;
use totara_evidence\formatter\evidence_item_field as evidence_item_field_formatter;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

class totara_evidence_item_fields_formatter_testcase extends totara_evidence_testcase {

    /** @var evidence_type|null */
    private $evidence_type = null;

    /** @var evidence_item|null */
    private $evidence_item = null;

    /**
     * @return void
     */
    public function setUp(): void {
        $this->setAdminUser();

        // Allow creation of actual files.
        $this->generator()->set_create_files(true);

        // Create evidence type.
        $this->evidence_type = $this->generator()->create_evidence_type([
            'name' => 'Completion',
            'field_types' => [
                'file',
                'checkbox',
            ],
        ]);

        // Create evidence bank item.
        $this->evidence_item = $this->generator()->create_evidence_item([
            'typeid' => $this->evidence_type->get_id(),
            'name' => 'Conference attendance',
        ]);
    }

    /**
     * @return void
     */
    public function tearDown(): void {
        $this->evidence_type = null;
        $this->evidence_item = null;
    }

    /**
     * @return void
     */
    public function test_field_data(): void {
        $context = context_system::instance();
        $item_formatter = new evidence_item_formatter($this->evidence_item, $context);

        /** @var field_data[] $fields */
        $fields = $item_formatter->format('fields', format::FORMAT_HTML);
        foreach ($fields as $field_data) {
            $field_formatter = new evidence_item_field_formatter($field_data, $context);

            // Validate label.
            $label = $field_formatter->format('label', format::FORMAT_HTML);
            $this->assertEquals($field_data->get_label(), $label);

            // Validate type.
            $type = $field_formatter->format('type', format::FORMAT_HTML);
            $this->assertEquals($field_data->get_type(), $type);

            // Validate content.
            $content = $field_formatter->format('content', format::FORMAT_HTML);
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