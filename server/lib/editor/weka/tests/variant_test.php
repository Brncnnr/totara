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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package editor_weka
 */

use core\editor\variant_name;
use editor_weka\variant;

class editor_weka_variant_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_set_extra_extensions_with_invalid_data(): void {
        $context = context_system::instance();
        $variant = variant::create(variant_name::STANDARD, $context->id);

        $variant->set_extra_extensions([
            ['no_name' => 'no_name']
        ]);

        $this->assertDebuggingCalled(
            "The extension data map does not provide the extension's name to add to the list of extensions"
        );
    }
}