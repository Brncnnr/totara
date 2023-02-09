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
use ml_recommender\local\flag;
use ml_recommender\local\importer;
use ml_recommender\task\import;
use ml_recommender\local\import\item_item;
use ml_recommender\local\import\item_user;

class ml_recommender_multi_tenancy_import_task_tesetcase extends advanced_testcase {
    /**
     * @var string|null
     */
    private $data_path;

    /**
     * @return void
     */
    protected function setUp(): void {
        $this->data_path = environment::get_data_path();
        $this->data_path = rtrim($this->data_path, "/\\");

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
     * @return void
     */
    public function test_import_with_tenants(): void {
        global $DB;
        $generator = self::getDataGenerator();
        $user = $generator->create_user();

        /** @var \totara_tenant\testing\generator $tenant_generator */
        $tenant_generator = $generator->get_plugin_generator('totara_tenant');
        $tenant_generator->enable_tenants();

        $tenant = $tenant_generator->create_tenant();

        // Create item to item files
        $i2i_data = implode("\n", [
            'target_iid,similar_iid,ranking',
            'engage_article1,totara_playlist2,0.743599951267',
            'engage_article2,totara_playlist2,0.743599951267',
            'engage_article3,totara_playlist2,0.743599951267',
            'engage_article4,totara_playlist2,0.743599951267',
            'engage_article5,totara_playlist2,0.743599951267',
            'engage_article6,totara_playlist2,0.743599951267',
        ]);

        file_put_contents(
            importer::get_import_csv_file((new item_item())->get_name(), $tenant->id, $this->data_path),
            $i2i_data
        );

        // Create item to user file
        $i2u_data = implode("\n", [
            'uid,iid,ranking',
            "{$user->id},engage_article42,1.936550498009",
            "{$user->id},engage_article43,1.936550498009",
            "{$user->id},engage_article44,1.936550498009",
            "{$user->id},engage_article45,1.936550498009",
        ]);

        file_put_contents(
            importer::get_import_csv_file((new item_user())->get_name(), $tenant->id, $this->data_path),
            $i2u_data
        );

        // Create tenants item id.
        file_put_contents(importer::get_tenant_csv_file($this->data_path), "tenants\n{$tenant->id}");

        self::assertEquals(0, $DB->count_records('ml_recommender_users'));
        self::assertEquals(0, $DB->count_records('ml_recommender_items'));

        $task = new import();
        $task->execute();

        // 6 newly added records to the table.
        self::assertEquals(6, $DB->count_records('ml_recommender_items'));
        self::assertEquals(4, $DB->count_records('ml_recommender_users'));
    }

    /**
     * @return void
     */
    public function test_import_with_lock_files(): void {
        flag::start(flag::IMPORT, $this->data_path . '/');
        $task = new import();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("import not finished.");

        $task->execute();
    }
}