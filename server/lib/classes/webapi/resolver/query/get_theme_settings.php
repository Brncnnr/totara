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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core
 */

namespace core\webapi\resolver\query;

use core\theme\helper;
use core\theme\settings as theme_settings;
use core\webapi\execution_context;
use core\webapi\middleware\require_theme_settings;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;

/**
 * Query to get theme settings for a specific tenant.
 */
class get_theme_settings extends query_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        // Get parameters.
        $theme = $args['theme'];
        $tenant_id = $args['tenant_id'] ?? 0;

        // Load theme config for theme.
        $theme_config = \theme_config::load($theme);

        // Get settings for theme.
        $theme_settings = new theme_settings($theme_config, $tenant_id);
        return helper::output_theme_settings($theme_settings);
    }

    /**
     * @inheritDoc
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_theme_settings('tenant_id'),
        ];
    }

}
