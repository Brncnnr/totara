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

use ml_recommender\local\environment;
use ml_recommender\local\csv\writer;
use ml_recommender\local\export\item_data;
use totara_engage\timeview\time_view;

class ml_recommender_export_item_data_testcase extends advanced_testcase {
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
     * @return \engage_article\testing\generator
     */
    private function get_article_generator(): \engage_article\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        return $article_generator;
    }

    /**
     * @return \totara_playlist\testing\generator
     */
    private function get_playlist_generator(): \totara_playlist\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \totara_playlist\testing\generator $playlist_generator */
        $playlist_generator = $generator->get_plugin_generator('totara_playlist');
        return $playlist_generator;
    }

    /**
     * @return \container_workspace\testing\generator
     */
    private function get_workspace_generator(): \container_workspace\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        return $workspace_generator;
    }

    /**
     * @return \totara_topic\testing\generator
     */
    private function get_topic_generator(): \totara_topic\testing\generator {
        $generator = self::getDataGenerator();

        /** @var \totara_topic\testing\generator $topic_generator */
        $topic_generator = $generator->get_plugin_generator('totara_topic');
        return $topic_generator;
    }

    /**
     * @return void
     * @covers \ml_recommender\local\export\item_data::one_hot_components
     */
    public function test_build_component_one_hot(): void {
        // One super hot
        $ref_class = new ReflectionClass(item_data::class);
        $method = $ref_class->getMethod('one_hot_components');
        $method->setAccessible(true);

        $expected = [
            'pudge' => [0 => 1, 1 => 0, 2 => 0],
            'windranger' => [0 => 0, 1 => 1, 2 => 0],
            'zeus' => [0 => 0, 1 => 0, 2 => 1]
        ];

        $item_data = new item_data();

        self::assertEquals($expected, $method->invoke($item_data, ['pudge', 'windranger', 'zeus']));
        self::assertEquals(
            $expected,
            $method->invoke(
                $item_data,
                [
                    'bob' => 'pudge',
                    '__jj' => 'windranger',
                    'double_down' => 'zeus'
                ]
            )
        );
    }

    /**
     * @return void
     */
    public function test_export_data(): void {
        $generator = self::getDataGenerator();
        $user_one = $generator->create_user();

        $article_generator = $this->get_article_generator();
        $playlist_generator = $this->get_playlist_generator();
        $workspace_generator = $this->get_workspace_generator();

        $this->setUser($user_one);

        $micro_article = $article_generator->create_public_article([
            'timeview' => time_view::LESS_THAN_FIVE,
            'name' => 'wow pikachu',
            'content' => 'boom'
        ]);

        $article = $article_generator->create_public_article([
            'timeview' => time_view::MORE_THAN_TEN,
            'name' => 'wow anima',
            'content' => 'martin garrix'
        ]);

        $playlist = $playlist_generator->create_public_playlist(['name' => 'amsterdam']);
        $workspace = $workspace_generator->create_workspace('luffy');

        // Unset the user in session.
        $this->setUser(null);

        $csv_file = "{$this->data_path}/file.csv";
        $writer = new writer($csv_file);

        $export = new item_data();
        $export->export($writer);

        $writer->close();

        self::assertTrue(file_exists($csv_file));

        $actual_content = file_get_contents($csv_file);
        self::assertNotEmpty($actual_content);

        // Convert all the double quotes of string into empty string only.
        $actual_content = str_replace('"', '', $actual_content);

        // There should be 5 rows from the csv content.
        self::assertEquals(5, substr_count($actual_content, "\n"));

        $components = item_data::get_supported_components();
        // There are no topics, hence the heading should not have topics.
        self::assertStringContainsString(
            "item_id,". implode(",", $components) . ",document",
            $actual_content
        );

        $one_hot_string = function (string $component) use ($components): string {
            $key = array_search($component, $components);
            $one_hot = array_fill(0, count($components), 0);
            $one_hot[$key] = 1;

            return implode(',', $one_hot);
        };

        // Check the row of micro article.
        self::assertStringContainsString(
            "engage_microlearning{$micro_article->get_id()},{$one_hot_string('engage_microlearning')},wow pikachu boom",
            $actual_content
        );

        // Check the row of article
        self::assertStringContainsString(
            "engage_article{$article->get_id()},{$one_hot_string('engage_article')},wow anima martin garrix",
            $actual_content
        );

        self::assertStringContainsString(
            "totara_playlist{$playlist->get_id()},{$one_hot_string('totara_playlist')},amsterdam",
            $actual_content
        );

        self::assertStringContainsString(
            "container_workspace{$workspace->get_id()},{$one_hot_string('container_workspace')},luffy",
            $actual_content
        );
    }

    /**
     * @return void
     */
    public function test_export_data_with_topic(): void {
        $this->setAdminUser();
        $topic_generator = $this->get_topic_generator();

        $topic_one = $topic_generator->create_topic('topicone');

        // Create an article and check if the topics are included in the export.
        $article_generator = $this->get_article_generator();
        $article = $article_generator->create_public_article([
            'name' => 'boom',
            'content' => 'xo',
            'topics' => [$topic_one->get_id()]
        ]);

        $csv_file = "{$this->data_path}/file.csv";
        $writer = new writer($csv_file);

        $export = new item_data();
        $result = $export->export($writer);

        self::assertTrue($result);
        self::assertTrue(file_exists($csv_file));

        $actual_content = file_get_contents($csv_file);
        self::assertNotEmpty($actual_content);

        // There should only have two rows.
        self::assertEquals(2, substr_count($actual_content, "\n"));

        // Remove all the encoded string
        $actual_content = str_replace('"', '', $actual_content);
        $components = item_data::get_supported_components();

        self::assertStringContainsString(
            'item_id,' . implode(',', $components) . ',topic_topicone,document',
            $actual_content
        );

        // Check for the article to be in
        self::assertStringContainsString(
            "engage_microlearning{$article->get_id()}",
            $actual_content
        );

        // Check for one hot data.
        $one_hot_map = array_fill(0, count($components), 0);
        $one_hot_map[array_search('engage_microlearning', $components)] = 1;

        self::assertStringContainsString(
            // One hot map with extra topic (sauces).
            implode(",", $one_hot_map) . ",1",
            $actual_content
        );
    }

    /**
     * @return void
     */
    public function test_export_article_with_tenants(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $playlist_generator = $this->get_playlist_generator();
        $article_generator = $this->get_article_generator();

        $tenant = $tenant_generator->create_tenant();
        $user_one = $generator->create_user(['tenantid' => $tenant->id]);

        // Create playlist and articles under this user.
        $this->setUser($user_one);
        $playlist = $playlist_generator->create_public_playlist();
        $article = $article_generator->create_public_article(['timeview' => time_view::MORE_THAN_TEN]);

        // Create article two under admin.
        $this->setAdminUser();
        $admin_article = $article_generator->create_public_article(['timeview' => time_view::MORE_THAN_TEN]);

        // Unset any user in the session.
        $this->setUser(null);
        $csv_file = "{$this->data_path}/file.csv";
        $writer = new writer($csv_file);

        $exporter = new item_data();
        $exporter->set_tenant($tenant);

        $result = $exporter->export($writer);
        self::assertTrue($result);
        self::assertTrue(file_exists($csv_file));

        $actual_content = file_get_contents($csv_file);
        self::assertNotEmpty($actual_content);

        // Converting encoded string to just string.
        $actual_content = str_replace('"', '', $actual_content);

        // There should be only 3 rows.
        self::assertEquals(3, substr_count($actual_content, "\n"));

        // Check for the article to be appearing in the content.
        self::assertStringContainsString("engage_article{$article->get_id()}", $actual_content);

        // Check for the playlist to be appearing in the content
        self::assertStringContainsString("totara_playlist{$playlist->get_id()}", $actual_content);

        // Check for the workspace to not appear in the content.
        self::assertStringNotContainsString("engage_article{$admin_article->get_id()}", $actual_content);
    }

        /**
     * @return void
     */
    public function test_export_tenant_with_no_items(): void {
        $generator = self::getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();
        $user_one = $generator->create_user(['tenantid' => $tenant->id]);

        $csv_file = "{$this->data_path}/file.csv";
        $writer = new writer($csv_file);

        $export = new item_data();
        $export->set_tenant($tenant);
        $export->export($writer);

        $writer->close();

        self::assertTrue(file_exists($csv_file));

        $actual_content = file_get_contents($csv_file);

        // Verify if the content is not empty
        self::assertNotEmpty($actual_content);

        // There should be 1 rows from the csv content containing only headers.
        self::assertEquals(1, substr_count($actual_content, "\n"));

        // Verify if the mandatory headers are there
        self::assertStringContainsString("item_id", $actual_content);
        self::assertStringContainsString("document", $actual_content);
    }
}