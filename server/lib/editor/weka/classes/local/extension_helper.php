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
namespace editor_weka\local;

use editor_weka\extension\extension;
use editor_weka\factory\extension_loader;

class extension_helper {
    /**
     * extension_helper constructor.
     */
    private function __construct() {
        // Prevent this class from instantiation.
    }

    /**
     * Return the extension class name from the unique extension name. If the extension
     * class name cannot be found then NULL will be returned.
     *
     * @param string $extension_name
     * @return string|null
     */
    public static function get_extension_class_name_from_extension_name(string $extension_name): ?string {
        $extension_classes = extension_loader::get_all_extension_classes();

        foreach ($extension_classes as $extension_class) {
            /**
             * @var string $name
             * @see extension::get_extension_name()
             */
            $name = call_user_func([$extension_class, 'get_extension_name']);
            if ($extension_name === $name || 'weka_' . $extension_name === $name) {
                return $extension_class;
            }
        }

        return null;
    }
}