<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package mod_certificate
 */

use core_phpunit\testcase;
use mod_certificate\output\view_generator_factory;

global $CFG;
require_once("{$CFG->dirroot}/mod/certificate/locallib.php");

class mod_certificate_view_generator_testcase extends testcase {

    /**
     * @return void
     */
    public function test_invalid_factory(): void {
        [$certificate, $certificate_record, $course, $course_module] =
            $this->get_course_certificate('');

        $certificate->certificatetype = 'non_existent';
        try {
            view_generator_factory::create(
                $certificate,
                $certificate_record,
                $course,
                $course_module
            );
            $this->fail("Expected invalid factory error");
        } catch (Exception $e) {
            $this->assertEquals(
                'Coding error detected, it must be fixed by a programmer: Invalid certificate type',
                $e->getMessage()
            );
        }
    }

    /**
     * @dataProvider data_create_certificates
     *
     * @param string $certificate_type
     *
     * @return void
     */
    public function test_valid_factory(string $certificate_type): void {
        [$certificate, $certificate_record, $course, $course_module] =
            $this->get_course_certificate($certificate_type);

        $generator = view_generator_factory::create(
            $certificate,
            $certificate_record,
            $course,
            $course_module
        );

        $this->assertEquals($certificate->certificatetype, $generator->get_type());
    }

    /**
     * @dataProvider data_create_certificates
     *
     * @return void
     */
    function test_html(string $certificate_type): void {
        global $CFG, $DB;

        foreach (['L', 'P'] as $orientation) {
            $course = $this->getDataGenerator()->create_course();
            $user = $this->getDataGenerator()->create_user();

            $certificate = $this->getDataGenerator()->create_module(
                'certificate',
                [
                    'course' => $course->id,
                    'certificatetype' => $certificate_type,
                    'orientation' => $orientation,
                    'borderstyle' => 'Fancy1-black.jpg',
                    'bordercolor' => '2',
                    'printwmark' => 'Crest.png',
                    'printdate' => 1,
                    'datefmt' => 1,
                    'printnumber' => 0,
                    'printgrade' => 1,
                    'gradefmt' => 1,
                    'printoutcome' => 0,
                    'printhours' => '122',
                    'printteacher' => 1,
                    'printsignature' => 'Line.png',
                    'printseal' => 'Fancy.png',
                    'customtext' => 'This is custom text',
                ]
            );

            $this->getDataGenerator()->enrol_user($user->id, $course->id);
            $teacher1 = self::getDataGenerator()->create_user(['firstname' => 'Teacher', 'lastname' => 'One']);
            $teacher2 = self::getDataGenerator()->create_user(['firstname' => 'Teacher', 'lastname' => 'Two']);
            $this->getDataGenerator()->enrol_user($teacher1->id, $course->id, 'teacher');
            $this->getDataGenerator()->enrol_user($teacher2->id, $course->id, 'teacher');

            $course_module = get_coursemodule_from_instance('certificate', $certificate->id, $course->id);
            $certificate_record = certificate_get_issue($course, $user, $certificate, $course_module);

            // Update time issued to match fixture.
            $certificate_record->timecreated = 1629517094;
            $DB->update_record('certificate_issues', $certificate_record);

            $view_generator = view_generator_factory::create($certificate, $certificate_record, $course, $course_module);
            $html = $view_generator->generate_html();

            // Uncomment this line to update the fixtures.
            //file_put_contents("{$CFG->dirroot}/mod/certificate/tests/fixtures/{$certificate_type}_{$orientation}.html", $html);

            $this->assertStringEqualsFile(
                "{$CFG->dirroot}/mod/certificate/tests/fixtures/{$certificate_type}_{$orientation}.html",
                $html
            );
        }
    }

    /**
     * @return void
     */
    public function test_uploaded_image(): void {
        $this->setAdminUser();
        $context = \context_system::instance();
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        $this->create_image('certificate_test_border_image', 'border', $context);
        $this->create_image('certificate_test_watermark_image', 'watermark', $context);
        $this->create_image('certificate_test_signature_image', 'signature', $context);
        $this->create_image('certificate_test_seal_image', 'seal', $context);

        $border_images = certificate_get_images(CERT_IMAGE_BORDER);
        $watermark_images = certificate_get_images(CERT_IMAGE_WATERMARK);
        $signature_images = certificate_get_images(CERT_IMAGE_SIGNATURE);
        $seal_images = certificate_get_images(CERT_IMAGE_SEAL);

        $this->assertArrayHasKey('certificate_test_border_image.png', $border_images);
        $this->assertArrayHasKey('certificate_test_watermark_image.png', $watermark_images);
        $this->assertArrayHasKey('certificate_test_signature_image.png', $signature_images);
        $this->assertArrayHasKey('certificate_test_seal_image.png', $seal_images);

        foreach ($this->data_create_certificates() as $data) {
            $certificate = $this->getDataGenerator()->create_module(
                'certificate',
                [
                    'course' => $course->id,
                    'certificatetype' => $data['certificatetype'],
                    'borderstyle' => 'certificate_test_border_image.png',
                    'printwmark' => 'certificate_test_watermark_image.png',
                    'printsignature' => 'certificate_test_signature_image.png',
                    'printseal' => 'certificate_test_seal_image.png',
                ]
            );

            $course_module = get_coursemodule_from_instance('certificate', $certificate->id, $course->id);
            $certificate_record = certificate_get_issue($course, $user, $certificate, $course_module);

            $view_generator = view_generator_factory::create($certificate, $certificate_record, $course, $course_module);
            $html = $view_generator->generate_html();
            $this->assertStringContainsString(
                '/moodle/pluginfile.php/1/mod_certificate/border/3/certificate_test_border_image.png',
                $html,
                'Border image not found in certificate'
            );
            $this->assertStringContainsString(
                '/moodle/pluginfile.php/1/mod_certificate/watermark/3/certificate_test_watermark_image.png',
                $html,
                'Watermark image not found in certificate'
            );
            $this->assertStringContainsString(
                '/moodle/pluginfile.php/1/mod_certificate/signature/3/certificate_test_signature_image.png',
                $html,
                'Signature image not found in certificate'
            );
            $this->assertStringContainsString(
                '/moodle/pluginfile.php/1/mod_certificate/seal/3/certificate_test_seal_image.png',
                $html,
                'Seal image not found in certificate'
            );
        }
    }

    /**
     * @param string $certificate_type
     * @return array
     */
    private function get_course_certificate(string $certificate_type) {
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $certificate = $this->getDataGenerator()->create_module(
            'certificate',
            [
                'course' => $course->id,
                'certificatetype' => $certificate_type,
            ]
        );
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $course_module = get_coursemodule_from_instance('certificate', $certificate->id, $course->id);
        $certificate_record = certificate_get_issue($course, $user, $certificate, $course_module);

        return [$certificate, $certificate_record, $course, $course_module];
    }

    /**
     * @return string[][]
     */
    public function data_create_certificates(): array {
        return [
            [
                'certificatetype' => 'A4_embedded',
            ],
            [
                'certificatetype' => 'A4_non_embedded',
            ],
            [
                'certificatetype' => 'letter_embedded',
            ],
            [
                'certificatetype' => 'letter_non_embedded',
            ],
        ];
    }

    /**
     * @param string $name
     * @param context $context
     *
     * @return int
     */
    private function create_image(string $name, string $area,  context $context): int {
        $draft_id = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $time = time();
        $file_record = new stdClass();
        $file_record->filename = "{$name}.png";
        $file_record->contextid = $context->id;
        $file_record->component = 'mod_certificate';
        $file_record->filearea = $area;
        $file_record->filepath = '/';
        $file_record->itemid = 3;
        $file_record->timecreated = $time;
        $file_record->timemodified = $time;
        $fs->create_file_from_string($file_record, $name);

        return $draft_id;
    }

}