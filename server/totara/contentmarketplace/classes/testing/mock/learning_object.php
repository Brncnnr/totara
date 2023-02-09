<?php
/**
 * This file is part of Totara Core
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
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\testing\mock;

use totara_contentmarketplace\learning_object\abstraction\metadata\model;

class learning_object implements model {
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $language;

    /**
     * @var string|null
     */
    private $image_url;

    /**
     * @var string
     */
    private static $marketplace_component;

    /**
     * learning_object constructor.
     * @param int|null    $id
     * @param string      $language
     * @param string|null $name
     * @param string|null $image_url
     */
    public function __construct(
        ?int $id = null,
        string $language = 'en',
        ?string $name = null,
        ?string $image_url = null
    ) {
        if (empty($id)) {
            $id = rand(1, 9999);
        }

        if (empty($name)) {
            $name = uniqid();
        }

        $this->id = $id;
        $this->language = $language;
        $this->name = $name;
        $this->image_url = $image_url;
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * @return int
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function set_marketplace_component(string $value): void {
        self::$marketplace_component = $value;
    }

    /**
     * @return string
     */
    public static function get_marketplace_component(): string {
        if (isset(self::$marketplace_component)) {
            return self::$marketplace_component;
        }

        // Default to linkedin content marketplace
        return 'contentmarketplace_linkedin';
    }

    /**
     * @return void
     */
    public static function clear(): void {
        self::$marketplace_component = null;
    }

    /**
     * @return string
     */
    public function get_language(): string {
        return $this->language;
    }

    /**
     * @return string|null
     */
    public function get_image_url(): ?string {
        return $this->image_url;
    }
}