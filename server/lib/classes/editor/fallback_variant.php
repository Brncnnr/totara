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
namespace core\editor;

use core\editor\abstraction\variant;

/**
 * A fallback variant for any editor plugin that did not implement the variant.
 */
final class fallback_variant implements variant {
    /**
     * @var int
     */
    private $context_id;

    /**
     * @var string
     */
    private $variant_name;

    /**
     * fallback_variant constructor.
     * @param string $variant_name
     * @param int    $context_id
     */
    public function __construct(string $variant_name, int $context_id) {
        $this->context_id = $context_id;
        $this->variant_name = $variant_name;
    }

    /**
     * @return array
     */
    public function get_additional_options(): array {
        return [];
    }

    /**
     * @return int
     */
    public function get_context_id(): int {
        return $this->context_id;
    }

    /**
     * @return string
     */
    public function get_variant_name(): string {
        return $this->variant_name;
    }

    /**
     * @param string $variant_name
     * @param int    $context_id
     *
     * @return variant|fallback_variant
     */
    public static function create(string $variant_name, int $context_id): variant {
        return new static($variant_name, $context_id);
    }
}