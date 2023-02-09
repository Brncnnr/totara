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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package performelement_long_text
 */

use core\json_editor\helper\document_helper;
use mod_perform\models\activity\element_plugin;
use performelement_long_text\answer_required_error;
use performelement_long_text\long_text;

global $CFG;
require_once($CFG->dirroot . '/mod/perform/tests/weka_testcase.php');

/**
 * @group perform
 * @group perform_element
 */
class performelement_long_text_validation_testcase extends mod_perform_weka_testcase {

    public function test_validation(): void {
        /** @var long_text $long_text */
        $long_text = element_plugin::load_by_plugin('long_text');

        $element = $this->perform_generator()->create_element([
            'title' => 'element one',
            'is_required' => true,
            'plugin_name' => 'long_text',
        ]);

        // Valid response JSON, before file handling has been applied
        $errors = $long_text->validate_response(document_helper::json_encode_document([
            'draft_id' => 1234,
            'weka' => $this->create_weka_document_with_text(false, 'Test!'),
        ]), $element);
        $this->assertEmpty($errors);

        // Valid response JSON, after file handling has been applied
        $errors = $long_text->validate_response($this->create_weka_document_with_text(true, 'Test!'), $element);
        $this->assertEmpty($errors);

        // Null response
        $errors = $long_text->validate_response(json_encode(null), $element);
        $this->assertCount(1, $errors);
        $this->assertInstanceOf(answer_required_error::class, $errors->first());

        // Response that only contains whitespace
        $errors = $long_text->validate_response(
            '{"type":"doc","content":[{"type":"paragraph"},{"type":"paragraph","content":[{"type":"text","text":"   "}]},{"type":"paragraph"}]}',
            $element
        );
        $this->assertCount(1, $errors);
        $this->assertInstanceOf(answer_required_error::class, $errors->first());

        // Missing weka JSON
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid document schema');
        $long_text->validate_response(document_helper::json_encode_document([
            'draft_id' => 1234,
        ]), $element);
    }

}
