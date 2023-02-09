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
 * @package core
 */
namespace core\editor\abstraction;

/**
 * An abstraction of variant that is used the set the editor's behaviour.
 */
interface variant {
    /**
     * Returning a metadata for extra options.
     * @return array
     */
    public function get_additional_options(): array;

    /**
     * Returning a context's id of which it is used to construct the variant.
     * @return int
     */
    public function get_context_id(): int;

    /**
     * Returning a variant name of which it is used to construct the variant.
     * @return string
     */
    public function get_variant_name(): string;

    /**
     * @param string $variant_name
     * @param int    $context_id
     * @return variant
     */
    public static function create(string $variant_name, int $context_id): variant;
}