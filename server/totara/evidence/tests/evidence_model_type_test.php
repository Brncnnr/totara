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
class totara_evidence_model_type_testcase extends totara_evidence_testcase {

    /**
     * Test that in_use check works correctly
     */
    public function test_model_type_in_use(): void {
        self::setAdminUser();
        $type = $this->generator()->create_evidence_type(['name' => 'Type']);
        $this->assertFalse($type->in_use());
        $item = $this->generator()->create_evidence_item(['type' => 'Type']);
        $this->assertTrue($type->in_use());
        $item->delete();
        $this->assertFalse($type->in_use());
    }

    /**
     * Test that can_modify() check works correctly
     */
    public function test_model_type_can_modify(): void {
        $context = context_system::instance();
        $role = builder::table('role')->where('shortname', 'user')->value('id');

        self::setAdminUser();

        $user = $this->generator()->create_evidence_user();

        $type = $this->generator()->create_evidence_type(['name' => 'Type']);

        self::setUser($user->id);

        // Can't modify due to not having the capability
        $this->assertFalse($type->can_modify());
        try {
            $this->assertFalse($type->can_modify(true));
            $this->fail('Expected required_capability_exception');
        } catch (required_capability_exception $exception) {
            $this->assertStringContainsString('Manage evidence types', $exception->getMessage());
        }

        // Can't modify due to type being in use
        $item = $this->generator()->create_evidence_item_entity(['type' => 'Type']);
        self::setUser($user->id);
        assign_capability('totara/evidence:managetype', CAP_ALLOW, $role, $context);
        $this->assertFalse($type->can_modify());
        try {
            $this->assertFalse($type->can_modify(true));
            $this->fail('Expected in use coding exception');
        } catch (coding_exception $exception) {
            $this->assertStringContainsString('currently in use elsewhere', $exception->getMessage());
        }

        // We can specify that we don't care if the type is in use
        $this->assertTrue($type->can_modify(false, false));
        $this->assertTrue($type->can_modify(true, false));

        // Can modify if have permissions and not in use
        $item->delete();
        $this->assertTrue($type->can_modify());
        $this->assertTrue($type->can_modify(true));

        // Can't modify a type that has been deleted.
        $type_entity = $this->generator()->create_evidence_type_entity(['name' => 'Type']);
        $deleted_type_model = models\evidence_type::load_by_entity($type_entity);
        $type_entity->delete();
        $this->assertFalse($deleted_type_model->can_modify());
        try {
            $this->assertFalse($deleted_type_model->can_modify(true));
            $this->fail('Expected evidence type no longer exists exception');
        } catch (coding_exception $exception) {
            $this->assertStringContainsString('Evidence type no longer exists', $exception->getMessage());
        }
    }

    /**
     * Test that a type is created correctly
     */
    public function test_model_type_create(): void {
        self::setAdminUser();
        $admin = user::logged_in();

        $name = 'Name';
        $idnumber = 'ID Number';
        $description = 'Description';

        $type = models\evidence_type::create($name, $idnumber, $description);
        $this->assertEquals($name, $type->get_display_name());
        $this->assertEquals($idnumber, $type->get_display_idnumber());
        $this->assertEquals($description, $type->get_display_description());
        $this->assertEquals($admin->id, $type->created_by_user->id);
        $this->assertEquals(models\evidence_type::LOCATION_EVIDENCE_BANK, $type->location);
        $this->assertEquals(models\evidence_type::STATUS_ACTIVE, $type->status);
    }

    /**
     * Test that a type can not have an empty name
     */
    public function test_model_type_create_with_empty_name(): void {
        self::setAdminUser();

        try {
            models\evidence_type::create('');
            self::fail('Expected exception not thrown');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('A name must be specified', $ex->getMessage());
            $this->assertCount(0, $this->types());
        }

        try {
            models\evidence_type::create('   ');
            self::fail('Expected exception not thrown');
        } catch (coding_exception $ex) {
            $this->assertStringContainsString('A name must be specified', $ex->getMessage());
            $this->assertCount(0, $this->types());
        }

        $type = models\evidence_type::create('Name');
        $this->assertEquals('Name', $type->name);
    }

    /**
     * Test that a type can be updated correctly
     */
    public function test_model_type_update(): void {
        self::setAdminUser();
        $this->generator()->create_evidence_type(['name' => 'Type']);

        $name = 'Name';
        $idnumber = 'ID Number';
        $description = 'Description';
        $name_new = 'Name New';
        $idnumber_new = 'ID Number New';
        $description_new = 'Description New';

        $type = $this->generator()->create_evidence_type(['name' => $name, 'idnumber' => $idnumber, 'description' => $description]);

        $type->update($name_new, $idnumber_new, $description_new);
        $this->assertNotEquals($name, $type->get_display_name());
        $this->assertNotEquals($idnumber, $type->get_display_idnumber());
        $this->assertNotEquals($description, $type->get_display_description());
        $this->assertEquals($name_new, $type->get_display_name());
        $this->assertEquals($idnumber_new, $type->get_display_idnumber());
        $this->assertEquals($description_new, $type->get_display_description());
    }

    /**
     * Test that only the creator of an evidence type may edit it
     */
    public function test_model_type_update_when_in_use(): void {
        self::setAdminUser();

        $type = $this->generator()->create_evidence_type(['name' => 'Type']);
        $type->update(null, null, 'XYZ');
        $type->update_status(models\evidence_type::STATUS_ACTIVE);

        $this->generator()->create_evidence_item(['type' => 'Type']);

        // Allowed to change the status (ie visibility) of the type when in use.
        $type->update_status(models\evidence_type::STATUS_HIDDEN);

        // Not allowed to modify the fundamental properties of the evidence type.
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches('/currently in use elsewhere/');
        $type->update(null, null, 'XYZ');
    }

    /**
     * Test that no two types can have the same ID number when creating
     */
    public function test_model_type_create_duplicate_idnumber(): void {
        self::setAdminUser();

        // Can create multiple types with blank ID number
        models\evidence_type::create('Type', '');
        models\evidence_type::create('Type', '');

        models\evidence_type::create('Type', '#1');
        models\evidence_type::create('Type', '#2');
        models\evidence_type::create('Type', '#3');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('ID number already exists');
        models\evidence_type::create('Type', '#1');
    }

    /**
     * Test that no two types can have the same ID number when updating
     */
    public function test_model_type_update_duplicate_idnumber(): void {
        self::setAdminUser();

        $type_one = models\evidence_type::create('Type', '#1');
        $type_two = models\evidence_type::create('Type', '#2');

        // Can update it to a blank ID number
        models\evidence_type::create('Type', '');
        $type_one->update(null, '');

        $type_one->update(null, '#3');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('ID number already exists');
        $type_two->update(null, '#3');
    }

    /**
     * Test that a name must be specified when creating a type
     */
    public function test_model_type_create_with_blank_name(): void {
        self::setAdminUser();
        models\evidence_type::create('Type');
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('A name must be specified');
        models\evidence_type::create('');
    }

    /**
     * Test that type cannot be updated without any changes specified
     */
    public function test_model_type_update_nothing(): void {
        self::setAdminUser();

        $type = models\evidence_type::create('A', 'B', 'C');

        $type->update('X');
        $type->update(null, 'Y');
        $type->update(null, null, 'Z');
        $type->update(null, null, null, FORMAT_HTML);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Must specify an attribute to change');
        $type->update(null, null, null, null);
    }

    /**
     * Test that type cannot be updated after it has been deleted
     */
    public function test_model_type_update_after_delete(): void {
        self::setAdminUser();

        $type = models\evidence_type::create('XYZ');
        $type->delete();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Must specify an attribute to change');
        $type->update();
    }

    /**
     * Test that type's visibility cannot be updated after it has been deleted
     */
    public function test_model_type_update_visibility_after_delete(): void {
        self::setAdminUser();

        $type = models\evidence_type::create('XYZ');
        $type->delete();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Evidence type no longer exists');
        $type->update_status(models\evidence_type::STATUS_ACTIVE);
    }

    /**
     * Test that a permissions exception is thrown when creating/updating a type without the right permissions
     */
    public function test_model_type_create_or_update_incorrect_permissions(): void {
        self::setAdminUser();
        $type = models\evidence_type::create('A', 'B', 'C');

        self::setGuestUser();
        $this->expectException(required_capability_exception::class);
        $type->update('!!!');
    }

    /**
     * Test that type and its fields are deleted properly
     */
    public function test_model_type_delete(): void {
        self::setAdminUser();

        $fields_count = 3;
        $type = $this->generator()->create_evidence_type([
            'name'       => 0,
            'fields'     => $fields_count
        ]);
        $fields = $this->fields();

        $dummy_types = 3;
        for ($i = 0; $i < $dummy_types; $i++) {
            $this->generator()->create_evidence_type(['fields' => 1]);
        }
        $dummy_fields = $dummy_types;

        $this->assertCount($dummy_types + 1, $this->types());
        $this->assertCount($fields_count + $dummy_fields, $this->fields());
        $this->assertNotNull(entity\evidence_type::repository()->find($type->get_id()));
        foreach ($fields as $field) {
            $this->assertNotNull(entity\evidence_type_field::repository()->find($field->id));
        }

        // Guest user doesn't have permission
        self::setGuestUser();
        try {
            $type->delete();
            self::fail('Expected exception not thrown');
        } catch (required_capability_exception $e) {
            $this->assertCount($dummy_types + 1, $this->types());
            $this->assertCount($fields_count + $dummy_fields, $this->fields());
        }

        // Can't delete when in use
        self::setAdminUser();
        $item = $this->generator()->create_evidence_item(['type' => 0]);
        try {
            $type->delete();
            self::fail('Expected exception not thrown');
        } catch (coding_exception $e) {
            $this->assertStringContainsString('currently in use elsewhere', $e->getMessage());
            $this->assertCount($dummy_types + 1, $this->types());
            $this->assertCount($fields_count + $dummy_fields, $this->fields());
        }

        // Can delete when not in use
        $item->delete();
        $type_id = $type->get_id();
        $type->delete();
        $this->assertNull(entity\evidence_type::repository()->find($type_id));
        $this->assertCount($dummy_types, $this->types());
        $this->assertCount($dummy_fields, $this->fields());
        foreach ($fields as $field) {
            $this->assertNull(entity\evidence_type_field::repository()->find($field->id));
        }
    }

    /**
     * Test that the model methods pertaining to the visibility of a type work correctly
     */
    public function test_model_type_update_visibility_is_visible(): void {
        self::setAdminUser();
        $shown = $this->generator()->create_evidence_type(['name' => 'shown', 'status' => models\evidence_type::STATUS_ACTIVE]);
        $hidden = $this->generator()->create_evidence_type(['name' => 'hidden', 'status' => models\evidence_type::STATUS_HIDDEN]);

        self::setGuestUser();

        $this->assertEquals(models\evidence_type::STATUS_ACTIVE, $shown->get_data()['status']);
        $this->assertTrue($shown->is_visible());
        $this->assertEquals(models\evidence_type::STATUS_HIDDEN, $hidden->get_data()['status']);
        $this->assertFalse($hidden->is_visible());

        try {
            self::setGuestUser();
            $shown->update_status(models\evidence_type::STATUS_HIDDEN);
            self::fail('Expected exception not thrown');
        } catch (required_capability_exception $ex) {
            self::setAdminUser();
            $this->assertTrue(models\evidence_type::load_by_id($shown->get_id())->is_visible());
        } try {
            self::setGuestUser();
            $shown->update_status(models\evidence_type::STATUS_ACTIVE);
            self::fail('Expected exception not thrown');
        } catch (required_capability_exception $ex) {
            self::setAdminUser();
            $this->assertTrue(models\evidence_type::load_by_id($shown->get_id())->is_visible());
        }

        self::setAdminUser();

        $shown->update_status(models\evidence_type::STATUS_ACTIVE);
        $this->assertTrue(models\evidence_type::load_by_id($shown->get_id())->is_visible());
        $shown->update_status(models\evidence_type::STATUS_HIDDEN);
        $this->assertFalse(models\evidence_type::load_by_id($shown->get_id())->is_visible());
        $shown->update_status(models\evidence_type::STATUS_ACTIVE);
        $this->assertTrue(models\evidence_type::load_by_id($shown->get_id())->is_visible());

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage('Invalid status \'-1\' provided to evidence type with ID ' . $shown->id);
        $shown->update_status(-1);
    }

    /**
     * Test that get_data in the type model returns relevant data
     */
    public function test_model_type_get_data(): void {
        $type_count = 3;
        $types = [];
        $type_data = [];

        self::setAdminUser();
        for ($i = 0; $i < $type_count; $i++) {
            $children_count = $i * 5;
            $data = [
                'name'        => "Test Info $i Name",
                'idnumber'    => "Test Info $i ID Number",
                'description' => "Test Info $i Description"
            ];
            $type = $this->generator()->create_evidence_type($data);
            $type_entity = $this->types()->all()[$i];
            for ($j = 0; $j < $children_count; $j++) {
                $this->generator()->create_evidence_item(['typeid' => $type->get_id()]);
                $this->generator()->create_evidence_field(['typeid' => $type->get_id(), 'sortorder' => $j]);
            }
            $types[] = $type;
            $type_data[] = array_merge($type_entity->to_array(), $data, [
                'items' => $type_entity->items->to_array(),
                'fields' => $type_entity->fields->to_array(),
                'in_use' => $children_count > 0,
                'is_visible' => $type->is_visible(),
                'is_system' => $type->is_system(),
                'is_modified' => $type->is_modified(),
                'is_creator' => $type->is_creator(),
                'can_modify' => $type->can_modify(),
                'display_name' => $type->get_display_name(),
                'display_idnumber' => $type->get_display_idnumber(),
                'display_description' => $type->get_display_description(),
            ]);
        }

        for ($i = 0; $i < $type_count; $i++) {
            $this->assertEquals($type_data[$i], $types[$i]->get_data());
        }
    }

    /**
     * Test that get_name() and get_display_name() Show unescaped and escaped text
     */
    public function test_model_type_get_name(): void {
        $unescaped = "Test<script>console.log('Error')</script> Text";

        self::setAdminUser();
        $type = models\evidence_type::create($unescaped);

        $this->assertEquals($unescaped, $type->name);
        $this->assertStringContainsString('Test', $type->display_name);
        $this->assertStringContainsString('Text', $type->display_name);
        $this->assertStringContainsString('console.log(', $type->display_name);
        $this->assertStringNotContainsString('<script>', $type->display_name);
    }

    /**
     * Test that get_idnumber() and get_display_idnumber() Show unescaped and escaped text
     */
    public function test_model_type_get_idnumber(): void {
        $unescaped = "Test<script>console.log('Error')</script> Text";

        self::setAdminUser();
        $type = models\evidence_type::create('Name', $unescaped);

        $this->assertEquals($unescaped, $type->idnumber);
        $this->assertStringContainsString('Test', $type->get_display_idnumber());
        $this->assertStringContainsString('Text', $type->get_display_idnumber());
        $this->assertStringContainsString('console.log(', $type->get_display_idnumber());
        $this->assertStringNotContainsString('<script>', $type->get_display_idnumber());
    }

    /**
     * Test that get_description() and get_display_description() Show unescaped and escaped text
     */
    public function test_model_type_get_description(): void {
        $unescaped = "Test<script>console.log('Error')</script> Text";

        self::setAdminUser();
        $type = models\evidence_type::create('Name', null, $unescaped);

        $this->assertEquals($unescaped, $type->description);
        $this->assertStringContainsString("Test Text", $type->display_description);
        $this->assertStringNotContainsString($unescaped, $type->display_description);
    }

    /**
     * Test that get_descriptionformat() returns properly
     */
    public function test_model_type_get_descriptionformat(): void {
        self::setAdminUser();
        $type = models\evidence_type::create('Name');
        $this->assertEquals(FORMAT_HTML, $type->get_descriptionformat());
        $type->update(null, null, null, FORMAT_PLAIN);
        $this->assertEquals(FORMAT_PLAIN, $type->get_descriptionformat());
    }

    /**
     * Test model capability handling
     */
    public function test_model_type_can_manage(): void {
        $context = context_system::instance();
        $role = builder::table('role')->where('shortname', 'user')->value('id');

        self::setAdminUser();
        $user = $this->generator()->create_evidence_user();

        self::setUser($user->id);
        unassign_capability('totara/evidence:managetype', $role);

        try {
            models\evidence_type::can_manage(true);
            self::fail('Expected exception not thrown');
        } catch (required_capability_exception $ex) {
            $this->assertFalse(models\evidence_type::can_manage());
        }

        assign_capability('totara/evidence:managetype', CAP_ALLOW, $role, $context);
        $this->assertTrue(models\evidence_type::can_manage());
        $this->assertTrue(models\evidence_type::can_manage(true));
    }

    /**
     * Make sure the is_system() and can_modify() methods are correct
     */
    public function test_model_type_location_is_system_can_modify(): void {
        self::setAdminUser();
        $standard_type = $this->generator()->create_evidence_type();
        $system_type = $this->generator()->create_evidence_type([
            'location' => models\evidence_type::LOCATION_RECORD_OF_LEARNING,
        ]);
        $course_completion_type = models\evidence_type::load_by_default_system_type('course');

        // Only the default systen types are regarded as 'system' types
        $this->assertFalse($standard_type->is_system());
        $this->assertFalse($system_type->is_system());
        $this->assertTrue($course_completion_type->is_system());

        $this->assertTrue($standard_type->can_modify());
        $this->assertTrue($system_type->can_modify());
        $this->assertFalse($course_completion_type->can_modify());
    }

    /**
     * Make sure that a system type can not be updated
     */
    public function test_model_type_location_system_update(): void {
        self::setAdminUser();
        $standard_type = $this->generator()->create_evidence_type();
        $system_type = $this->generator()->create_evidence_type([
            'location' => models\evidence_type::LOCATION_RECORD_OF_LEARNING,
        ]);
        $course_completion_type = models\evidence_type::load_by_default_system_type('course');

        $standard_type->update('New Name');
        $system_type->update('New Name');

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Evidence type with ID {$course_completion_type->id} is a system type and can not be modified");
        $course_completion_type->update('New Name');
    }

    /**
     * Make sure that a system type can not have its status updated
     */
    public function test_model_type_location_system_update_status(): void {
        self::setAdminUser();
        $standard_type = $this->generator()->create_evidence_type();
        $system_type = $this->generator()->create_evidence_type([
            'location' => models\evidence_type::LOCATION_RECORD_OF_LEARNING,
        ]);
        $course_completion_type = models\evidence_type::load_by_default_system_type('course');

        $standard_type->update_status(models\evidence_type::STATUS_HIDDEN);
        $system_type->update_status(models\evidence_type::STATUS_HIDDEN);

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Evidence type with ID {$course_completion_type->id} is a system type and can not be modified");
        $course_completion_type->update_status(models\evidence_type::STATUS_HIDDEN);
    }

    /**
     * Make sure that a system type can not be deleted
     */
    public function test_model_type_location_system_delete(): void {
        self::setAdminUser();
        $standard_type = $this->generator()->create_evidence_type();
        $system_type = $this->generator()->create_evidence_type([
            'location' => models\evidence_type::LOCATION_RECORD_OF_LEARNING,
        ]);
        $course_completion_type = models\evidence_type::load_by_default_system_type('course');

        $standard_type->delete();
        $system_type->delete();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Evidence type with ID {$course_completion_type->id} is a system type and can not be modified");
        $course_completion_type->delete();
    }

    /**
     * Make sure that a type's name and description supports multi-lang but can be overridden
     */
    public function test_model_type_multilang(): void {
        self::setAdminUser();

        /** @var entity\evidence_type $course_type_entity */
        $course_type_entity = entity\evidence_type::repository()
            ->where('idnumber', 'coursecompletionimport')->one();
        $course_type = models\evidence_type::load_by_entity($course_type_entity);

        /** @var entity\evidence_type $certification_type_entity */
        $certification_type_entity = entity\evidence_type::repository()
            ->where('idnumber', 'certificationcompletionimport')->one();
        $certification_type = models\evidence_type::load_by_entity($certification_type_entity);

        // The course completion system type should have these values by default
        $this->assertEquals('multilang:completion_course', $course_type->name);
        $this->assertEquals('multilang:completion_course', $course_type->description);
        $this->assertEquals(
            get_string('system_type_name:completion_course', 'totara_evidence'),
            $course_type->get_display_name()
        );
        $this->assertEquals(
            get_string('system_type_desc:completion_course', 'totara_evidence'),
            $course_type->get_display_description()
        );

        // The certification completion system type should have these values by default
        $this->assertEquals('multilang:completion_certification', $certification_type->name);
        $this->assertEquals('multilang:completion_certification', $certification_type->description);
        $this->assertEquals(
            get_string('system_type_name:completion_certification', 'totara_evidence'),
            $certification_type->get_display_name()
        );
        $this->assertEquals(
            get_string('system_type_desc:completion_certification', 'totara_evidence'),
            $certification_type->get_display_description()
        );

        // Specifying another language string identifier for the name should resolve correctly
        $course_type_entity->name = 'multilang:completion_legacy';
        $course_type_entity->save();
        $course_type = models\evidence_type::load_by_entity($course_type_entity);
        $this->assertEquals('multilang:completion_legacy', $course_type->name);
        $this->assertEquals(
            get_string('system_type_name:completion_legacy', 'totara_evidence'),
            $course_type->get_display_name()
        );

        // Specifying another language string identifier for the description should resolve correctly
        $certification_type_entity->description = 'multilang:completion_legacy';
        $certification_type_entity->save();
        $certification_type = models\evidence_type::load_by_entity($certification_type_entity);
        $this->assertEquals('multilang:completion_legacy', $certification_type->description);
        $this->assertEquals(
            get_string('system_type_desc:completion_legacy', 'totara_evidence'),
            $certification_type->get_display_description()
        );

        // Specifying a custom string for the description should just be displayed as is
        $custom_string = 'Custom Course Description Text';
        $course_type_entity->description = $custom_string;
        $course_type_entity->save();
        $course_type = models\evidence_type::load_by_entity($course_type_entity);
        $this->assertEquals($custom_string, $course_type->description);
        $this->assertEquals($custom_string, $course_type->get_display_description());

        // Specifying a custom string for the name should just be displayed as is
        $custom_string = 'Custom Certification Name Text';
        $certification_type_entity->name = $custom_string;
        $certification_type_entity->save();
        $certification_type = models\evidence_type::load_by_entity($certification_type_entity);
        $this->assertEquals($custom_string, $certification_type->name);
        $this->assertEquals($custom_string, $certification_type->get_display_name());
    }

    /**
     * @return void
     */
    public function test_load_data(): void {
        self::setAdminUser();
        $type = $this->generator()->create_evidence_type();
        $this->generator()->create_evidence_item(['typeid' => $type->get_id()]);

        $data = $type->get_data();
        self::assertNotEmpty($data['items']);


        $data = $type->get_data(false);
        self::assertEmpty($data['items']);
    }

}