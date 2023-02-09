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
 * @package core_user
 */
namespace core_user\profile;

/**
 * For handling the value card field and the none value card field.
 */
interface card_display_field {
    /**
     * @return mixed|null
     */
    public function get_field_value();

    /**
     * @return mixed|null
     */
    public function get_field_label();

    /**
     * @return mixed|null
     */
    public function get_field_url();

    /**
     * @return bool
     */
    public function is_custom_field(): bool;
}