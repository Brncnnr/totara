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

namespace core\webapi\resolver\mutation;

use core\theme\helper;
use core\theme\settings as theme_settings;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\middleware\require_theme_settings;
use core\webapi\mutation_resolver;

/**
 * Mutation to update theme settings for a specific theme.
 */
class update_theme_settings extends mutation_resolver {

    /**
     * @inheritDoc
     */
    public static function resolve(array $args, execution_context $ec) {
        global $CFG;

        // Get parameters.
        $theme = $args['theme'];
        $tenant_id = $args['tenant_id'] ?? 0;
        $categories = $args['categories'];
        $files = $args['files'];

        if (!empty($tenant_id) && !$CFG->tenantsenabled) {
            throw new \invalid_parameter_exception('Can only set tenant_id when multi-tenancy is enabled.');
        }

        // Load theme config for theme.
        $theme_config = \theme_config::load($theme);

        // Save settings.
        $theme_settings = new theme_settings($theme_config, $tenant_id);
        $theme_settings->validate_categories($categories);

        $is_initial_tenant_update = $theme_settings->is_initial_tenant_branding();
        $is_re_enabling_tenant = $theme_settings->is_re_enabling_tenant_branding($categories);

        if (!$is_initial_tenant_update && $is_re_enabling_tenant) {
            // Update only the tenant settings
            $categories = array_filter($categories, function ($category) {
                return $category['name'] === 'tenant';
            });
        }
        $theme_settings->update_categories($categories);

        if (!$is_re_enabling_tenant) {
            // Don't update files when re-enabling tenant
            $theme_settings->update_files($files, $is_initial_tenant_update);
        }

        // Clear theme caches so that updated styles are served to the client.
        theme_reset_all_caches();

        // Return updated settings.
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
