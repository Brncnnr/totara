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

use totara_extfiledir\local\store;

defined('MOODLE_INTERNAL') || die();

/**
 * Test hook integration of external filedir plugin.
 */
final class totara_extfiledir_hook_testcase extends advanced_testcase {
    public function test_fixtures() {
        $content = 'haha';
        $contenthash = sha1($content);

        self::purge_local_filedir();
        $this->assertFileDoesNotExist(self::get_local_file($contenthash));

        get_file_storage()->add_string_to_pool($content);
        $this->assertFileExists(self::get_local_file($contenthash));

        self::purge_local_filedir();
        $this->assertFileDoesNotExist(self::get_local_file($contenthash));
    }

    public function test_content_adding() {
        global $CFG;

        mkdir($CFG->dataroot . '/extfiledir1/');
        mkdir($CFG->dataroot . '/extfiledir2/');
        mkdir($CFG->dataroot . '/extfiledir3/');
        mkdir($CFG->dataroot . '/extfiledir4/');

        $CFG->totara_extfiledir_stores = [
            [
                'idnumber' => 'external1',
                'description' => 'First test external store',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'add' => true,
                'active' => true,
            ],
            [
                'idnumber' => 'external2',
                'filedir' => $CFG->dataroot . '/extfiledir2',
                'add' => false,
                'active' => true,
            ],
            [
                'idnumber' => 'external3',
                'filedir' => $CFG->dataroot . '/extfiledir3/',
                'add' => true,
                'active' => false,
            ],
            [
                'idnumber' => 'external4',
                'description' => 'External store that already has file',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'add' => true,
                'active' => true,
            ],
        ];

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        $extfile1 = $CFG->dataroot . '/extfiledir1/' . store::get_relative_filepath($contenthash);
        $extfile2 = $CFG->dataroot . '/extfiledir2/' . store::get_relative_filepath($contenthash);
        $extfile3 = $CFG->dataroot . '/extfiledir3/' . store::get_relative_filepath($contenthash);
        $extfile4 = $CFG->dataroot . '/extfiledir4/' . store::get_relative_filepath($contenthash);

        self::purge_local_filedir();
        $this->assertFileDoesNotExist($contentfile);
        $this->assertFileDoesNotExist($extfile1);
        $this->assertFileDoesNotExist($extfile2);
        $this->assertFileDoesNotExist($extfile3);
        @mkdir(dirname($extfile4), $CFG->directorypermissions, true);
        file_put_contents($extfile4, $content);

        get_file_storage()->add_string_to_pool($content);
        $this->assertFileExists($contentfile);
        $this->assertFileExists($extfile1);
        $this->assertFileDoesNotExist($extfile2);
        $this->assertFileDoesNotExist($extfile3);
        $this->assertFileExists($extfile4);
    }

    public function test_content_deleting() {
        global $CFG;

        mkdir($CFG->dataroot . '/extfiledir1/');
        mkdir($CFG->dataroot . '/extfiledir2/');
        mkdir($CFG->dataroot . '/extfiledir3/');
        mkdir($CFG->dataroot . '/extfiledir4/');

        $CFG->totara_extfiledir_stores = [
            [
                'idnumber' => 'external1',
                'description' => 'First test external store',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'delete' => true,
                'active' => true,
            ],
            [
                'idnumber' => 'external2',
                'filedir' => $CFG->dataroot . '/extfiledir2',
                'delete' => false,
                'active' => true,
            ],
            [
                'idnumber' => 'external3',
                'filedir' => $CFG->dataroot . '/extfiledir3/',
                'delete' => true,
                'active' => false,
            ],
            [
                'idnumber' => 'external4',
                'description' => 'External store without file',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'delete' => true,
                'active' => true,
            ],
        ];

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        $trashfile = self::get_local_trash($contenthash);
        $extfile1 = $CFG->dataroot . '/extfiledir1/' . store::get_relative_filepath($contenthash);
        $extfile2 = $CFG->dataroot . '/extfiledir2/' . store::get_relative_filepath($contenthash);
        $extfile3 = $CFG->dataroot . '/extfiledir3/' . store::get_relative_filepath($contenthash);
        $extfile4 = $CFG->dataroot . '/extfiledir4/' . store::get_relative_filepath($contenthash);

        self::purge_local_filedir();
        @mkdir(dirname($contentfile), $CFG->directorypermissions, true);
        @mkdir(dirname($extfile1), $CFG->directorypermissions, true);
        @mkdir(dirname($extfile2), $CFG->directorypermissions, true);
        @mkdir(dirname($extfile3), $CFG->directorypermissions, true);
        @mkdir(dirname($trashfile), $CFG->directorypermissions, true);
        file_put_contents($contentfile, $content);
        file_put_contents($extfile1, $content);
        file_put_contents($extfile2, $content);
        file_put_contents($extfile3, $content);
        $this->assertFileDoesNotExist($extfile4);
        $this->assertFileDoesNotExist($trashfile);

        get_file_storage()->deleted_file_cleanup($contenthash);
        $this->assertFileExists($trashfile); // Trash file is kept unless there is a restore option in ext filedir.
        $this->assertFileDoesNotExist($contentfile);
        $this->assertFileDoesNotExist($extfile1);
        $this->assertFileExists($extfile2);
        $this->assertFileExists($extfile3);
        $this->assertFileDoesNotExist($extfile4);
    }

    public function test_content_restore() {
        global $CFG;

        mkdir($CFG->dataroot . '/extfiledir1/');
        mkdir($CFG->dataroot . '/extfiledir2/');
        mkdir($CFG->dataroot . '/extfiledir3/');
        mkdir($CFG->dataroot . '/extfiledir4/');

        $CFG->totara_extfiledir_stores = [
            [
                'idnumber' => 'external1',
                'description' => 'First test external store',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'restore' => true,
                'active' => true,
            ],
            [
                'idnumber' => 'external2',
                'filedir' => $CFG->dataroot . '/extfiledir2',
                'restore' => false,
                'active' => true,
            ],
            [
                'idnumber' => 'external3',
                'filedir' => $CFG->dataroot . '/extfiledir3/',
                'restore' => true,
                'active' => false,
            ],
            [
                'idnumber' => 'external4',
                'filedir' => $CFG->dataroot . '/extfiledir4/',
                'add' => true,
                'restore' => false,
                'active' => true,
            ],
        ];

        $content1 = 'haha1';
        $content2 = 'haha2';
        $content3 = 'haha3';
        $contenthash1 = sha1($content1);
        $contenthash2 = sha1($content2);
        $contenthash3 = sha1($content3);
        $contentfile1 = self::get_local_file($contenthash1);
        $contentfile2 = self::get_local_file($contenthash2);
        $contentfile3 = self::get_local_file($contenthash3);
        $extfile1 = $CFG->dataroot . '/extfiledir1/' . store::get_relative_filepath($contenthash1);
        $extfile2 = $CFG->dataroot . '/extfiledir2/' . store::get_relative_filepath($contenthash2);
        $extfile3 = $CFG->dataroot . '/extfiledir3/' . store::get_relative_filepath($contenthash3);

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

        self::purge_local_filedir();
        @mkdir(dirname($extfile1), $CFG->directorypermissions, true);
        @mkdir(dirname($extfile2), $CFG->directorypermissions, true);
        @mkdir(dirname($extfile3), $CFG->directorypermissions, true);
        file_put_contents($extfile1, $content1);
        file_put_contents($extfile2, $content2);
        file_put_contents($extfile3, $content3);
        $this->assertFileDoesNotExist($contentfile1);
        $this->assertFileDoesNotExist($contentfile2);
        $this->assertFileDoesNotExist($contentfile3);
        remove_dir($CFG->dataroot . '/extfiledir4', true);
        $this->assertFileDoesNotExist($CFG->dataroot . '/extfiledir4/' . store::get_relative_filepath($contenthash1));
        $this->assertFileDoesNotExist($CFG->dataroot . '/extfiledir4/' . store::get_relative_filepath($contenthash2));
        $this->assertFileDoesNotExist($CFG->dataroot . '/extfiledir4/' . store::get_relative_filepath($contenthash3));

        $this->assertTrue(get_file_storage()->try_content_recovery($file1));
        $this->assertFileExists($contentfile1);

        $this->assertFalse(get_file_storage()->try_content_recovery($file2));
        $this->assertFileDoesNotExist($contentfile2);

        $this->assertFalse(get_file_storage()->try_content_recovery($file3));
        $this->assertFileDoesNotExist($contentfile3);

        $this->assertFileExists($CFG->dataroot . '/extfiledir4/' . store::get_relative_filepath($contenthash1));
        $this->assertFileDoesNotExist($CFG->dataroot . '/extfiledir4/' . store::get_relative_filepath($contenthash2));
        $this->assertFileDoesNotExist($CFG->dataroot . '/extfiledir4/' . store::get_relative_filepath($contenthash3));
    }

    public function test_content_deleting_with_instant_trash_purge() {
        global $CFG;

        mkdir($CFG->dataroot . '/extfiledir1/');
        mkdir($CFG->dataroot . '/extfiledir2/');

        $CFG->totara_extfiledir_stores = [
            [
                'idnumber' => 'external1',
                'description' => 'First test external store',
                'filedir' => $CFG->dataroot . '/extfiledir1/',
                'delete' => true,
                'restore' => false,
                'active' => true,
            ],
            [
                'idnumber' => 'external2',
                'filedir' => $CFG->dataroot . '/extfiledir2',
                'delete' => false,
                'restore' => true,
                'active' => true,
            ],
        ];

        $content = 'haha';
        $contenthash = sha1($content);
        $contentfile = self::get_local_file($contenthash);
        $trashfile = self::get_local_trash($contenthash);
        $extfile1 = $CFG->dataroot . '/extfiledir1/' . store::get_relative_filepath($contenthash);
        $extfile2 = $CFG->dataroot . '/extfiledir2/' . store::get_relative_filepath($contenthash);

        self::purge_local_filedir();
        @mkdir(dirname($contentfile), $CFG->directorypermissions, true);
        @mkdir(dirname($extfile1), $CFG->directorypermissions, true);
        @mkdir(dirname($extfile2), $CFG->directorypermissions, true);
        @mkdir(dirname($trashfile), $CFG->directorypermissions, true);
        file_put_contents($contentfile, $content);
        file_put_contents($extfile1, $content);
        file_put_contents($extfile2, $content);
        $this->assertFileDoesNotExist($trashfile);

        get_file_storage()->deleted_file_cleanup($contenthash);
        $this->assertFileDoesNotExist($trashfile); // This is what we are testing here.
        $this->assertFileDoesNotExist($contentfile);
        $this->assertFileDoesNotExist($extfile1);
        $this->assertFileExists($extfile2);
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

        return $CFG->dataroot . '/filedir/' . store::get_relative_filepath($contenthash);
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

        return $CFG->dataroot . '/trashdir/' . store::get_relative_filepath($contenthash);
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
