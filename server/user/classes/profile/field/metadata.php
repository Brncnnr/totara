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
namespace core_user\profile\field;

/**
 * A dummy class that hold the metadata related to the field.
 */
final class metadata {
    /**
     * @var string
     */
    private $key;

    /**
     * @var bool
     */
    private $custom_field;

    /**
     * @var string
     */
    private $associate_url_field;

    /**
     * @var string
     */
    private $label;

    /**
     * field_metadata constructor.
     * @param string        $key
     * @param string        $label
     * @param string|null   $associate_url_field
     */
    public function __construct(string $key, string $label,  ?string $associate_url_field = null) {
        $this->key = $key;
        $this->label = $label;
        $this->associate_url_field = $associate_url_field;

        // Default to no custom field.
        $this->custom_field = false;
    }

    /**
     * @return string
     */
    public function get_label(): string {
        return $this->label;
    }

    /**
     * This getter will return the original data value of the field key.
     *
     * @return string
     */
    public function get_original_key_value(): string {
        return $this->key;
    }

    /**
     * If the field is custom, this getter will try to prefix the field key with "custom_".
     *
     * @return string
     */
    public function get_key(): string {
        if ($this->custom_field) {
            // Only formatting the key if the field is custom.
            return field_helper::format_custom_field_short_name($this->key);
        }

        return $this->key;
    }

    /**
     * @return bool
     */
    public function is_custom_field(): bool {
        return $this->custom_field;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function set_custom_field(bool $value): void {
        $this->custom_field = $value;
    }

    /**
     * @return string|null
     */
    public function get_associate_url_field(): ?string {
        return $this->associate_url_field;
    }
}