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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

use core\entity\user;
use core_phpunit\testcase;
use totara_job\job_assignment;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\scale;

abstract class pathway_manual_base_testcase extends testcase {

    /**
     * @var \totara_competency\testing\generator
     */
    protected $generator;

    /**
     * @var user
     */
    protected $user1;

    /**
     * @var user
     */
    protected $user2;

    /**
     * @var competency
     */
    protected $competency1;

    /**
     * @var competency
     */
    protected $competency2;

    /**
     * @var scale
     */
    protected $scale1;

    /**
     * @var scale
     */
    protected $scale2;

    /**
     * Create testing data.
     */
    protected function setUp(): void {
        $this->generator = $this->getDataGenerator()->get_plugin_generator('totara_competency');

        $this->user1 = new user($this->getDataGenerator()->create_user(), false);
        $this->user2 = new user($this->getDataGenerator()->create_user(), false);

        $this->scale1 = $this->generator->create_scale('1', '1', [
            ['name' => '11', 'proficient' => false, 'default' => true, 'sortorder' => 1],
            ['name' => '12', 'proficient' => true, 'default' => false, 'sortorder' => 2],
        ]);
        $this->scale2 = $this->generator->create_scale('2', '2', [
            ['name' => '21', 'proficient' => false, 'default' => true, 'sortorder' => 1],
            ['name' => '22', 'proficient' => true, 'default' => false, 'sortorder' => 2],
        ]);

        $fw1 = $this->generator->create_framework($this->scale1);
        $fw2 = $this->generator->create_framework($this->scale2);

        $this->competency1 = $this->generator->create_competency('comp1', $fw1);
        $this->competency2 = $this->generator->create_competency('comp2', $fw2);
    }

    /**
     * Unset the testing data.
     */
    protected function tearDown(): void {
        $this->generator = null;
        $this->user1 = null;
        $this->user2 = null;
        $this->competency1 = null;
        $this->competency2 = null;
        $this->scale1 = null;
        $this->scale2 = null;
    }

    /**
     * Get the id for a given scale value name.
     *
     * @param string $name
     * @return mixed
     */
    protected function get_scale_value_id(string $name) {
        global $DB;
        return $DB->get_field_sql(
            "SELECT id FROM {comp_scale_values} WHERE "
            . $DB->sql_compare_text('name') . " = " . $DB->sql_compare_text(':name'),
            ['name' => $name],
            MUST_EXIST
        );
    }

    /**
     * Make a user a manager of the other user and assign the capability to rate.
     *
     * @param int $user_id
     * @param int $manager_id
     */
    protected function set_as_rating_manager(int $user_id, int $manager_id) {
        global $DB;

        $manager_ja = job_assignment::create_default($manager_id);
        job_assignment::create_default(
            $user_id,
            ['managerjaid' => $manager_ja->id]
        );

        $user_role_id = $DB->get_record('role', ['shortname' => 'user'])->id;
        assign_capability(
            'totara/competency:rate_other_competencies',
            CAP_ALLOW,
            $user_role_id,
            context_user::instance($manager_id)
        );
    }
}
