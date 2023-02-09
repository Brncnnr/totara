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
 * @author  Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
 * @package ml_recommender
 */

defined('MOODLE_INTERNAL') || die();

use core\task\manager;
use ml_recommender\local\csv\writer;
use ml_recommender\local\environment;
use ml_recommender\task\import;

class ml_recommender_import_testcase extends advanced_testcase {

    public function test_import_task_task() {
        global $DB;

        [$i2i, $i2u] = $this->prepare();

        ob_start();

        /** @var import $task */
        $task = manager::get_scheduled_task(import::class);
        $task->set_print_output(true);
        $task->execute();

        $output = ob_get_contents();
        $this->assertStringContainsString('Import completed', $output);
        ob_end_clean();

        // Check count of uploaded records.
        $count_i2i = $DB->count_records_sql("SELECT COUNT(mri.id) FROM  {ml_recommender_items} mri");
        $this->assertCount($count_i2i, $i2i);

        $count_i2u = $DB->count_records_sql("SELECT COUNT(mru.id) FROM  {ml_recommender_users} mru");
        $this->assertCount($count_i2u, $i2u);
    }

    public function test_ml_import_task_tenant_empty() {
        global $DB;

        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        [$i2i, $i2u] = $this->prepare();

        ob_start();

        /** @var import $task */
        $task = manager::get_scheduled_task(import::class);
        $task->set_print_output(true);
        $task->execute();

        $output = ob_get_contents();
        $this->assertStringContainsString('Import completed', $output);
        ob_end_clean();

        // Check count of uploaded records.
        $count_i2i = $DB->count_records_sql("SELECT COUNT(mri.id) FROM  {ml_recommender_items} mri");
        $this->assertCount($count_i2i, $i2i);

        $count_i2u = $DB->count_records_sql("SELECT COUNT(mru.id) FROM  {ml_recommender_users} mru");
        $this->assertCount($count_i2u, $i2u);
    }

    public function test_ml_import_task_tenant_populated() {
        global $DB;

        $generator = $this->getDataGenerator();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();
        $tenant1 = $tenant_generator->create_tenant();

        $tenant_user = $this->getDataGenerator()->create_user();
        $tenant_generator->migrate_user_to_tenant($tenant_user->id, $tenant1->id);

        [$i2i, $i2u] = $this->prepare();
        [$i2i_tenant, $i2u_tenant] = $this->prepare_tenant($tenant1->id, $tenant_user->id, $i2i, $i2u);

        ob_start();

        /** @var import $task */
        $task = manager::get_scheduled_task(import::class);
        $task->set_print_output(true);
        $task->execute();

        $output = ob_get_contents();
        $this->assertStringContainsString('Import completed', $output);
        ob_end_clean();

        // Check count of uploaded records.
        $count_i2i = $DB->count_records_sql("SELECT COUNT(mri.id) FROM  {ml_recommender_items} mri");
        $this->assertCount($count_i2i, array_merge($i2i, $i2i_tenant));

        $count_i2u = $DB->count_records_sql("SELECT COUNT(mru.id) FROM  {ml_recommender_users} mru");
        $this->assertCount($count_i2u, array_merge($i2u, $i2u_tenant));
    }

    protected function prepare() {
        $data_path = environment::get_data_path();
        if (!is_dir($data_path)) {
            mkdir($data_path, 0777, true);
        }

        $i2i = [
            ['target_iid' => 'engage_article1', 'similar_iid' => 'totara_playlist7', 'ranking' => 0.743599951267],
            ['target_iid' => 'engage_article1', 'similar_iid' => 'engage_article267', 'ranking' => 0.633537650108],
            ['target_iid' => 'totara_playlist1', 'similar_iid' => 'engage_article96', 'ranking' => 0.629082918167],
            ['target_iid' => 'totara_playlist1', 'similar_iid' => 'totara_playlist70', 'ranking' => 0.627931892872],
            ['target_iid' => 'engage_article2', 'similar_iid' => 'engage_article24', 'ranking' => 0.602713346481],
            ['target_iid' => 'engage_article2', 'similar_iid' => 'engage_article47', 'ranking' => 0.599358260632],
        ];

        $i2u = [
            ['uid' => 1, 'iid' => 'engage_article277', 'ranking' => 2.203594684601],
            ['uid' => 1, 'iid' => 'engage_article1', 'ranking' => 1.936550498009],
            ['uid' => 2, 'iid' => 'engage_article242', 'ranking' => 1.892577528954],
            ['uid' => 2, 'iid' => 'totara_playlist1', 'ranking' => 1.879106402397],
            ['uid' => 1, 'iid' => 'engage_article271', 'ranking' => 1.845853328705],
            ['uid' => 1, 'iid' => 'engage_article122', 'ranking' => 1.669565081596],
        ];

        $iwriter = new writer($data_path . 'i2i_0.csv');
        $iwriter->add_headings(array_keys(current($i2i)));
        foreach ($i2i as $i2i_item) {
            $iwriter->add_data(array_values($i2i_item));
        }

        $uwriter = new writer($data_path . 'i2u_0.csv');
        $uwriter->add_headings(array_keys(current($i2u)));
        foreach ($i2u as $i2u_item) {
            $uwriter->add_data(array_values($i2u_item));
        }

        file_put_contents($data_path . 'tenants.csv', 'tenants');

        return [$i2i, $i2u];
    }

    protected function prepare_tenant(int $tenant_id, int $tenant_user_id, array $i2i, array $i2u) {
        $data_path = environment::get_data_path();
        if (!is_dir($data_path)) {
            mkdir($data_path, 0777, true);
        }

        // Reuse existing data for new tenant, but substitute user id.
        $i2i_tenant = $i2i;
        $i2u_tenant = $i2u;
        foreach ($i2u_tenant as $index => $i2u) {
            $i2u_tenant[$index]['uid'] = $tenant_user_id;
        }

        // Item-to-item recommendations.
        $iwriter = new writer($data_path . 'i2i_' . $tenant_id . '.csv');
        $iwriter->add_headings(array_keys(current($i2i_tenant)));
        foreach ($i2i_tenant as $i2i_item) {
            $iwriter->add_data(array_values($i2i_item));
        }

        // Item-to-user recommendations.
        $uwriter = new writer($data_path . 'i2u_' . $tenant_id . '.csv');
        $uwriter->add_headings(array_keys(current($i2u_tenant)));
        foreach ($i2u_tenant as $i2u_item) {
            $uwriter->add_data(array_values($i2u_item));
        }

        // List of tenants.
        $twriter = new writer($data_path . 'tenants.csv');
        $twriter->add_headings(['tenants']);
        $twriter->add_data([$tenant_id]);

        return [$i2i_tenant, $i2u_tenant];
    }
}