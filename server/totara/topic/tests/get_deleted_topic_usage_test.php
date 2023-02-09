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
 * @package totara_topic
 */
defined('MOODLE_INTERNAL') || die();

use totara_topic\hook\get_deleted_topic_usages;

class totara_topic_get_deleted_topic_usage_testcase extends advanced_testcase {
    /**
     * @return void
     */
    public function test_invalid_constructor(): void {
        $instance_ids = ['x', 'd', 'c'];

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid array of instance id(s)');
        new get_deleted_topic_usages('totara_topic', 'topic', $instance_ids);
    }
}
