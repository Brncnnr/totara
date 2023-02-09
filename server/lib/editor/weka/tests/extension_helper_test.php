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

use editor_weka\extension\extension;
use editor_weka\factory\extension_loader;
use editor_weka\local\extension_helper;

class editor_weka_extension_helper_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_get_extension_class_name_from_name(): void {
        self::assertNull(extension_helper::get_extension_class_name_from_extension_name('not_existing_yet'));
        self::assertNull(extension_helper::get_extension_class_name_from_extension_name('150'));
        self::assertNull(extension_helper::get_extension_class_name_from_extension_name('dd_++150'));
        self::assertNull(extension_helper::get_extension_class_name_from_extension_name('~special~'));

        $extensions = extension_loader::get_all_extension_classes();
        foreach ($extensions as $extension_cls) {
            self::assertTrue(class_exists($extension_cls));

            $name = call_user_func([$extension_cls, 'get_extension_name']);

            /** @see extension::get_extension_name() */
            self::assertEquals(
                $extension_cls,
                extension_helper::get_extension_class_name_from_extension_name($name)
            );

            if (str_starts_with($name, 'weka_')) {
                self::assertEquals(
                    $extension_cls,
                    extension_helper::get_extension_class_name_from_extension_name(substr($name, 5))
                );
            }
        }
    }
}