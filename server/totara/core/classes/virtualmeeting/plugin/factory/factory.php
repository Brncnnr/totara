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
 * @author  Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\virtualmeeting\plugin\factory;

use admin_settingpage;
use totara_core\http\client;
use totara_core\virtualmeeting\plugin\provider\provider;

/**
 * Main interface for virtualmeeting plugin factory, which describes the plugin and allows consumers to get a
 * service provider instance.
 */
interface factory {
    /**
     * Get the availability of the virtualmeeting plugin
     *
     * @return boolean
     */
    public function is_available(): bool;

    /**
     * Get an instance of the virtualmeeting plugin service provider
     *
     * @param client $client
     * @return provider
     */
    public function create_service_provider(client $client): provider;

    /**
     * Create the settings page for the virtualmeeting plugin
     *
     * @param string $section
     * @param string $displayname
     * @param bool $fulltree
     * @param bool $hidden
     * @return admin_settingpage|null
     */
    public function create_setting_page(string $section, string $displayname, bool $fulltree, bool $hidden): ?admin_settingpage;
}
