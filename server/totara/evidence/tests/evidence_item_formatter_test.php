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

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

class totara_evidence_item_formatter_testcase extends totara_evidence_testcase {

    /** @var evidence_type|null */
    private $evidence_type = null;

    /** @var evidence_item|null */
    private $evidence_item = null;

    /**
     * @return void
     */
    public function setUp(): void {
        $this->setAdminUser();

        // Create evidence type.
        $this->evidence_type = $this->generator()->create_evidence_type([
            'name' => 'Completion',
            'field_types' => [
                'file',
                'checkbox',
            ]
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
    public function test_fields(): void {
        $context = context_system::instance();
        $formatter = new evidence_item_formatter($this->evidence_item, $context);

        /** @var field_data[] $result */
        $result = $formatter->format('fields', format::FORMAT_HTML);

        $this->assertCount(2, $result);
        foreach ($result as $field_data) {
            $this->assertInstanceOf(field_data::class, $field_data);
            $this->assertTrue(in_array($field_data->get_type(), ['file', 'checkbox']));
        }
    }

}