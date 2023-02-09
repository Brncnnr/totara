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
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package totara_contentmarketplace
 */

use core\testing\generator as core_generator;
use core_course\totara_catalog\course\dataholder_factory\course_logo;
use core_phpunit\testcase;
use mod_contentmarketplace\testing\generator as module_generator;
use totara_catalog\dataformatter\formatter;
use totara_contentmarketplace\totara_catalog\course_logo_dataholder_factory;

/**
 * @group totara_contentmarketplace
 */
class totara_contentmarketplace_catalog_course_logo_dataformatter_testcase extends testcase {

    /**
     * @var stdClass
     */
    private $course;

    /**
     * @var course_logo
     */
    private $dataholder;

    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
        $this->course = core_generator::instance()->create_course();
        $data_holders = course_logo::get_dataholders();
        $this->dataholder = current($data_holders);
    }

    protected function tearDown(): void {
        parent::tearDown();
        $this->course = null;
        $this->dataholder = null;
    }

    public function test_course_logo_dataformatter_with_no_course_module_source(): void {
        self::assertNull($this->get_logo([], []));
    }

    public function test_dataholder_constants_are_correct(): void {
        self::assertEquals(course_logo_dataholder_factory::DATAHOLDER_KEY, $this->dataholder->key);
        self::assertEquals(course_logo_dataholder_factory::DATAHOLDER_NAME, $this->dataholder->name);
    }

    /**
     * Ensure that a logo is returned if there is a single course module source record for a course.
     */
    public function test_course_logo_dataformatter_with_single_course_module_source(): void {
        $module_generator = module_generator::instance();
        $module = $module_generator->create_content_marketplace_instance([
            'course' => $this->course, 'marketplace_component' => 'contentmarketplace_goone', 'name' => 29271,
        ]);

        $result = $this->get_logo(['contentmarketplace_goone'], [$module->cmid]);
        self::assertStringContainsString('contentmarketplace_goone', $result->url);
        self::assertStringContainsString(
            get_string('pluginname', 'contentmarketplace_goone'),
            $result->alt
        );
    }

    /**
     * Ensures that if there are multiple course module sources for a course,
     * that the first module's (ordered by ID) marketplace logo is used.
     */
    public function test_course_logo_dataformatter_with_multiple_course_module_sources(): void {
        $module_generator = module_generator::instance();
        $lil_module = $module_generator->create_content_marketplace_instance([
            'course' => $this->course, 'marketplace_component' => 'contentmarketplace_linkedin', 'name' => 'foobar',
        ]);
        $go1_module = $module_generator->create_content_marketplace_instance([
            'course' => $this->course, 'marketplace_component' => 'contentmarketplace_goone', 'name' => 29271,
        ]);

        $result = $this->get_logo(
            ['contentmarketplace_goone', 'contentmarketplace_linkedin'],
            [$go1_module->cmid, $lil_module->cmid]
        );
        self::assertStringContainsString('contentmarketplace_linkedin', $result->url);
        self::assertStringContainsString(
            get_string('pluginname', 'contentmarketplace_linkedin'),
            $result->alt
        );
    }

    /**
     * @param array $marketplace_components
     * @param array $cm_ids
     * @return object|null
     */
    private function get_logo(array $marketplace_components, array $cm_ids): ?object {
        return $this->dataholder->get_formatted_value(
            formatter::TYPE_PLACEHOLDER_IMAGE,
            [
                'marketplace_component' => implode('|', $marketplace_components),
                'cm_ids' => implode('|', $cm_ids),
            ],
            context_course::instance($this->course->id)
        );
    }

}