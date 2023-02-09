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

use core_user\profile\field\metadata;

/**
 * Class value_card_display_field
 * @package core_user\profile
 */
final class value_card_display_field implements card_display_field {
    /**
     * @var metadata
     */
    private $field_metadata;

    /**
     * @var user_field_resolver
     */
    private $user_field_resolver;

    /**
     * card_display_field constructor.
     * @param user_field_resolver $resolver
     * @param metadata $field_metadata
     */
    public function __construct(user_field_resolver $resolver, metadata $field_metadata) {
        $this->user_field_resolver = $resolver;
        $this->field_metadata = $field_metadata;
    }

    /**
     * @return metadata
     */
    public function get_field_metadata(): metadata {
        return $this->field_metadata;
    }

    /**
     * @return user_field_resolver
     */
    public function get_resolver(): user_field_resolver {
        return $this->user_field_resolver;
    }

    /**
     * @return string|null
     */
    public function get_field_value(): ?string {
        if ($this->field_metadata->is_custom_field()) {
            $field_name = $this->field_metadata->get_original_key_value();
            return $this->user_field_resolver->get_custom_field_value($field_name);
        }

        $field_name = $this->field_metadata->get_key();
        return $this->user_field_resolver->get_field_value($field_name);
    }

    /**
     * @return string|null
     */
    public function get_field_label(): ?string {
        return $this->field_metadata->get_label();
    }

    /**
     * @return string|null
     */
    public function get_field_url(): ?string {
        $url_field = $this->field_metadata->get_associate_url_field();
        if (null === $url_field) {
            return null;
        }

        return $this->user_field_resolver->get_field_value($url_field);
    }

    /**
     * @return bool
     */
    public function is_custom_field(): bool {
        return $this->field_metadata->is_custom_field();
    }
}