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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_evidence
 */

use core\collection;
use core_phpunit\testcase;
use mod_perform\entity\activity\external_participant;
use mod_perform\models\activity\subject_instance;
use totara_evidence\models\evidence_item as evidence_item_model;
use totara_evidence\performelement_linked_review\evidence;
use mod_perform\entity\activity\subject_instance as subject_instance_entity;
use mod_perform\constants;
use mod_perform\models\activity\element;
use mod_perform\testing\generator as perform_generator;
use performelement_linked_review\models\linked_review_content;
use totara_evidence\entity\evidence_item as evidence_item_entity;

class totara_evidence_perform_linked_evidence_content_testcase extends testcase {

    public function test_query_linked_evidences_successful() {
        $user = $this->getDataGenerator()->create_user();
        [$evidence1, $evidence2] = $this->create_evidence_items($user);
        $dummy_subject_instance = subject_instance::load_by_entity(new subject_instance_entity([
            'id' => 123456,
            'subject_user_id' => $user->id,
        ]));

        $content_type = new evidence(context_system::instance());
        $result = $content_type->load_content_items(
            $dummy_subject_instance,
            collection::new([$evidence1->id, $evidence2->id]),
            null,
            true,
            time()
        );

        $this->assertEmpty($result);
    }

    /**
     * @return array
     */
    private function create_evidence_items(stdClass $user) {
        $evidence_generator = $this->getDataGenerator()->get_plugin_generator('totara_evidence');

        // Allow creation of actual files.
        $evidence_generator->set_create_files(true);

        // Create evidence type.
        $evidence_type = $evidence_generator->create_evidence_type([
            'name' => 'EvidenceType1',
            'field_types' => [
                'file',
                'checkbox',
            ],
        ]);

        $this->setUser($user);

        // Create evidence bank items.
        $evidence_item1 = $evidence_generator->create_evidence_item([
            'name' => 'Evidence1',
            'typeid' => $evidence_type->get_id(),
            'user_id' => $user->id,
        ]);
        $evidence_item2 = $evidence_generator->create_evidence_item([
            'name' => 'Evidence2',
            'typeid' => $evidence_type->get_id(),
            'user_id' => $user->id,
        ]);

        return [$evidence_item1, $evidence_item2];
    }

    /**
     * @return void
     */
    public function test_load_evidence_items(): void {
        // Create activity with the evidence attached.
        $data = $this->create_activity_data();
        $user = $data->subject_user1;
        $evidence_item1 = $data->evidence_item1;
        $evidence_item2 = $data->evidence_item2;
        $created_at = time();

        // Set user to activity subject.
        self::setUser($user);

        // Get content items.
        $content_items = collection::new([
            ['content_id' => $evidence_item1->id],
            ['content_id' => - 123],
            ['content_id' => $evidence_item2->id],
        ]);

        // Create linked evidence instance.
        $content_type = new evidence(context_system::instance());

        // Get content items for subject instance.
        $subject_instance_model = subject_instance::load_by_entity($data->subject_instance1);
        $result = $content_type->load_content_items(
            $subject_instance_model,
            $content_items,
            null,
            true,
            $created_at
        );

        self::assertIsArray($result);
        self::assertCount(2, $result);

        // Filter result to get evidence_item 1.
        $evidence_result_item1 = array_filter(
            $result,
            static function (array $item) use ($evidence_item1) {
                return (int)$item['id'] === (int)$evidence_item1->id;
            }
        );
        $evidence_result_item1 = array_shift($evidence_result_item1);
        $this->assertArrayHasKey('fields', $evidence_result_item1);

        // Extract content out of fields that we can test those individually.
        $contents = [];
        foreach ($evidence_result_item1['fields'] as &$field) {
            $contents[$field['type']] = $field['content'];
            unset($field['content']);
        }

        // Confirm that the basic values for evidence item 1 matches the expected result.
        $expected_content_evidence1 = [
            'id' => $evidence_item1->id,
            'display_name' => $evidence_item1->name,
            'type' => $evidence_item1->type->name,
            'content_type' => 'totara_evidence',
            'created_at' => '1 January 2017',
            'fields' => [
                [
                    'label' => 'Confirmation of Consent',
                    'type' => 'checkbox',
                ],
                [
                    'label' => 'Photo of Certificate',
                    'type' => 'file',
                ],
            ]
        ];
        self::assertEquals($expected_content_evidence1, $evidence_result_item1);

        // Confirm that content fields match (soft of - files names are random so cant
        // really compare actual values).
        foreach ($contents as $type => $content) {
            $content = json_decode($content, true);
            $this->assertArrayHasKey('html', $content);
            if ($type === 'file') {
                $this->assertArrayHasKey('url', $content);
                $this->assertArrayHasKey('file_name', $content);
                $this->assertArrayHasKey('file_size', $content);
            }
        }

        /** @var evidence_item_entity $expected_evidence_item1 */
        $expected_evidence_item1 = evidence_item_entity::repository()->find($evidence_item1->id);

        // Confirm that file contents match evidence item.
        foreach ($evidence_result_item1 as $key => $value) {
            if (property_exists($expected_evidence_item1, $key)) {
                $this->assertEquals($value, $expected_evidence_item1->{$key});
            }
        }
    }

    /**
     * @return void
     */
    public function test_external_participant_access_to_content(): void {
        // Create complete activity with evidence.
        $data = $this->create_activity_data();
        $evidence_item1 = $data->evidence_item1;
        $evidence_item3 = $data->evidence_item3;

        // Create external participant for subject instance 1.
        $perform_generator = perform_generator::instance();
        $perform_generator->generate_external_participant_instances(
            $data->subject_instance1->id,
            [
                'fullname' => 'A name',
                'email' => 'A email',
            ]
        );

        // Get external participant token.
        /** @var external_participant $external_participant */
        $external_participant = external_participant::repository()->get()->first();
        $token = $external_participant->token;

        // Reset user.
        $this->setGuestUser();

        // External participant should be able to access evidence item 1's content.
        $this->process_evidence_files($evidence_item1, $token, function ($expected_error) {
            $this->assertNull($expected_error);
        });

        // External participant should NOT be able to access evidence item 3's content
        // as that evidence belongs to subject user 2 and external participant is only
        // linked to subject user 1.
        $this->process_evidence_files($evidence_item3, $token, function ($expected_error) {
            $this->assertNotNull($expected_error);
            $this->assertInstanceOf(Exception::class, $expected_error);
            $this->assertEquals(
                'Sorry, the requested file could not be found',
                $expected_error->getMessage()
            );
        });

    }

    /**
     * @return void
     */
    public function test_cannot_delete_in_use(): void {
        // By linking the evidence item to a linked review the item should be
        // locked and cannot be deleted.
        $data = $this->create_activity_data();

        /** @var evidence_item_model $evidence_item1 */
        $evidence_item1 = $data->evidence_item1;

        $this->setAdminUser();

        try {
            $evidence_item1->delete();
            $this->fail('Expected item in use exception');
        } catch (Exception $e) {
            $this->assertEquals(
                "Coding error detected, it must be fixed by a programmer: Evidence item with ID {$evidence_item1->id} is currently in use elsewhere",
                $e->getMessage()
            );
        }
    }

    /**
     * @return stdClass
     */
    protected function create_activity_data(): stdClass {
        self::setAdminUser();
        $perform_generator = perform_generator::instance();
        $activity = $perform_generator->create_activity_in_container(['activity_name' => 'Test activity']);
        $section = $perform_generator->create_section($activity);

        // Create section relationships.
        $subject_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_SUBJECT]
        );
        $external_section_relationship = $perform_generator->create_section_relationship(
            $section,
            ['relationship' => constants::RELATIONSHIP_EXTERNAL]
        );

        // Assign subject_user to activity.
        $subject_user1 = self::getDataGenerator()->create_user(['firstname' => 'SubjectUser', 'One' => 'User']);
        $subject_instance1 = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $subject_user1->id
        ]);

        // Create subject participant instance.
        $subject_participant_section1 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user1,
            $subject_instance1->id,
            $section,
            $subject_section_relationship->core_relationship->id
        );

        // Create external participant instance.
        $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user1,
            $subject_instance1->id,
            $section,
            $external_section_relationship->core_relationship->id
        );

        // Assign subject_user to activity.
        $subject_user2 = self::getDataGenerator()->create_user(['firstname' => 'SubjectUser', 'Two' => 'User']);
        $subject_instance2 = $perform_generator->create_subject_instance([
            'activity_id' => $activity->id,
            'subject_user_id' => $subject_user2->id
        ]);

        // Create subject participant instance.
        $subject_participant_section2 = $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user2,
            $subject_instance2->id,
            $section,
            $subject_section_relationship->core_relationship->id
        );

        // Create external participant instance.
        $perform_generator->create_participant_instance_and_section(
            $activity,
            $subject_user2,
            $subject_instance2->id,
            $section,
            $external_section_relationship->core_relationship->id
        );

        // Create linked review element and attach to activity section.
        $element = element::create(
            $activity->get_context(),
            'linked_review',
            'title',
            '',
            json_encode([
                'content_type' => 'totara_evidence',
                'content_type_settings' => [],
                'selection_relationships' => [$subject_section_relationship->core_relationship_id],
            ])
        );
        $section_element = $perform_generator->create_section_element($section, $element);

        // Create evidence items.
        [$evidence_item1, $evidence_item2] = $this->create_evidence_items($subject_user1);
        [$evidence_item3, $evidence_item4] = $this->create_evidence_items($subject_user2);

        // Link evidence items to activity.
        $linked_assignment1 = linked_review_content::create(
            $evidence_item1->id,
            $section_element->id,
            $subject_participant_section1->participant_instance_id,
            false
        );
        $linked_assignment2 = linked_review_content::create(
            $evidence_item2->id,
            $section_element->id,
            $subject_participant_section1->participant_instance_id,
            false
        );
        // Link evidence items to activity.
        $linked_assignment3 = linked_review_content::create(
            $evidence_item3->id,
            $section_element->id,
            $subject_participant_section2->participant_instance_id,
            false
        );
        $linked_assignment4 = linked_review_content::create(
            $evidence_item4->id,
            $section_element->id,
            $subject_participant_section2->participant_instance_id,
            false
        );

        $data = new stdClass();
        $data->subject_user1 = $subject_user1;
        $data->subject_user2 = $subject_user2;
        $data->activity = $activity;
        $data->subject_instance1 = $subject_instance1;
        $data->subject_instance2 = $subject_instance2;
        $data->subject_participant_instance1 = $subject_participant_section1->participant_instance;
        $data->subject_participant_instance2 = $subject_participant_section2->participant_instance;
        $data->section_element = $section_element;
        $data->section = $section;
        $data->linked_assignment1 = $linked_assignment1;
        $data->linked_assignment2 = $linked_assignment2;
        $data->linked_assignment3 = $linked_assignment3;
        $data->linked_assignment4 = $linked_assignment4;
        $data->evidence_item1 = $evidence_item1;
        $data->evidence_item2 = $evidence_item2;
        $data->evidence_item3 = $evidence_item3;
        $data->evidence_item4 = $evidence_item4;

        return $data;
    }

    /**
     * @param evidence_item_model $evidence_item
     * @param string $token
     *
     * @return void
     */
    protected function process_evidence_files(evidence_item_model $evidence_item, string $token, callable $expected) {
        $field_data = $evidence_item->get_customfield_data();
        if ($field_data->count() <= 0) {
            $this->fail('Expected custom field files for evidence item');
        }

        foreach ($evidence_item->get_customfield_data() as $field_data) {
            $field = $field_data->field;
            if ($field->datatype === 'file') {
                // Get files.
                $context = context_system::instance();
                $fs = get_file_storage();
                $files = $fs->get_area_files(
                    $context->id,
                    'totara_customfield',
                    'evidence_filemgr',
                    $field_data->data,
                    null,
                    false
                );

                foreach ($files as $file) {
                    $this->validate_file_access($file, $token, $expected);
                }
            }
        }
    }

    /**
     * @param $file
     * @param $token
     * @param callable $expected
     */
    private function validate_file_access($file, $token, callable $expected) {
        // Install a custom error handler to shut up an error message when header() is called.
        set_error_handler(
            function (int $errno , string $errstr) {
                if (strpos($errstr, 'Cannot modify header information - headers already sent by') === false) {
                    return false;
                }
                return true;
            },
            E_WARNING
        );

        /** @var context $context */
        list($context, $course, $cm) = get_context_info_array($file->get_contextid());

        ob_start();
        $expected_error = null;
        try {
            // send_file_not_found() just throws moodle_exception.
            // send_stored_file() does not die if 'dontdie' is set.
            \totara_evidence\customfield_area\evidence::pluginfile(
                $course,
                $cm,
                $context,
                'evidence_filemgr',
                [
                    $file->get_itemid(),
                    $file->get_filename(),
                    $token
                ],
                false,
                [
                    'dontdie' => true,
                ]
            );
        } catch (Exception $e) {
            $expected_error = $e;
        }

        ob_end_clean();
        restore_error_handler();

        $expected($expected_error);
    }

}