<?php
/*
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_core
 */

use totara_core\entity\relationship as relationship_entity;
use totara_core\entity\relationship_resolver;
use totara_core\relationship\resolvers\subject;

global $CFG;
require_once($CFG->dirroot . '/totara/core/db/upgradelib.php');

/**
 * @group totara_core_relationship
 */
class totara_core_relationship_upgrade_testcase extends \advanced_testcase {

    public function test_create_relationship(): void {
        // Need to delete the existing records from installing in order to test.
        relationship_entity::repository()->delete();

        $this->assertEquals(0, relationship_entity::repository()->count());
        $this->assertEquals(0, relationship_resolver::repository()->count());

        totara_core_upgrade_create_relationship(subject::class, 'subject', 1);

        $this->assertEquals(1, relationship_entity::repository()->count());
        $this->assertEquals(1, relationship_resolver::repository()->count());

        /** @var relationship_entity $relationship_entity */
        $relationship_entity = relationship_entity::repository()->one();

        /** @var relationship_resolver $relationship_resolver */
        $relationship_resolver = relationship_resolver::repository()->one();

        $this->assertEquals(subject::class, $relationship_resolver->class_name);
        $this->assertEquals($relationship_entity->id, $relationship_resolver->relationship_id);
    }

}
