<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_cloudfiledir
 */

use totara_cloudfiledir\local\store;

defined('MOODLE_INTERNAL') || die();

/**
 * Test hook integration of cloud filedir plugin.
 */
abstract class totara_cloudfiledir_hook_testcase extends advanced_testcase {
    abstract protected function prepare_store_config(array $config): array;
    abstract protected function get_provider(array $config): totara_cloudfiledir\local\provider\base;

    public function test_content_adding() {
        global $CFG, $DB;

        // Enabled Add and active.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);

        $this->setCurrentTimeStart();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertTimeCurrent($syncrecord->timeuploaded);
        $this->assertNull($syncrecord->timedownloaded);

        // Disabled Add and active.

        $config = [
            'idnumber' => 'external',
            'add' => false,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);

        get_file_storage()->add_string_to_pool($content);
        $this->assertFalse($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertFalse($syncrecord);

        // Enabled Add and not active.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'active' => false,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertFalse($syncrecord);

        get_file_storage()->add_string_to_pool($content);
        $this->assertFalse($provider->is_content_available($contenthash));

        // Disabled by default.

        $config = [
            'idnumber' => 'external',
            'add' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);

        get_file_storage()->add_string_to_pool($content);
        $this->assertFalse($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertFalse($syncrecord);
    }

    public function test_content_adding_size_limits() {
        global $CFG, $DB;

        // Max size.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'active' => true,
            'maxinstantuploadsize' => 10,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = '1234567890';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);

        $this->setCurrentTimeStart();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertTimeCurrent($syncrecord->timeuploaded);
        $this->assertNull($syncrecord->timedownloaded);

        $content = $content . '1';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        get_file_storage()->add_string_to_pool($content);
        $this->assertFalse($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertFalse($syncrecord);

        // All via cron later.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'active' => true,
            'maxinstantuploadsize' => 0,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);

        $this->setCurrentTimeStart();
        get_file_storage()->add_string_to_pool($content);
        $this->assertFalse($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertFalse($syncrecord);
    }

    public function test_content_deleting() {
        global $CFG, $DB;

        // Enabled Delete and active.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $trashfile = self::get_local_trash($contenthash);
        self::purge_local_filedir();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));

        $this->setCurrentTimeStart();
        get_file_storage()->deleted_file_cleanup($contenthash);
        $this->assertFileExists($trashfile); // Trash file is kept unless there is a restore option from cloud.
        $this->assertFalse($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertFalse($syncrecord);

        // Enabled Delete and active - sync record missing.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $trashfile = self::get_local_trash($contenthash);
        self::purge_local_filedir();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $this->setCurrentTimeStart();
        get_file_storage()->deleted_file_cleanup($contenthash);
        $this->assertFileExists($trashfile); // Trash file is kept unless there is a restore option from cloud.
        $this->assertFalse($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertFalse($syncrecord);

        // Disabled Delete and active.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => false,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $trashfile = self::get_local_trash($contenthash);
        self::purge_local_filedir();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);

        get_file_storage()->deleted_file_cleanup($contenthash);
        $this->assertFileExists($trashfile); // Trash file is kept unless there is a restore option from cloud.
        $this->assertTrue($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertNotNull($syncrecord);

        // Enabled Delete and non-active.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $trashfile = self::get_local_trash($contenthash);
        self::purge_local_filedir();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => false,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);

        get_file_storage()->deleted_file_cleanup($contenthash);
        $this->assertFileExists($trashfile); // Trash file is kept unless there is a restore option from cloud.
        $this->assertTrue($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertNotNull($syncrecord);
    }

    public function test_content_restore() {
        global $CFG, $DB;

        // Enabled Restore and active.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'restore' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertNull($syncrecord->timedownloaded);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertNull($syncrecord->timedownloaded);

        $this->setCurrentTimeStart();
        $this->assertTrue(get_file_storage()->try_content_recovery($contenthash));
        $this->assertFileExists($contentfile);
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertTimeCurrent($syncrecord->timedownloaded);

        // Enabled Restore and active - no sync record.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'restore' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertNull($syncrecord->timedownloaded);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertNull($syncrecord->timedownloaded);
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $this->setCurrentTimeStart();
        $this->assertTrue(get_file_storage()->try_content_recovery($contenthash));
        $this->assertFileExists($contentfile);
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertTimeCurrent($syncrecord->timedownloaded);

        // Disabled Restore and active.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'restore' => false,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertNull($syncrecord->timedownloaded);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);

        $this->assertFalse(get_file_storage()->try_content_recovery($contenthash));
        $this->assertFileDoesNotExist($contentfile);

        // Enabled Restore and not-active.

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'restore' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);
        $provider->clear_test_bucket();
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        self::purge_local_filedir();
        get_file_storage()->add_string_to_pool($content);
        $this->assertTrue($provider->is_content_available($contenthash));
        $syncrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => $config['idnumber']]);
        $this->assertNull($syncrecord->timedownloaded);
        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'restore' => true,
            'active' => false,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $provider = $this->get_provider($config);

        $this->assertFalse(get_file_storage()->try_content_recovery($contenthash));
        $this->assertFileDoesNotExist($contentfile);
    }

    /**
     * Returns standard relative path to content hash file in external filedir.
     * @param string $contenthash
     * @return string
     */
    protected static function get_relative_filepath(string $contenthash): string {
        $l1 = $contenthash[0].$contenthash[1];
        $l2 = $contenthash[2].$contenthash[3];
        return "$l1/$l2/$contenthash";
    }

    /**
     * A hack to get real file path in local filedir,
     * do not use anything like this in production code!!!
     *
     * @param string $contenthash
     * @return string
     * @internal
     */
    protected static function get_local_file(string $contenthash): string {
        global $CFG;

        return $CFG->dataroot . '/filedir/' . self::get_relative_filepath($contenthash);
    }

    /**
     * A hack to get real file path in local filedir,
     * do not use anything like this in production code!!!
     *
     * @param string $contenthash
     * @return string
     * @internal
     */
    protected static function get_local_trash(string $contenthash): string {
        global $CFG;

        return $CFG->dataroot . '/trashdir/' . self::get_relative_filepath($contenthash);
    }

    /**
     * Purges local filedir adn trashdir.
     * @internal
     */
    protected static function purge_local_filedir(): void {
        global $CFG;
        remove_dir($CFG->dataroot . '/filedir', true);
        remove_dir($CFG->dataroot . '/trashdir', true);
    }
}
