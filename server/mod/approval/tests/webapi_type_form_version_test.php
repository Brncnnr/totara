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
 * @author Angela Kuznetsova <angela.kuznetsova@totaralearning.com>
 * @package mod_approval
 */

use core\date_format;
use core\format;
use core_phpunit\testcase;
use mod_approval\model\form\form;
use mod_approval\model\workflow\workflow as workflow_model;
use mod_approval\testing\approval_workflow_test_setup;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass mod_approval\webapi\resolver\type\form_version
 *
 * @group approval_workflow
 */
class mod_approval_webapi_type_form_version_testcase extends testcase {

    use webapi_phpunit_helper;
    use approval_workflow_test_setup;

    private const TYPE = 'mod_approval_form_version';

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $this->expectException(coding_exception::class);
        $this->expectExceptionMessage("Expected form_version model");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        [$workflow] = $this->create_workflow_and_assignment();
        $workflow = workflow_model::load_by_entity($workflow);
        $form = form::create('simple', 'test form');
        $form_version = $form->get_active_version();

        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $form_version, [], $workflow->get_context());
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        [$workflow] = $this->create_workflow_and_assignment();
        $workflow = workflow_model::load_by_entity($workflow);
        $form = form::create('simple', 'test form');
        $form_version = $form->get_active_version();

        $value = $this->resolve_graphql_type(self::TYPE, 'id', $form_version, [], $workflow->get_context());
        $this->assertEquals($form_version->id, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'version', $form_version, ['format' => format::FORMAT_PLAIN], $workflow->get_context());
        $this->assertEquals($form_version->version, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'status', $form_version, [], $workflow->get_context());
        $this->assertEquals($form_version->status, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'created', $form_version, ['format' => date_format::FORMAT_TIMESTAMP], $workflow->get_context());
        $this->assertEquals($form_version->created, $value);
        $value = $this->resolve_graphql_type(self::TYPE, 'updated', $form_version, ['format' => date_format::FORMAT_TIMESTAMP], $workflow->get_context());
        $this->assertEquals($form_version->updated, $value);
    }
}