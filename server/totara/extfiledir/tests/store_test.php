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
 * @package totara_extfiledir
 */

use \totara_extfiledir\local\store;

defined('MOODLE_INTERNAL') || die();

/**
 * External filedir store class.
 */
final class totara_extfiledir_store_testcase extends advanced_testcase {
    public function test_get_stores() {
        global $CFG;

        $this->assertObjectNotHasAttribute('totara_extfiledir', $CFG);

        mkdir($CFG->dataroot . '/extfiledir1/');
        mkdir($CFG->dataroot . '/extfiledir2/');
        mkdir($CFG->dataroot . '/extfiledir3/');
        mkdir($CFG->dataroot . '/extfiledir4/');
        mkdir($CFG->dataroot . '/extfiledir5/');
        mkdir($CFG->dataroot . '/extfiledir6/');
        mkdir($CFG->dataroot . '/extfiledir7/');

        $CFG->totara_extfiledir_stores = [
            [
                'idnumber' => 'external1',
                'description' => 'First test external store',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'add' => true,
                'delete' => true,
                'restore' => true,
                'directorypermissions' => 0700,
                'filepermissions' => 0600,
                'active' => true,
            ],
            [
                'idnumber' => 'external2',
                'filedir' => $CFG->dataroot . '/extfiledir2',
                'add' => false,
                'delete' => true,
                'restore' => true,
            ],
            [
                'idnumber' => 'external3',
                'filedir' => $CFG->dataroot . '/extfiledir3/',
                'add' => true,
                'delete' => false,
                'restore' => true,
                'active' => true,
            ],
            [
                'idnumber' => 'external4',
                'filedir' => $CFG->dataroot . '/extfiledir4/',
                'add' => false,
                'delete' => false,
                'restore' => true,
                'active' => true,
            ],
            [
                'idnumber' => 'external5',
                'filedir' => $CFG->dataroot . '/extfiledir5/',
                'add' => false,
                'delete' => false,
                'restore' => false,
                'active' => true,
            ],
            [
                'idnumber' => 'external6',
                'filedir' => $CFG->dataroot . '/extfiledirX/',
                'add' => true,
                'delete' => true,
                'restore' => true,
                'active' => true,
            ],
            [
                'idnumber' => 'external7',
                'filedir' => $CFG->dataroot . '/extfiledir7/',
                'active' => true,
            ],
            [
                'idnumber' => 'external8',
                'add' => true,
                'delete' => true,
                'restore' => true,
                'active' => true,
            ],
            [
                'filedir' => $CFG->dataroot . '/extfiledir9/',
                'add' => true,
                'delete' => true,
                'restore' => true,
                'active' => true,
            ],
        ];

        $logfile = "$CFG->dataroot/testlog.log";
        touch($logfile);
        $oldlog = ini_get('error_log');
        ini_set('error_log', $logfile);
        $stores = store::get_stores();
        ini_set('error_log', $oldlog);

        $this->assertCount(7, $stores);
        $this->assertSame(['external1', 'external2', 'external3', 'external4', 'external5', 'external6', 'external7'], array_keys($stores));

        $this->assertInstanceOf(store::class, $stores['external1']);
        $this->assertSame('external1', $stores['external1']->get_idnumber());
        $this->assertSame('First test external store', $stores['external1']->get_description());
        $this->assertTrue($stores['external1']->add_enabled());
        $this->assertTrue($stores['external1']->delete_enabled());
        $this->assertTrue($stores['external1']->restore_enabled());
        $this->assertTrue($stores['external1']->is_active());
        $this->assertSame(0700, $stores['external1']->get_directorypermissions());
        $this->assertSame(0600, $stores['external1']->get_filepermissions());
        $this->assertSame($CFG->dataroot . '/extfiledir1', $stores['external1']->get_filedir());

        $this->assertInstanceOf(store::class, $stores['external2']);
        $this->assertSame('external2', $stores['external2']->get_idnumber());
        $this->assertSame('', $stores['external2']->get_description());
        $this->assertFalse($stores['external2']->add_enabled());
        $this->assertTrue($stores['external2']->delete_enabled());
        $this->assertTrue($stores['external2']->restore_enabled());
        $this->assertFalse($stores['external2']->is_active());
        $this->assertSame((int)$CFG->directorypermissions, $stores['external2']->get_directorypermissions());
        $this->assertSame((int)$CFG->filepermissions, $stores['external2']->get_filepermissions());
        $this->assertSame($CFG->dataroot . '/extfiledir2', $stores['external2']->get_filedir());

        $this->assertInstanceOf(store::class, $stores['external3']);
        $this->assertSame('external3', $stores['external3']->get_idnumber());
        $this->assertSame('', $stores['external3']->get_description());
        $this->assertTrue($stores['external3']->add_enabled());
        $this->assertFalse($stores['external3']->delete_enabled());
        $this->assertTrue($stores['external3']->restore_enabled());
        $this->assertTrue($stores['external3']->is_active());
        $this->assertSame((int)$CFG->directorypermissions, $stores['external3']->get_directorypermissions());
        $this->assertSame((int)$CFG->filepermissions, $stores['external3']->get_filepermissions());
        $this->assertSame($CFG->dataroot . '/extfiledir3', $stores['external3']->get_filedir());

        $this->assertInstanceOf(store::class, $stores['external4']);
        $this->assertSame('external4', $stores['external4']->get_idnumber());
        $this->assertSame('', $stores['external4']->get_description());
        $this->assertFalse($stores['external4']->add_enabled());
        $this->assertFalse($stores['external4']->delete_enabled());
        $this->assertTrue($stores['external4']->restore_enabled());
        $this->assertTrue($stores['external4']->is_active());
        $this->assertSame((int)$CFG->directorypermissions, $stores['external4']->get_directorypermissions());
        $this->assertSame((int)$CFG->filepermissions, $stores['external4']->get_filepermissions());
        $this->assertSame($CFG->dataroot . '/extfiledir4', $stores['external4']->get_filedir());

        $this->assertInstanceOf(store::class, $stores['external5']);
        $this->assertSame('external5', $stores['external5']->get_idnumber());
        $this->assertSame('', $stores['external5']->get_description());
        $this->assertFalse($stores['external5']->add_enabled());
        $this->assertFalse($stores['external5']->delete_enabled());
        $this->assertFalse($stores['external5']->restore_enabled());
        $this->assertTrue($stores['external5']->is_active());
        $this->assertSame((int)$CFG->directorypermissions, $stores['external5']->get_directorypermissions());
        $this->assertSame((int)$CFG->filepermissions, $stores['external5']->get_filepermissions());
        $this->assertSame($CFG->dataroot . '/extfiledir5', $stores['external5']->get_filedir());

        $this->assertInstanceOf(store::class, $stores['external6']);
        $this->assertSame('external6', $stores['external6']->get_idnumber());
        $this->assertSame('', $stores['external6']->get_description());
        $this->assertTrue($stores['external6']->add_enabled());
        $this->assertTrue($stores['external6']->delete_enabled());
        $this->assertTrue($stores['external6']->restore_enabled());
        $this->assertFalse($stores['external6']->is_active());
        $this->assertSame((int)$CFG->directorypermissions, $stores['external6']->get_directorypermissions());
        $this->assertSame((int)$CFG->filepermissions, $stores['external6']->get_filepermissions());
        $this->assertSame($CFG->dataroot . '/extfiledirX', $stores['external6']->get_filedir());

        $this->assertInstanceOf(store::class, $stores['external7']);
        $this->assertSame('external7', $stores['external7']->get_idnumber());
        $this->assertSame('', $stores['external7']->get_description());
        $this->assertFalse($stores['external7']->add_enabled());
        $this->assertFalse($stores['external7']->delete_enabled());
        $this->assertFalse($stores['external7']->restore_enabled());
        $this->assertTrue($stores['external7']->is_active());
        $this->assertSame((int)$CFG->directorypermissions, $stores['external7']->get_directorypermissions());
        $this->assertSame((int)$CFG->filepermissions, $stores['external7']->get_filepermissions());
        $this->assertSame($CFG->dataroot . '/extfiledir7', $stores['external7']->get_filedir());

        $this->assertStringContainsString('External filedir storage config \'external6\' contains invalid directory', file_get_contents($logfile));
    }

    public function test_write_content() {
        global $CFG;

        mkdir($CFG->dataroot . '/extfiledir1/');
        mkdir($CFG->dataroot . '/extfiledir2/');
        mkdir($CFG->dataroot . '/extfiledir3/');
        $CFG->totara_extfiledir_stores = [
            [
                'idnumber' => 'external1',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'add' => true,
                'active' => true,
            ],
            [
                'idnumber' => 'external2',
                'filedir' => $CFG->dataroot . '/extfiledir2/',
                'add' => false,
                'active' => true,
            ],
            [
                'idnumber' => 'external3',
                'filedir' => $CFG->dataroot . '/extfiledir3/',
                'add' => true,
                'active' => false,
            ],
        ];

        $logfile = "$CFG->dataroot/testlog.log";
        touch($logfile);
        $oldlog = ini_get('error_log');
        ini_set('error_log', $logfile);

        $content = 'test test test';
        $contenthash = sha1($content);
        $contentfile = $CFG->dataroot . '/test';
        file_put_contents($contentfile, $content);

        $filewriter = function (string $targetfile) use ($contentfile) {
            return copy($contentfile, $targetfile);
        };

        $store = store::get_stores()['external1'];
        $relativepath = $store::get_relative_filepath($contenthash);
        $resultfile = $store->get_filedir() . '/' . $relativepath;

        $this->assertFileDoesNotExist($resultfile);
        $success = $store->write_content($contenthash, $filewriter);
        $this->assertTrue($success);
        $this->assertFileExists($resultfile);
        $this->assertSame($content, file_get_contents($resultfile));

        // Second write should succeed too.
        $success = $store->write_content($contenthash, $filewriter);
        $this->assertTrue($success);
        $this->assertFileExists($resultfile);
        $this->assertSame($content, file_get_contents($resultfile));

        // Respect 'add' setting.
        $store = store::get_stores()['external2'];
        $relativepath = $store::get_relative_filepath($contenthash);
        $resultfile = $store->get_filedir() . '/' . $relativepath;
        $this->assertFileDoesNotExist($resultfile);
        $success = $store->write_content($contenthash, $filewriter);
        $this->assertFalse($success);
        $this->assertFileDoesNotExist($resultfile);

        // Respect store disabling setting.
        $store = store::get_stores()['external3'];
        $relativepath = $store::get_relative_filepath($contenthash);
        $resultfile = $store->get_filedir() . '/' . $relativepath;
        $this->assertFileDoesNotExist($resultfile);
        $success = $store->write_content($contenthash, $filewriter);
        $this->assertFalse($success);
        $this->assertFileDoesNotExist($resultfile);

        ini_set('error_log', $oldlog);
        $this->assertSame('', file_get_contents($logfile));
    }

    public function test_read_content() {
        global $CFG;

        mkdir($CFG->dataroot . '/extfiledir1/');
        $CFG->totara_extfiledir_stores = [
            [
                'idnumber' => 'external1',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'active' => true,
            ],
            [
                'idnumber' => 'external2',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'active' => false,
            ],
        ];
        $resultfile = $CFG->dataroot . '/test.result';

        $filereader = function (string $file) use ($resultfile) {
            return copy($file, $resultfile);
        };

        $content = 'test test test';
        $contenthash = sha1($content);
        $contentfile = $CFG->dataroot . '/extfiledir1/' . store::get_relative_filepath($contenthash);
        mkdir(dirname($contentfile), 0777, true);
        file_put_contents($contentfile, $content);

        // Regular store.
        $store = store::get_stores()['external1'];
        $this->assertFileDoesNotExist($resultfile);
        $success = $store->read_content($contenthash, $filereader);
        $this->assertTrue($success);
        $this->assertFileExists($resultfile);
        $this->assertSame($content, file_get_contents($resultfile));

        @unlink($resultfile);
        $success = $store->read_content(sha1('test'), $filereader);
        $this->assertFalse($success);
        $this->assertFileDoesNotExist($resultfile);

        // Respect store disabling setting.
        @unlink($resultfile);
        $store = store::get_stores()['external2'];
        $success = $store->read_content($contenthash, $filereader);
        $this->assertFalse($success);
        $this->assertFileDoesNotExist($resultfile);
    }

    public function test_is_content_available() {
        global $CFG;

        mkdir($CFG->dataroot . '/extfiledir1/');
        $CFG->totara_extfiledir_stores = [
            [
                'idnumber' => 'external1',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'active' => true,
            ],
            [
                'idnumber' => 'external2',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'active' => false,
            ],
        ];

        $content = 'test test test';
        $contenthash = sha1($content);
        $contentfile = $CFG->dataroot . '/extfiledir1/' . store::get_relative_filepath($contenthash);
        mkdir(dirname($contentfile), 0777, true);
        file_put_contents($contentfile, $content);

        // Regular enabled store.
        $store = store::get_stores()['external1'];
        $this->assertTrue($store->is_content_available($contenthash));
        $this->assertFalse($store->is_content_available(sha1('test')));

        // Respect store disabling setting.
        $store = store::get_stores()['external2'];
        $this->assertFalse($store->is_content_available($contenthash));
    }

    public function test_delete_content() {
        global $CFG;

        mkdir($CFG->dataroot . '/extfiledir1/');
        mkdir($CFG->dataroot . '/extfiledir2/');
        mkdir($CFG->dataroot . '/extfiledir3/');
        $CFG->totara_extfiledir_stores = [
            [
                'idnumber' => 'external1',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'delete' => true,
                'active' => true,
            ],
            [
                'idnumber' => 'external2',
                'filedir' => $CFG->dataroot . '/extfiledir2/',
                'delete' => false,
                'active' => true,
            ],
            [
                'idnumber' => 'external3',
                'filedir' => $CFG->dataroot . '/extfiledir3/',
                'delete' => false,
                'active' => true,
            ],
        ];

        $content = 'test test test';
        $contenthash = sha1($content);
        $contentfile1 = $CFG->dataroot . '/extfiledir1/' . store::get_relative_filepath($contenthash);
        mkdir(dirname($contentfile1), 0777, true);
        file_put_contents($contentfile1, $content);

        $contentfile2 = $CFG->dataroot . '/extfiledir2/' . store::get_relative_filepath($contenthash);
        mkdir(dirname($contentfile2), 0777, true);
        file_put_contents($contentfile2, $content);

        $contentfile3 = $CFG->dataroot . '/extfiledir3/' . store::get_relative_filepath($contenthash);
        mkdir(dirname($contentfile3), 0777, true);
        file_put_contents($contentfile3, $content);

        $store = store::get_stores()['external1'];
        $this->assertTrue($store->delete_content($contenthash));
        $this->assertFileDoesNotExist($contentfile1);

        $store = store::get_stores()['external2'];
        $this->assertFalse($store->delete_content($contenthash));
        $this->assertFileExists($contentfile2);

        $store = store::get_stores()['external3'];
        $this->assertFalse($store->delete_content($contenthash));
        $this->assertFileExists($contentfile3);
    }
}
