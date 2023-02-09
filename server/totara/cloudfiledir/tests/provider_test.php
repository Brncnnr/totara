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

defined('MOODLE_INTERNAL') || die();

/**
 * Cloud provider base.
 */
abstract class totara_cloudfiledir_provider_testcase extends advanced_testcase {
    /**
     * @return totara_cloudfiledir\local\provider\base
     */
    abstract protected function get_provider();

    public function test_test_connection() {
        $provider = $this->get_provider();
        $this->assertTrue($provider->is_ready());
        $this->assertTrue($provider->test_connection());
    }

    public function test_content_crud() {
        global $CFG;

        $logfile = "$CFG->dataroot/testlog.log";
        touch($logfile);
        $oldlog = ini_get('error_log');
        ini_set('error_log', $logfile);

        $provider = $this->get_provider();
        $this->assertTrue($provider->is_ready());
        $this->assertTrue($provider->test_connection());

        $content = 'some fancy content for testing';
        $contentfile = make_request_directory() . '/myfile.txt';
        file_put_contents($contentfile, $content);
        $contenthash = sha1_file($contentfile);
        $this->assertSame($contenthash, sha1($content));

        $this->assertTrue($provider->delete_content($contenthash));
        $this->assertFalse($provider->is_content_available($contenthash));

        $provider->upload_content($contenthash, $contentfile);
        $this->assertTrue($provider->is_content_available($contenthash));

        $newcontentfile = make_request_directory() . '/myfile2.txt';
        $this->assertTrue($provider->download_content($contenthash, $newcontentfile));
        $this->assertSame($contenthash, sha1_file($newcontentfile));

        $provider->upload_content($contenthash, $contentfile);
        $this->assertTrue($provider->is_content_available($contenthash));

        $this->assertTrue($provider->delete_content($contenthash));
        $this->assertFalse($provider->is_content_available($contenthash));

        $handle = fopen($contentfile, 'r');
        $provider->upload_content_stream($contenthash, $handle, filesize($contentfile));
        $this->assertTrue($provider->is_content_available($contenthash));

        unlink($newcontentfile);
        $this->assertTrue($provider->download_content($contenthash, $newcontentfile));
        $this->assertSame($contenthash, sha1_file($newcontentfile));

        $this->assertTrue($provider->delete_content($contenthash));
        $this->assertFalse($provider->is_content_available($contenthash));

        $this->assertTrue($provider->delete_content($contenthash));
        $this->assertFalse($provider->is_content_available($contenthash));

        ini_set('error_log', $oldlog);
        $this->assertSame('', file_get_contents($logfile));

        ini_set('error_log', $logfile);
        $this->assertFalse($provider->download_content($contenthash, $newcontentfile));
        ini_set('error_log', $oldlog);
        $this->assertStringContainsString('cloudfiledir store error [test]: Cannot download content file ' . $contenthash, file_get_contents($logfile));
        @unlink($logfile);
    }

    public function test_list_contents() {
        $contentfile1 = make_request_directory() . '/myfile1.txt';
        file_put_contents($contentfile1, 'some fancy content for testing');
        $contenthash1 = sha1_file($contentfile1);

        $contentfile2 = make_request_directory() . '/myfile2.txt';
        file_put_contents($contentfile2, 'some no so fance content for testing');
        $contenthash2 = sha1_file($contentfile2);

        $contenthash3 = sha1('deleted content');

        $provider = $this->get_provider();

        $provider->upload_content($contenthash1, $contentfile1);
        $provider->upload_content($contenthash2, $contentfile2);
        $provider->delete_content($contenthash3);

        $contenthashes = $provider->list_contents();

        $contenthashes = iterator_to_array($contenthashes);

        $this->assertContains($contenthash1, $contenthashes);
        $this->assertContains($contenthash2, $contenthashes);
        $this->assertNotContainsEquals($contenthash3, $contenthashes);
    }

    public function test_create_download_link() {
        $contentfile1 = make_request_directory() . '/myfile1.txt';
        file_put_contents($contentfile1, 'some fancy content for testing');
        $contenthash1 = sha1_file($contentfile1);

        $contentfile2 = make_request_directory() . '/myfile2.txt';
        file_put_contents($contentfile2, 'some no so fance content for testing');
        $contenthash2 = sha1_file($contentfile2);

        $provider = $this->get_provider();

        $provider->upload_content($contenthash1, $contentfile1);
        $provider->upload_content($contenthash2, $contentfile2);

        $link = $provider->create_download_link($contenthash1);
        if ($provider instanceof \totara_cloudfiledir\local\provider\azure) {
            $this->assertNull($link);
            $this->markTestSkipped('Azure does not supoprt download links yet');
        }
        $this->assertNotNull($link);
        $content = download_file_content($link);
        $this->assertSame(file_get_contents($contentfile1), $content);
    }

    public function test_clear_test_bucket() {
        $contentfile1 = make_request_directory() . '/myfile1.txt';
        file_put_contents($contentfile1, 'some fancy content for testing');
        $contenthash1 = sha1_file($contentfile1);

        $contentfile2 = make_request_directory() . '/myfile2.txt';
        file_put_contents($contentfile2, 'some no so fance content for testing');
        $contenthash2 = sha1_file($contentfile2);

        $provider = $this->get_provider();

        $provider->clear_test_bucket();
        $contenthashes = $provider->list_contents();
        $contenthashes = iterator_to_array($contenthashes);
        $this->assertSame([], $contenthashes);

        $provider->upload_content($contenthash1, $contentfile1);
        $provider->upload_content($contenthash2, $contentfile2);
        $contenthashes = $provider->list_contents();
        $contenthashes = iterator_to_array($contenthashes);
        $expected = [$contenthash1, $contenthash2];
        asort($contenthashes);
        asort($expected);
        $this->assertSame($expected, $contenthashes);

        $provider->clear_test_bucket();
        $contenthashes = $provider->list_contents();
        $contenthashes = iterator_to_array($contenthashes);
        $this->assertSame([], $contenthashes);
    }
}
