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
 * @package totara_contentmarketplace
 */
namespace totara_contentmarketplace\learning_object\abstraction\metadata;

interface model {
    /**
     * Returns the name of learning object.
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Returns the id of learning object.
     *
     * @return int
     */
    public function get_id(): int;

    /**
     * Returns the marketplace type of learning object.
     *
     * @return string
     */
    public static function get_marketplace_component(): string;

    /**
     * Returns the locale language that this learning object is written under.
     *
     * @return string
     */
    public function get_language(): ?string;

    /**
     * Returns the URL for displaying the thumbnail image of the learning object.
     *
     * @return string|null
     */
    public function get_image_url(): ?string;

}