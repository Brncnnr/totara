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

namespace core\theme\file;

use core\files\file_area;
use core\files\file_helper;
use core\files\type\file_type;
use core\theme\settings as theme_settings;
use core\theme\helper as theme_helper;
use context;
use moodle_url;
use stored_file;
use theme_config;

/**
 * Class theme_file
 *
 * Abstract base class for theme settings related files.
 *
 * @package core\theme\file
 */
abstract class theme_file {

    /** @var string */
    protected $theme;

    /** @var theme_config */
    protected $theme_config;

    /** @var int */
    protected $tenant_id = 0;

    /** @var context */
    protected $context;

    /** @var file_type */
    protected $type;

    /** @var int */
    protected $item_id = 0;

    /** @var theme_settings|null */
    protected $theme_settings = null;

    /**
     * theme_file constructor.
     *
     * @param theme_config|null $theme_config
     * @param string|null $unused This parameter is no longer used as of Totara 13.6
     */
    public function __construct(?theme_config $theme_config = null, ?string $unused = null) {
        $this->theme_config = $theme_config;
    }

    /**
     * @param int $tenant_id
     */
    public function set_tenant_id(int $tenant_id): void {
        $this->tenant_id = $tenant_id;
    }

    /**
     * @param int $user_id
     */
    public function set_user_id(int $user_id): void {
        $this->user_id = $user_id;
    }

    /**
     * @param context $context
     */
    public function set_context(context $context): void {
        $this->context = $context;
    }

    /**
     * @param bool $determine_tenant_branding
     *
     * @return context|null
     */
    public function get_context(bool $determine_tenant_branding = true): ?context {
        if (empty($this->context)) {
            return $this->get_default_context(null, $determine_tenant_branding);
        }
        return $this->context;
    }

    /**
     * @param theme_config $theme_config
     */
    public function set_theme_config(theme_config $theme_config): void {
        $this->theme_config = $theme_config;
    }

    /**
     * @return theme_config
     */
    protected function get_theme_config(): theme_config {
        if (empty($this->theme_config)) {
            $this->theme_config = theme_helper::load_theme_config();
        }

        return $this->theme_config;
    }

    /**
     * @param int $item_id
     */
    public function set_item_id(int $item_id): void {
        $this->item_id = $item_id;
    }

    /**
     * Get item ID of the theme plugin.
     *
     * @param int|null $tenant_id
     * @param string|null $theme
     * @param bool|null $use_reference Use reference version of the file originally saved when copied from site branding.
     *
     * @return int
     */
    public function get_item_id(?int $tenant_id = null, ?string $theme = null, ?bool $use_reference = false): int {
        global $DB;

        $id = $tenant_id ?? $this->get_tenant_id();

        // If we should use the reference version but this is for site then we just return nothing so that
        // no image could be found as a reference copy.
        if ($use_reference && $id === 0) {
            return 0;
        }

        $plugin = "theme_" . ($theme ?? $this->get_theme_config()->name);
        $name = "tenant_{$id}_settings" . ($use_reference ? '_reference' : '');

        // Always make sure that there is a record representing this config.
        if (!get_config($plugin, $name)) {
            set_config($name, '{}', $plugin);
        }

        // To keep this settings unique per theme we need to get a
        // unique ID and the plugin ID is as good as any.
        $this->item_id = $DB->get_field(
            'config_plugins',
            'id',
            [
                'plugin' => $plugin,
                'name' => $name,
            ]
        );

        return $this->item_id;
    }

    /**
     * Get the name.
     *
     * @return string
     */
    public function get_name(): string {
        return $this->get_component() . "/" . $this->get_area();
    }

    /**
     * Indicates whether a default file exist if a
     * custom one has not been provided.
     *
     * @return bool
     */
    public function has_default(): bool {
        return true;
    }

    /**
     * Get file currently overriding the default file.
     *
     * @param int|null $item_id
     * @param context|null $context
     *
     * @return stored_file|null
     */
    public function get_current_file(?int $item_id = null, ?context $context = null): ?stored_file {
        $item_id = $item_id ?? $this->get_item_id();

        // Get context.
        if (empty($context)) {
            $context = $this->get_context();
            if (empty($context)) {
                return null;
            }
        }

        // Get files for current component and context.
        $file_helper = new file_helper(
            $this->get_component(),
            $this->get_area(),
            $context
        );
        $file_helper->set_item_id($item_id);
        $files = $file_helper->get_stored_files();

        // No files found.
        if (empty($files)) {
            return null;
        }

        // Return first file found.
        return reset($files);
    }

    /**
     * Get the URL to the current file. We first check if there is a file set
     * for the tenant and if not then we fall back on the site file if any.
     *
     * @param string|null $theme
     *
     * @return moodle_url|null
     */
    public function get_current_url(?string $theme = null): ?moodle_url {
        // If site has not yet been installed we can return null here as no
        // file would have been overridden at this point.
        if (during_initial_install()) {
            return null;
        }

        $tenant_id = $this->get_tenant_id();
        $file = $this->get_current_file($this->get_item_id($tenant_id, $theme));

        // If file is empty and this if for a tenant we need to see if there was a reference copy saved
        // when we enabled tenant customisation.
        if (empty($file) && $tenant_id > 0) {
            $file = $this->get_current_file($this->get_item_id($tenant_id, $theme, true));
        }

        return !empty($file) ? $this->get_url($file) : null;
    }

    /**
     * Get the current URL or theme default as fallback.
     *
     * @return moodle_url
     */
    public function get_current_or_default_url(): moodle_url {
        $url = $this->get_current_url();
        if (empty($url)) {
            $url = $this->get_default_url();
        }
        return $url;
    }

    /**
     * @param stored_file $file
     *
     * @return moodle_url
     */
    protected function get_url(stored_file $file): moodle_url {
        return moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            '/',
            $file->get_filename()
        );
    }

    /**
     * Get the URL to the default file.
     *
     * @return moodle_url|null
     */
    public function get_default_url(): ?moodle_url {
        if ($this->has_default()) {
            $parts = explode('/', static::get_id());
            return $this->type->create_url(
                $this->get_theme_config()->name,
                $parts[0],
                $parts[1],
                theme_get_revision(),
                false
            );
        }
        return null;
    }

    /**
     * @return moodle_url|null
     */
    public function get_reference_url(): ?moodle_url {
        $file = $this->get_current_file($this->get_item_id($this->get_tenant_id(), null, true));
        return !empty($file) ? $this->get_url($file) : null;
    }

    /**
     * Get file area to which files need to be uploaded.
     *
     * @return file_area
     */
    public function get_file_area(): file_area {
        global $USER;

        $file_helper = new file_helper(
            $this->get_component(),
            $this->get_area(),
            $this->get_context(false)
        );

        return $file_helper->create_file_area($USER->id);
    }

    /**
     * Get the files in the draft area.
     *
     * @param int $draft_id
     *
     * @return stored_file[]
     */
    protected function get_draft_files(int $draft_id): array {
        global $USER;

        // Get files in user draft area.
        $file_helper = new file_helper(
            'user',
            'draft',
            \context_user::instance($USER->id)
        );
        $file_helper->set_item_id($draft_id);
        $file_helper->set_sort('timecreated desc');
        return $file_helper->get_stored_files();
    }

    /**
     * The file area might be polluted with multiple uploaded files or the user
     * might have uploaded a file with an incorrect extension. We need to clean
     * the draft area to get rid of such invalid files.
     *
     * @param stored_file[] $files
     *
     * @return stored_file[]
     */
    protected function clean_draft_files(array $files): array {
        // Get the list of valid file extensions for this theme file.
        $extensions = $this->get_type()->get_valid_extensions();
        $mimetypes = [];
        foreach ($extensions as $extension) {
            $mimetypes[] = mimeinfo('type', $extension);
        }

        // The draft area might be polluted with irrelevant files. We are only interested
        // in the most recent file uploaded with the right extension. The files should be
        // fetched according to time created so we basically can just use the first file
        // that is correct and remove the rest.
        $found = false;
        foreach ($files as $key => $file) {
            if (!$found && in_array($file->get_mimetype(), $mimetypes)) {
                // We found a file and it should stay in the files array.
                $found = true;
                continue;
            }
            // Delete the files that we don't want.
            $file->delete();
            unset($files[$key]);
        }

        return $files;
    }

    /**
     * Save files that are currently in draft area.
     *
     * @param int $draft_id
     * @param bool|null $save_as_reference Save the file as a reference copy.
     *
     * @return void
     */
    public function save_files(int $draft_id, ?bool $save_as_reference = false): void {
        // Get the settings currently used for this theme file.
        $setting = new \admin_setting_configstoredfile(
            $this->get_name(),
            '',
            '',
            $this->get_area(),
            $this->get_item_id($this->tenant_id, null, $save_as_reference),
            [
                'accepted_types' => $this->get_type()->get_group(),
                'context' => $this->get_context(false)
            ]
        );

        // Get current file name.
        $current = $setting->get_setting();

        // Get and clean files in draft area.
        $files = $this->get_draft_files($draft_id);
        $files = $this->clean_draft_files($files);

        // If we have any files left after cleaning then we need to save them.
        if (sizeof($files) > 0) {
            if ($setting->write_setting($draft_id) !== '') {
                throw new \moodle_exception('themesavefiles', 'error');
            }
            $setting->post_write_settings($current);
        }
    }

    /**
     * Copy site file to tenant
     */
    public function copy_site_file_to_tenant(): void {
        global $USER;

        if ($this->tenant_id === 0) {
            return;
        }

        $site_file = $this->get_current_file(
            $this->get_item_id(0),
            \context_system::instance()
        );
        if (!$site_file) {
            return;
        }

        $draft_id = $this->get_file_area()->get_draft_id();
        $fs = get_file_storage();
        $tenant_file = $fs->create_file_from_storedfile(
            [
                'contextid' => \context_user::instance($USER->id)->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draft_id
            ],
            $site_file
        );

        // In order to be able to reset a specific file back to the original we need to save a reference
        // copy for that file so that we don't reset a file to theme default if there was a site one
        // available at that time.
        $this->save_files($draft_id, true);
    }


    /**
     * Delete the current stored file associated with this theme file
     * and clean up configuration.
     */
    public function delete(): void {
        if ($current_file = $this->get_current_file()) {
            unset_config($this->get_area(), $this->get_component());
            $current_file->delete();
        }
    }

    /**
     * Is this feature enabled. This is different from is_available as this
     * function should be used to determine if the feature is enabled.
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return true;
    }

    /**
     * Is this file available. This is different from is_enabled as this
     * function should be used to determine if the file is available based
     * on settings or if the file exists.
     *
     * @return bool
     */
    public function is_available(): bool {
        return $this->is_enabled();
    }

    /**
     * Get default properties.
     *
     * @return array
     */
    public function get_default_categories(): array {
        return [];
    }

    /**
     * Get the default context for theme file.
     *  - Tenant context to fetch files limited to a specific tenant.
     *  - System context if we don't have tenants.
     *
     * @param int|null $unused Deprecated since Totara 13.1
     * @param bool|null $determine_tenant_branding
     *
     * @return context|null
     */
    protected function get_default_context(?int $unused = null, ?bool $determine_tenant_branding = true): ?context {
        global $USER;

        // Determine if we need to use tenant context.
        if (!$determine_tenant_branding || $this->is_tenant_branding_enabled()) {
            if (!empty($this->tenant_id)) {
                return \context_tenant::instance($this->tenant_id);
            } else if (!empty($USER->tenantid)) {
                return \context_tenant::instance($USER->tenantid);
            }
        }

        // Fall back on site branding when tenant branding is not enabled.
        return \context_system::instance();
    }

    /**
     * @return bool
     */
    protected function is_tenant_branding_enabled(): bool {
        $settings = $this->get_theme_settings_instance();
        return $settings->is_tenant_branding_enabled();
    }

    /**
     * @return int
     */
    protected function get_tenant_id(): int {
        if ($this->is_tenant_branding_enabled()) {
            return $this->tenant_id;
        }
        return 0;
    }

    /**
     * @return theme_settings
     */
    protected function get_theme_settings_instance(): theme_settings {
        if (is_null($this->theme_settings)) {
            // Load theme settings for current tenant.
            $this->theme_settings = new theme_settings($this->get_theme_config(), $this->tenant_id);
        }

        // Reset the tenant ID as it might have changed.
        $this->theme_settings->set_tenant_id($this->tenant_id);

        return $this->theme_settings;
    }

    /**
     * Get the ID of the file currently being used by the system
     * that is being overwritten.
     * For example:
     *    For $OUTPUT->image_url('filename', 'component') the ID
     *    would be 'component/filename'.
     *
     * @return string
     */
    abstract public static function get_id(): string;

    /**
     * @return string
     */
    abstract public function get_component(): string;

    /**
     * @return string
     */
    abstract public function get_area(): string;

    /**
     * Get a unique key to map to theme categories.
     *
     * @return string
     */
    abstract public function get_ui_key(): string;

    /**
     * Get the category to which this must be merged.
     *
     * @return string
     */
    abstract public function get_ui_category(): string;

    /**
     * Get the type of file.
     *
     * @return file_type
     */
    abstract public function get_type(): file_type;

}