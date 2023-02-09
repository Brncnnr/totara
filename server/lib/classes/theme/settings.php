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

namespace core\theme;

use cache;
use core\hook\tenant_customizable_theme_settings as tenant_customizable_theme_settings_hook;
use core\hook\theme_settings_css_categories as theme_settings_css_categories_hook;
use core\theme\file\helper;
use core\theme\file\theme_file;
use core\theme\validation\property\property_validator;
use theme_config;

/**
 * Theme appearance settings manager.
 */
final class settings {

    /** @var theme_config */
    private $theme_config;

    /** @var int */
    private $tenant_id;

    /** @var theme_file[] */
    private $file_instances = [];

    /** @var tenant_customizable_theme_settings_hook */
    private $tenant_settings_hook;

    /** @var array */
    protected static $adhoc_categories = [];

    /** @var array */
    private $css_categories = [];

    /**
     * settings constructor.
     *
     * @param theme_config $theme_config
     * @param int $tenant_id
     */
    public function __construct(theme_config $theme_config, int $tenant_id) {
        $this->theme_config = $theme_config;
        $this->tenant_id = $tenant_id;
    }

    /**
     * @param int $tenant_id
     */
    public function set_tenant_id(int $tenant_id) {
        $this->tenant_id = $tenant_id;

        // Also update tenant ID for theme files.
        $instances = $this->get_file_instances();
        foreach ($instances as $instance) {
            $instance->set_tenant_id($tenant_id);
        }
    }

    /**
     * Get setting categories for theme.
     *
     * @param bool $tenant_enabled
     * @param bool $include_default_file_categories
     *
     * @return array|mixed
     */
    public function get_categories(bool $tenant_enabled = true, bool $include_default_file_categories = true): array {
        global $DB;

        // If we don't have adhoc categories then fetch from cache.
        if (empty(settings::$adhoc_categories)) {
            // Return from cache.
            $cache = cache::make('core', 'theme_setting_categories');
            $key = "{$this->theme_config->name}_tenant_{$this->tenant_id}_categories_{$tenant_enabled}_{$include_default_file_categories}";
            if ($categories = $cache->get($key)) {
                return $categories;
            }
        }

        // Setup cache.
        $theme = $this->theme_config->name;
        $categories = $this->get_default_categories($include_default_file_categories);

        // Get all variables for site.
        $values = $DB->get_field(
            'config_plugins',
            'value',
            $this->get_config_parameters(0, $theme)
        );
        if (!empty($values)) {
            $theme_categories = json_decode($values, true);
            $this->merge_categories($categories, $theme_categories);
        }

        // Get all variables for current tenant and override site.
        if ($tenant_enabled && $this->tenant_id > 0) {
            $values = $DB->get_field(
                'config_plugins',
                'value',
                $this->get_config_parameters($this->tenant_id, $theme)
            );
            if (!empty($values)) {
                $theme_categories = json_decode($values, true);
                $this->merge_categories($categories, $theme_categories);
            }
        }

        // If any adhoc categories are set then that takes precedence over all the rest.
        if (!empty(settings::$adhoc_categories)) {
            $this->merge_categories($categories, settings::$adhoc_categories);
        } else {
            $cache->set($key, $categories);
        }

        return $categories;
    }

    /**
     * @param array $categories
     */
    public static function set_adhoc_categories(array $categories): void {
        settings::$adhoc_categories = $categories;
    }

    /**
     * Overwrite values in array1 with values in array2.
     *
     * @param array $categories1
     * @param array $categories2
     */
    private function merge_categories(array &$categories1, array $categories2) {
        foreach ($categories2 as $category2) {
            foreach ($categories1 as &$category1) {
                if ($category1['name'] === $category2['name']) {
                    $this->merge_properties(
                        $category1['properties'],
                        $category2['properties']
                    );
                    continue 2;
                }
            }
            $categories1[] = $category2;
        }
    }

    /**
     * Overwrite values in array1 with values in array2.
     *
     * @param array $props1
     * @param array $props2
     */
    private function merge_properties(array &$props1, array $props2) {
        foreach ($props2 as $prop2) {
            foreach ($props1 as &$prop1) {
                if ($prop1['name'] === $prop2['name']) {
                    $prop1['value'] = $prop2['value'];
                    continue 2;
                }
            }
            $props1[] = $prop2;
        }
    }

    /**
     * Get default category properties.
     *
     * @param bool $include_files
     *
     * @return array
     */
    private function get_default_categories(bool $include_files): array {
        $categories = [];

        if ($include_files) {
            $instances = $this->get_file_instances();
            foreach ($instances as $instance) {
                if ($instance->is_enabled() && $this->can_manage($instance)) {
                    $categories = array_merge($categories, $instance->get_default_categories());
                }
            }
        }

        return $categories;
    }

    /**
     * @return theme_file[]
     */
    public function get_files(): array {
        return $this->get_file_instances();
    }

    /**
     * @param array $categories
     */
    public function validate_categories(array $categories): void {
        // Check if category and its properties are allowed to be updated for tenant.
        if ($this->tenant_id !== 0) {
            foreach ($categories as $category) {
                if (!$this->is_tenant_customizable_category($category['name'])) {
                    throw new \invalid_parameter_exception("Category '{$category['name']}' is not allowed to be updated for tenant.");
                }
                foreach ($category['properties'] as $property) {
                    if (!$this->is_tenant_customizable_setting($category['name'], $property['name'])) {
                        throw new \invalid_parameter_exception("Property '{$property['name']}' is not allowed to be updated for tenant.");
                    }
                }
            }
        }

        // Confirm that properties have correct values.
        foreach ($categories as $category) {
            foreach ($category['properties'] as $property) {
                $this->validate_property($property);
            }
        }
    }

    /**
     * @param array $property
     */
    public function validate_property(array $property): void {
        $validator = "core\\theme\\validation\\property\\{$property['type']}_validator";
        if (class_exists($validator)) {
            $validator = new $validator();
            if (!$validator instanceof property_validator) {
                throw new \coding_exception('Invalid validator class');
            }
            $validator->validate($property);
        }
    }

    /**
     * Some properties needs to be cleaned before we can save them to the database.
     *
     * @param array $properties
     * @return array
     *
     * @deprecated since Totara 15.0
     */
    public function clean_properties(array $properties): array {
        debugging('\core\theme\settings::clean_properties is deprecated', DEBUG_DEVELOPER);
        foreach ($properties as &$property) {
            $property['value'] = clean_text(
                $property['value'],
                FORMAT_HTML,
                [
                    'allowid' => true
                ]
            );
        }
        return $properties;
    }

    /**
     * @param array $files
     */
    public function validate_files(array $files): void {
        $instances = $this->get_file_instances();
        foreach ($files as $file) {
            foreach ($instances as $instance) {
                if ($instance->get_ui_key() === $file['ui_key']) {
                    // Confirm that the user has capability to manage the file.
                    if (!$instance->is_enabled() || !$this->can_manage($instance)) {
                        throw new \moodle_exception('nopermissionthemefile', 'error');
                    }
                    continue 2;
                }
            }
            throw new \moodle_exception('nohandlerthemefile', 'error', null, $file['ui_key']);
        }
    }

    /**
     * @param array $categories
     */
    public function update_categories(array $categories): void {
        global $DB;

        $condition = $this->get_config_parameters();

        // Update per category if found, otherwise insert new record.
        $cats = $categories;
        if ($record = $DB->get_record('config_plugins', $condition)) {
            $cats = json_decode($record->value, true);
            foreach ($categories as $category) {
                // Update category if found.
                foreach ($cats as &$cat) {
                    if ($cat['name'] === $category['name']) {
                        $cat['properties'] = $category['properties'];
                        continue 2;
                    }
                }
                // Add new category if not found.
                $cats[] = $category;
            }
        }

        set_config($condition['name'], json_encode($cats), $condition['plugin']);

        // Reset category cache.
        settings::purge_cache();
    }

    /**
     * @param array $files
     * @param bool $copy_site_files
     */
    public function update_files(array $files, bool $copy_site_files = false): void {
        $instances = $this->get_file_instances();

        // Update files.
        foreach ($files as $file) {
            foreach ($instances as $instance_key => $instance) {
                if ($instance->get_ui_key() === $file['ui_key']) {
                    if (empty($file['action']) || $file['action'] === 'SAVE') {
                        // If draft ID is set we need to save it.
                        $instance->save_files($file['draft_id']);
                        unset($instances[$instance_key]);
                    } else if (!empty($file['action']) && $file['action'] === 'RESET') {
                        // If draft ID is not set and current URL is empty we need to delete the
                        // current stored file and restore the theme file to its default.
                        $instance->delete();
                    }

                    continue 2;
                }
            }
        }

        if ($this->tenant_id > 0 && $copy_site_files) {
            // Make copy of customizable files not yet customized for the tenant
            foreach ($instances as $instance_key => $instance) {
                if ($this->is_tenant_customizable_setting($instance->get_ui_category(), $instance->get_ui_key())) {
                    $instance->copy_site_file_to_tenant();
                }
            }

        }
    }

    /**
     * Delete the current stored file for a specific theme file so that it defaults
     * back to the default setting or site setting if removed for a tenant.
     *
     * @param array $file
     *
     * @return theme_file|null
     */
    public function delete_file(array $file): ?theme_file {
        $instances = $this->get_file_instances();

        foreach ($instances as $instance) {
            if ($instance->get_ui_key() === $file['ui_key']) {
                $instance->delete();
                return $instance;
            }
        }

        return null;
    }

    /**
     * @return theme_file[]
     */
    private function get_file_instances(): array {
        // Get classes and instantiate them all.
        if (empty($this->file_instances)) {
            $classes = helper::get_classes();
            /** @var theme_file $class */
            foreach ($classes as $class) {
                $instance = new $class($this->theme_config);
                $instance->set_tenant_id($this->tenant_id);
                $this->file_instances[] = $instance;
            }
        }
        return $this->file_instances;
    }

    /**
     * Get a specific property.
     *
     * @param string $category
     * @param string $property
     * @param array|null $categories
     *
     * @return array|null
     */
    public function get_property(string $category, string $property, ?array $categories = null): ?array {
        $categories = $categories ?? $this->get_categories();
        foreach ($categories as $cat) {
            if ($cat['name'] === $category) {
                foreach ($cat['properties'] as $prop) {
                    if ($prop['name'] === $property) {
                        return $prop;
                    }
                }
                break;
            }
        }

        return null;
    }

    /**
     * Get a list of css variables from the categories.
     *
     * @return string
     */
    public function get_css_variables(): string {
        // If tenant then check if tenant enabled.
        $tenant_enabled = false;
        if ($this->tenant_id > 0) {
            $tenant_enabled = $this->is_tenant_branding_enabled();
        }

        $css = '';
        $custom_css = '';
        $categories = $this->get_categories($tenant_enabled, false);
        foreach ($categories as $category) {
            // First check if this category is contains CSS properties.
            if ($this->is_category_with_css_settings($category['name'])) {
                // Get all the switches that controls which properties are active.
                $switches = array_filter($category['properties'], function (array $property) {
                    return $property['type'] === 'boolean';
                });
                // Loop through all the properties of this category.
                foreach ($category['properties'] as $property) {
                    // Check if the property has been registered as a CSS property.
                    if ($this->is_css_property($category['name'], $property['name'], false)) {
                        // Some properties need to be transformed into `--name: value;` pairs.
                        if ($this->require_css_property_transformation($category['name'], $property['name'])) {
                            if ($property['type'] !== 'value') {
                                continue;
                            }
                            if ($this->is_on($switches, $property['name'])) {
                                $css .= "--{$property['name']}: {$property['value']};";
                            }
                        } else {
                            $custom_css .= "\n{$property['value']}\n";
                        }
                    }
                }
            }
        }

        // Each transformed property needs to be in the root element of the document tree.
        $css = ':root{' . $css . '}';

        // Return any category css with custom css added to the end.
        return $css . $custom_css;
    }

    /**
     * Check if a value is controlled by a switch and that the switch is on.
     *
     * @param array $switches
     * @param string $name
     *
     * @return bool
     */
    private function is_on(array $switches, string $name): bool {
        foreach ($switches as $switch) {
            if (isset($switch['selectors']) && in_array($name, $switch['selectors'])) {
                return filter_var($switch['value'], FILTER_VALIDATE_BOOLEAN);
            }
        }
        return true;
    }

    /**
     * Check if a setting is enabled.
     *
     * @param string $category
     * @param string $property
     * @param bool $default
     *
     * @return bool
     */
    public function is_enabled(string $category, string $property, bool $default = false): bool {
        $prop = $this->get_property($category, $property);
        return !empty($prop)
            ? filter_var($prop['value'], FILTER_VALIDATE_BOOLEAN) ?? $default
            : $default;
    }

    /**
     * Check if tenant previously enabled.
     *
     * @return bool
     */
    public function is_initial_tenant_branding(): bool {
        return $this->tenant_id > 0 && $this->get_property('tenant', 'formtenant_field_tenant') === null;
    }

    /**
     * Check if tenant branding is enabled.
     *
     * @return bool
     */
    public function is_tenant_branding_enabled(): bool {
        return $this->is_enabled('tenant', 'formtenant_field_tenant', false);
    }

    /**
     * Check whether tenant branding is being re-enabled.
     *
     * @param array $categories
     *
     * @return bool
     */
    public function is_re_enabling_tenant_branding(array $categories): bool {
        if ($this->tenant_id === 0) {
            return false;
        }

        // Not previously set
        $prop = $this->get_property('tenant', 'formtenant_field_tenant');
        if ($prop === null) {
            return false;
        }

        // Set but currently enabled
        if (filter_var($prop['value'], FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        // Set but currently disabled
        $new_prop = $this->get_property('tenant', 'formtenant_field_tenant', $categories);
        return !empty($new_prop)
            ? filter_var($new_prop['value'], FILTER_VALIDATE_BOOLEAN) ?? false
            : false;
    }

    /**
     * Confirm if a user has the capability required to manage a theme file.
     *
     * @param theme_file $theme_file
     *
     * @return bool
     */
    public function can_manage(theme_file $theme_file): bool {
        global $USER;

        // Site admin always has access.
        if (is_siteadmin($USER)) {
            return true;
        }

        // Check if capability exists (might not exist during upgrade from version < t13).
        if (!get_capability_info('totara/tui:themesettings')) {
            return false;
        }

        // Get context of user and check capability.
        $context = $theme_file->get_context(false);
        if ($context instanceof \context_tenant) {
            $tenant = \core\record\tenant::fetch($context->tenantid);
            $context = \context_coursecat::instance($tenant->categoryid);
        }
        return has_capability('totara/tui:themesettings', $context);
    }

    /**
     * @return tenant_customizable_theme_settings_hook
     */
    public function get_customizable_tenant_theme_settings_hook(): tenant_customizable_theme_settings_hook {
        if (is_null($this->tenant_settings_hook)) {
            $default_categories = [
                'brand' => '*',
                'colours' => '*',
                'images' => '*',
                'custom' => ['formcustom_field_customfooter'],
                'tenant' => '*',
            ];

            $this->tenant_settings_hook = new tenant_customizable_theme_settings_hook($default_categories, $this->theme_config);
            $this->tenant_settings_hook->execute();
        }
        return $this->tenant_settings_hook;
    }

    /**
     * Return the list of theme settings that can be customized for tenants
     *
     * @return array
     */
    public function get_customizable_tenant_theme_settings(): array {
        $hook = $this->get_customizable_tenant_theme_settings_hook();
        return $hook->get_customizable_settings();
    }

    /**
     * Return the list of categories that contains CSS settings.
     *
     * @return array
     */
    public function get_categories_with_css_settings(): array {
        if (empty($this->css_categories)) {
            $default_css_settings_categories = [
                'colours' => '*',
                'custom' => [
                    'formcustom_field_customcss' => ['transform' => false],
                ],
            ];
            $css_categories_hook = new theme_settings_css_categories_hook($default_css_settings_categories);
            $css_categories_hook->execute();
            $this->css_categories = $css_categories_hook->get_categories();
        }
        return $this->css_categories;
    }

    /**
     * @param string $category
     *
     * @return bool
     */
    public function is_category_with_css_settings(string $category): bool {
        return in_array($category, array_keys($this->get_categories_with_css_settings()),true);
    }

    /**
     * @param string $category
     * @param string $property
     * @param bool|null $validate_category
     *
     * @return bool
     */
    public function is_css_property(string $category, string $property, ?bool $validate_category = true): bool {
        if ($validate_category && !$this->is_category_with_css_settings($category)) {
            return false;
        }

        $categories = $this->get_categories_with_css_settings();
        $properties = $categories[$category];
        if (!is_array($properties)) {
            return $properties === '*';
        }

        return array_key_exists($property, $properties);
    }

    /**
     * @param string $category
     * @param string $property
     *
     * @return bool
     */
    public function require_css_property_transformation(string $category, string $property): bool {
        $categories = $this->get_categories_with_css_settings();
        $properties = $categories[$category];
        if (!is_array($properties)) {
            return $properties === '*';
        }
        if (array_key_exists($property, $properties)) {
            $settings = $properties[$property];
            return !array_key_exists('transform', $settings) || $settings['transform'];
        }
        return true;
    }

    /**
     * @return void
     */
    public static function purge_cache(): void {
        // Purge theme settings category cache.
        cache::make('core', 'theme_setting_categories')->purge();
    }

    /**
     * @param int|null $tenant_id
     * @param string|null $theme_name
     *
     * @return array
     */
    private function get_config_parameters(?int $tenant_id = null, ?string $theme_name = null): array {
        $tenant_id = $tenant_id ?? $this->tenant_id;
        $theme_name = $theme_name ?? $this->theme_config->name;

        return [
            'name' => "tenant_{$tenant_id}_settings",
            'plugin' => "theme_{$theme_name}"
        ];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    private function is_tenant_customizable_category(string $name): bool {
        $hook = $this->get_customizable_tenant_theme_settings_hook();
        return $hook->is_tenant_customizable_category($name);
    }

    /**
     * @param string $category
     * @param string $ui_key
     *
     * @return bool
     */
    private function is_tenant_customizable_setting(string $category, string $ui_key): bool {
        $hook = $this->get_customizable_tenant_theme_settings_hook();
        return $hook->is_tenant_customizable_category_setting($category, $ui_key);
    }
}