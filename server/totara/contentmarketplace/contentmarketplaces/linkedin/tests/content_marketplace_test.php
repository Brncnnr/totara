<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\contentmarketplace;
use core_phpunit\testcase;
use totara_contentmarketplace\plugininfo\contentmarketplace as plugin_info;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_content_marketplace_testcase extends testcase{
    /**
     * @var contentmarketplace|null
     */
    private $linkedin_marketplace;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->linkedin_marketplace = new contentmarketplace();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        $this->linkedin_marketplace = null;
    }

    /**
     * @return void
     */
    public function test_get_course_create_page(): void {
        self::assertEmpty($this->linkedin_marketplace->course_create_page());
    }

    /**
     * @return void
     */
    public function test_get_url(): void {
        self::assertEquals(
            "https://www.linkedin.com/learning",
            $this->linkedin_marketplace->url()
        );
    }

    /**
     * @return void
     */
    public function test_get_description_url(): void {
        global $OUTPUT;

        $browse_url = new moodle_url('/totara/contentmarketplace/explorer.php?marketplace=linkedin');
        self::assertEquals(
            $OUTPUT->render_from_template(
                "contentmarketplace_linkedin/plugin_description",
                [
                    "is_plugin_enabled" => false,
                    "browse_url" => $browse_url
                ],
            ),
            $this->linkedin_marketplace->get_description_html()
        );

        // Enable plugin
        $plugin_info = plugin_info::plugin("linkedin");
        $plugin_info->enable();

        self::assertEquals(
            $OUTPUT->render_from_template(
                "contentmarketplace_linkedin/plugin_description",
                [
                    "is_plugin_enabled" => true,
                    "browse_url" => $browse_url
                ]
            ),
            $this->linkedin_marketplace->get_description_html()
        );
    }

    /**
     * @return void
     */
    public function test_get_settings_url(): void {
        $moodle_url = new moodle_url("/admin/settings.php", ["section" => "content_marketplace_setting_linkedin"]);

        self::assertEquals(
            $moodle_url,
            $this->linkedin_marketplace->settings_url()
        );

        // Check that we are ignoring tabs.
        self::assertEquals(
            $moodle_url,
            $this->linkedin_marketplace->settings_url("sometab")
        );
    }
}