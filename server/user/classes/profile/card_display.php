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
use core_user\profile\field\summary_field_provider;

final class card_display {
    /**
     * @var metadata[]
     */
    private $fields;

    /**
     * @var user_field_resolver
     */
    private $user_field_resolver;

    /**
     * card_display constructor.
     * @param user_field_resolver $resolver
     * @param metadata[] $fields
     */
    private function __construct(user_field_resolver $resolver, array $fields) {
        $this->user_field_resolver = $resolver;
        $this->fields = $fields;
    }

    /**
     * Construct an instance with the list of fields that stored in the config settings.
     *
     * @param user_field_resolver $resolver
     * @return card_display
     */
    public static function create(user_field_resolver $resolver): card_display {
        $display_fields = display_setting::get_display_fields();
        $provider = new summary_field_provider();

        $metadata_fields = [];

        foreach ($display_fields as $field_name) {
            if (empty($field_name)) {
                $metadata_fields[] = null;
                continue;
            }

            $field_metadata = $provider->get_field_metadata($field_name);
            if (null === $field_metadata) {
                throw new \coding_exception("Cannot find the field metadata from field name '{$field_name}'");
            }

            $key = $field_metadata->get_key();
            $metadata_fields[$key] = $field_metadata;
        }

        return new static($resolver, $metadata_fields);
    }

    /**
     * @return user_field_resolver
     */
    public function get_resolver(): user_field_resolver {
        return $this->user_field_resolver;
    }

    /**
     * Create an array off {@see card_display_field} and return to the external requester.
     * @return card_display_field[]
     */
    public function get_card_display_fields(): array {
        $rtn = [];
        foreach ($this->fields as $field_metadata) {
            if (null === $field_metadata) {
                $rtn[] = new null_card_display_field();
            } else {
                $rtn[] = new value_card_display_field($this->user_field_resolver, $field_metadata);
            }
        }

        return $rtn;
    }
}