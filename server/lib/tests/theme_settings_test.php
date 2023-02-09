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

defined('MOODLE_INTERNAL') || die();

use core\hook\tenant_customizable_theme_settings;
use core\hook\theme_settings_css_categories;
use core\theme\file\favicon_image;
use core\theme\file\login_image;
use core\theme\file\logo_image;
use core\theme\helper;
use core\theme\settings;
use core_course\theme\file\course_image;
use core_phpunit\testcase;
use totara_certification\theme\file\certification_image;
use totara_program\theme\file\program_image;
use totara_tui\local\locator\bundle;
use totara_tui\local\mediation\resolver;
use totara_tui\local\mediation\styles\resolver as styles_resolver;
use totara_tui\local\mediation\styles\mediator;
use totara_webapi\phpunit\webapi_phpunit_helper;
use totara_core\hook\manager as hook_manager;

class core_theme_settings_testcase extends testcase {
    use webapi_phpunit_helper;

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();

        // Clear the adhoc categories after each test. The adhoc_categories field is static
        // and therefore persists across unit tests.
        settings::set_adhoc_categories([]);
    }

    /**
     * Confirm that categories are valid.
     *
     * @param $categories
     */
    protected function validate_default_categories($categories) {
        // Confirm brand.
        $brand = array_filter($categories, function (array $category) {
            $this->assertArrayHasKey('name', $category);
            return $category['name'] === 'brand';
        });
        $this->assertIsArray($brand);
        $brand = array_values($brand);
        $this->assertEquals(1, sizeof($brand));
        $this->assertArrayHasKey('properties', $brand[0]);
        $this->assertEquals(3, sizeof($brand[0]['properties']));

        foreach ($brand[0]['properties'] as $property) {
            $this->assertArrayHasKey('name', $property);
            $this->assertArrayHasKey('type', $property);
            $this->assertArrayHasKey('value', $property);
            switch ($property['name']) {
                case 'formbrand_field_logoalttext':
                    $this->assertEquals('text', $property['type']);
                    $this->assertEquals('Totara Logo', $property['value']);
                    break;
                case 'sitelogo':
                case 'sitefavicon':
                    $this->assertEquals('file', $property['type']);
                    $this->assertEquals('', $property['value']);
                    break;
                default:
                    $this->fail('Unexpected default property');
            }
        }

        // Confirm images.
        $images = array_filter($categories, function (array $category) {
            $this->assertArrayHasKey('name', $category);
            return $category['name'] === 'images';
        });
        $this->assertIsArray($images);
        $images = array_values($images);
        $this->assertEquals(1, sizeof($images));
        $this->assertArrayHasKey('properties', $images[0]);
        $this->assertEquals(9, sizeof($images[0]['properties']));

        foreach ($images[0]['properties'] as $property) {
            $this->assertArrayHasKey('name', $property);
            $this->assertArrayHasKey('type', $property);
            $this->assertArrayHasKey('value', $property);

            switch ($property['name']) {
                case 'formimages_field_displaylogin':
                    $this->assertEquals('boolean', $property['type']);
                    $this->assertEquals('true', $property['value']);
                    break;
                case 'formimages_field_loginalttext':
                    $this->assertEquals('text', $property['type']);
                    $this->assertEquals('Totara Login', $property['value']);
                    break;
                case 'engageworkspace':
                case 'sitelogin':
                case 'learncourse':
                case 'engageresource':
                case 'engagesurvey':
                case 'learncert':
                case 'learnprogram':
                    $this->assertEquals('file', $property['type']);
                    $this->assertEquals('', $property['value']);
                    break;
                default:
                    $this->fail('Unexpected default property');
            }
        }
    }

    /**
     * Test default properties via the web api.
     */
    public function test_webapi_get_theme_settings() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        // Test user that does not have permission.
        $result = $this->execute_graphql_operation(
            'core_get_theme_settings', [
                'theme' => 'ventura'
            ]
        );
        $this->assertNotEmpty($result->errors);
        $this->assertEquals(
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)',
            $result->errors[0]->getMessage()
        );

        // Assign capability to manage theme settings.
        $this->assign_themesettings_capability();

        // Test with capability.
        $result = $this->execute_graphql_operation(
            'core_get_theme_settings', [
                'theme' => 'ventura'
            ]
        );
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        // Confirm that the default categories are correct.
        $this->assertArrayHasKey('core_get_theme_settings', $result->data);
        $this->assertArrayHasKey('categories', $result->data['core_get_theme_settings']);
        $categories = $result->data['core_get_theme_settings']['categories'];
        $this->assertIsArray($categories);
        $this->assertEquals(2, sizeof($categories));
        $this->validate_default_categories($categories);
    }

    public function test_webapi_get_theme_settings_tenant() {
        $generator = self::getDataGenerator();

        // Create tenants.
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        // Create users.
        $tenant_user1 = $generator->create_user(
            ['tenantid' => $tenant1->id, 'tenantdomainmanager' => $tenant1->idnumber]
        );
        $tenant_user2 = $generator->create_user(
            ['tenantid' => $tenant2->id, 'tenantdomainmanager' => $tenant2->idnumber]
        );

        $this->setUser($tenant_user1);

        // User1 should be able to get tenant one theme settings.
        $result = $this->execute_graphql_operation(
            'core_get_theme_settings', [
                'theme' => 'ventura',
                'tenant_id' => $tenant1->id
            ]
        );
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        // Confirm that domain manager of tenant one can not access settings of tenant two.
        $result = $this->execute_graphql_operation(
            'core_get_theme_settings', [
                'theme' => 'ventura',
                'tenant_id' => $tenant2->id
            ]
        );
        $this->assertNotEmpty($result->errors);
        $this->assertEquals(
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)',
            $result->errors[0]->getMessage()
        );

        $this->setUser($tenant_user2);

        // User2 should be able to get tenant two theme settings.
        $result = $this->execute_graphql_operation(
            'core_get_theme_settings', [
                'theme' => 'ventura',
                'tenant_id' => $tenant2->id
            ]
        );
        $this->assertEmpty($result->errors);

        // Confirm that domain manager of tenant two can not access settings of tenant one.
        $result = $this->execute_graphql_operation(
            'core_get_theme_settings', [
                'theme' => 'ventura',
                'tenant_id' => $tenant1->id
            ]
        );
        $this->assertNotEmpty($result->errors);
        $this->assertEquals(
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)',
            $result->errors[0]->getMessage()
        );

        // Switch on tenant isolation and confirm that user one still has access
        $this->setUser($tenant_user1);
        set_config('tenantsisolated', 1);
        purge_all_caches();
        $result = $this->execute_graphql_operation(
            'core_get_theme_settings', [
                'theme' => 'ventura',
                'tenant_id' => $tenant1->id
            ]
        );
        $this->assertEmpty($result->errors, !empty($result->errors) ? $result->errors[0]->getMessage() : '');
        $this->assertNotEmpty($result->data);
    }

    public function test_webapi_update_theme_settings() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $params = [
            'theme' => 'ventura',
            'categories' => [
                [
                    'name' => 'colours',
                    'properties' => [
                        [
                            'name' => 'test_name',
                            'type' => 'value',
                            'value' => 'yellow_test'
                        ]
                    ]
                ]
            ],
            'files' => []
        ];

        // Test user that does not have permission.
        $result = $this->execute_graphql_operation('core_update_theme_settings', $params);
        $this->assertNotEmpty($result->errors);
        $this->assertEquals(
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)',
            $result->errors[0]->getMessage()
        );

        // Assign capability to manage theme settings.
        $this->assign_themesettings_capability();

        // Test with capability.
        $result = $this->execute_graphql_operation('core_update_theme_settings', $params);
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        // Confirm that the default categories are still correct.
        $this->assertArrayHasKey('core_update_theme_settings', $result->data);
        $this->assertArrayHasKey('categories', $result->data['core_update_theme_settings']);
        $categories = $result->data['core_update_theme_settings']['categories'];
        $this->assertIsArray($categories);
        $this->assertCount(3, $categories);
        $this->validate_default_categories($categories);

        // Check colours.
        $colours = array_filter($categories, function (array $category) {
            $this->assertArrayHasKey('name', $category);
            return $category['name'] === 'colours';
        });
        $this->assertIsArray($colours);
        $colours = array_values($colours);
        $this->assertCount(1, $colours);
        $this->assertArrayHasKey('properties', $colours[0]);
        $this->assertCount(1, $colours[0]['properties']);
        $property = reset($colours[0]['properties']);
        $this->assertEquals('test_name', $property['name']);
        $this->assertEquals('value', $property['type']);
        $this->assertEquals('yellow_test', $property['value']);
    }

    /**
     * Test default properties.
     */
    public function test_default_categories() {
        $this->setAdminUser();
        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);
        $output = helper::output_theme_settings($theme_settings);
        $this->assertIsArray($output);
        $this->assertArrayHasKey('categories', $output);
        $this->assertIsArray($output['categories']);
        $this->assertEquals(2, sizeof($output['categories']));
        $this->validate_default_categories($output['categories']);
    }

    /**
     * Test that site logo behaves as it should.
     */
    public function test_logo() {
        global $OUTPUT, $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $user_context = context_user::instance($user_one->id);

        // Assign capability to manage theme settings.
        $this->assign_themesettings_capability();

        $categories = [
            [
                'name' => 'brand',
                'properties' => [
                    [
                        'name' => 'formbrand_field_logoalttext',
                        'type' => 'text',
                        'value' => 'Totara Logo Updated',
                    ],
                ],
            ]
        ];
        $files = [
            [
                'ui_key' => 'sitelogo',
                'draft_id' => $this->create_image('new_site_logo', $user_context),
            ]
        ];

        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);
        $theme_settings->update_files($files);

        $output = helper::output_theme_settings($theme_settings);
        $this->assertIsArray($output);

        // Confirm brand is present and has properties.
        $this->assertArrayHasKey('categories', $output);
        $categories = $output['categories'];
        $brand = array_filter($categories, function (array $category) {
            $this->assertArrayHasKey('name', $category);
            return $category['name'] === 'brand';
        });
        $this->assertIsArray($brand);
        $brand = array_values($brand);
        $this->assertEquals(1, sizeof($brand));
        $this->assertArrayHasKey('properties', $brand[0]);
        $this->assertEquals(3, sizeof($brand[0]['properties']));

        // Confirm alternative text for logo is correct.
        $logo_alt_text = array_filter($brand[0]['properties'], function (array $property) {
            return $property['name'] === 'formbrand_field_logoalttext';
        });
        $this->assertIsArray($logo_alt_text);
        $logo_alt_text = array_values($logo_alt_text);
        $this->assertEquals(1, sizeof($logo_alt_text));
        $this->assertEquals('Totara Logo Updated', $logo_alt_text[0]['value']);

        // Confirm site logo is correct.
        $site_logo = array_filter($brand[0]['properties'], function (array $property) {
            return $property['name'] === 'sitelogo';
        });
        $this->assertIsArray($site_logo);
        $site_logo = array_values($site_logo);
        $this->assertEquals(1, sizeof($site_logo));
        $this->assertEquals('file', $site_logo[0]['type']);

        $logo_image = new logo_image($theme_config);
        $url = $logo_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/logo/{$logo_image->get_item_id()}/new_site_logo.png",
            $url
        );
        $alt_text = $logo_image->get_alt_text();
        $this->assertEquals('Totara Logo Updated', $alt_text);
        $this->assertEquals(true, $logo_image->is_available());

        // Confirm that the default URL is still pointing to the correct default image.
        $url = $logo_image->get_default_url();
        $rev = theme_get_revision();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/totara_core/{$rev}/logo",
            $url->out()
        );

        // Confirm that new logo and alternative text load through master header.
        $mastheadlogo = new totara_core\output\masthead_logo();
        $mastheaddata = $mastheadlogo->export_for_template($OUTPUT);
        $this->assertEquals('Totara Logo Updated', $mastheaddata['logoalt']);
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/logo/{$logo_image->get_item_id()}/new_site_logo.png",
            $mastheaddata['logourl']
        );

        // Test without theme parameter
        $logo_image = new logo_image();
        $logo_image->get_current_or_default_url();
        $this->assertDebuggingCalled(
            "'theme' parameter not set. Falling back on {$CFG->theme}. The resolved assets "
            . "will be associated with {$CFG->theme}, which might not be the expected result."
        );
    }

    /**
     * Test that favicon behaves as expected.
     */
    public function test_favicon() {
        global $OUTPUT, $CFG;

        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $user_context = context_user::instance($user_one->id);

        // Assign capability to manage theme settings.
        $this->assign_themesettings_capability();

        $files = [
            [
                'ui_key' => 'sitefavicon',
                'draft_id' => $this->create_image('new_favicon', $user_context),
            ]
        ];

        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);
        $theme_settings->update_files($files);

        $output = helper::output_theme_settings($theme_settings);
        $this->assertIsArray($output);

        // Confirm brand is present and has properties.
        $this->assertArrayHasKey('categories', $output);
        $categories = $output['categories'];
        $brand = array_filter($categories, function (array $category) {
            $this->assertArrayHasKey('name', $category);
            return $category['name'] === 'brand';
        });
        $this->assertIsArray($brand);
        $brand = array_values($brand);
        $this->assertEquals(1, sizeof($brand));
        $this->assertArrayHasKey('properties', $brand[0]);
        $this->assertEquals(3, sizeof($brand[0]['properties']));

        // Confirm favicon is correct.
        $site_favicon = array_filter($brand[0]['properties'], function (array $property) {
            return $property['name'] === 'sitefavicon';
        });
        $this->assertIsArray($site_favicon);
        $site_favicon = array_values($site_favicon);
        $this->assertEquals(1, sizeof($site_favicon));
        $this->assertEquals('file', $site_favicon[0]['type']);

        $favicon_image = new favicon_image($theme_config);
        $url = $favicon_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/favicon/{$favicon_image->get_item_id()}/new_favicon.png",
            $url
        );

        // Confirm that the default URL is still pointing to the correct default image.
        $url = $favicon_image->get_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/theme/1/favicon",
            $url->out()
        );

        // Confirm that new favicon loads through master header.
        $mastheadlogo = new totara_core\output\masthead_logo();
        $mastheaddata = $mastheadlogo->export_for_template($OUTPUT);
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/favicon/{$favicon_image->get_item_id()}/new_favicon.png",
            $mastheaddata['faviconurl']
        );

        // Test without theme parameter
        $favicon_image = new favicon_image();
        $favicon_image->get_current_or_default_url();
        $this->assertDebuggingCalled(
            "'theme' parameter not set. Falling back on {$CFG->theme}. The resolved assets "
            . "will be associated with {$CFG->theme}, which might not be the expected result."
        );
    }

    /**
     * Test that:
     *  -> colours can be updated and fetched.
     *  -> override switch determines what colours are active.
     */
    public function test_colours() {
        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        $categories = $this->get_colours(true);
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        $output = helper::output_theme_settings($theme_settings);
        $this->assertIsArray($output);
        $this->assertArrayHasKey('categories', $output);

        // Confirm that colours is present in the categories.
        $colours = array_filter($output['categories'], function (array $category) {
            $this->assertArrayHasKey('name', $category);
            return $category['name'] === 'colours';
        });
        $this->assertIsArray($colours);
        $colours = array_values($colours);
        $this->assertArrayHasKey('properties', $colours[0]);
        $this->assertEquals(4, sizeof($colours[0]['properties']));
        foreach ($colours[0]['properties'] as $property) {
            $this->assertArrayHasKey('name', $property);
            $this->assertArrayHasKey('type', $property);
            $this->assertArrayHasKey('value', $property);
            switch ($property['name']) {
                case 'formcolours_field_useoverrides':
                    $this->assertEquals('boolean', $property['type']);
                    $this->assertEquals(true, $property['value']);
                    break;
                case 'btn-prim-accent-color':
                    $this->assertEquals('value', $property['type']);
                    $this->assertEquals('#ff0013', $property['value']);
                    break;
                case 'link-color':
                    $this->assertEquals('value', $property['type']);
                    $this->assertEquals('#f50009', $property['value']);
                    break;
                case 'nav-bg-color':
                    $this->assertEquals('value', $property['type']);
                    $this->assertEquals('#ff0000', $property['value']);
                    break;
                default:
                    $this->fail('Invalid colour property present in colour category');
            }
        }

        // Confirm that overridden colours are present.
        $css = $theme_settings->get_css_variables();
        $this->assertEquals(
            ':root{--btn-prim-accent-color: #ff0013;--link-color: #f50009;--nav-bg-color: #ff0000;}',
            $css
        );

        $categories = $this->get_colours(false);
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        // Confirm that overridden colours are not present.
        $css = $theme_settings->get_css_variables();
        $this->assertEquals(
            ':root{--nav-bg-color: #ff0000;}',
            $css
        );
    }

    /**
     * @param bool $override
     * @return array[]
     */
    private function get_colours(bool $override): array {
        return [
            [
                'name' => 'colours',
                'properties' => [
                    [
                        'name' => 'formcolours_field_useoverrides',
                        'type' => 'boolean',
                        'value' => $override,
                        'selectors' => [
                            'btn-prim-accent-color',
                            'btn-accent-color',
                            'link-color',
                        ]
                    ],
                    [
                        'name' => 'btn-prim-accent-color',
                        'type' => 'value',
                        'value' => '#ff0013',
                    ],
                    [
                        'name' => 'link-color',
                        'type' => 'value',
                        'value' => '#f50009',
                    ],
                    [
                        'name' => 'nav-bg-color',
                        'type' => 'value',
                        'value' => '#ff0000',
                    ],
                ]
            ]
        ];
    }

    /**
     * Test that login image behaves as it should.
     */
    public function test_login_image() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $user_context = context_user::instance($user_one->id);

        $files = [
            [
                'ui_key' => 'sitelogin',
                'draft_id' => $this->create_image('new_site_login_image', $user_context),
            ]
        ];

        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);
        $theme_settings->update_files($files);

        $output = helper::output_theme_settings($theme_settings);
        $this->assertIsArray($output);

        $login_image = new login_image($theme_config);
        $url = $login_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/loginimage/{$login_image->get_item_id()}/new_site_login_image.png",
            $url
        );
        $this->assertEquals(true, $login_image->is_available());
        $this->assertEquals('Totara Login', $login_image->get_alt_text());

        // Confirm that the default URL is still pointing to the correct default image.
        $url = $login_image->get_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/totara_core/1/default_login",
            $url->out()
        );

        // Disable site login image and update alternative text.
        $categories = [
            [
                'name' => 'images',
                'properties' => [
                    [
                        'name' => 'formimages_field_displaylogin',
                        'type' => 'boolean',
                        'value' => 'false',
                    ],
                    [
                        'name' => 'formimages_field_loginalttext',
                        'type' => 'text',
                        'value' => 'New alternative text',
                    ]
                ],
            ]
        ];
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);
        $this->assertEquals(false, $login_image->is_available());
        $this->assertEquals('New alternative text', $login_image->get_alt_text());
    }

    /**
     * Testing the following scenarios:
     *   1. Tenants default images
     *   2. Update images for a tenant and confirm other tenants are still on default
     */
    public function test_multitenant_images() {
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

        // Go through all theme files and confirm that they have not changed for tenant.
        $theme_settings = new settings($theme_config, $tenant_one->id);
        $files = $theme_settings->get_files();
        foreach ($files as $file) {
            $actual_url = $file->get_current_or_default_url();
            $expected_url = $file->get_default_url();

            // Just confirm that the default URL points to the image resolver, as it always should, and not to a pluginfile.
            $this->assertStringContainsString('/moodle/theme/image.php', $expected_url->get_path());

            $this->assertEquals($expected_url, $actual_url);
        }

        // Enable settings for tenant one.
        $categories = [
            [
                'name' => 'brand',
                'properties' => [
                    [
                        'name' => 'formbrand_field_logoalttext',
                        'type' => 'text',
                        'value' => 'Totara Logo Updated',
                    ],
                ],
            ],
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
        $theme_settings = new settings($theme_config, $tenant_one->id);
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        $user_context = context_user::instance($tenant_user1->id);

        // Update images for tenant one.
        $files = [
            [
                'ui_key' => 'sitelogo',
                'draft_id' => $this->create_image('new_site_logo', $user_context),
            ],
            [
                'ui_key' => 'sitefavicon',
                'draft_id' => $this->create_image('new_favicon', $user_context),
            ]
        ];
        $theme_settings->validate_files($files);
        $theme_settings->update_files($files);

        // Confirm that tenant one has new logo.
        $logo_image = new logo_image($theme_config);
        $logo_image->set_tenant_id($tenant_one->id);
        $url = $logo_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$logo_image->get_context()->id}/totara_core/logo/{$logo_image->get_item_id()}/new_site_logo.png",
            $url
        );
        $this->assertEquals('Totara Logo Updated', $logo_image->get_alt_text());
        $this->assertEquals(true, $logo_image->is_available());

        // Confirm that tenant one has new favicon.
        $favicon_image = new favicon_image($theme_config);
        $favicon_image->set_tenant_id($tenant_one->id);
        $url = $favicon_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$favicon_image->get_context()->id}/totara_core/favicon/{$favicon_image->get_item_id()}/new_favicon.png",
            $url
        );

        // Confirm that tenant two does not have new logo.
        $this->setUser($tenant_user2);
        $logo_image->set_tenant_id($tenant_two->id);
        $url = $logo_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/totara_core/1/logo",
            $url
        );
        $alt_text = $logo_image->get_alt_text();
        $this->assertEquals('Totara Logo', $alt_text);
        $this->assertEquals(true, $logo_image->is_available());

        // Confirm that tenant two does not have new favicon.
        $favicon_image->set_tenant_id($tenant_two->id);
        $url = $favicon_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/theme/1/favicon",
            $url
        );

        // Update images for tenant two.
        $this->setUser($tenant_user2);
        $theme_settings->set_tenant_id($tenant_two->id);

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
                'ui_key' => 'learncourse',
                'draft_id' => $this->create_image('new_course_image', $user_context),
            ],
            [
                'ui_key' => 'learnprogram',
                'draft_id' => $this->create_image('new_program_image', $user_context),
            ],
            [
                'ui_key' => 'learncert',
                'draft_id' => $this->create_image('new_certification_image', $user_context),
            ],
        ];
        $theme_settings->validate_files($files);
        $theme_settings->update_files($files);

        // Confirm that tenant two has new course image
        $course_image = new course_image($theme_config);
        $course_image->set_tenant_id($tenant_two->id);
        $url = $course_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$course_image->get_context()->id}/course/defaultcourseimage/{$course_image->get_item_id()}/new_course_image.png",
            $url
        );

        // Confirm that tenant two has new program image
        $program_image = new program_image($theme_config);
        $program_image->set_tenant_id($tenant_two->id);
        $url = $program_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$program_image->get_context()->id}/totara_core/defaultprogramimage/{$program_image->get_item_id()}/new_program_image.png",
            $url
        );

        // Confirm that tenant two has new certification image
        $certification_image = new certification_image($theme_config);
        $certification_image->set_tenant_id($tenant_two->id);
        $url = $certification_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$certification_image->get_context()->id}/totara_core/defaultcertificationimage/{$certification_image->get_item_id()}/new_certification_image.png",
            $url
        );

        // Confirm that tenant one still has default course image
        $course_image = new course_image($theme_config);
        $course_image->set_tenant_id($tenant_one->id);
        $url = $course_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/core/1/course_defaultimage",
            $url
        );

        // Confirm that tenant one still has default program image
        $program_image = new program_image($theme_config);
        $program_image->set_tenant_id($tenant_one->id);
        $url = $program_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/totara_program/1/defaultimage",
            $url
        );

        // Confirm that tenant one still has default certification image
        $certification_image = new certification_image($theme_config);
        $certification_image->set_tenant_id($tenant_one->id);
        $url = $certification_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/totara_certification/1/defaultimage",
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

    public function test_custom_css() {
        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        $categories = [
            [
                'name' => 'custom',
                'properties' => [
                    [
                        'name' => 'formcustom_field_customcss',
                        'type' => 'text',
                        'value' => 'body > * . * {background-color: pink;}',
                    ]
                ]
            ]
        ];

        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        // Confirm that the custom css applied.
        $rev = time();
        [$css, $messages, $file] = $this->get_resolver($rev, 'p', 0);
        $this->assertStringContainsString(
            'body > * . * {background-color: pink;}',
            $css
        );
    }

    public function test_multitenant_colours() {
        $this->skip_if_build_not_present();

        $generator = $this->getDataGenerator();
        $this->setAdminUser();
        $theme_config = theme_config::load('ventura');

        // Enable tenants.
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Create tenants.
        $tenant_one = $tenant_generator->create_tenant();
        $tenant_two = $tenant_generator->create_tenant();

        // Update colours for tenant one.
        $theme_settings = new settings($theme_config, $tenant_one->id);
        $categories = $this->get_colours(true);
        $categories[] = [
            'name' => 'tenant',
            'properties' => [
                [
                    'name' => 'formtenant_field_tenant',
                    'type' => 'boolean',
                    'value' => 'true',
                ]
            ]
        ];
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        $rev = time();

        // Confirm that the settings applied for tenant one.
        [$css, $messages, $file] = $this->get_resolver($rev, 'p', $tenant_one->id);
        $this->assertStringContainsString(
            ':root{--btn-prim-accent-color: #ff0013;--link-color: #f50009;--nav-bg-color: #ff0000;}',
            $css
        );

        // Confirm that the settings DID NOT apply for tenant two.
        [$css, $messages, $file] = $this->get_resolver($rev, 'p', $tenant_two->id);
        $this->assertStringNotContainsString(
            ':root{--btn-prim-accent-color: #ff0013;--link-color: #f50009;--nav-bg-color: #ff0000;}',
            $css
        );
    }

    public function test_is_tenant_branding_enabled() {
        $theme_config = theme_config::load('ventura');
        $settings = new settings($theme_config, 0);
        self::assertFalse($settings->is_tenant_branding_enabled());

        $generator = self::getDataGenerator();
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        $settings = new settings($theme_config, $tenant1->id);
        self::assertFalse($settings->is_tenant_branding_enabled());
        $settings = new settings($theme_config, $tenant2->id);
        self::assertFalse($settings->is_tenant_branding_enabled());

        self::enable_tenant_branding($tenant1->id);
        $settings = new settings($theme_config, $tenant1->id);
        self::assertTrue($settings->is_tenant_branding_enabled());
        $settings = new settings($theme_config, $tenant2->id);
        self::assertFalse($settings->is_tenant_branding_enabled());

        self::disable_tenant_branding($tenant1->id);
        self::enable_tenant_branding($tenant2->id);
        $settings = new settings($theme_config, $tenant1->id);
        self::assertFalse($settings->is_tenant_branding_enabled());
        $settings = new settings($theme_config, $tenant2->id);
        self::assertTrue($settings->is_tenant_branding_enabled());
    }

    public function test_is_initial_tenant_branding() {
        $theme_config = theme_config::load('ventura');
        $site_settings = new settings($theme_config, 0);
        self::assertFalse($site_settings->is_initial_tenant_branding());

        $generator = self::getDataGenerator();
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();

        $tenant_settings = new settings($theme_config, $tenant->id);
        self::assertTrue($tenant_settings->is_initial_tenant_branding());

        // Now enable custom tenant branding
        $categories = [
            [
                'name' => 'tenant',
                'properties' => [
                    [
                        'name' => 'formtenant_field_tenant',
                        'type' => 'boolean',
                        'value' => 'true',
                    ]
                ]
            ],
        ];
        $tenant_settings->validate_categories($categories);
        $tenant_settings->update_categories($categories);
        self::assertFalse($tenant_settings->is_initial_tenant_branding());
    }

    public function test_is_re_enabling_tenant_branding() {
        $categories = [
            [
                'name' => 'tenant',
                'properties' => [
                    [
                        'name' => 'formtenant_field_tenant',
                        'type' => 'boolean',
                        'value' => 'true',
                    ]
                ]
            ],
        ];

        $theme_config = theme_config::load('ventura');
        $site_settings = new settings($theme_config, 0);
        self::assertFalse($site_settings->is_re_enabling_tenant_branding($categories));

        $generator = self::getDataGenerator();
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant = $tenant_generator->create_tenant();

        // Enable twice
        $tenant_settings = new settings($theme_config, $tenant->id);
        self::assertFalse($tenant_settings->is_re_enabling_tenant_branding($categories));

        $tenant_settings->validate_categories($categories);
        $tenant_settings->update_categories($categories);
        self::assertFalse($tenant_settings->is_re_enabling_tenant_branding($categories));

        // Disable first and then re-able
        $categories[0]['properties'][0]['value'] = 'false';
        self::assertFalse($tenant_settings->is_re_enabling_tenant_branding($categories));

        $tenant_settings->validate_categories($categories);
        $tenant_settings->update_categories($categories);

        $categories[0]['properties'][0]['value'] = 'true';
        self::assertTrue($tenant_settings->is_re_enabling_tenant_branding($categories));
    }

    public function test_enabling_tenant_also_copies_site_files() {
        global $USER;

        $generator = $this->getDataGenerator();

        // Create tenants.
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();

        $this->setAdminUser();
        $admin_context = context_user::instance($USER->id);

        // Add a logo for the site
        $files = [
            [
                'ui_key' => 'sitelogo',
                'draft_id' => $this->create_image('new_site_logo', $admin_context),
            ]
        ];

        $theme_config = theme_config::load('ventura');
        $site_settings = new settings($theme_config, 0);
        $site_settings->update_files($files);

        $site_files = $site_settings->get_files();
        $site_files = array_filter($site_files, function ($file) {
            return $file instanceof logo_image;
        });
        $this->assertCount(1, $site_files);
        /** @var logo_image $site_logo */
        $site_logo = reset($site_files);

        $tenant_settings = new settings($theme_config, $tenant1->id);
        $site_files = $tenant_settings->get_files();
        $site_files = array_filter($site_files, function ($file) {
            return $file instanceof logo_image;
        });
        $this->assertCount(1, $site_files);
        /** @var logo_image $tenant_logo */
        $tenant_logo = reset($site_files);

        $site_item_id = $site_logo->get_item_id();
        $tenant_item_id = $tenant_logo->get_item_id($tenant1->id);

        // tenant should not have a file
        $site_file_record = $this->get_logo_file_record($site_item_id);
        $tenant_file_record = $this->get_logo_file_record($tenant_item_id);
        $this->assertNotNull($site_file_record);
        $this->assertNull($tenant_file_record);

        // Update the tenant files without copying site files should have no effect
        $tenant_settings->update_files([], false);
        $site_file_record = $this->get_logo_file_record($site_item_id);
        $tenant_file_record = $this->get_logo_file_record($tenant_item_id);
        $this->assertNotNull($site_file_record);
        $this->assertNull($tenant_file_record);

        // Now update the tenant files without passing a file
        $tenant_settings->update_files([], true);

        // The tenant should now have a reference copy of the site file
        $tenant_reference_item_id = $tenant_logo->get_item_id($tenant1->id, null, true);
        $site_file_record = $this->get_logo_file_record($site_item_id);
        $tenant_file_record = $this->get_logo_file_record($tenant_item_id);
        $tenant_file_reference_record = $this->get_logo_file_record($tenant_reference_item_id);
        $this->assertNotNull($site_file_record);
        $this->assertNull($tenant_file_record);
        $this->assertNotNull($tenant_file_reference_record);
        $this->assertNotEqualsCanonicalizing($site_file_record, $tenant_file_record);
    }

    /**
     * Test that file validation validates incorrect ui keys.
     */
    public function test_theme_file_with_invalid_ui_key(): void {
        $this->setAdminUser();
        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);
        $files = [
            [
                'ui_key' => 'sitelogo',
                'draft_id' => 0,
            ],
            [
                'ui_key' => 'invalid_ui_key',
                'draft_id' => 0,
            ]
        ];
        try {
            $theme_settings->validate_files($files);
            $this->fail('Expected a coding_exception for invalid UI key');
        } catch (Exception $e) {
            $this->assertInstanceOf('moodle_exception', $e);
            $this->assertEquals("Invalid theme file UI key: 'invalid_ui_key'", $e->getMessage());
        }
    }

    /**
     * Test the following scenarios:
     *   1. Tenant images default to site.
     *   2. When setting tenant images only that tenant is affected.
     *   3. When disabling tenant theme settings the fallback is site images.
     */
    public function test_fallback_on_site_when_tenant_branding_disabled(): void {
        global $USER;

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

        // Set user to tenant one's domain manager.
        $this->setAdminUser();

        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        // Add a logo and favicon for the site
        $admin_context = context_user::instance($USER->id);
        $files = [
            [
                'ui_key' => 'sitelogo',
                'draft_id' => $this->create_image('new_site_logo', $admin_context),
            ],
            [
                'ui_key' => 'sitefavicon',
                'draft_id' => $this->create_image('new_site_favicon', $admin_context),
            ]
        ];
        $theme_settings->validate_files($files);
        $theme_settings->update_files($files);

        // Set user to tenant one's domain manager.
        $this->setUser($tenant_user1);

        // Enable settings for tenant one.
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
        $theme_settings->set_tenant_id($tenant_one->id);
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        // Add a logo and favicon for the tenant one.
        $user_context = context_user::instance($tenant_user1->id);
        $files = [
            [
                'ui_key' => 'sitelogo',
                'draft_id' => $this->create_image('new_tenant_one_logo', $user_context),
            ],
            [
                'ui_key' => 'sitefavicon',
                'draft_id' => $this->create_image('new_tenant_one_favicon', $user_context),
            ]
        ];
        $theme_settings->validate_files($files);
        $theme_settings->update_files($files);

        // User one should see new site logo.
        $logo_image = new logo_image($theme_config);
        $logo_image->set_tenant_id($tenant_one->id);
        $url = $logo_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$logo_image->get_context()->id}/totara_core/logo/{$logo_image->get_item_id()}/new_tenant_one_logo.png",
            $url
        );

        // User one should see new favicon.
        $favicon_image = new favicon_image($theme_config);
        $favicon_image->set_tenant_id($tenant_one->id);
        $url = $favicon_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$favicon_image->get_context()->id}/totara_core/favicon/{$favicon_image->get_item_id()}/new_tenant_one_favicon.png",
            $url
        );

        // User two should see site logo.
        $this->setUser($tenant_user2);
        $logo_image->set_tenant_id($tenant_two->id);
        $url = $logo_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$logo_image->get_context()->id}/totara_core/logo/{$logo_image->get_item_id()}/new_site_logo.png",
            $url
        );

        // User two should see site favicon.
        $favicon_image->set_tenant_id($tenant_two->id);
        $url = $favicon_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$favicon_image->get_context()->id}/totara_core/favicon/{$favicon_image->get_item_id()}/new_site_favicon.png",
            $url
        );

        // Disable tenant branding for tenant one.
        $theme_settings = new settings($theme_config, $tenant_one->id);
        $categories = [
            [
                'name' => 'tenant',
                'properties' => [
                    [
                        'name' => 'formtenant_field_tenant',
                        'type' => 'boolean',
                        'value' => 'false',
                    ]
                ],
            ],
        ];
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        // User ons should now see site logo.
        $this->setUser($tenant_user2);
        $logo_image->set_tenant_id($tenant_one->id);
        $url = $logo_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$logo_image->get_context()->id}/totara_core/logo/{$logo_image->get_item_id()}/new_site_logo.png",
            $url
        );

        // User one should now see site favicon.
        $favicon_image->set_tenant_id($tenant_one->id);
        $url = $favicon_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$favicon_image->get_context()->id}/totara_core/favicon/{$favicon_image->get_item_id()}/new_site_favicon.png",
            $url
        );
    }

    public function test_get_categories() {
        $this->setAdminUser();
        $ventura_config = theme_config::load('ventura');
        $base_config = theme_config::load('base');
        $ventura_settings = new settings($ventura_config, 0);
        $base_settings = new settings($base_config, 0);

        $test_base_categories = [
            [
                'name' => 'test_base_category',
                'properties' => [
                    [
                        'name' => 'test_category',
                        'type' => 'text',
                        'value' => '123',
                    ],
                ],
            ]
        ];
        $base_settings->update_categories($test_base_categories);
        $categories = $ventura_settings->get_categories(false);

        $category_names = array_map(function ($category) {
            return $category['name'];
        }, $categories);

        $this->assertFalse(in_array('test_base_category', $category_names));
        $this->assertTrue(in_array('images', $category_names));
        $this->assertTrue(in_array('brand', $category_names));

        // Exclude default file categories.
        $categories = $ventura_settings->get_categories(false, false);
        $this->assertCount(0, $categories);
    }

    public function test_tenant_theme_hooks_default() {
        $this->setAdminUser();
        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        $expected = [
            'brand' => '*',
            'colours' => '*',
            'images' => '*',
            'custom' => ['formcustom_field_customfooter'],
            'tenant' => '*',
        ];
        $this->assertEqualsCanonicalizing($expected, $theme_settings->get_customizable_tenant_theme_settings());
    }

    public function test_tenant_theme_hooks_custom() {
        // Replace the customizable categories and image keys
        hook_manager::phpunit_replace_watchers([
            [
                'hookname' => tenant_customizable_theme_settings::class,
                'callback' => [__CLASS__, 'custom_theme_settings_watcher']
            ],
        ]);

        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        $expected = [
            'A' => '*',
            'B' => ['a', 'b', 'c'],
            'C' => ['whatever'],
        ];
        $this->assertEqualsCanonicalizing($expected, $theme_settings->get_customizable_tenant_theme_settings());
    }

    public static function custom_theme_settings_watcher(tenant_customizable_theme_settings $hook) {
        $new = [
            'A' => '*',
            'B' => ['a', 'b', 'c'],
            'C' => ['whatever'],
        ];
        $hook->set_customizable_settings($new);
    }

    public function test_css_categories() {
        // Replace the customizable categories and image keys
        hook_manager::phpunit_replace_watchers([
            [
                'hookname' => theme_settings_css_categories::class,
                'callback' => [__CLASS__, 'custom_css_category_watcher']
            ],
        ]);

        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        $expected = [
            'colours' => '*',
            'custom' => ['formcustom_field_customcss' => ['transform' => false]],
            'new_category' => [
                'css_field_1' => [],
                'css_field_2' => [],
                'css_field_3' => ['transform' => false],
            ],
        ];
        $this->assertEqualsCanonicalizing($expected, $theme_settings->get_categories_with_css_settings());
        $this->assertTrue($theme_settings->is_category_with_css_settings('colours'));
        $this->assertTrue($theme_settings->is_category_with_css_settings('custom'));
        $this->assertTrue($theme_settings->is_category_with_css_settings('new_category'));
        $this->assertFalse($theme_settings->is_category_with_css_settings('other_category'));
        $this->assertTrue($theme_settings->is_css_property('new_category', 'css_field_1'));
        $this->assertTrue($theme_settings->is_css_property('new_category', 'css_field_2'));
        $this->assertTrue($theme_settings->is_css_property('new_category', 'css_field_3'));
        $this->assertFalse($theme_settings->is_css_property('new_category', 'other_property'));
        $this->assertTrue($theme_settings->require_css_property_transformation('new_category', 'css_field_1'));
        $this->assertFalse($theme_settings->require_css_property_transformation('new_category', 'css_field_3'));

        // Update theme settings with some CSS categories.
        $categories = $this->get_css_categories();
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        $css = $theme_settings->get_css_variables();
        $this->assertEquals(":root{--css_field_1: #ff0013;--css_field_2: #f50009;}\nbody{background-color:pink;}\n", $css);

        $categories = $this->get_colours(false);
        $theme_settings->validate_categories($categories);
        $theme_settings->update_categories($categories);

        $css = $theme_settings->get_css_variables();
        $this->assertEquals(":root{--css_field_1: #ff0013;--css_field_2: #f50009;--nav-bg-color: #ff0000;}\nbody{background-color:pink;}\n", $css);
    }

    public static function custom_css_category_watcher(theme_settings_css_categories $hook) {
        $hook->add_category(
            'new_category',
            [
                'css_field_1' => [],
                'css_field_2' => [],
                'css_field_3' => ['transform' => false]
            ]
        );
    }

    /**
     * @return array[]
     */
    private function get_css_categories(): array {
        return [
            [
                'name' => 'new_category',
                'properties' => [
                    [
                        'name' => 'css_field_1',
                        'type' => 'value',
                        'value' => '#ff0013',
                    ],
                    [
                        'name' => 'css_field_2',
                        'type' => 'value',
                        'value' => '#f50009',
                    ],
                    [
                        'name' => 'css_field_3',
                        'type' => 'value',
                        'value' => 'body{background-color:pink;}',
                    ],
                    [
                        'name' => 'other_field_1',
                        'type' => 'value',
                        'value' => 'foo',
                    ],
                    [
                        'name' => 'other_field_2',
                        'type' => 'value',
                        'value' => 'bar',
                    ],
                ]
            ]
        ];
    }

    /**
     * @param int $item_id
     * @return Object|null
     */
    private function get_logo_file_record(int $item_id): ?object {
        global $DB;

        $rows = $DB->get_records('files',
            [
                'component' => 'totara_core',
                'filearea' => 'logo',
                'itemid' => $item_id,
            ]
        );
        if (!$rows) {
            return null;
        }

        $rows = array_filter($rows, function ($row) {
            return $row->filename !== '.';
        });

        return reset($rows);
    }

    public function test_helper_get_prelogin_tenantid() {
        global $SESSION;

        $generator = $this->getDataGenerator();
        $user1 = $generator->create_user();
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        // Default - unconfigured not set
        self::assertEquals(0, helper::get_prelogin_tenantid());

        // Configured and set
        set_config('allowprelogintenanttheme', '1');
        $SESSION->themetenantid = $tenant2->id;
        $SESSION->themetenantidnumber = $tenant2->idnumber;
        self::assertEquals($tenant2->id, helper::get_prelogin_tenantid());

        // Configured not set
        set_config('allowprelogintenanttheme', '1');
        unset($SESSION->themetenantid);
        unset($SESSION->themetenantidnumber);
        self::assertEquals(0, helper::get_prelogin_tenantid());

        // Unconfigured and set
        set_config('allowprelogintenanttheme', '0');
        $SESSION->themetenantid = $tenant2->id;
        $SESSION->themetenantidnumber = $tenant2->idnumber;
        self::assertEquals(0, helper::get_prelogin_tenantid());

        // Configured and set guest user
        set_config('allowprelogintenanttheme', '1');
        $this->setGuestUser();
        $SESSION->themetenantid = $tenant2->id;
        $SESSION->themetenantidnumber = $tenant2->idnumber;
        self::assertEquals($tenant2->id, helper::get_prelogin_tenantid());

        // Configured and set authenticated user
        set_config('allowprelogintenanttheme', '1');
        $this->setUser($user1->id);
        $SESSION->themetenantid = $tenant2->id;
        $SESSION->themetenantidnumber = $tenant2->idnumber;
        self::assertEquals(0, helper::get_prelogin_tenantid());
    }

    public function test_theme_colours_in_email_to_user() {
        $theme_config = \theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        $user1 = $this->getDataGenerator()->create_user(array('mailformat' => 1, 'maildisplay' => 0));
        $user2 = $this->getDataGenerator()->create_user(array('mailformat' => 1, 'maildisplay' => 0));
        $subject = 'subject';
        $messagetext = 'message text';

        // Confirm that email is using the default colour hard coded in mustache template.
        $sink = $this->redirectEmails();
        email_to_user($user1, $user2, $subject, $messagetext);
        $result = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $result);
        $body = quoted_printable_decode($result[0]->body);
        $body = preg_replace("#\r\n#", "\n", $body);

        $this->assertStringContainsString('background-color: #69bd45;', $body);
        $this->assertStringNotContainsString('background-color: #850400;', $body);

        $categories = [
            [
                'name' => 'colours',
                'properties' => [
                    [
                        'name' => 'color-primary',
                        'type' => 'value',
                        'value' => '#850400',
                    ],
                ],
            ]
        ];
        $theme_settings->update_categories($categories);

        // Confirm that email is using the theme settings colour.
        $sink = $this->redirectEmails();
        email_to_user($user1, $user2, $subject, $messagetext);
        $result = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $result);
        $body = quoted_printable_decode($result[0]->body);
        $body = preg_replace("#\r\n#", "\n", $body);

        $this->assertStringContainsString('background-color: #850400;', $body);
        $this->assertStringNotContainsString('background-color: #69bd45;', $body);
    }

    public function test_email_to_user() {
        $theme_config = \theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        $categories = [
            [
                'name' => 'brand',
                'properties' => [
                    [
                        'name' => 'formbrand_field_notificationshtmlheader',
                        'type' => 'html',
                        'value' => '<div>This is the header</div>',
                    ],
                    [
                        'name' => 'formbrand_field_notificationshtmlfooter',
                        'type' => 'html',
                        'value' => '<div>This is the footer</div>',
                    ],
                    [
                        'name' => 'formbrand_field_notificationstextfooter',
                        'type' => 'text',
                        'value' => 'This is the plain-text footer',
                    ],
                ],
            ]
        ];
        $theme_settings->update_categories($categories);

        $user1 = $this->getDataGenerator()->create_user(array('mailformat' => 1, 'maildisplay' => 0));
        $user2 = $this->getDataGenerator()->create_user(array('mailformat' => 0, 'maildisplay' => 0));

        $subject = 'subject';
        $messagetext = 'message text';

        // Test HTML mail format.
        $sink = $this->redirectEmails();
        email_to_user($user1, $user2, $subject, $messagetext);
        $result = $sink->get_messages();
        $sink->close();
        $this->assertCount(1, $result);
        $body = quoted_printable_decode($result[0]->body);
        $body = preg_replace("#\r\n#", "\n", $body);

        $this->assertStringContainsString('<div>This is the header</div>', $body);
        $this->assertStringContainsString('<div>This is the footer</div>', $body);
        $this->assertStringContainsString("\nmessage text\nThis is the plain-text footer\n", $body);

        // Test plain-text mail format.
        $sink = $this->redirectEmails();
        email_to_user($user2, $user1, $subject, $messagetext);
        $result = $sink->get_messages();
        $sink->close();
        $body = quoted_printable_decode($result[0]->body);
        $body = preg_replace("#\r\n#", "\n", $body);

        $this->assertCount(1, $result);
        $this->assertStringNotContainsString('<div>This is the header</div>', $body);
        $this->assertStringNotContainsString('<div>This is the footer</div>', $body);
        $this->assertStringContainsString("\nmessage text\nThis is the plain-text footer\n", $body);

        // Confirm that both header and footer is used if either one of them is not empty.
        $test_categories = $categories;
        $test_categories[0]['properties'][0]['value'] = '';
        $theme_settings::set_adhoc_categories($test_categories);
        $sink = $this->redirectEmails();
        email_to_user($user1, $user2, $subject, $messagetext);
        $result = $sink->get_messages();
        $sink->close();
        $body = quoted_printable_decode($result[0]->body);
        $body = preg_replace("#\r\n#", "\n", $body);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('<tr><td colspan="3">&nbsp;</td></tr>', $body);
        $this->assertStringContainsString('<div>This is the footer</div>', $body);
        $this->assertStringContainsString("\nmessage text\nThis is the plain-text footer\n", $body);

        $test_categories = $categories;
        $test_categories[0]['properties'][1]['value'] = '';
        $theme_settings::set_adhoc_categories($test_categories);
        $sink = $this->redirectEmails();
        email_to_user($user1, $user2, $subject, $messagetext);
        $result = $sink->get_messages();
        $sink->close();
        $body = quoted_printable_decode($result[0]->body);
        $body = preg_replace("#\r\n#", "\n", $body);

        $this->assertCount(1, $result);
        $this->assertStringContainsString('<div>This is the header</div>', $body);
        $this->assertStringContainsString('<tr><td colspan="3">&nbsp;</td></tr>', $body);
        $this->assertStringContainsString("\nmessage text\nThis is the plain-text footer\n", $body);

        // Confirm that neither header nor footer is NOT used when both of them are empty.
        $test_categories = $categories;
        $test_categories[0]['properties'][0]['value'] = '';
        $test_categories[0]['properties'][1]['value'] = '';
        $theme_settings::set_adhoc_categories($test_categories);
        $sink = $this->redirectEmails();
        email_to_user($user1, $user2, $subject, $messagetext);
        $result = $sink->get_messages();
        $sink->close();
        $body = quoted_printable_decode($result[0]->body);
        $body = preg_replace("#\r\n#", "\n", $body);

        $this->assertCount(1, $result);
        $this->assertStringNotContainsString('<div>This is the header</div>', $body);
        $this->assertStringNotContainsString('<div>This is the footer</div>', $body);
        $this->assertStringContainsString("\nmessage text\nThis is the plain-text footer\n", $body);
    }

    /**
     * Test default properties via the web api.
     */
    public function test_webapi_theme_settings_send_email_notification() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        // Test user that does not have permission.
        $result = $this->execute_graphql_operation(
            'core_theme_settings_send_email_notification', [
                'html_header' => '<div>test html header</div>',
                'html_footer' => '<div>test html footer</div>',
                'text_footer' => 'test text footer',
            ]
        );
        $this->assertNotEmpty($result->errors);
        $this->assertEquals(
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)',
            $result->errors[0]->getMessage()
        );

        // Assign capability to manage theme settings.
        $this->assign_themesettings_capability();

        $sink = $this->redirectEmails();

        // Test with capability.
        $result = $this->execute_graphql_operation(
            'core_theme_settings_send_email_notification', [
                'html_header' => '<div>test html header</div>',
                'html_footer' => '<div>test html footer</div>',
                'text_footer' => 'test text footer',
            ]
        );
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        // Confirm that the operation was successful.
        $this->assertArrayHasKey('core_theme_settings_send_email_notification', $result->data);
        $this->assertTrue($result->data['core_theme_settings_send_email_notification']);

        // Confirm that we sent 2 emails.
        $result = $sink->get_messages();
        $sink->close();
        $this->assertCount(2, $result);
    }

    /**
     * Test default properties via the web api.
     */
    public function test_webapi_theme_settings_tenant_send_email_notification() {
        $generator = $this->getDataGenerator();

        // Create tenants.
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();
        $tenant2 = $tenant_generator->create_tenant();

        // Create tenant members.
        $tenant_user1 = $generator->create_user(
            ['tenantid' => $tenant1->id, 'tenantdomainmanager' => $tenant1->idnumber]
        );
        $tenant_user2 = $generator->create_user(
            ['tenantid' => $tenant2->id]
        );

        // Tenant user 1 should NOT be able to test email notification for site.
        $this->setUser($tenant_user1);
        $result = $this->execute_graphql_operation(
            'core_theme_settings_send_email_notification', [
                'html_header' => '<div>test html header</div>',
                'html_footer' => '<div>test html footer</div>',
                'text_footer' => 'test text footer',
            ]
        );
        $this->assertNotEmpty($result->errors);
        $this->assertEquals(
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)',
            $result->errors[0]->getMessage()
        );

        // Tenant user 2 should NOT be able to test email notification.
        $this->setUser($tenant_user2);
        $result = $this->execute_graphql_operation(
            'core_theme_settings_send_email_notification', [
                'html_header' => '<div>test html header</div>',
                'html_footer' => '<div>test html footer</div>',
                'text_footer' => 'test text footer',
                'tenant_id' => $tenant1->id,
            ]
        );
        $this->assertNotEmpty($result->errors);
        $this->assertEquals(
            'Sorry, but you do not currently have permissions to do that (Manage theme settings)',
            $result->errors[0]->getMessage()
        );

        // Redirect emails to sink.
        $sink = $this->redirectEmails();

        // Tenant domain manager of tenancy 1 should be able to test email notification for tenancy 1.
        $this->setUser($tenant_user1);
        $result = $this->execute_graphql_operation(
            'core_theme_settings_send_email_notification', [
                'html_header' => '<div>test html header</div>',
                'html_footer' => '<div>test html footer</div>',
                'text_footer' => 'test text footer',
                'tenant_id' => $tenant1->id,
            ]
        );
        $this->assertEmpty($result->errors);
        $this->assertNotEmpty($result->data);

        // Confirm that the operation was successful.
        $this->assertArrayHasKey('core_theme_settings_send_email_notification', $result->data);
        $this->assertTrue($result->data['core_theme_settings_send_email_notification']);

        // Confirm that we sent 2 emails.
        $result = $sink->get_messages();
        $sink->close();
        $this->assertCount(2, $result);
    }

    private function skip_if_build_not_present() {
        $vendors_file = bundle::get_vendors_file();
        if ($vendors_file === null || !file_exists($vendors_file)) {
            $this->markTestSkipped('Tui build files must exist for this test to complete.');
        }
    }

    private function get_resolver(int $rev, $mode = 'p', int $tenant_id = 0) {
        global $CFG;
        require_once($CFG->libdir . '/configonlylib.php');
        $resolver = new styles_resolver(
            mediator::class,
            $rev,
            'ventura',
            'theme_ventura',
            $mode,
            $tenant_id
        );

        ob_start();
        $resolver->resolve();
        $css = ob_get_contents();
        ob_end_clean();
        $messages = $this->getDebuggingMessages();
        $this->resetDebugging();

        $prop = new ReflectionProperty(resolver::class, 'cachefile');
        $prop->setAccessible(true);

        return [$css, $messages, $prop->getValue($resolver)];
    }

    /**
     * Enable or disable tenant branding for given tenant_id.
     *
     * @param int $tenant_id
     * @param bool $enable
     */
    private static function enable_tenant_branding(int $tenant_id, $enable = true): void {
        $value = $enable ? 'true' : 'false';
        $theme_config = \theme_config::load('ventura');
        $theme_settings = new settings($theme_config, $tenant_id);
        $theme_settings->update_categories([
            [
                'name' => 'tenant',
                'properties' => [
                    [
                        'name' => 'formtenant_field_tenant',
                        'type' => 'boolean',
                        'value' => $value
                    ]
                ]
            ]
        ]);
    }

    /**
     * Disable tenant branding for given id
     *
     * @param int $tenant_id
     */
    private static function disable_tenant_branding(int $tenant_id): void {
        self::enable_tenant_branding($tenant_id, false);
    }

    /**
     * Assign capability to all users to manage theme settings.
     */
    private function assign_themesettings_capability(): void {
        $roles = get_archetype_roles('user');
        $role = reset($roles);
        assign_capability('totara/tui:themesettings', CAP_ALLOW, $role->id, context_system::instance(), true);
    }
}
