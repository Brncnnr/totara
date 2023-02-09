<?php
/*
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core
 */

namespace core\hook;

use totara_core\hook\base;
use theme_config;

/**
 * Class tenant_customizable_theme_settings *
 * Hook to set which theme settings and settings can be customized for tenants
 *
 * Settings array entries must have the following format:
 *  key: Name of category
 *  value: '*' || Array of setting names. '*' indicates all settings
 *
 * Example:
 *  [
 *      'brand' => '*',
 *      'colours' => '*',
 *      'images' => ['sitelogo', 'sitefavicon', 'sitelogin'],
 *      'custom' => ['formcustom_field_customfooter'],
 *      'tenant' => '*'
 *  ]
 */
class tenant_customizable_theme_settings extends base {
    /**
     * @var array $settings Array of customizable settings.
     */
    private $settings;

    /**
     * @var $theme_config theme_config
     */
    private $theme_config;

    /**
     * tenant_customizable_theme_settings constructor.
     *
     * @param array $settings
     * @param theme_config|null $theme_config Optional so as not to break existing theme settings implementations.
     */
    public function __construct(array $settings, ?theme_config $theme_config = null) {
        $this->settings = $settings;
        $this->theme_config = $theme_config;
    }

    /**
     * @param array $settings New set of customizable settings
     */
    public function set_customizable_settings(array $settings) {
        $this->settings = $settings;
    }

    /**
     * @return array
     */
    public function get_customizable_settings(): array {
        return $this->settings;
    }

    /**
     * @param string $category
     * @return bool
     */
    public function is_tenant_customizable_category(string $category): bool {
        return in_array($category, array_keys($this->settings), true);
    }

    /**
     * @param string $category
     * @param string $setting
     * @return bool
     */
    public function is_tenant_customizable_category_setting(string $category, string $setting): bool {
        if (!$this->is_tenant_customizable_category($category)) {
            return false;
        }

        $settings = $this->settings[$category];
        if (!is_array($settings)) {
            return $settings === '*';
        }

        return in_array($setting, $settings, true);
    }

    /**
     * Get the theme_config object passed to the hook on creation, if there was one.
     *
     * @return theme_config|null
     */
    public function get_theme_config(): ?theme_config {
        return $this->theme_config;
    }
}
