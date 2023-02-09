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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */
defined('MOODLE_INTERNAL') || die();

use core\editor\variant_name;
use editor_weka\factory\variant_definition;

class editor_weka_variant_definition_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_check_valid_variant(): void {
        // standard
        self::assertTrue(variant_definition::in_supported(variant_name::FULL));
        self::assertTrue(variant_definition::in_supported(variant_name::STANDARD));
        self::assertTrue(variant_definition::in_supported(variant_name::BASIC));
        self::assertTrue(variant_definition::in_supported(variant_name::SIMPLE));
        self::assertTrue(variant_definition::in_supported(variant_name::DESCRIPTION));

        // deprecated area-specific variants
        self::assertTrue(variant_definition::in_supported('editor_weka-phpunit'));
        self::assertTrue(variant_definition::in_supported('editor_weka-behat'));
        self::assertTrue(variant_definition::in_supported('editor_weka-learn'));
        self::assertTrue(variant_definition::in_supported('editor_weka-default'));
        self::assertTrue(variant_definition::in_supported('totara_playlist-comment'));
        self::assertTrue(variant_definition::in_supported('totara_playlist-summary'));
        self::assertTrue(variant_definition::in_supported('container_workspace-description'));
        self::assertTrue(variant_definition::in_supported('container_workspace-discussion'));
        self::assertTrue(variant_definition::in_supported('engage_article-content'));
        self::assertTrue(variant_definition::in_supported('engage_article-comment'));
        self::assertTrue(variant_definition::in_supported('performelement_static_content-content'));

        // nonexistent
        self::assertFalse(variant_definition::in_supported('shakira_number-one'));
        self::assertFalse(variant_definition::in_supported('lakad_matatag-normalin'));
    }
}