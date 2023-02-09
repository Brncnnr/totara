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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_perform
 * @category test
 */

use mod_perform\constants;
use mod_perform\notification\composer;
use mod_perform\notification\placeholder;
use mod_perform\notification\recipient;
use mod_perform\notification\trigger;
use totara_core\entity\relationship as relationship_entity;
use totara_core\relationship\relationship;

require_once(__DIR__ . '/notification_testcase.php');

/**
 * @coversDefaultClass \mod_perform\notification\composer
 * @group perform
 * @group perform_notifications
 */
class mod_perform_notification_composer_testcase extends mod_perform_notification_testcase {
    public function setUp(): void {
        parent::setUp();
        $this->mock_loader(null);
        $this->overrideLangString('template_mock_one_subject_subject', 'mod_perform', 'mock #1 subject of subject', true);
        $this->overrideLangString('template_mock_one_appraiser_body', 'mod_perform', 'mock #1 body of appraiser', true);
        $this->overrideLangString('template_mock_one_manager_subject', 'mod_perform', 'mock #1 subject of manager', true);
        $this->overrideLangString('template_mock_one_manager_body', 'mod_perform', 'mock #1 body of manager', true);
        $this->overrideLangString('template_mock_one_perform_mentor_subject', 'mod_perform', 'mock #1 subject of mentor', true);
        $this->overrideLangString('template_mock_one_perform_reviewer_body', 'mod_perform', 'mock #1 body of reviewer', true);
        $this->overrideLangString('template_mock_one_perform_external_subject', 'mod_perform', 'mock #1 subject of external', true);
        $this->overrideLangString('template_mock_one_perform_external_body', 'mod_perform', 'mock #1 body of external', true);
    }

    /**
     * @covers ::set_relationship
     */
    public function test_set_relationship() {
        $prop = new ReflectionProperty(composer::class, 'lang_key_prefix');
        $prop->setAccessible(true);
        $composer = new composer('mock_one');
        $subject_relationship = $this->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $this->assertTrue($composer->set_relationship($subject_relationship));
        $this->assertEquals('template_mock_one_' . constants::RELATIONSHIP_SUBJECT . '_', $prop->getValue($composer));

        $composer = new composer('mock_two');
        $peer_relationship = $this->get_core_relationship(constants::RELATIONSHIP_PEER);
        $this->assertTrue($composer->set_relationship($peer_relationship));
        $this->assertEquals('template_mock_two_' . constants::RELATIONSHIP_PEER . '_', $prop->getValue($composer));

        $composer = new composer('mock_three');
        $entity = relationship_entity::repository()->where('idnumber', constants::RELATIONSHIP_EXTERNAL)->one();
        $entity->idnumber = '';
        $external_relationship = relationship::load_by_entity($entity);
        $this->assertFalse($composer->set_relationship($external_relationship));
        $this->assertNull($prop->getValue($composer));
    }

    /**
     * @covers ::get_subject_lang_key
     */
    public function test_get_subject_lang_key() {
        $composer = new composer('mock_one');
        try {
            $composer->get_subject_lang_key();
            $this->fail('coding_exception expected');
        } catch (coding_exception $e) {
        }

        $subject_relationship = $this->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $this->assertTrue($composer->set_relationship($subject_relationship));
        $this->assertEquals('template_mock_one_' . constants::RELATIONSHIP_SUBJECT . '_subject', $composer->get_subject_lang_key());

        $peer_relationship = $this->get_core_relationship(constants::RELATIONSHIP_PEER);
        $this->assertTrue($composer->set_relationship($peer_relationship));
        $this->assertEquals('template_mock_one_' . constants::RELATIONSHIP_PEER . '_subject', $composer->get_subject_lang_key());
    }

    /**
     * @covers ::get_body_lang_key
     */
    public function test_get_body_lang_key() {
        $composer = new composer('mock_one');
        try {
            $composer->get_body_lang_key();
            $this->fail('coding_exception expected');
        } catch (coding_exception $e) {
        }

        $subject_relationship = $this->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $this->assertTrue($composer->set_relationship($subject_relationship));
        $this->assertEquals('template_mock_one_' . constants::RELATIONSHIP_SUBJECT . '_body', $composer->get_body_lang_key());

        $peer_relationship = $this->get_core_relationship(constants::RELATIONSHIP_PEER);
        $this->assertTrue($composer->set_relationship($peer_relationship));
        $this->assertEquals('template_mock_one_' . constants::RELATIONSHIP_PEER . '_body', $composer->get_body_lang_key());
    }

    /**
     * @covers ::get_subject_lang_string
     */
    public function test_get_subject_lang_string() {
        $composer = new composer('mock_one');
        try {
            $composer->get_subject_lang_string(new placeholder());
            $this->fail('coding_exception expected');
        } catch (coding_exception $e) {
        }

        $subject_relationship = $this->get_core_relationship(constants::RELATIONSHIP_SUBJECT);
        $this->assertTrue($composer->set_relationship($subject_relationship));
        $this->assertEquals('mock #1 subject of subject', $composer->get_subject_lang_string(new placeholder()));

        $mentor_relationship = $this->get_core_relationship(constants::RELATIONSHIP_MENTOR);
        $this->assertTrue($composer->set_relationship($mentor_relationship));
        $this->assertEquals('mock #1 subject of mentor', $composer->get_subject_lang_string(new placeholder()));
    }

    /**
     * @covers ::get_body_lang_string
     */
    public function test_get_body_lang_string() {
        $composer = new composer('mock_one');
        try {
            $composer->get_body_lang_string(new placeholder());
            $this->fail('coding_exception expected');
        } catch (coding_exception $e) {
        }

        $appraiser_relationship = $this->get_core_relationship(constants::RELATIONSHIP_APPRAISER);
        $this->assertTrue($composer->set_relationship($appraiser_relationship));
        $this->assertEquals('mock #1 body of appraiser', $composer->get_body_lang_string(new placeholder()));

        $reviewer_relationship = $this->get_core_relationship(constants::RELATIONSHIP_REVIEWER);
        $this->assertTrue($composer->set_relationship($reviewer_relationship));
        $this->assertEquals('mock #1 body of reviewer', $composer->get_body_lang_string(new placeholder()));
    }

    /**
     * @covers ::is_reminder
     */
    public function test_is_reminder() {
        $this->mock_loader([
            'mock_notification' => [
                'class' => mod_perform_mock_broker_one::class,
                'name' => 'mock notification',
                'trigger_type' => trigger::TYPE_ONCE,
                'recipients' => recipient::ALL,
            ],
            'mock_reminder' => [
                'class' => mod_perform_mock_broker_two::class,
                'name' => 'mock reminder',
                'trigger_type' => trigger::TYPE_ONCE,
                'recipients' => recipient::ALL,
                'is_reminder' => true,
            ],
        ]);
        $composer1 = new composer('mock_notification');
        $composer2 = new composer('mock_reminder');
        $this->assertFalse($composer1->is_reminder());
        $this->assertTrue($composer2->is_reminder());
    }

    /**
     * @covers ::compose
     */
    public function test_compose() {
        $composer = new composer('mock_one');
        try {
            $composer->compose(new placeholder());
            $this->fail('coding_exception expected');
        } catch (coding_exception $e) {
        }

        $manager_relationship = $this->get_core_relationship(constants::RELATIONSHIP_MANAGER);
        $this->assertTrue($composer->set_relationship($manager_relationship));
        $message = $composer->compose(new placeholder());
        $this->assertEquals('mock #1 subject of manager', $message->subject);
        $this->assertEquals('mock #1 body of manager', $message->fullmessage);
        $this->assertEquals('mock #1 body of manager', $message->smallmessage);
        $this->assertEquals(FORMAT_PLAIN, $message->fullmessageformat);
        $this->assertStringContainsString('mock #1 body of manager', $message->fullmessagehtml);

        $external_relationship = $this->get_core_relationship(constants::RELATIONSHIP_EXTERNAL);
        $this->assertTrue($composer->set_relationship($external_relationship));
        $message = $composer->compose(new placeholder());
        $this->assertEquals('mock #1 subject of external', $message->subject);
        $this->assertEquals('mock #1 body of external', $message->fullmessage);
        $this->assertEquals('mock #1 body of external', $message->smallmessage);
        $this->assertEquals(FORMAT_PLAIN, $message->fullmessageformat);
        $this->assertStringContainsString('mock #1 body of external', $message->fullmessagehtml);
    }
}
