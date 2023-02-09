<?php
/**
 * This file is part of Totara TXP
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package repository_upload
 */

class repository_upload_lib_testcase extends advanced_testcase {

    public static function setUpBeforeClass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/repository/upload/lib.php');
    }

    private static function get_repository_instance(): repository_upload {
        global $DB;
        $id = $DB->get_field_sql(
            'SELECT ri.id
                   FROM "ttr_repository_instances" ri
                   JOIN "ttr_repository" r ON r.id = ri.typeid
                  WHERE r.type = :name',
            ['name' => 'upload']
        );
        /** @var repository_upload $repo */
        $repo = repository::get_instance($id);
        return $repo;
    }

    public function test_prepare_mimetype_whitelist_accepted_types() {
        self::assertEmpty(get_config('repository_upload', 'mimetype_whitelist'));
        $repository = self::get_repository_instance();

        // First test basic preferred types.
        self::assertCount(0, $repository->prepare_mimetype_whitelist([]));
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png']));
        self::assertSame(['image/png', 'image/jpeg'], $repository->prepare_mimetype_whitelist(['image/png', 'image/jpeg']));
        self::assertSame(['image/png', 'image/jpeg', 'application/zip'], $repository->prepare_mimetype_whitelist(['image/png', 'image/jpeg', 'application/zip']));
        self::assertSame(['image/png', 'image/jpeg', 'application/zip'], $repository->prepare_mimetype_whitelist(['image/png', 'image/jpeg', 'application/zip', 'application/zip']));

        // This method doesn't normalise *, it expects normalised mimetypes.
        self::assertSame(['image/png', '*'], $repository->prepare_mimetype_whitelist(['image/png', '*']));

        // Finally some bogus mimetypes
        self::assertSame(['image/png', 'astrophysics'], $repository->prepare_mimetype_whitelist(['image/png', 'astrophysics']));
        self::assertSame(['image/png', '42'], $repository->prepare_mimetype_whitelist(['image/png', 42]));
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', null]));
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', false]));
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', 0]));
    }

    public function test_prepare_mimetype_whitelist_user_defined_whitelist() {
        $repository = self::get_repository_instance();

        // Confirm that the default whitelist is now restricted.
        set_config('mimetype_whitelist', "image/png\napplication/zip", 'upload');
        self::assertSame(['image/png', 'application/zip'], $repository->prepare_mimetype_whitelist([]));

        set_config('mimetype_whitelist', "image/png", 'upload');
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist([]));

        set_config('mimetype_whitelist', "image/png\n", 'upload');
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist([]));

        set_config('mimetype_whitelist', "image/png\n\napplication/zip", 'upload');
        self::assertSame(['image/png', 'application/zip'], $repository->prepare_mimetype_whitelist([]));

        set_config('mimetype_whitelist', "image/png\n \napplication/zip", 'upload');
        self::assertSame(['image/png', 'application/zip'], $repository->prepare_mimetype_whitelist([]));

        set_config('mimetype_whitelist', "image/png\n0\napplication/zip", 'upload');
        self::assertSame(['image/png', 'application/zip'], $repository->prepare_mimetype_whitelist([]));

        set_config('mimetype_whitelist', "image/png\nfalse\napplication/zip", 'upload');
        self::assertSame(['image/png', 'false', 'application/zip'], $repository->prepare_mimetype_whitelist([]));

        set_config('mimetype_whitelist', "image/png\nnull\napplication/zip", 'upload');
        self::assertSame(['image/png', 'null', 'application/zip'], $repository->prepare_mimetype_whitelist([]));

        set_config('mimetype_whitelist', "image/png\nastrophysics\napplication/zip", 'upload');
        self::assertSame(['image/png', 'astrophysics', 'application/zip'], $repository->prepare_mimetype_whitelist([]));
    }

    public function test_prepare_mimetype_whitelist_blended_whitelist_and_accepted_types() {
        $repository = self::get_repository_instance();

        set_config('mimetype_whitelist', "image/png\napplication/zip", 'upload');

        // Confirm that the default whitelist is now restricted.
        self::assertCount(2, $repository->prepare_mimetype_whitelist([]));

        // Now confirm that preferred types are limited to expected types.
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png']));
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', 'image/jpeg']));
        self::assertSame(['image/png', 'application/zip'], $repository->prepare_mimetype_whitelist(['image/png', 'image/jpeg', 'application/zip']));
        self::assertSame(['image/png', 'application/zip'], $repository->prepare_mimetype_whitelist(['image/png', 'image/jpeg', 'application/zip', 'application/zip']));

        // This method doesn't normalise *, it expects normalised mimetypes.
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', '*']));

        // Finally some bogus mimetypes
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', 'astrophysics']));
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', 42]));
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', null]));
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', false]));
        self::assertSame(['image/png'], $repository->prepare_mimetype_whitelist(['image/png', 0]));

        set_config('mimetype_whitelist', "image/png\nastrophysics\napplication/zip", 'upload');
        self::assertSame(['image/png', 'astrophysics'], $repository->prepare_mimetype_whitelist(['image/png', 'astrophysics']));

        // Now where there is no cross over. Upload is essentially not supported.
        set_config('mimetype_whitelist', "image/png", 'upload');
        self::assertSame(['upload/notsupported'], $repository->prepare_mimetype_whitelist(['image/jpeg']));
    }

    public function test_normalise_accepted_extensions_to_mimetypes() {
        // Empties
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes([]));
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes(''));
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes(null));
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes(false));
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes(0));

        // Old default
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes('*'));

        // Some legit use cases
        self::assertSame(['image/png'], repository_upload::normalise_accepted_extensions_to_mimetypes('.png'));
        self::assertSame(['image/png'], repository_upload::normalise_accepted_extensions_to_mimetypes(['.png']));
        self::assertSame(['image/png'], repository_upload::normalise_accepted_extensions_to_mimetypes('astrophysics.png'));
        self::assertSame(['image/png', 'image/jpeg'], repository_upload::normalise_accepted_extensions_to_mimetypes(['.png', '.jpg']));
        self::assertSame(['image/png', 'image/jpeg'], repository_upload::normalise_accepted_extensions_to_mimetypes(['.png', '.jpg']));
        self::assertSame(['image/png', 'image/jpeg'], repository_upload::normalise_accepted_extensions_to_mimetypes(['.png', '.jpg', '.jpeg']));

        // Only extensions are accepted.
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes('png'));
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes('image/png'));
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes('astrophysics'));

        // Wildcards are respected.
        self::assertSame([], repository_upload::normalise_accepted_extensions_to_mimetypes(['.png', '*']));

        // Just confirm that the unknown mimetype has not changed.
        self::assertSame('document/unknown', mimeinfo('type', 'document/unknown'));
    }

    public function test_get_type_option_names() {
        self::assertSame(['pluginname', 'mimetype_whitelist'], repository_upload::get_type_option_names());
    }

    public function test_type_config_form() {
        $form = new MoodleQuickForm('test', 'POST', new moodle_url('test_repository_upload_lib.php'));
        \repository_upload::type_config_form($form);

        // Check the mimetype_whitelist option has been added.
        self::assertTrue($form->elementExists('mimetype_whitelist'));
    }

    public function test_type_form_validation() {
        self::assertSame([], \repository_upload::type_form_validation(null, [], []));
        self::assertSame(['astrophysics'], \repository_upload::type_form_validation(null, [], ['astrophysics']));
        self::assertSame(['mimetype_whitelist' => 'Fail'], \repository_upload::type_form_validation(null, [], ['mimetype_whitelist' => 'Fail']));

        $valid_mimetypes = [
            'text/yaml',
            'application/yang',
            'application/yin+xml',
            'application/vnd.zul',
            'application/zip',
            'application/vnd.handheld-entertainment+xml',
            'application/vnd.zzazz.deck+xml',
            '*/*',
        ];
        foreach ($valid_mimetypes as $mimetype) {
            self::assertSame([], \repository_upload::type_form_validation(null, ['mimetype_whitelist' => $mimetype], []));
        }

        $invalid_mimetypes = [
            '*',
            'astrophysics',
        ];
        foreach ($invalid_mimetypes as $mimetype) {
            self::assertSame(
                [
                    'mimetype_whitelist' => get_string(
                        'mimetype_whitelist_validation_errors',
                        'repository_upload',
                        "\n * " . get_string('mimetype_whitelist_validation_invalid_type', 'repository_upload', $mimetype)
                    )
                ],
                \repository_upload::type_form_validation(null, ['mimetype_whitelist' => $mimetype], [])
            );
        }

        $mimetype = "image/png\n\nimage/png\nimage/jpeg";
        $result = \repository_upload::type_form_validation(null, ['mimetype_whitelist' => $mimetype], []);
        self::assertArrayHasKey('mimetype_whitelist', $result);
        self::assertStringContainsString(get_string('mimetype_whitelist_validation_empties', 'repository_upload'), $result['mimetype_whitelist']);
        self::assertStringContainsString(get_string('mimetype_whitelist_validation_duplicates', 'repository_upload'), $result['mimetype_whitelist']);
    }

    public function test_validate_mimetype() {
        // List from https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
        $valid_mimetypes = [
            'audio/aac',
            'application/x-abiword',
            'application/x-freearc',
            'video/x-msvideo',
            'application/vnd.amazon.ebook',
            'application/octet-stream',
            'image/bmp',
            'application/x-bzip',
            'application/x-bzip2',
            'application/x-csh',
            'text/css',
            'text/csv',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-fontobject',
            'application/epub+zip',
            'application/gzip',
            'image/gif',
            'text/html',
            'image/vnd.microsoft.icon',
            'text/calendar',
            'application/java-archive',
            'image/jpeg',
            'text/javascript',
            'application/ld+json',
            'audio/midi ',
            'audio/x-midi',
            'text/javascript',
            'audio/mpeg',
            'video/mpeg',
            'application/vnd.apple.installer+xml',
            'application/vnd.oasis.opendocument.presentation',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.text',
            'audio/ogg',
            'video/ogg',
            'application/ogg',
            'audio/opus',
            'font/otf',
            'image/png',
            'application/pdf',
            'application/x-httpd-php',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.rar',
            'application/rtf',
            'application/x-sh',
            'image/svg+xml',
            'application/x-shockwave-flash',
            'application/x-tar',
            'image/tiff',
            'video/mp2t',
            'font/ttf',
            'text/plain',
            'application/vnd.visio',
            'audio/wav',
            'audio/webm',
            'video/webm',
            'image/webp',
            'font/woff',
            'font/woff2',
            'application/xhtml+xml',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/xml',
            'text/xml',
            'application/vnd.mozilla.xul+xml',
            'application/zip',
            'video/3gpp',
            'audio/3gpp',
            'video/3gpp2',
            'audio/3gpp2',
            'application/x-7z-compressed',
            '*/*',
        ];
        foreach ($valid_mimetypes as $mimetype) {
            self::assertTrue(repository_upload::validate_mimetype($mimetype), $mimetype . ' was not identified as a valid mimetype.');
        }

        $invalid_mimetypes = [
            '',
            '*',
            'astrophysics',
            ' ',
            '0',
            'false',
            0,
            127,
            false,
            null
        ];
        foreach ($invalid_mimetypes as $mimetype) {
            self::assertFalse(repository_upload::validate_mimetype($mimetype), $mimetype . ' was identified as a valid mimetype.');
        }
    }

    public function test_supported_returntypes() {
        $repository = $this->get_repository_instance();
        self::assertSame(FILE_INTERNAL, $repository->supported_returntypes());
    }

    public function test_contains_private_data() {
        $repository = $this->get_repository_instance();
        self::assertFalse($repository->contains_private_data());
    }

    public function test_get_listing() {
        $repository = $this->get_repository_instance();
        $return = $repository->get_listing();
        self::assertIsArray($return);
        self::assertArrayHasKey('nologin', $return);
        self::assertArrayHasKey('nosearch', $return);
        self::assertArrayHasKey('norefresh', $return);
        self::assertArrayHasKey('list', $return);
        self::assertArrayHasKey('dynload', $return);
        self::assertArrayHasKey('upload', $return);
        self::assertArrayHasKey('allowcaching', $return);
    }

    public function test_print_login() {
        $repository = $this->get_repository_instance();
        $return = $repository->print_login();
        self::assertIsArray($return);
        self::assertArrayHasKey('nologin', $return);
        self::assertArrayHasKey('nosearch', $return);
        self::assertArrayHasKey('norefresh', $return);
        self::assertArrayHasKey('list', $return);
        self::assertArrayHasKey('dynload', $return);
        self::assertArrayHasKey('upload', $return);
        self::assertArrayHasKey('allowcaching', $return);
    }

    public function test_check_valid_contents() {
        $repository = $this->get_repository_instance();
        $reflection = new ReflectionMethod(repository_upload::class, 'check_valid_contents');
        $reflection->setAccessible(true);

        // Check this file, which must be valid.
        self::assertTrue($reflection->invoke($repository, __FILE__));

        // Check a null file, as created by touch.
        $path = make_cache_directory('test_repository_upload_lib');
        touch($path . '/test');
        self::assertFalse($reflection->invoke($repository, $path . '/test'));

        // Check a non-existent file. This will produce a warning, which we must expect.
        $this->expectWarning();
        self::assertFalse($reflection->invoke($repository, __FILE__ . '.fake'));
    }

    public function test_upload() {
        $mock = $this->getMockBuilder('repository_upload')
            ->disableOriginalConstructor()
            ->onlyMethods(['process_upload'])
            ->getMock();

        $mock->expects($this->any())
            ->method('process_upload')
            ->willReturn(
                $this->returnCallback(
                    function () {
                        return func_get_args();
                    }
                )
            );

        $expected = [
            'test',
            '199',
            '*',
            '/',
            0,
            'allrightsreserved',
            '',
            false,
            -1,
        ];
        self::assertSame($expected, $mock->upload('test', '199'));
    }

    public function test_process_upload() {
        global $CFG;

        $user = $this->getDataGenerator()->create_user();
        $userctxid = \context_user::instance($user->id)->id;
        $this->setUser($user);

        $saveas_filename = 'icon.png';
        $maxbytes = -1;
        $draftfileid = file_get_unused_draft_itemid();
        $file_name = 'icon.png';
        $file_path = $CFG->dirroot . '/repository/upload/pix/icon.png';

        $_FILES['repo_upload_file'] = [
            'error' => null,
            'name' => $file_name,
            'tmp_name' => $file_path
        ];

        $repository = $this->get_repository_instance();

        // First test a successful upload.
        $expected = [
            'url' => $CFG->wwwroot . '/draftfile.php/' . $userctxid . '/user/draft/'.$draftfileid.'/' . $file_name,
            'id' => $draftfileid,
            'file' => $file_name
        ];
        $actual = $repository->process_upload($saveas_filename, $maxbytes, '*', '/', $draftfileid);
        self::assertSame($expected, $actual);

        // Now prevent png's from being allowed and confirm they are rejected.
        set_config('mimetype_whitelist', "image/jpeg\napplication/zip", 'upload');
        try {
            $repository->process_upload($saveas_filename, $maxbytes, '*', '/', $draftfileid);
            $this->fail('The PNG image should have been rejected.');
        } catch (moodle_exception $ex) {
            self::assertStringContainsString('Image (PNG) filetype cannot be accepted', $ex->getMessage());
        } catch (Exception $ex) {
            $this->fail('Unexpected exception');
        }
    }
}
