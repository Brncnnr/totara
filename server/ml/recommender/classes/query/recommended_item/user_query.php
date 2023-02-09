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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package ml_recommender
 */

namespace ml_recommender\query\recommended_item;

use core\pagination\base_cursor;
use core\pagination\offset_cursor;

/**
 * Query class for recommender items
 */
final class user_query {
    /**
     * @var offset_cursor|null
     */
    private $cursor;

    /**
     * ID of the user to target
     *
     * @var int
     */
    private $target_user_id;

    /**
     * Target component
     *
     * @var string
     */
    private $target_component;

    /**
     * Target area
     *
     * @var string|null
     */
    private $target_area;

    /**
     * query constructor.
     *
     * @param int $target_user_id
     * @param string $target_component
     * @param string|null $target_area
     */
    public function __construct(int $target_user_id, string $target_component, ?string $target_area = null) {
        $this->target_user_id = $target_user_id;
        $this->target_component = $target_component;
        $this->target_area = $target_area;
        $this->cursor = null;
    }

    /**
     * @return base_cursor
     */
    public function get_cursor(): base_cursor {
        if (null === $this->cursor) {
            $this->cursor = offset_cursor::create();
        }

        return $this->cursor;
    }

    /**
     * @param base_cursor $cursor
     * @return void
     */
    public function set_cursor(base_cursor $cursor): void {
        $this->cursor = $cursor;
    }

    /**
     * @return int
     */
    public function get_target_user_id(): int {
        return $this->target_user_id;
    }

    /**
     * @param int $target_user_id
     */
    public function set_target_user_id(int $target_user_id): void {
        $this->target_user_id = $target_user_id;
    }

    /**
     * @return string
     */
    public function get_target_component(): string {
        return $this->target_component;
    }

    /**
     * @param string $target_component
     */
    public function set_target_component(string $target_component): void {
        $this->target_component = $target_component;
    }

    /**
     * @return string|null
     */
    public function get_target_area(): ?string {
        return $this->target_area;
    }

    /**
     * @param string|null $target_area
     */
    public function set_target_area(?string $target_area): void {
        $this->target_area = $target_area;
    }
}