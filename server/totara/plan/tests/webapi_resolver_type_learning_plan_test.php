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
 * @package totara_plan
 * @subpackage test
 */

use core\format;
use core\orm\query\builder;
use core\webapi\execution_context;
use totara_plan\webapi\resolver\type\learning_plan;

defined('MOODLE_INTERNAL') || die();

/**
 * Testing the learning plan GraphQL type resolver.
 */
class totara_plan_webapi_resolver_type_learning_plan_testcase extends advanced_testcase {

    private $user1;

    private $user2;

    protected function setUp(): void {
        $this->user1 = $this->getDataGenerator()->create_user();
        $this->user2 = $this->getDataGenerator()->create_user();
    }

    protected function tearDown(): void {
        $this->user1 = null;
        $this->user2 = null;
    }

    /**
     * Resolve the type.
     *
     * @param development_plan $plan
     * @param string $field
     * @param array $args
     * @return mixed
     */
    private function resolve(development_plan $plan, string $field, array $args = []) {
        return learning_plan::resolve($field, $plan, $args, execution_context::create('dev', null));
    }

    /**
     * Make sure nothing is resolved if the user isn't logged in
     */
    public function test_resolve_not_logged_in() {
        $plan1 = $this->create_learning_plan(['userid' => $this->user1->id]);

        $this->expectException(require_login_exception::class);
        $this->expectExceptionMessage('You are not logged in');

        $this->resolve($plan1, 'id');
    }

    /**
     * Make sure that the ID is resolved correctly
     */
    public function test_resolve_id() {
        $plan1 = $this->create_learning_plan(['userid' => $this->user1->id]);
        $plan2 = $this->create_learning_plan(['userid' => $this->user2->id]);

        $this->setUser($this->user1);

        $this->assertEquals($plan1->id, $this->resolve($plan1, 'id'));
        $this->assertEquals($plan2->id, $this->resolve($plan2, 'id'));
    }

    /**
     * Make sure that we can resolve whether the current user has permission to view the plan
     */
    public function test_resolve_can_view() {
        $this->setUser($this->user1);
        $plan1 = $this->create_learning_plan(['userid' => $this->user1->id]);

        $this->setUser($this->user2);
        $plan2 = $this->create_learning_plan(['userid' => $this->user2->id]);

        $this->assertFalse($plan1->can_view());
        $this->assertTrue($plan2->can_view());
    }

    /**
     * Make sure the name is resolved correctly and can handle different formatting
     */
    public function test_resolve_name() {
        $this->setUser($this->user1);
        $plan1 = $this->create_learning_plan([
            'userid' => $this->user1->id,
            'name' => '<p>Plan One</p>',
        ]);

        $this->assertEquals('Plan One', $this->resolve($plan1, 'name', ['format' => format::FORMAT_HTML]));
        $this->assertEquals('<p>Plan One</p>', $this->resolve($plan1, 'name', ['format' => format::FORMAT_RAW]));
        $this->assertEquals('Plan One', $this->resolve($plan1, 'name', ['format' => format::FORMAT_PLAIN]));
    }

    /**
     * Make sure that the description (including images/files uploaded to it) is resolved correctly
     */
    public function test_resolve_description() {
        $this->setUser($this->user1);

        $plan1 = $this->create_learning_plan([
            'userid' => $this->user1->id,
            'description' => "<p>Description</p><script>alert('This shouldn\'t be here\')</script>",
        ]);

        $this->assertEquals("<p>Description</p>", $this->resolve($plan1, 'description', ['format' => format::FORMAT_HTML]));
        $this->assertEquals(
            "<p>Description</p><script>alert('This shouldn\'t be here\')</script>",
            $this->resolve($plan1, 'description', ['format' => format::FORMAT_RAW])
        );
        $this->assertEquals("Description\n", $this->resolve($plan1, 'description', ['format' => format::FORMAT_PLAIN]));

        $file = get_file_storage()->create_file_from_string([
            'contextid' => context_system::instance()->id,
            'component' => 'totara_plan',
            'filearea' => 'dp_plan',
            'filepath' => '/',
            'itemid' => $plan1->id,
            'filename' => 'lp_test.png',
        ], "File Content");
        builder::table('dp_plan')->update_record([
            'id' => $plan1->id,
            'description' => "<img src=\"@@PLUGINFILE@@/lp_test.png\"/>"
        ]);
        $plan1 = new development_plan($plan1->id);

        $description = $this->resolve($plan1, 'description');
        $this->assertStringContainsString($file->get_component(), $description);
        $this->assertStringContainsString($file->get_filearea(), $description);
        $this->assertStringContainsString($file->get_itemid(), $description);
    }

    /**
     * Make sure that fields containing actual plan information aren't resolved without permission to view the plan
     */
    public function test_field_permissions() {
        $this->setUser($this->user1);
        $plan1 = $this->create_learning_plan([
            'userid' => $this->user1->id, 'name' => '<p>Plan One</p>', 'description' => '<p>Description One</p>'
        ]);

        $this->setUser($this->user2);
        $this->assertNotNull($this->resolve($plan1, 'id'));
        $this->assertNull($this->resolve($plan1, 'name'));
        $this->assertNull($this->resolve($plan1, 'description'));
        $this->assertFalse($this->resolve($plan1, 'can_view'));

        $this->setUser($this->user1);
        $this->assertNotNull($this->resolve($plan1, 'id'));
        $this->assertNotNull($this->resolve($plan1, 'name'));
        $this->assertNotNull($this->resolve($plan1, 'description'));
        $this->assertTrue($this->resolve($plan1, 'can_view'));
    }

    /**
     * Create a learning plan.
     *
     * @param array $record
     * @return development_plan
     */
    private function create_learning_plan(array $record): development_plan {
        global $CFG;
        require_once($CFG->dirroot . '/totara/plan/component.class.php');

        /** @var \totara_plan\testing\generator $plan_generator */
        $plan_generator = $this->getDataGenerator()->get_plugin_generator('totara_plan');

        $plan = $plan_generator->create_learning_plan($record);
        return new development_plan($plan->id);
    }

}
