<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 * @package container_workspace
 */

use container_workspace\enrol\manager;
use container_workspace\loader\audience\loader;
use container_workspace\query\audience\query;
use core\collection;
use core\entity\cohort as cohort_entity;
use core_phpunit\testcase;

/**
 * @group container_workspace
 */
class container_workspace_loader_audience_loader_test extends testcase {

    /**
     * @var \container_workspace\workspace
     */
    private $workspace;

    /**
     * @var \stdClass[]
     */
    private $audiences;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $this->setAdminUser();
        $generator = $this->getDataGenerator();

        /** @var \container_workspace\testing\generator $workspace_generator */
        $workspace_generator = $generator->get_plugin_generator('container_workspace');
        $workspace = $workspace_generator->create_workspace();
        $this->workspace = $workspace;
        $enrol = manager::from_workspace($workspace);

        $this->audiences = [];

        $audience_names = [
            "Architecture" => 5,
            "Finance" => 7,
            "Health" => 4,
            "Business" => 2,
        ];

        foreach ($audience_names as $audience_name => $no_of_users) {
            $audience = $generator->create_cohort([
                'name' => $audience_name,
                'idnumber' => $audience_name,
            ]);
            $this->audiences[] = $audience;
            for ($i = 0; $i <= $no_of_users; $i++) {
                $user = $generator->create_user();
                cohort_add_member($audience->id, $user->id);
            }
            $enrol->enrol_audiences([$audience->id]);
        }
    }

    /**
     * @inheritDoc
     */
    protected function tearDown(): void {
        $this->workspace = null;
        $this->audiences = null;
    }

    /**
     * Test getting audiences added to a workspace
     *
     * @return void
     */
    public function test_get_audiences(): void {
        $query = new query($this->workspace);
        $audiences_paginator = loader::get_audiences($query);
        $items = $audiences_paginator->get_items()->map(function (cohort_entity $cohort) {
            return $cohort->name;
        });

        $this->assertEquals(
            ["Architecture", "Business", "Finance", "Health"],
            $items->all()
        );
    }

    /**
     * Test searching for an audience added to a workspace
     *
     * @return void
     */
    public function test_search_for_audience(): void {
        $query = new query($this->workspace, "a");
        $audiences_paginator = loader::get_audiences($query);
        $items = $audiences_paginator->get_items()->map(function (cohort_entity $cohort) {
            return $cohort->name;
        });

        $this->assertEquals(
            ["Architecture", "Finance", "Health"],
            $items->all()
        );
    }

    /**
     * Test getting a specific page & limit of audiences added to a workspace
     *
     * @return void
     */
    public function test_pagination(): void {
        // First page
        $query = new query($this->workspace, null, 1, 2);
        $audiences_paginator = loader::get_audiences($query);
        $this->assertCount(2, $audiences_paginator->get_items());
        $this->assertEquals(
            ["Architecture", "Business"],
            $audiences_paginator->get_items()->map(function (cohort_entity $cohort) {
                return $cohort->name;
            })->to_array()
        );

        // Second page
        $query = new query($this->workspace, null, 2, 2);
        $audiences_paginator = loader::get_audiences($query);
        $this->assertCount(2, $audiences_paginator->get_items());
        $this->assertEquals(
            ["Finance", "Health"],
            $audiences_paginator->get_items()->map(function (cohort_entity $cohort) {
                return $cohort->name;
            })->to_array()
        );

        // Out of bound page
        $query = new query($this->workspace, null, 3, 5);
        $audiences_paginator = loader::get_audiences($query);
        $this->assertEmpty($audiences_paginator->get_items());
    }
    
    /**
     * Test getting the ids of all added audiences
     *
     * @return void
     */
    public function test_get_audience_ids(): void {
        $ids = loader::get_audience_ids($this->workspace);
        $this->assertEqualsCanonicalizing(
            collection::new($this->audiences)->pluck('id'),
            $ids
        );
    }
}
