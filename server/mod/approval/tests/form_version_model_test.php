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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use core\orm\query\builder;
use core_phpunit\testcase;
use mod_approval\entity\form\form as form_entity;
use mod_approval\entity\form\form_version as form_version_entity;
use mod_approval\exception\model_exception;
use mod_approval\form_schema\form_schema;
use mod_approval\model\form\form;
use mod_approval\model\form\form_version;
use mod_approval\model\status;

/**
 * @group approval_workflow
 * @coversDefaultClass mod_approval\model\form\form_version
 */
class mod_approval_form_version_model_testcase extends testcase {
    /** @var form */
    private $form;

    public function setUp(): void {
        parent::setUp();
        $this->form = form::create('simple', 'kia ora');
    }

    public function tearDown(): void {
        $this->form = null;
        parent::tearDown();
    }

    public function data_creation_succeeds(): array {
        return [
            'normal' => ['3.14.159', '[{"kia ora":"\ud83e\udd51"}]'],
            'version A' => ['A', '[]'],
            'version zero' => ['0', '[]'],
            'version thirty' => ['зо', '[]'],
            'version emoji' => ['⛄', '[]'],
        ];
    }

    /**
     * @param string $version_str
     * @param string $json_schema
     * @dataProvider data_creation_succeeds
     * @covers ::create
     * @covers ::is_draft
     */
    public function test_creation_succeeds(string $version_str, string $json_schema): void {
        $time = time();
        $version = form_version::create($this->form, $version_str, $json_schema, status::DRAFT);
        $this->assertInstanceOf(form_version::class, $version);
        $this->assertNotEmpty($version->id);
        $this->assertEquals($this->form->id, $version->form_id);
        $this->assertEquals($this->form->id, $version->form->id);
        $this->assertEquals($version_str, $version->version);
        $this->assertEquals($json_schema, $version->json_schema);
        $this->assertEquals(status::DRAFT, $version->status);
        $this->assertGreaterThanOrEqual($time, $version->created);
        $this->assertLessThanOrEqual($version->updated, $version->created);
        $this->assertTrue($version->is_draft());
        $this->assertFalse($version->is_active());
        $this->assertFalse($version->is_archived());
    }

    /**
     * @covers ::create
     */
    public function test_creation_fails_on_invalid_form(): void {
        $form_version_entity = form_version_entity::repository()->where('form_id', '=', $this->form->id)->one();
        $form_version_entity->status = status::DRAFT;
        $form_version_entity->save();
        $this->form->delete();
        try {
            form_version::create($this->form, '2', '[]');
            $this->fail('dml_write_exception expected');
        } catch (dml_write_exception $ex) {
            $this->assertStringContainsString('Error writing to database', $ex->getMessage());
        }
        $this->form = form::create('simple', 'tena koutou');
        builder::table(form_version_entity::TABLE)->delete();
        builder::table(form_entity::TABLE)->delete();
        try {
            form_version::create($this->form, '3', '[]');
            $this->fail('dml_write_exception expected');
        } catch (dml_write_exception $ex) {
            $this->assertStringContainsString('Error writing to database', $ex->getMessage());
        }
    }

    /**
     * @covers ::create
     */
    public function test_creation_fails_on_inactive_form(): void {
        $form_version_entity = form_version_entity::repository()->where('form_id', '=', $this->form->id)->one();
        $form_version_entity->status = status::DRAFT;
        $form_version_entity->save();
        $this->form->deactivate();

        try {
            form_version::create($this->form, '2', '[]');
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertEquals('Form must be active', $ex->debuginfo);
        }

    }

    public function data_creation_fails_on_invalid_parameter(): array {
        return [
            'empty version' => ['', '[]', 'Version cannot be empty'],
            'empty schema' => ['1', '', ''],
            'malicious schema' => ['1', '[i am malicious]', 'Malicious json schema'],
            'bogus schema (null)' => ['1', 'null', 'Malicious json schema'],
            'bogus schema (false)' => ['1', 'false', 'Malicious json schema'],
            'bogus schema (true)' => ['1', 'true', 'Malicious json schema'],
            'bogus schema (number)' => ['1', '42.195', 'Malicious json schema'],
            'bogus schema (string)' => ['1', '"yes!"', 'Malicious json schema'],
        ];
    }

    /**
     * @param string $version
     * @param string $json_schema
     * @param string $exception
     * @dataProvider data_creation_fails_on_invalid_parameter
     * @covers ::create
     */
    public function test_creation_fails_on_invalid_parameter(string $version, string $json_schema, string $exception): void {
        try {
            form_version::create($this->form, $version, $json_schema);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString($exception, $ex->getMessage());
        }
    }

    /**
     * @covers ::activate
     * @covers ::is_active
     * @covers ::archive
     * @covers ::is_archived
     */
    public function test_activate_and_archive(): void {
        $version = form_version::create($this->form, '1', '[]');
        $version->activate();
        $this->assertFalse($version->is_draft());
        $this->assertTrue($version->is_active());
        $this->assertFalse($version->is_archived());

        $version->archive();
        $this->assertFalse($version->is_draft());
        $this->assertFalse($version->is_active());
        $this->assertTrue($version->is_archived());
    }

    /**
     * @covers ::delete
     */
    public function test_delete(): void {
        global $DB;
        $version = form_version::create($this->form, '1', '[]', status::DRAFT);
        $this->assertCount(2, $DB->get_records('approval_form_version'));
        $version->delete();
        $this->assertCount(1, $DB->get_records('approval_form_version'));
    }

    /**
     * @covers ::delete
     */
    public function test_unable_to_delete_active(): void {
        $version = form_version::create($this->form, '1', '[]');
        $version->activate();
        try {
            $version->delete();
            $this->fail('Expected model_exception');
        } catch (model_exception $e) {
            $this->assertEquals('Only draft objects can be deleted', $e->debuginfo);
        }
    }

    /**
     * @covers ::set_schema
     */
    public function test_set_schema(): void {
        $version = form_version::create($this->form, '1', '[]');

        $new_schema = '{"a":"b"}';
        $version->set_schema(form_schema::from_json($new_schema), 2);
        $this->assertEquals(2, $version->version);
        $this->assertEquals($new_schema, $version->json_schema);

        // Works when active, too.
        $version->activate();
        $new_schema = '{"c":"d"}';
        $version->set_schema(form_schema::from_json($new_schema), 3);
        $this->assertEquals(3, $version->version);
        $this->assertEquals($new_schema, $version->json_schema);
    }
}
