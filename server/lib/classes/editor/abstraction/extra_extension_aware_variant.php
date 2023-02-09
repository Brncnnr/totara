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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */
namespace core\editor\abstraction;

/**
 * For any variant instant that want to have the declaration of the custom extra extensions
 * on the runtime by the client side.
 */
interface extra_extension_aware_variant {
    /**
     * The parameter $extra_extensions is an array of extensions map. The example
     * structure of $extra_extensions is:
     * [
     *  [
     *      'name' => 'unique_extension_name',
     *      'options' => [
     *          // A hash map with any named parameters and its value for the extension to use.
     *      ]
     *  ],
     *  // And so on, but the extension map must be similar as above.
     * ]
     *
     * @param array $extra_extensions
     * @return void
     */
    public function set_extra_extensions(array $extra_extensions): void;
}