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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package container_workspace
 */
defined('MOODLE_INTERNAL') || die();

use container_workspace\theme\file\workspace_image;
use core\theme\settings;
use totara_core\advanced_feature;

class container_workspace_workspace_image_testcase extends advanced_testcase {

    /**
     * @return void
     */
    public function test_image_enabled(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $theme_config = theme_config::load('ventura');

        // Disable advanced feature.
        advanced_feature::disable('container_workspace');

        // Image should be disabled and not found in files.
        $workspace_image = new workspace_image($theme_config);
        $this->assertEquals(false, $workspace_image->is_enabled());

        $theme_settings = new settings($theme_config, 0);
        $files = $theme_settings->get_files();
        foreach ($files as $file) {
            if ($file instanceof workspace_image) {
                $this->assertFalse($file->is_enabled());
            }
        }
    }

    /**
     * @return void
     */
    public function test_multitenant_images(): void {
        $generator = $this->getDataGenerator();

        // Enable tenants.
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Create tenants.
        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        // Create tenant user.
        $tenant_user1 = $generator->create_user(
            ['tenantid' => $tenant_one->id, 'tenantdomainmanager' => $tenant_one->idnumber]
        );
        $tenant_user2 = $generator->create_user(
            ['tenantid' => $tenant_two->id, 'tenantdomainmanager' => $tenant_two->idnumber]
        );
        $this->setUser($tenant_user1);

        $theme_config = theme_config::load('ventura');

        // Update article image for tenant two.
        $this->setUser($tenant_user2);
        $theme_settings = new settings($theme_config, $tenant_two->id);

        // Enable settings for tenant two.
        $categories = [
            [
                'name' => 'tenant',
                'properties' => [
                    [
                        'name' => 'formtenant_field_tenant',
                        'type' => 'boolean',
                        'value' => 'true',
                    ]
                ],
            ],
        ];
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        $user_context = context_user::instance($tenant_user2->id);
        $files = [
            [
                'ui_key' => 'engageworkspace',
                'draft_id' => $this->create_image('new_workspace_image', $user_context),
            ],
        ];
        $theme_settings->validate_files($files);
        $theme_settings->update_files($files);

        // Confirm that tenant two has new workspace image
        $workspace_image = new workspace_image($theme_config);
        $workspace_image->set_tenant_id($tenant_two->id);
        $url = $workspace_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$workspace_image->get_context()->id}/totara_core/defaultworkspaceimage/{$workspace_image->get_item_id()}/new_workspace_image.png",
            $url
        );

        // Confirm that tenant one still has default workspace image
        $workspace_image = new workspace_image($theme_config);
        $workspace_image->set_tenant_id($tenant_one->id);
        $url = $workspace_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/container_workspace/1/default_space",
            $url
        );
    }

    /**
     * @param string $name
     * @param context $context
     *
     * @return int
     */
    private function create_image(string $name, context $context): int {
        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $time = time();
        $file_record = new stdClass();
        $file_record->filename = "{$name}.png";
        $file_record->contextid = $context->id;
        $file_record->component = 'user';
        $file_record->filearea = 'draft';
        $file_record->filepath = '/';
        $file_record->itemid = $draft_id;
        $file_record->timecreated = $time;
        $file_record->timemodified = $time;
        $fs->create_file_from_string($file_record, $name);

        return $draft_id;
    }

}
