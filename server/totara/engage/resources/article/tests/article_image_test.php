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
 * @package engage_article
 */

use core\theme\settings;
use engage_article\theme\file\article_image;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

class engage_article_image_testcase extends advanced_testcase {

    public function test_article_image(): void {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $user_context = \context_user::instance($user_one->id);
        $theme_config = theme_config::load('ventura');
        $theme_settings = new settings($theme_config, 0);

        // Get current default image.
        $article_image = new article_image($theme_config);
        $this->assertEquals(true, $article_image->is_available());
        $url = $article_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/engage_article/1/default",
            $url
        );

        // Update default image.
        $files = [
            [
                'ui_key' => 'engageresource',
                'draft_id' => $this->create_image('new_article_image', $user_context),
            ]
        ];
        $theme_settings->update_files($files);

        // Confirm that new default image is fetched.
        $url = $article_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/1/totara_core/defaultarticleimage/{$article_image->get_item_id()}/new_article_image.png",
            $url
        );

        // Confirm that the default URL is still pointing to the correct default image.
        $url = $article_image->get_default_url();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/engage_article/1/default",
            $url->out()
        );
    }

    public function test_image_enabled() {
        $generator = $this->getDataGenerator();
        $user_one = $generator->create_user();
        $this->setUser($user_one);
        $theme_config = theme_config::load('ventura');

        // Disable advanced feature.
        advanced_feature::disable('engage_resources');

        // Image should be disabled and not found in files.
        $article_image = new article_image($theme_config);
        $this->assertEquals(false, $article_image->is_enabled());

        $theme_settings = new settings($theme_config, 0);
        $files = $theme_settings->get_files();
        foreach ($files as $file) {
            if ($file instanceof article_image) {
                $this->assertFalse($file->is_enabled());
            }
        }
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

    /**
     * @return void
     */
    public function test_image_alt_txt(): void {
        global $CFG;

        $generator = $this->getDataGenerator();
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $user_one = $generator->create_user();
        $this->setUser($user_one);

        $article = $article_generator->create_article_with_image('totara1', '/totara/engage/resources/article/tests/fixtures/green.png', 1, 'test alt');
        $extra = json_decode($article->get_extra(), true);
        self::assertEquals('test alt', $extra['alt_text']);

        // Remove old image, upload new image and image text.
        $draft_id = file_get_unused_draft_itemid();
        require_once("{$CFG->dirroot}/lib/filelib.php");
        $fs = get_file_storage();
        $record = $article_generator->create_image_record_for_article($article->get_context_id(), $draft_id, $user_one->id, 'test1.png');
        $file = $fs->create_file_from_string($record, 'file');
        $url = \moodle_url::make_draftfile_url(
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );

        $doc = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'image',
                    'attrs' => [
                        'filename' => $file->get_filename(),
                        'url' => $url->out(),
                        'alttext' => 'New alt'
                    ],
                ]
            ]
        ];
        $article->update([
            'content' => json_encode($doc),
            'draft_id' => $record->itemid,
            'format' => FORMAT_JSON_EDITOR,
        ]);

        $extra = json_decode($article->get_extra(), true);
        self::assertEquals('New alt', $extra['alt_text']);
    }

    /**
     * Asserts that the variable contains a raw PNG file data.
     *
     * @param string $actual
     * @param string $message
     */
    private static function assert_png(string $actual, $message = ''): void {
        // Just verify the PNG file signature.
        static::assertEquals("\x89PNG\r\n\x1a\n", substr($actual, 0, 8), $message);
    }

    /**
     * Call engage_article_pluginfile() without any disruption.
     *
     * @param context $context
     * @param integer $itemid
     * @param string $filearea
     * @param string $filename
     * @param string|null $theme
     * @param string|null $preview
     * @return string
     */
    private static function call_engage_article_pluginfile(context $context, int $itemid, string $filearea, string $filename, string $theme = null, string $preview = null): string {
        // Install a custom error handler to shut up an error message when header() is called.
        set_error_handler(function (int $errno, string $errstr) {
            if (strpos($errstr, 'Cannot modify header information - headers already sent by') === false) {
                return false;
            }
        }, E_WARNING);
        ob_start();
        try {
            // send_file_not_found() just throws moodle_exception.
            // send_stored_file() does not die if 'dontdie' is set.
            engage_article_pluginfile(null, null, $context, $filearea, [$itemid, $filename], false, ['theme' => $theme, 'preview' => $preview, 'dontdie' => true]);
            return ob_get_contents();
        } finally {
            ob_end_clean();
            restore_error_handler();
        }
    }

    /**
     * Test engage_article_pluginfile() with publishgridcatalogimage disabled.
     *
     */

    public function test_pluginfile_visibility_unpublish() {

        $gen = $this->getDataGenerator();
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $gen->get_plugin_generator('engage_article');
        $user1 = $gen->create_user();
        $this->setAdminUser();

        $article1 = $article_generator->create_article_with_image('totara1', '/totara/engage/resources/article/tests/fixtures/green.png', 1);
        $article2 = $article_generator->create_article_with_image('totara2', '/totara/engage/resources/article/tests/fixtures/blue.png', 0);

        $image1args = [$article1->get_context(), $article1->get_id(), 'image', 'totara1.png', 'ventura', 'totara_catalog_medium'];
        $image2args = [$article2->get_context(), $article2->get_id(), 'image', 'totara2.png', 'ventura', 'totara_catalog_medium'];

        // Do not expose catalogue images.
        set_config('publishgridcatalogimage', 0);

        // Admin should be able to access any image of article.

        $contents = self::call_engage_article_pluginfile(...$image1args);
        $this->assert_png($contents);

        $contents = self::call_engage_article_pluginfile(...$image2args);
        $this->assert_png($contents);

        // User should be able to access only images of a public article.
        $this->setUser($user1->id);
        $contents = self::call_engage_article_pluginfile(...$image1args);
        $this->assert_png($contents);
        try {
            $contents = self::call_engage_article_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Guest user should not be able to access any image of any article.
        $this->setGuestUser();
        try {
            $contents = self::call_engage_article_pluginfile(...$image1args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        try {
            $contents = self::call_engage_article_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        // Unauthorised user should not be able to access any image of any article.
        $this->setUser(null);
        try {
            $contents = self::call_engage_article_pluginfile(...$image1args);
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }

        try {
            $contents = self::call_engage_article_pluginfile(...$image2args);
            $this->fail('moodle_exception expected');
        } catch (moodle_exception $ex) {
            $this->assertStringContainsString('Sorry, the requested file could not be found', $ex->getMessage());
        }
    }

    /**
     * Test engage_article_pluginfile() with publishgridcatalogimage enabled.
     *
     */
    public function test_pluginfile_visibility_publish() {

        $gen = $this->getDataGenerator();
        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $gen->get_plugin_generator('engage_article');
        $user1 = $gen->create_user();
        $this->setAdminUser();

        $article1 = $article_generator->create_article_with_image('totara1', '/totara/engage/resources/article/tests/fixtures/green.png', 1);
        $article2 = $article_generator->create_article_with_image('totara2', '/totara/engage/resources/article/tests/fixtures/blue.png', 0);

        $image1args = [$article1->get_context(), $article1->get_id(), 'image', 'totara1.png', 'ventura', 'totara_catalog_medium'];
        $image2args = [$article2->get_context(), $article2->get_id(), 'image', 'totara2.png', 'ventura', 'totara_catalog_medium'];


        // Expose catalogue images.
        set_config('publishgridcatalogimage', 1);

        // Admin should be able to access any image of any article.
        $this->setAdminUser();
        $contents = self::call_engage_article_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_engage_article_pluginfile(...$image2args);
        $this->assert_png($contents);

        // Guest user should be able to access catalog images.
        $this->setUser($user1->id);
        $contents = self::call_engage_article_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_engage_article_pluginfile(...$image2args);
        $this->assert_png($contents);


        // Guest user should be able to access catalog images.
        $this->setGuestUser();
        $contents = self::call_engage_article_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_engage_article_pluginfile(...$image2args);
        $this->assert_png($contents);


        // Unauthorised user should be able to access catalog images.
        $this->setUser(null);
        $contents = self::call_engage_article_pluginfile(...$image1args);
        $this->assert_png($contents);
        $contents = self::call_engage_article_pluginfile(...$image2args);
        $this->assert_png($contents);
    }

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
                'ui_key' => 'engageresource',
                'draft_id' => $this->create_image('new_article_image', $user_context),
            ],
        ];
        $theme_settings->validate_files($files);
        $theme_settings->update_files($files);

        // Confirm that tenant two has new resource image
        $article_image = new article_image($theme_config);
        $article_image->set_tenant_id($tenant_two->id);
        $url = $article_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/pluginfile.php/{$article_image->get_context()->id}/totara_core/defaultarticleimage/{$article_image->get_item_id()}/new_article_image.png",
            $url
        );

        // Confirm that tenant one still has default resource image
        $article_image = new article_image($theme_config);
        $article_image->set_tenant_id($tenant_one->id);
        $url = $article_image->get_current_or_default_url();
        $this->assertInstanceOf(moodle_url::class, $url);
        $url = $url->out();
        $this->assertEquals(
            "https://www.example.com/moodle/theme/image.php/_s/ventura/engage_article/1/default",
            $url
        );
    }
}
