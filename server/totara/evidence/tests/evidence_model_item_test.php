<?php
/**
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
 * @package totara_evidence
 * @category test
 */

use core\entity\user;
use core\orm\query\builder;
use totara_evidence\entity;
use totara_evidence\models;

global $CFG;
require_once($CFG->dirroot . '/totara/evidence/tests/evidence_testcase.php');

/**
 * @group totara_evidence
 */
class totara_evidence_model_item_testcase extends totara_evidence_testcase {

    /**
     * Test that in_use() returns the correct value based on if there is a learning plan using the evidence
     */
    public function test_model_item_in_use(): void {
        self::setAdminUser();

        $this->generator()->create_evidence_type(['name' => 'Type']);
        $item_one = $this->generator()->create_evidence_item(['name' => 'One']);
        $item_two = $this->generator()->create_evidence_item(['name' => 'Two']);
        $this->assertFalse($item_one->in_use());
        $this->assertFalse($item_two->in_use());

        $item_one_relation = $this->generator()->create_evidence_plan_relation($item_one);
        $this->assertTrue($item_one->in_use());
        $this->assertFalse($item_two->in_use());

        $item_two_relation = $this->generator()->create_evidence_plan_relation($item_two);
        $this->assertTrue($item_one->in_use());
        $this->assertTrue($item_two->in_use());

        $item_one_relation->delete();
        $this->assertFalse($item_one->in_use());
        $this->assertTrue($item_two->in_use());

        $item_two_relation->delete();
        $this->assertFalse($item_one->in_use());
        $this->assertFalse($item_two->in_use());
    }

    /**
     * Test that can_modify() works correctly and throws appropriate exceptions if specified.
     */
    public function test_can_modify(): void {
        self::setAdminUser();

        $this->generator()->create_evidence_type(['name' => 'Type']);
        $item_entity = $this->generator()->create_evidence_item_entity(['name' => 'One']);
        $item = models\evidence_item::load_by_entity($item_entity);

        // Can modify for now.
        $this->assertTrue($item->can_modify());
        $item->can_modify(true);

        // Can't modify due to lacking capabilities.
        self::setGuestUser();
        $this->assertFalse($item->can_modify());
        try {
            $item->can_modify(true);
            $this->fail('Expected required_capability_exception');
        } catch (required_capability_exception $exception) {
            // Success
        }

        // Can't modify due to being in use.
        self::setAdminUser();
        $item_relation = $this->generator()->create_evidence_plan_relation($item);
        $this->assertFalse($item->can_modify());
        try {
            $item->can_modify(true);
            $this->fail('Expected required_capability_exception');
        } catch (coding_exception $exception) {
            $item_relation->delete();
        }

        // Can't modify due to not existing.
        $item_entity->delete();
        $this->assertFalse($item->can_modify());
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Evidence item no longer exists');
        $item->can_modify(true);
    }

    /**
     * Test that a item is created correctly
     */
    public function test_model_item_create(): void {
        self::setAdminUser();

        $user = $this->generator()->create_evidence_user();
        $admin = user::logged_in();

        $type = $this->generator()->create_evidence_type(['name' => 'Type']);

        $field_data = (object) [
            'key' => 'value'
        ];
        $item_name = 'Evidence';

        $item = models\evidence_item::create($type, $user, $field_data, $item_name);
        $this->assertEquals($item_name, $item->get_display_name());
        $this->assertEquals($user->id, $item->user->id);
        $this->assertEquals($admin->id, $item->created_by_user->id);
        $this->assertEquals($admin->id, $item->modified_by_user->id);
        $this->assertEquals(models\evidence_item::STATUS_ACTIVE, $item->status);
    }

    /**
     * Test that a item can be updated correctly
     */
    public function test_model_item_update(): void {
        self::setAdminUser();
        $this->generator()->create_evidence_type(['name' => 'Type']);

        $item = $this->generator()->create_evidence_item(['type' => 'Type']);
        $name = $item->get_data()['name'];
        $name_new = $name . ' New';

        $item->update(null, $name_new);
        $this->assertNotEquals($name, $item->name);
        $this->assertEquals($name_new, $item->name);
    }

    /**
     * Test that an evidence item can not be updated if the user lacks the correct capabilities
     */
    public function test_model_item_update_without_capability(): void {
        $role = builder::table('role')->where('shortname', 'user')->value('id');
        $user = $this->generator()->create_evidence_user();
        self::setUser($user->id);

        $item = $this->generator()->create_evidence_item(['name' => 'Name', 'user_id' => $user->id]);
        $item->update(null, 'New Name');
        $this->assertEquals('New Name', $item->name);

        unassign_capability('totara/evidence:manageanyevidenceonself', $role);
        $this->expectException(required_capability_exception::class);
        $item->update(null, 'New New Name');
    }

    /**
     * Test that an evidence item can not be update if it is in use
     */
    public function test_model_item_update_when_in_use(): void {
        self::setAdminUser();

        $this->generator()->create_evidence_type();
        $item = $this->generator()->create_evidence_item(['name' => 'Name']);
        $item->update(null, 'New Name');
        $this->assertEquals('New Name', $item->name);

        $this->generator()->create_evidence_plan_relation($item);
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Evidence item with ID {$item->id} is currently in use elsewhere");
        $item->update(null, 'New New Name');
    }

    /**
     * Test that a value to be changed must be specified when updating an evidence item
     */
    public function test_model_item_update_invalid_arguments(): void {
        self::setAdminUser();
        $this->generator()->create_evidence_type(['name' => 'One']);

        $user = user::logged_in();
        $item = $this->generator()->create_evidence_item(['type' => 'One', 'created_by' => $user->id]);

        $item->update(null, 'XYZ');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('/Must specify an attribute to change for evidence item with ID/');
        $item->update(null, null);
    }

    /**
     * Test that an evidence type must be specified when creating a new evidence item
     */
    public function test_model_item_create_no_type(): void {
        self::setAdminUser();
        $type = $this->generator()->create_evidence_type(['name' => 'Type']);

        models\evidence_item::create($type, user::logged_in()->id);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('A name must be specified');
        models\evidence_item::create(models\evidence_type::create(''), user::logged_in()->id);
    }

    /**
     * Test that evidence item cannot be updated after being deleted
     */
    public function test_model_item_update_after_delete(): void {
        self::setAdminUser();
        $type = $this->generator()->create_evidence_type(['name' => 'Type']);

        $item = models\evidence_item::create($type, user::logged_in()->id);
        $item->delete();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Evidence item no longer exists');
        $item->update(null, 'XYZ');
    }

    /**
     * Test that evidence and its associated data is correctly deleted
     */
    public function test_model_item_delete(): void {
        $role = builder::table('role')->where('shortname', 'user')->value('id');

        self::setAdminUser();

        $user_one = $this->generator()->create_evidence_user(['username' => 'user_one']);
        $user_two = $this->generator()->create_evidence_user(['username' => 'user_two']);

        $fields_count = 3;
        $this->generator()->create_evidence_type(['name' => 0, 'fields' => $fields_count]);
        $item = $this->generator()->create_evidence_item([
            'type'       => 0,
            'user_id'    => $user_one->id,
            'created_by' => $user_two->id
        ]);

        $dummy_items = 3;
        for ($i = 0; $i < $dummy_items; $i++) {
            $this->generator()->create_evidence_item(['type' => 0]);
        }
        $fields = $this->fields()->all();

        $deleted_data   = [];
        $deleted_params = [];

        foreach ($fields as $i => $field) {
            $data = entity\evidence_field_data::repository()
                ->where('fieldid', $field->id)
                ->where('evidenceid', $item->get_id())
                ->order_by('id')
                ->first();
            $deleted_data[] = $data;

            $param = new entity\evidence_field_data_param([
                'dataid' => $data->id,
                'value'  => "Data $i Param"
            ]);
            $param->save();
            $deleted_params[] = $param;
        }

        $evidence_id = $item->get_id();

        $item = models\evidence_item::load_by_id($evidence_id);

        $this->assertNotNull(entity\evidence_item::repository()->find($evidence_id));
        foreach ($deleted_data as $data) {
            $this->assertNotNull(entity\evidence_field_data::repository()->find($data->id));
        }
        foreach ($deleted_params as $param) {
            $this->assertNotNull(entity\evidence_field_data_param::repository()->find($param->id));
        }

        $item_entity = $this->generator()->create_evidence_item_entity();
        try {
            $item_model_temp = models\evidence_item::load_by_entity($item_entity);
            $item_entity->delete();
            $item_model_temp->delete();
            self::fail('Expected exception not thrown');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('Evidence item no longer exists', $e->getMessage());
            $this->assertCount($dummy_items + 1, $this->items());
            $this->assertCount($fields_count, $this->fields());
        }
        unset($item_model_temp, $item_entity);

        // Guest user doesn't have permission
        self::setGuestUser();
        try {
            $item->delete();
            self::fail('Expected exception not thrown');
        } catch (required_capability_exception $e) {
            $this->assertCount($dummy_items + 1, $this->items());
            $this->assertCount($fields_count, $this->fields());
        }

        // Non-creator can't delete
        unassign_capability('totara/evidence:manageanyevidenceonself', $role);
        self::setUser($user_one->id);
        unassign_capability('totara/evidence:manageanyevidenceonself', $role);
        try {
            $item->delete();
            self::fail('Expected exception not thrown');
        } catch (required_capability_exception $e) {
            $this->assertCount($dummy_items + 1, $this->items());
            $this->assertCount($fields_count, $this->fields());
        }

        self::setUser($user_two->id);
        $context = context_user::instance($user_one->id);
        assign_capability('totara/evidence:manageownevidenceonothers', CAP_ALLOW, $role, $context);

        // In use evidence can't be deleted
        $relation = $this->generator()->create_evidence_plan_relation($item);
        try {
            $item->delete();
            self::fail('Expected exception not thrown');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('currently in use elsewhere', $e->getMessage());
            $this->assertCount($dummy_items + 1, $this->items());
            $this->assertCount($fields_count, $this->fields());
        }
        $relation->delete();

        // Creator can delete with correct capability
        $item->delete();
        $this->assertCount($dummy_items, $this->items());
        $this->assertCount($fields_count, $this->fields());
        $this->assertNull(entity\evidence_item::repository()->find($evidence_id));
        foreach ($deleted_data as $data) {
            $this->assertNull(entity\evidence_field_data::repository()->find($data->id));
        }
        foreach ($deleted_params as $param) {
            $this->assertNull(entity\evidence_field_data_param::repository()->find($param->id));
        }
    }

    /**
     * Test that the model returns a placeholder name containing the user's name and the type name if an item has no name
     */
    public function test_model_item_get_name(): void {
        self::setAdminUser();

        $user = $this->generator()->create_evidence_user(['username' => 'user_one']);
        $type = $this->generator()->create_evidence_type(['name' => 'Type']);

        $with_name = models\evidence_item::create($type, $user, null, 'Name');
        $this->assertEquals('Name', $with_name->get_display_name());

        $without_name_null = models\evidence_item::create($type, $user);
        $without_name_zero = models\evidence_item::create($type, $user, null, '   ');
        $this->assertStringContainsString($user->fullname, $without_name_null->get_display_name());
        $this->assertStringContainsString($user->fullname, $without_name_zero->get_display_name());
        $this->assertStringContainsString($type->get_display_name(), $without_name_null->get_display_name());
        $this->assertStringContainsString($type->get_display_name(), $without_name_zero->get_display_name());
    }

    /**
     * Test that is_modified in item returns whether it has been modified correctly
     */
    public function test_model_item_is_modified(): void {
        self::setAdminUser();

        $user = $this->generator()->create_evidence_user(['username' => 'user_one']);
        $type = $this->generator()->create_evidence_type(['name' => 'Type']);

        $item = new entity\evidence_item();
        $item->name        = 1;
        $item->typeid      = $type->get_id();
        $item->user_id     = $user->id;
        $item->created_by  = 1;
        $item->modified_by = 1;
        $item->status      = 1;
        $item->save();

        self::setAdminUser();
        $item_model = models\evidence_item::load_by_id($item->id);

        $this->assertFalse($item_model->is_modified());

        $item->do_not_update_timestamps();

        $item->modified_at = -1;
        $item->save();

        $item_model = models\evidence_item::load_by_id($item->id);

        $this->assertTrue($item_model->is_modified());
    }

    /**
     * Test that get_data in the item model returns relevant data
     */
    public function test_model_item_get_data(): void {
        /** @var models\evidence_item[] $items */
        $item_count = 3;
        $items = [];
        $item_data = [];

        $field_one = $this->generator()->create_evidence_field(['sortorder' => 1]);
        $field_two = $this->generator()->create_evidence_field(['sortorder' => 0]);

        self::setAdminUser();
        $user = $this->generator()->create_evidence_user();
        $this->generator()->create_evidence_type();
        for ($i = 0; $i < $item_count; $i++) {
            $data = [
                'name'    => $i,
                'user_id' => $user->id,
            ];
            $item = $this->generator()->create_evidence_item($data);
            $item_entity = $this->items()->all()[$i];
            $data_one = (new entity\evidence_field_data([
                'evidenceid' => $item->get_id(),
                'fieldid' => $field_one->id,
                'data' => $i
            ]))->save();
            $data_two = (new entity\evidence_field_data([
                'evidenceid' => $item->get_id(),
                'fieldid' => $field_two->id,
                'data' => $i
            ]))->save();
            $items[] = $item;
            $item_data[] = array_merge($item_entity->to_array(), [
                'data' => [$data_two->to_array(), $data_one->to_array()], // Check its got the correct sort order
                'in_use' => $item->in_use(),
                'is_modified' => $item->is_modified(),
                'can_modify' => $item->can_modify(),
                'display_name' => $item->get_display_name(),
                'is_creator' => true,
                'is_for_current_user' => false,
                'type' => $item->type,
            ]);
        }

        for ($i = 0; $i < $item_count; $i++) {
            $this->assertEquals($item_data[$i], $items[$i]->get_data());
        }
    }

    /**
     * Test that get_type in the item model returns an instance of evidence type model
     */
    public function test_model_item_get_type(): void {
        self::setAdminUser();
        $type1 = $this->generator()->create_evidence_type();
        $type2 = $this->generator()->create_evidence_type();
        $item1 = $this->generator()->create_evidence_item(['typeid' => $type1->get_id()]);
        $item2 = $this->generator()->create_evidence_item(['typeid' => $type2->get_id()]);
        $this->assertEquals($type1->get_id(), $item1->type->id);
        $this->assertEquals($type2->id, $item2->get_type()->get_id());
    }

    /**
     * Make sure the get_display_creation_date() and get_display_last_modified_date() methods correctly return the formatted date
     */
    public function test_model_display_date_methods(): void {
        self::setAdminUser();
        $item_entity = $this->generator()->create_evidence_item_entity(['created_at' => 0, 'modified_at' => 0]);

        $this->assertEquals(0, $item_entity->model->get_data()['created_at']);
        $this->assertEquals(0, $item_entity->model->get_data()['modified_at']);

        $this->assertEquals(
            userdate($item_entity->created_at, get_string('strftimedatetime', 'core_langconfig')),
            $item_entity->model->get_display_created_at()
        );
        $this->assertEquals(
            userdate($item_entity->modified_at, get_string('strftimedatetime', 'core_langconfig')),
            $item_entity->model->get_display_modified_at()
        );
    }

}
