<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Chester <simon.chester@totara.com>
 * @package totara_useraction
 */

use core_phpunit\testcase;
use totara_useraction\watcher\relation_delete;

/**
 * Test the relation_delete watcher.
 *
 * @group totara_useraction
 */
class totara_useraction_watcher_relation_delete_testcase extends testcase {
    /**
     * Test reporting of related rules when deleting an audience.
     *
     * @return void
     */
    public function test_audience_delete(): void {
        /** @var \totara_cohort\testing\generator $audience_generator */
        $audience_generator = $this->getDataGenerator()->get_plugin_generator('totara_cohort');
        $audience_used = $audience_generator->create_cohort();
        $audience_unused = $audience_generator->create_cohort();

        /** @var \totara_useraction\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('totara_useraction');

        $generator->create_scheduled_rule(['filter_applies_to' => [$audience_used->id]]);

        // Unused audience -- count 0
        $hook = new \totara_cohort\hook\delete_affects($audience_unused->id);
        relation_delete::cohort_delete_affects($hook);
        $this->assertCount(1, $hook->get_affected());
        $this->assertEquals(0, $hook->get_affected()[0]->count);

        // Used audience -- count 1
        $hook = new \totara_cohort\hook\delete_affects($audience_used->id);
        relation_delete::cohort_delete_affects($hook);
        $this->assertCount(1, $hook->get_affected());
        $this->assertEquals(1, $hook->get_affected()[0]->count);
    }
}
