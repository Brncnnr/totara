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
 * @package contentmarketplace_linkedin
 */

use core_phpunit\testcase;
use totara_contentmarketplace\plugininfo\contentmarketplace;
use contentmarketplace_linkedin\testing\generator;
use contentmarketplace_linkedin\task\create_course_delay_task;
use container_course\course;

/**
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_create_course_delay_task_testcase extends testcase {
    /**
     * @var array
     */
    protected $data;

    /**
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        self::setAdminUser();
        $plugin = contentmarketplace::plugin('linkedin');
        $plugin->enable();
        $this->prepare_data();
    }

    /**
     * @return void
     */
    protected function tearDown(): void {
        parent::tearDown();
        $this->data = null;
    }

    /**
     * @return void
     */
    public function test_create_course_delay_task(): void {
        global $DB;

        $count = $DB->count_records('course', ['containertype' => course::get_type()]);
        self::assertEquals(0, $count);

        $task = create_course_delay_task::enqueue($this->data);
        $task->execute();

        $count = $DB->count_records('course', ['containertype' => course::get_type()]);
        self::assertEquals(60, $count);
    }

    /**
     * @return void
     */
    private function prepare_data() : void {
        $generator = self::getDataGenerator();
        $new_category = $generator->create_category();
        $generator = generator::instance();
        $data = [];
        for ($x = 0; $x < 60; $x++) {
            $learning_object = $generator->create_learning_object('urn:lyndaCourse:25' . $x);
            $data['learning_object_id'] = $learning_object->id;
            $data['category_id'] = $new_category->id;

            $this->data[] = $data;
        }
    }
}