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
 * Test cloud filedir sores.
 */
abstract class totara_cloudfiledir_store_testcase extends advanced_testcase {
    abstract protected function prepare_store_config(array $config): array;
    abstract protected function get_provider(array $config): totara_cloudfiledir\local\provider\base;

    public function test_get_stores() {
        global $CFG;

        unset($CFG->totara_cloudfiledir_stores);
        $stores = store::get_stores();
        $this->assertCount(0, $stores);

        $config1 = $this->prepare_store_config([
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ]);
        $config2 = $this->prepare_store_config([
            'idnumber' => 'other',
            'active' => false,
        ]);

        $CFG->totara_cloudfiledir_stores = [$config1, $config2];
        $stores = store::get_stores();
        $this->assertSame(['external', 'other'], array_keys($stores));
        $this->assertInstanceOf(store::class, $stores['external']);
        $this->assertInstanceOf(store::class, $stores['other']);

        $CFG->totara_cloudfiledir_stores = [$config1, $config2, $config2];
        $stores = store::get_stores();
        $this->assertSame(['external', 'other'], array_keys($stores));
        $this->assertInstanceOf(store::class, $stores['external']);
        $this->assertInstanceOf(store::class, $stores['other']);

        unset($config1['provider']);
        $CFG->totara_cloudfiledir_stores = [$config1, $config2];
        $stores = store::get_stores();
        $this->assertSame(['other'], array_keys($stores));
    }

    public function test_get_idnumber() {
        global $CFG;

        $config = $this->prepare_store_config([
            'idnumber' => 'external1_2'
        ]);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $this->assertSame(['external1_2'], array_keys($stores));
        $this->assertSame('external1_2', $stores['external1_2']->get_idnumber());

        // Invalid idnumbers must be ignored.

        $CFG->totara_cloudfiledir_stores = $this->prepare_store_config([
            ['idnumber' => '1'],
            ['idnumber' => 'b a'],
            ['idnumber' => 'bú'],
        ]);
        $stores = store::get_stores();
        $this->assertSame([], array_keys($stores));
    }

    public function test_get_provider() {
        global $CFG;

        $config = $this->prepare_store_config([
            'idnumber' => 'external'
        ]);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $this->assertSame($config['provider'], $stores['external']->get_provider());
    }

    public function test_get_bucket() {
        global $CFG;

        $config = $this->prepare_store_config([
            'idnumber' => 'external'
        ]);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $this->assertSame($config['bucket'], $stores['external']->get_bucket());
    }

    public function test_get_maxinstantuploadsize() {
        global $CFG;

        $CFG->totara_cloudfiledir_stores = [
            $this->prepare_store_config([
                'idnumber' => 'external1',
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external2',
                'maxinstantuploadsize' => null,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external3',
                'maxinstantuploadsize' => -2,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external4',
                'maxinstantuploadsize' => 0,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external5',
                'maxinstantuploadsize' => 1024,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external6',
                'maxinstantuploadsize' => '10M',
            ]),
        ];
        $stores = store::get_stores();
        $this->assertSame(-1, $stores['external1']->get_maxinstantuploadsize());
        $this->assertSame(-1, $stores['external2']->get_maxinstantuploadsize());
        $this->assertSame(-1, $stores['external3']->get_maxinstantuploadsize());
        $this->assertSame(0, $stores['external4']->get_maxinstantuploadsize());
        $this->assertSame(1024, $stores['external5']->get_maxinstantuploadsize());
        $this->assertSame(10 * 1024 * 1024, $stores['external6']->get_maxinstantuploadsize());
    }

    public function test_get_description() {
        global $CFG;

        $CFG->totara_cloudfiledir_stores = [
            $this->prepare_store_config([
                'idnumber' => 'external1',
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external2',
                'description' => null,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external3',
                'description' => 'grrr',
            ]),
        ];
        $stores = store::get_stores();
        $this->assertSame('', $stores['external1']->get_description());
        $this->assertSame('', $stores['external2']->get_description());
        $this->assertSame('grrr', $stores['external3']->get_description());
    }

    public function test_is_active() {
        global $CFG;

        $CFG->totara_cloudfiledir_stores = [
            $this->prepare_store_config([
                'idnumber' => 'external1',
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external2',
                'active' => true,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external3',
                'active' => false,
            ]),
        ];
        $stores = store::get_stores();
        $this->assertFalse($stores['external1']->is_active());
        $this->assertTrue($stores['external2']->is_active());
        $this->assertFalse($stores['external3']->is_active());
    }

    public function test_add_enabled() {
        global $CFG;

        $CFG->totara_cloudfiledir_stores = [
            $this->prepare_store_config([
                'idnumber' => 'external1',
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external2',
                'add' => true,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external3',
                'add' => false,
            ]),
        ];
        $stores = store::get_stores();
        $this->assertFalse($stores['external1']->add_enabled());
        $this->assertTrue($stores['external2']->add_enabled());
        $this->assertFalse($stores['external3']->add_enabled());
    }

    public function test_delete_enabled() {
        global $CFG;

        $CFG->totara_cloudfiledir_stores = [
            $this->prepare_store_config([
                'idnumber' => 'external1',
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external2',
                'delete' => true,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external3',
                'delete' => false,
            ]),
        ];
        $stores = store::get_stores();
        $this->assertFalse($stores['external1']->delete_enabled());
        $this->assertTrue($stores['external2']->delete_enabled());
        $this->assertFalse($stores['external3']->delete_enabled());
    }

    public function test_restore_enabled() {
        global $CFG;

        $CFG->totara_cloudfiledir_stores = [
            $this->prepare_store_config([
                'idnumber' => 'external1',
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external2',
                'restore' => true,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external3',
                'restore' => false,
            ]),
        ];
        $stores = store::get_stores();
        $this->assertFalse($stores['external1']->restore_enabled());
        $this->assertTrue($stores['external2']->restore_enabled());
        $this->assertFalse($stores['external3']->restore_enabled());
    }

    public function test_is_instant_upload() {
        global $CFG;
        $CFG->totara_cloudfiledir_stores = [
            $this->prepare_store_config([
                'idnumber' => 'external1',
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external2',
                'maxinstantuploadsize' => null,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external3',
                'maxinstantuploadsize' => -2,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external4',
                'maxinstantuploadsize' => 0,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external5',
                'maxinstantuploadsize' => 1024,
            ]),
            $this->prepare_store_config([
                'idnumber' => 'external6',
                'maxinstantuploadsize' => '10M',
            ]),
        ];

        $stores = store::get_stores();


        $this->assertTrue($stores['external1']->is_instant_upload(1025));
        $this->assertTrue($stores['external2']->is_instant_upload(1025));
        $this->assertTrue($stores['external3']->is_instant_upload(1025));
        $this->assertFalse($stores['external4']->is_instant_upload(1025));
        $this->assertTrue($stores['external5']->is_instant_upload(1024));
        $this->assertFalse($stores['external5']->is_instant_upload(1025));
        $this->assertTrue($stores['external6']->is_instant_upload(1025));
    }

    public function test_is_content_available() {
        global $CFG, $DB;

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $store = $stores['external'];
        $provider = $this->get_provider($config);

        $provider->clear_test_bucket();
        self::purge_local_filedir();
        $DB->delete_records('files', []);
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'grrrr';
        $contenthash = sha1($content);
        $contentfile = make_request_directory() . '/myfile.txt';
        file_put_contents($contentfile, $content);

        $this->assertFalse($store->is_content_available($contenthash));
        $this->assertFalse($store->is_content_available($contenthash, true));
        $this->assertFalse($store->is_content_available($contenthash, false));

        $this->assertFalse($DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']));
        $provider->upload_content($contenthash, $contentfile);
        $this->assertFalse($store->is_content_available($contenthash));
        $this->assertFalse($store->is_content_available($contenthash, false));
        $this->assertFalse($DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']));
        $this->setCurrentTimeStart();
        $this->assertTrue($store->is_content_available($contenthash, true));
        $record = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->assertNotFalse($record);
        $this->assertTimeCurrent($record->timeuploaded);
        $this->assertNull($record->timedownloaded);
        $this->assertSame('0', $record->localproblem);

        $provider->delete_content($contenthash);
        $this->assertTrue($store->is_content_available($contenthash));
        $this->assertTrue($store->is_content_available($contenthash, false));
        $this->assertFalse($store->is_content_available($contenthash, true));
        $this->assertTrue($store->is_content_available($contenthash, false));
    }

    public function test_write_content() {
        global $CFG, $DB;

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $store = $stores['external'];
        $provider = $this->get_provider($config);

        $provider->clear_test_bucket();
        self::purge_local_filedir();
        $DB->delete_records('files', []);
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'grrrr';
        $contenthash = sha1($content);
        $contentfile = make_request_directory() . '/myfile.txt';
        file_put_contents($contentfile, $content);

        $streaminfo = function () use ($contentfile) {
            return [fopen($contentfile, 'r'), filesize($contentfile)];
        };
        $this->setCurrentTimeStart();
        $result = $store->write_content($contenthash, $streaminfo);
        $this->assertTrue($result);
        $this->assertTrue($store->is_content_available($contenthash));
        $this->assertTrue($store->is_content_available($contenthash, false));
        $record = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->assertNotFalse($record);
        $this->assertTimeCurrent($record->timeuploaded);
        $this->assertNull($record->timedownloaded);
        $this->assertSame('0', $record->localproblem);
        $this->assertTrue($store->is_content_available($contenthash, true));

        // The same upload should not change the timestamp.

        $record->timeuploaded = '10';
        $DB->update_record('totara_cloudfiledir_sync', $record);
        $result = $store->write_content($contenthash, $streaminfo);
        $this->assertTrue($result);
        $newrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->assertNotFalse($newrecord);
        $this->assertSame((array)$record, (array)$newrecord);

        // Cloud content goes missing.

        $provider->delete_content($contenthash);
        $this->assertFalse($store->is_content_available($contenthash, true));
        $result = $store->write_content($contenthash, $streaminfo);
        $this->assertTrue($result);
        $newrecord = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->assertNotFalse($newrecord);
        $this->assertSame((array)$record, (array)$newrecord);
        $this->assertTrue($store->is_content_available($contenthash, true));
    }

    public function test_read_content() {
        global $CFG, $DB;

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $store = $stores['external'];
        $provider = $this->get_provider($config);

        $provider->clear_test_bucket();
        self::purge_local_filedir();
        $DB->delete_records('files', []);
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'grrrr';
        $contenthash = sha1($content);
        $contentfile = make_request_directory() . '/myfile.txt';
        file_put_contents($contentfile, $content);
        $this->addd_file_to_store($contentfile, $store);

        $newcontent = false;
        $filereader = function ($filepath) use (&$newcontent) {
            $newcontent = file_get_contents($filepath);
            return true;
        };
        $this->setCurrentTimeStart();
        $result = $store->read_content($contenthash, $filereader);
        $this->assertTrue($result);
        $this->assertSame($newcontent, $content);
        $record = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->assertNotFalse($record);
        $this->assertTimeCurrent($record->timedownloaded);
        $this->assertSame('0', $record->localproblem);

        $record->timeuploaded = '10';
        $record->timedownloaded = '20';
        $DB->update_record('totara_cloudfiledir_sync', $record);
        $this->setCurrentTimeStart();
        $result = $store->read_content($contenthash, $filereader);
        $this->assertTrue($result);
        $this->assertSame($newcontent, $content);
        $record = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->assertNotFalse($record);
        $this->assertSame('10', $record->timeuploaded);
        $this->assertTimeCurrent($record->timedownloaded);
        $this->assertSame('0', $record->localproblem);

        $DB->delete_records('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->setCurrentTimeStart();
        $result = $store->read_content($contenthash, $filereader);
        $this->assertTrue($result);
        $this->assertSame($newcontent, $content);
        $record = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->assertNotFalse($record);
        $this->assertTimeCurrent($record->timeuploaded);
        $this->assertTimeCurrent($record->timedownloaded);
        $this->assertSame('0', $record->localproblem);
    }

    public function test_delete_content() {
        global $CFG, $DB;

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $store = $stores['external'];
        $provider = $this->get_provider($config);

        $provider->clear_test_bucket();
        self::purge_local_filedir();
        $DB->delete_records('files', []);
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'grrrr';
        $contenthash = sha1($content);
        $contentfile = make_request_directory() . '/myfile.txt';
        file_put_contents($contentfile, $content);
        $this->addd_file_to_store($contentfile, $store);

        $store->delete_content($contenthash);
        $this->assertFalse($store->is_content_available($contenthash));
        $this->assertFalse($store->is_content_available($contenthash, false));
        $this->assertFalse($store->is_content_available($contenthash, true));
        $record = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->assertFalse($record);
    }

    public function test_fetch_list() {
        global $CFG, $DB;

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $store = $stores['external'];
        $provider = $this->get_provider($config);

        $provider->clear_test_bucket();
        self::purge_local_filedir();

        $content1 = 'haha';
        $contenthash1 = sha1($content1);
        get_file_storage()->add_string_to_pool($content1);
        $content2 = 'hahahaha';
        $contenthash2 = sha1($content2);
        get_file_storage()->add_string_to_pool($content2);

        $DB->delete_records('files', []);
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $this->setCurrentTimeStart();
        $store->fetch_list();
        $records = $DB->get_records('totara_cloudfiledir_sync', []);
        $this->assertCount(2, $records);
        foreach ($records as $record) {
            $this->assertContains($record->contenthash, [$contenthash1, $contenthash2]);
            $this->assertTimeCurrent($record->timeuploaded);
            $this->assertSame($store->get_idnumber(), $record->idnumber);
            $this->assertNull($record->timedownloaded);
        }

        // Add one more file.

        $content3 = 'grrrr';
        $contenthash3 = sha1($content3);
        get_file_storage()->add_string_to_pool($content3);

        $store->fetch_list();
        $records = $DB->get_records('totara_cloudfiledir_sync', []);
        $this->assertCount(3, $records);
        foreach ($records as $record) {
            $this->assertContains($record->contenthash, [$contenthash1, $contenthash2, $contenthash3]);
            $this->assertSame($store->get_idnumber(), $record->idnumber);
            $this->assertNull($record->timedownloaded);
        }

        // Test removal of orphaned sync entries.

        $provider->delete_content($contenthash3);
        $records = $DB->get_records('totara_cloudfiledir_sync', []);
        $this->assertCount(3, $records);
        $store->fetch_list();
        $records = $DB->get_records('totara_cloudfiledir_sync', []);
        $this->assertCount(2, $records);
        foreach ($records as $record) {
            $this->assertContains($record->contenthash, [$contenthash1, $contenthash2]);
            $this->assertSame($store->get_idnumber(), $record->idnumber);
            $this->assertNull($record->timedownloaded);
        }
    }

    public function test_reset_localproblem_flag() {
        global $CFG, $DB;

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $store = $stores['external'];
        $provider = $this->get_provider($config);

        $provider->clear_test_bucket();
        self::purge_local_filedir();
        $DB->delete_records('files', []);
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $content = 'grrrr';
        $contenthash = sha1($content);
        $contentfile = make_request_directory() . '/myfile.txt';
        file_put_contents($contentfile, $content);
        $streaminfo = function () use ($contentfile) {
            return [fopen($contentfile, 'r'), filesize($contentfile)];
        };
        $result = $store->write_content($contenthash, $streaminfo);
        $this->assertTrue($result);
        $record = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash, 'idnumber' => 'external']);
        $this->assertNotFalse($record);
        $record->localproblem = 1;
        $DB->update_record('totara_cloudfiledir_sync', $record);

        $store->reset_localproblem_flag();
        $this->assertSame('0', $DB->get_field('totara_cloudfiledir_sync', 'localproblem', ['id' => $record->id]));
    }

    public function test_push_changes() {
        global $CFG, $DB;

        $config = [
            'idnumber' => 'external',
            'add' => true,
            'delete' => true,
            'active' => true,
        ];
        $config = $this->prepare_store_config($config);
        $CFG->totara_cloudfiledir_stores = [$config];
        $stores = store::get_stores();
        $store = $stores['external'];
        $provider = $this->get_provider($config);

        $provider->clear_test_bucket();
        self::purge_local_filedir();
        $DB->delete_records('files', []);
        $DB->delete_records('totara_cloudfiledir_sync', []);

        $fs = get_file_storage();

        $content1 = 'haha1';
        $content2 = 'haha2';
        $content3 = 'haha3';
        $content4 = 'haha4';
        $content5 = 'haha5';
        $contenthash1 = sha1($content1);
        $contenthash2 = sha1($content2);
        $contenthash3 = sha1($content3);
        $contenthash4 = sha1($content4);
        $contenthash5 = sha1($content5);
        $contentfile1 = self::get_local_file($contenthash1);
        $contentfile2 = self::get_local_file($contenthash2);
        $contentfile3 = self::get_local_file($contenthash3);
        $contentfile4 = self::get_local_file($contenthash4);
        $contentfile5 = self::get_local_file($contenthash5);
        $dirhash = sha1('');
        $fs->add_string_to_pool('');

        $syscontext = context_system::instance();
        $file1 = get_file_storage()->create_file_from_string(
            ['contextid' => $syscontext->id, 'component' => 'totara_core', 'filearea' => 'testarea', 'itemid' => 0, 'filepath' => '/', 'filename' => 'test1.txt'],
            $content1);
        $file2 = get_file_storage()->create_file_from_string(
            ['contextid' => $syscontext->id, 'component' => 'totara_core', 'filearea' => 'testarea', 'itemid' => 0, 'filepath' => '/', 'filename' => 'test2.txt'],
            $content2);
        $file3 = get_file_storage()->create_file_from_string(
            ['contextid' => $syscontext->id, 'component' => 'totara_core', 'filearea' => 'testarea', 'itemid' => 0, 'filepath' => '/', 'filename' => 'test3.txt'],
            $content3);
        $file4 = get_file_storage()->create_file_from_string(
            ['contextid' => $syscontext->id, 'component' => 'totara_core', 'filearea' => 'testarea', 'itemid' => 0, 'filepath' => '/', 'filename' => 'test4.txt'],
            $content4);
        $file5 = get_file_storage()->create_file_from_string(
            ['contextid' => $syscontext->id, 'component' => 'totara_core', 'filearea' => 'testarea', 'itemid' => 0, 'filepath' => '/', 'filename' => 'test5.txt'],
            $content5);
        $cloudcontenthashes = iterator_to_array($provider->list_contents());
        sort($cloudcontenthashes);
        $expected = [$contenthash1, $contenthash2, $contenthash3, $contenthash4, $contenthash5, $dirhash];
        sort($expected);
        $this->assertSame($expected, $cloudcontenthashes);
        $syncs = $DB->get_records('totara_cloudfiledir_sync', ['idnumber' => 'external']);
        $this->assertCount(6, $syncs);

        // Make sure nothing changes if data ok.

        $store->push_changes(null);
        $cloudcontenthashes = iterator_to_array($provider->list_contents());
        sort($cloudcontenthashes);
        $this->assertSame($expected, $cloudcontenthashes);
        $syncs = $DB->get_records('totara_cloudfiledir_sync', ['idnumber' => 'external']);
        $this->assertCount(6, $syncs);

        // This is the test.

        $provider->delete_content($contenthash2);
        $store->delete_content($contenthash3);
        $DB->delete_records('files', ['id' => $file4->get_id()]);
        unlink($contentfile5);
        $DB->set_field('totara_cloudfiledir_sync', 'timeuploaded', null, ['contenthash' => $contenthash5, 'idnumber' => 'external']);

        $store->push_changes(null);
        $cloudcontenthashes = iterator_to_array($provider->list_contents());
        sort($cloudcontenthashes);
        $expected = [$contenthash1, $contenthash3, $contenthash5, $dirhash];
        sort($expected);
        $this->assertSame($expected, $cloudcontenthashes);
        $syncs = $DB->get_records('totara_cloudfiledir_sync', ['idnumber' => 'external']);
        $this->assertCount(5, $syncs);
        $record5 = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash5, 'idnumber' => 'external']);
        $this->assertSame('1', $record5->localproblem);
        $record4 = $DB->get_record('totara_cloudfiledir_sync', ['contenthash' => $contenthash4, 'idnumber' => 'external']);
        $this->assertFalse($record4);
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

    /**
     * Add content file to store.
     * @param string $filepath
     * @param store $store
     */
    protected function addd_file_to_store(string $filepath, store $store) {
        $streaminfo = function () use (&$filepath) {
            return [fopen($filepath, 'r'), filesize($filepath)];
        };
        $this->assertTrue($store->write_content(sha1_file($filepath), $streaminfo));
    }
}
