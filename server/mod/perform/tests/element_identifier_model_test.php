<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Simon Coggins <simon.coggins@totaralearning.com>
 * @package mod_perform
 */

use container_perform\perform;
use core\orm\collection;
use mod_perform\models\activity\element;
use mod_perform\models\activity\element_identifier;

/**
 * @group perform
 */
class mod_perform_element_identifier_model_testcase extends advanced_testcase {

    public function test_create() {

        $element_identifier = element_identifier::create(
            'test identifier'
        );

        // Reload, just to make sure that we're getting it out of the DB.
        /** @var element_identifier $actual_element */
        $element_identifier_model = element_identifier::load_by_id($element_identifier->id);

        $this->assertSame('test identifier', $element_identifier_model->identifier);
        $this->assertInstanceOf(collection::class, $element_identifier_model->elements);
        $this->assertEmpty($element_identifier_model->elements);
    }

    public function test_create_via_element_model() {
        $default_context = context_coursecat::instance(perform::get_default_category_id());

        $element1 = element::create(
            $default_context,
            'short_text',
            'test element 1 title',
            'test identifier',
            null,
            true
        );

        $element2 = element::create(
            $default_context,
            'short_text',
            'test element 2 title',
            'test same identifier',
            null,
            true
        );

        $element3 = element::create(
            $default_context,
            'short_text',
            'test element 3 title',
            'test same identifier',
            null,
            true
        );

        $identifier1 = $element1->element_identifier;
        $identifier2 = $element2->element_identifier;

        // Test identifiers got created.
        $this->assertSame('test identifier', $identifier1->identifier);
        $this->assertSame('test same identifier', $identifier2->identifier);

        // Test relationships are defined.
        $this->assertSame($identifier1->id, $element1->element_identifier->id);
        $this->assertSame($identifier2->id, $element2->element_identifier->id);
        $this->assertSame($identifier2->id, $element3->element_identifier->id);
        $this->assertSame([$element1->id], $identifier1->elements->pluck('id'));
        $this->assertEqualsCanonicalizing([$element2->id, $element3->id], $identifier2->elements->pluck('id'));
    }

    public function test_create_with_empty_string(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Cannot create empty identifier');
        element_identifier::create('');
    }

    public function test_create_max_length(): void {
        $element_identifier = element_identifier::create($this->get_string_with_length(255));
        self::assertEquals(255, strlen($element_identifier->identifier));

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Identifier string exceeds maximum length');
        element_identifier::create($this->get_string_with_length(256));
    }

    /**
     * @param int $length
     * @return string
     */
    private function get_string_with_length(int $length): string {
        $string = '';
        while (strlen($string) < $length) {
            $string .= 'x';
        }
        return $string;
    }
}