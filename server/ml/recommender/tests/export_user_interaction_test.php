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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use ml_recommender\local\csv\writer;
use ml_recommender\local\environment;
use ml_recommender\local\export\user_interactions;
use totara_engage\timeview\time_view;

class ml_recommender_export_user_interaction_testcase extends advanced_testcase {
    /**
     * @var string|null
     */
    private $data_path;

    /**
     * @return void
     */
    protected function setUp(): void {
        $this->data_path = environment::get_data_path();
        $this->data_path = rtrim($this->data_path,  "/\\");

        if (!is_dir($this->data_path)) {
            make_writable_directory($this->data_path);
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        global $CFG;

        if (is_dir($this->data_path)) {
            require_once("{$CFG->dirroot}/lib/filelib.php");
            fulldelete($this->data_path);
        }

        $this->data_path = null;
    }

    /**
     * @return \ml_recommender\testing\generator
     */
    private function get_recommender_generator(): \ml_recommender\testing\generator {
        return \ml_recommender\testing\generator::instance();
    }

    /**
     * @return \engage_article\testing\generator
     */
    private function get_article_generator(): \engage_article\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        return $article_generator;
    }

    /**
     * @return void
     */
    public function test_export_data(): void {
        global $DB;

        $generator = self::getDataGenerator();
        $owner = $generator->create_user();

        $article_generator = $this->get_article_generator();
        $article = $article_generator->create_public_article([
            'timeview' => time_view::MORE_THAN_TEN,
            'userid' => $owner->id,
        ]);

        $recommender_generator = $this->get_recommender_generator();

        for ($i = 0; $i < 5; $i++) {
            $user = $generator->create_user();
            $recommender_generator->create_recommender_interaction(
                $user->id,
                $article->get_id(),
                'engage_article'
            );
        }

        $csv_file = $this->data_path . "/file.csv";
        $writer = new writer($csv_file);

        $export = new user_interactions();
        $result = $export->export($writer);

        self::assertTrue($result);
        self::assertTrue(file_exists($csv_file));

        $actual_content = file_get_contents($csv_file);
        self::assertNotEmpty($actual_content);

        // There should be  6 rows in total.
        self::assertEquals(6, substr_count($actual_content, "\n"));
        self::assertStringContainsString(
            "user_id,item_id,rating,timestamp",
            $actual_content
        );

        $interaction_records = $DB->get_records(
            'ml_recommender_interactions',
            [
                'component_id' => $DB->get_field('ml_recommender_components', 'id', ['component' => 'engage_article']),
                'item_id' => $article->get_id()
            ]
        );

        foreach ($interaction_records as $record) {
            self::assertStringContainsString(
                "{$record->user_id},engage_article{$record->item_id},0,{$record->time_created}",
                $actual_content
            );
        }
    }

    /**
     * @return void
     */
    public function test_export_items_with_tenancy(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $user_one = $generator->create_user(['tenantid' => $tenant->id]);
        $user_two = $generator->create_user(['tenantid' => $tenant->id]);
        $user_three = $generator->create_user();

        // Log in as user admin and create a public article.
        $this->setAdminUser();
        $article_generator = $this->get_article_generator();

        $article = $article_generator->create_public_article(['timeview' => time_view::MORE_THAN_TEN]);

        $recommender_generator = $this->get_recommender_generator();

        foreach ([$user_one, $user_two, $user_three] as $user) {
            $recommender_generator->create_recommender_interaction(
                $user->id,
                $article->get_id(),
                'engage_article'
            );
        }

        // Unset the user in session so that we can run the test properly.
        $this->setUser(null);
        $csv_file = "{$this->data_path}/file.csv";

        $writer = new writer($csv_file);
        $export = new user_interactions();
        $export->set_tenant($tenant);

        $result = $export->export($writer);
        self::assertTrue($result);
        self::assertTrue(file_exists($csv_file));

        $actual_content = file_get_contents($csv_file);
        self::assertNotEmpty($actual_content);

        // Check for user one and two.
        self::assertStringContainsString(
            "{$user_one->id},engage_article{$article->get_id()},0",
            $actual_content
        );

        self::assertStringContainsString(
            "{$user_two->id},engage_article{$article->get_id()},0",
            $actual_content
        );

        self::assertStringNotContainsString(
            "{$user_three->id},engage_article{$article->get_id()},0",
            $actual_content
        );
    }

    /**
     * @return void
     */
    public function test_export_tenant_with_no_interactions(): void {
        $generator = self::getDataGenerator();

        /** @var totara_tenant_generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $article_generator = $this->get_article_generator();

        $tenant = $tenant_generator->create_tenant();
        $user_one = $generator->create_user(['tenantid' => $tenant->id]);
        $user_two = $generator->create_user(['tenantid' => $tenant->id]);

        // Log in as user_one and create article.
        $this->setUser($user_one);
        $article = $article_generator->create_public_article(['timeview' => time_view::MORE_THAN_TEN]);

        // Log in as user_two and create article.
        $this->setUser($user_two);
        $article = $article_generator->create_public_article(['timeview' => time_view::MORE_THAN_TEN]);

        $recommender_generator = $this->get_recommender_generator();

        // Unset the user in session so that we can run the test properly.
        $this->setUser(null);
        $csv_file = "{$this->data_path}/file.csv";

        $writer = new writer($csv_file);
        $export = new user_interactions();
        $export->set_tenant($tenant);

        $result = $export->export($writer);
        $writer->close();

        //Verify that the user_interactions file is exported.
        self::assertTrue(file_exists($csv_file));

        // Verify that the file is not empty.
        $actual_content = file_get_contents($csv_file);
        self::assertNotEmpty($actual_content);

        // Check for the presence of headers.
        self::assertStringContainsString(
            "user_id,item_id,rating,timestamp",
            $actual_content
        );

        // There should be 1 rows from the csv content containing only headers.
        self::assertEquals(1, substr_count($actual_content, "\n"));
    }
}