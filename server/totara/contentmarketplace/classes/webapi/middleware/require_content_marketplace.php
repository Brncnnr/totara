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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_contentmarketplace
 */

namespace totara_contentmarketplace\webapi\middleware;

use Closure;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use core_plugin_manager;
use moodle_exception;
use totara_contentmarketplace\local;
use totara_contentmarketplace\plugininfo\contentmarketplace;

/**
 * Interceptor that checks if the specified content marketplace is enabled and active before allowing further graphql operations.
 */
class require_content_marketplace implements middleware {

    /**
     * @var string $marketplace_plugin Marketplace plugin that needs to be looked up.
     */
    private $marketplace_plugin;

    /**
     * Default constructor.
     *
     * @param string $marketplace_plugin Marketplace plugin that needs to be looked up.
     */
    public function __construct(string $marketplace_plugin) {
        if (strpos($marketplace_plugin, 'contentmarketplace_') === false) {
            $marketplace_plugin = 'contentmarketplace_' . $marketplace_plugin;
        }
        $this->marketplace_plugin = $marketplace_plugin;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        local::require_contentmarketplace();

        /** @var contentmarketplace $plugin */
        $plugin = core_plugin_manager::instance()->get_plugin_info($this->marketplace_plugin);
        if (!$plugin->is_enabled()) {
            throw new moodle_exception('error:disabledmarketplace', 'totara_contentmarketplace', '', $plugin->displayname);
        }

        return $next($payload);
    }

}
