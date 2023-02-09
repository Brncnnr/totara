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
 * @author  Vernon Denny <vernon.denny@totaralearning.com>
 * @package ml_recommender
 */
defined('MOODLE_INTERNAL') || die();

use core\task\manager;
use ml_recommender\local\csv\writer;
use ml_recommender\local\environment;
use ml_recommender\local\exporter;
use ml_recommender\task\export;
use totara_engage\access\access;
use totara_topic\topic;

class ml_recommender_export_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_ml_export(): void {
        $this->prepare();

        // Set up list of exporters.
        $data_path = self::get_data_path();
        $exporter = new exporter($data_path);
        $exporters = $exporter->get_exports();
        $this->assertCount(3, $exporters);

        // Run the data exports.
        foreach ($exporters as $export) {
            $exportname = $export->get_name();
            // Delete old data.
            $csv_path = $data_path . '/' . $exportname . '_0.csv';
            @unlink($csv_path);

            $writer = new writer($csv_path);
            $export->export($writer);
        }

        $this->assert_export();
    }

    public function test_ml_export_task() {
        $this->prepare();

        ob_start();
        export::cleanup(true);

        /** @var export $task */
        $task = manager::get_scheduled_task(export::class);
        $task->set_print_output(true);
        $task->execute();

        $output = ob_get_contents();
        $this->assertStringContainsString('Export completed', $output);
        ob_end_clean();

        $this->assert_export();
    }

    public function test_ml_export_task_tenant() {
        $this->prepare();

        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        // Create an additional user within a tenant (users 1 & 2 created in prepare()).
        $this->setAdminUser();
        $tenant1 = $tenant_generator->create_tenant();
        $user3 = $this->getDataGenerator()->create_user();
        $tenant_generator->migrate_user_to_tenant($user3->id, $tenant1->id);

        // Engage topics.
        $mytopics = ['T1topic1', 'T1topic2', 'T1topic3'];
        foreach ($mytopics as $key => $topic) {
            $topics[$key] = topic::create($topic);
        }

        // Create content within tenant.
        $this->setUser($user3->id);

        /** @var \engage_article\testing\generator $article_generator */
        $article_generator = $generator->get_plugin_generator('engage_article');
        $article_4 = $article_generator->create_article([
            'access' => access::PUBLIC,
            'topics' => [
                $topics[1]->get_id(),
                $topics[2]->get_id(),
            ],
            'content' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Content with image"}]},{"type":"image","attrs":{"filename":"image.png","url":"@@PLUGINFILE@@/image.png","alttext":""}}]}',
            'format' => FORMAT_JSON_EDITOR,
        ]);

        // Interactions.
        /** @var \ml_recommender\testing\generator $recommendations_generator */
        $recommendations_generator = $generator->get_plugin_generator('ml_recommender');
        $recommendations_generator->create_recommender_interaction(2, $article_4->get_id(), 'engage_article');
        $recommendations_generator->create_recommender_interaction($user3->id, $article_4->get_id(), 'engage_article');

        ob_start();
        export::cleanup(true);

        /** @var export $task */
        $task = manager::get_scheduled_task(export::class);
        $task->set_print_output(true);
        $task->execute();

        $output = ob_get_contents();
        $this->assertStringContainsString('Export completed', $output);
        ob_end_clean();

        $this->assert_export_tenant($tenant1->id);
    }

    protected function prepare() {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/enrol/self/externallib.php');

        // Set up as admin.
        $gen = $this->getDataGenerator();
        $this->setAdminUser();

        // Some users to work with.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Courses with tags - one will have self enrolment.
        core_tag_tag::create_if_missing(core_tag_collection::get_default(), ['tag1', 'tag2', 'tag3'], true);
        $course_self_enrol = $this->getDataGenerator()->create_course();
        core_tag_tag::add_item_tag('core', 'course', $course_self_enrol->id, context_course::instance($course_self_enrol->id), 'tag1');
        core_tag_tag::add_item_tag('core', 'course', $course_self_enrol->id, context_course::instance($course_self_enrol->id), 'tag3');

        $course_not_self = $this->getDataGenerator()->create_course();
        core_tag_tag::add_item_tag('core', 'course', $course_not_self->id, context_course::instance($course_not_self->id), 'tag1');
        core_tag_tag::add_item_tag('core', 'course', $course_not_self->id, context_course::instance($course_not_self->id), 'tag2');

        // Self enrol a user.
        $selfplugin = enrol_get_plugin('self');
        $this->assertNotEmpty($selfplugin);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $this->assertNotEmpty($studentrole);
        $selfplugin->add_instance($course_self_enrol, ['status' => ENROL_INSTANCE_ENABLED, 'name' => 'Test instance 1', 'customint6' => 1, 'roleid' => $studentrole->id]);
        self::setUser($user1);
        enrol_self_external::enrol_user($course_self_enrol->id);

        // The rest we do as admin.
        $this->setAdminUser();

        // Engage topics.
        $mytopics = ['topic1', 'topic2', 'topics3'];
        foreach ($mytopics as $key => $topic) {
            $topics[$key] = topic::create($topic);
        }

        // Engage content.
        $article_generator = $gen->get_plugin_generator('engage_article');

        $article_1 = $article_generator->create_article([
            'access' => access::PUBLIC,
            'topics' => [
                $topics[1]->get_id(),
                $topics[2]->get_id(),
            ],
            'content' => '{"type":"doc","content":[{"type":"paragraph","content":[{"type":"text","text":"Content with image"}]},{"type":"image","attrs":{"filename":"image.png","url":"@@PLUGINFILE@@/image.png","alttext":""}}]}',
            'format' => FORMAT_JSON_EDITOR,
        ]);

        $article_2 = $article_generator->create_article([
            'access' => access::PUBLIC,
            'topics' => [
                $topics[0]->get_id(),
                $topics[2]->get_id(),
            ],
        ]);

        $article_3 = $article_generator->create_article([
            'access' => access::PRIVATE,
            'topics' => [
                $topics[1]->get_id(),
                $topics[2]->get_id(),
            ],
        ]);

        // Interactions.
        $recommendations_generator = $gen->get_plugin_generator('ml_recommender');
        $recommendations_generator->create_recommender_interaction($user1->id, $article_1->get_id(), 'engage_article');
        $recommendations_generator->create_recommender_interaction($user2->id, $article_1->get_id(), 'engage_article');
        $recommendations_generator->create_recommender_interaction($user2->id, $article_2->get_id(), 'engage_article');
    }

    protected function assert_export() {
        $data_path = self::get_data_path();
        // Compare DB counts to CSV record counts.
        $csv_files = [
            'user_data' => 3, // 2 + admin
            'item_data' => 3, // 2 articles + course
            'user_interactions' => 4 // 3 + enrolment
        ];

        foreach ($csv_files as $csv => $db_count) {
            $data = [];
            $path = "{$data_path}/{$csv}_0.csv";
            $file = fopen($path, 'r');
            while (!feof($file)) {
                $row = fgetcsv($file);
                if (!empty($row)) {
                    $data[] = $row;
                }
            }
            fclose($file);

            $this->assertCount($db_count + 1, $data, $csv);
        }

    }

    /**
     * When multinenancy is configured, check that exports split content by tenant.
     *
     * The tenant user is a participant, thus is expected in both exports.
     * No other users should be in tenant export.
     */
    protected function assert_export_tenant($tenant) {
        $data_path = self::get_data_path();

        // Compare DB counts to CSV record counts.
        $tenants = [];

        // Tenant 0 (i.e. users not assigned to any tenant).
        $tenants[0] = [
            'user_data' => 4, // 2 + admin + tenant user
            'item_data' => 4, // 2 articles + course + extra article
            'user_interactions' => 6 // 3 + enrolment + 2 on extra article
        ];

        // Tenant 1.
        $tenants[$tenant] = [
            'user_data' => 1, // tenant user
            'item_data' => 2, // new article + self-enrol course
            'user_interactions' => 2,
        ];

        foreach ($tenants as $tenant_id => $csv_files) {
            foreach ($csv_files as $csv => $db_count) {
                $data = [];
                $path = "{$data_path}/{$csv}_" . $tenant_id . ".csv";
                $file = fopen($path, 'r');
                while (!feof($file)) {
                    $row = fgetcsv($file);
                    if (!empty($row)) {
                        $data[] = $row;
                    }
                }
                fclose($file);

                $this->assertCount($db_count + 1, $data, $csv);
            }
        }
    }

    /**
     * Ensure data directory exists and return its path.
     *
     * @return string Path to ML data directory.
     */
    private function get_data_path(): string {
        global $CFG;

        // Ensure we have a data directory to work with for exports and uploads.
        $data_path = environment::get_data_path();
        if (!is_dir($data_path)) {
            if (!mkdir($data_path, $CFG->directorypermissions, true)) {
                $this->assertEquals(true, false);
            }
        }

        return $data_path;
    }
}
