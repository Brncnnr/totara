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
 * Class null_card_display_field
 * @package core_user\profile
 */
final class null_card_display_field implements card_display_field {
    /**
     * @return bool
     */
    public function is_custom_field(): bool {
        return false;
    }

    /**
     * @return string|null
     */
    public function get_field_value(): ?string {
        return null;
    }

    /**
     * @return string|null
     */
    public function get_field_label(): ?string {
        return null;
    }

    /**
     * @return string|null
     */
    public function get_field_url(): ?string {
        return null;
    }
}
