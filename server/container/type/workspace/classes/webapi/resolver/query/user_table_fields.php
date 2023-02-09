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
 * @package container_workspace
 */
namespace container_workspace\webapi\resolver\query;

use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\query_resolver;
use core\webapi\middleware\require_login;
use core_user\profile\display_setting;
use core_user\profile\field\field_helper;
use core_user\profile\field\summary_field_provider;

/**
 * Query to fetch the display fields
 *
 * @deprecated since Totara 16. This query is no longer used.
 */
class user_table_fields extends query_resolver {
    /**
     * For maximum fields that we are going to fetch.
     * @var int
     */
    private const MAX_DISPLAY_FIELDS = 2;

    /**
     * @param array $args
     * @param execution_context $ec
     * @return array
     */
    public static function resolve(array $args, execution_context $ec): array {
        debugging('The container_workspace_user_table_fields query is deprecated with no replacement', DEBUG_DEVELOPER);

        $display_fields = display_setting::get_display_fields();
        $field_provider = new summary_field_provider();

        $fields = [];

        for ($i = 0; $i < display_setting::MAGIC_NUMBER_OF_DISPLAY_FIELDS; $i++) {
            $position_key = field_helper::format_position_key($i);
            if (!array_key_exists($position_key, $display_fields)) {
                throw new \coding_exception("No field found at '{$position_key}'");
            }

            $field_name = $display_fields[$position_key];
            if ('fullname' === $field_name) {
                // We will skip fullname for now.
                continue;
            }

            if (null === $field_name) {
                // No field was set.
                continue;
            }

            $field_metadata = $field_provider->get_field_metadata($field_name);
            $fields[] = [
                'position' => $i,
                'label' => $field_metadata->get_label()
            ];

            if (static::MAX_DISPLAY_FIELDS === count($fields)) {
                break;
            }
        }

        return $fields;
    }

    /**
     * @return require_login[]
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_advanced_feature('container_workspace')
        ];
    }

}