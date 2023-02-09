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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_core
 */

use core\plugininfo\virtualmeeting as virtualmeeting_plugininfo;
use totara_core\entity\virtual_meeting as virtual_meeting_entity;
use totara_core\entity\virtual_meeting_auth as virtual_meeting_auth_entity;
use totara_core\entity\virtual_meeting_config as virtual_meeting_config_entity;
use totara_core\virtualmeeting\authoriser\authoriser;
use totara_core\virtualmeeting\authoriser\oauth2_authoriser;
use totara_core\virtualmeeting\dto\meeting_dto;
use totara_core\virtualmeeting\dto\meeting_edit_dto;
use totara_core\virtualmeeting\plugin\factory\auth_factory;
use totara_core\virtualmeeting\plugin\factory\factory;
use totara_core\virtualmeeting\plugin\provider\auth_provider;
use totara_core\virtualmeeting\plugin\provider\provider;
use totara_core\virtualmeeting\storage;
use totara_core\virtualmeeting\user_auth;
use totara_core\virtualmeeting\virtual_meeting as virtual_meeting_model;
use totara_core\virtualmeeting\virtual_meeting_auth as virtual_meeting_auth_model;
use virtualmeeting_poc_app\poc_auth_provider;
use virtualmeeting_poc_app\poc_factory;
use virtualmeeting_poc_app\poc_service_provider;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/code_quality_testcase.php');

/**
 * Class totara_core_code_quality_testcase
 */
class totara_core_code_quality_testcase extends totara_core_code_quality_testcase_base {
    /**
     * @var string[]
     */
    private $tested_classes = [
        // self test
        totara_core_code_quality_testcase::class,

        virtualmeeting_plugininfo::class,
        virtual_meeting_entity::class,
        virtual_meeting_auth_entity::class,
        virtual_meeting_config_entity::class,
        virtual_meeting_model::class,
        virtual_meeting_auth_model::class,

        auth_factory::class,
        auth_provider::class,
        authoriser::class,
        factory::class,
        oauth2_authoriser::class,
        provider::class,
        storage::class,
        user_auth::class,
        meeting_dto::class,
        meeting_edit_dto::class,

        // virtualmeeting plugins
        poc_auth_provider::class,
        poc_factory::class,
        poc_service_provider::class,
    ];

    /**
     * @inheritDoc
     */
    protected function get_classes_to_test(): array {
        $tested_classes = $this->tested_classes;
        return $tested_classes;
    }

    /**
     * @inheritDoc
     */
    protected function get_whitelist_crlf(): array {
        return [
            'tabexport',
            // some old files we don't care about for now
            'classes/hook/calendar_upcoming_event.php',
            'classes/watcher/calendar_dynamic_content.php',
        ];
    }

    /**
     * Self-test inspect_class_docblock().
     */
    public function test_inspect_class_docblock(): void {
        require_once(__DIR__ . '/fixtures/code_quality_test_classes.php');

        $errors = $this->inspect_class_docblock(totara_core\tests\docblock\class_has_no_docblock::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Empty docblock for class', $errors[1]);
        $errors = $this->inspect_class_docblock(totara_core\tests\docblock\class_docblock_has_only_stars::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Empty docblock for class', $errors[1]);
        $errors = $this->inspect_class_docblock(totara_core\tests\docblock\class_docblock_starts_with_single_star::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Empty docblock for class', $errors[1]);
        $errors = $this->inspect_class_docblock(totara_core\tests\docblock\class_docblock_undocumented::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Undocumented docblock for class', $errors[1]);
        $errors = $this->inspect_class_docblock(totara_core\tests\docblock\class_docblock_is_ok::class);
        $this->assertCount(0, $errors);
    }

    /**
     * Self-test inspect_property_docblocks().
     */
    public function test_inspect_property_docblocks(): void {
        require_once(__DIR__ . '/fixtures/code_quality_test_classes.php');

        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_has_no_docblock::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Empty docblock for $foo', $errors[1]);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_has_invalid_var_type::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Invalid @var type for $foo', $errors[1]);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_has_only_stars::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Empty docblock for $foo', $errors[1]);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_starts_with_single_star::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Empty docblock for $foo', $errors[1]);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_undocumented::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Undocumented docblock for $foo', $errors[1]);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_has_no_type::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Missing @var declaration for $foo', $errors[1]);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_is_ok1::class);
        $this->assertCount(0, $errors);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_is_ok2::class);
        $this->assertCount(0, $errors);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_inherit1::class);
        $this->assertCount(0, $errors);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_inherit2::class);
        $this->assertCount(0, $errors);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_inherit3::class);
        $this->assertCount(0, $errors);
        $errors = $this->inspect_property_docblocks(totara_core\tests\docblock\prop_docblock_not_inherited::class);
        $this->assertCount(2, $errors);
        $this->assertSame('$bar is not inherited', $errors[1]);
    }

    /**
     * Self-test inspect_method_docblocks().
     */
    public function test_inspect_method_docblocks(): void {
        require_once(__DIR__ . '/fixtures/code_quality_test_classes.php');

        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_has_no_docblock::class);
        $this->assertCount(2, $errors);
        $this->assertSame('No method docblock for totara_core\tests\docblock\method_has_no_docblock::foo()', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_has_no_docblock_but_different_param_name::class);
        $this->assertCount(2, $errors);
        $this->assertSame('No method docblock for totara_core\tests\docblock\method_has_no_docblock_but_different_param_name::foo(), which extends totara_core\tests\docblock\method_docblock_base::foo() but has different parameters', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_has_only_stars1::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Empty docblock for totara_core\tests\docblock\method_docblock_has_only_stars1::foo()', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_has_only_stars2::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Empty docblock for totara_core\tests\docblock\method_docblock_has_only_stars2::foo()', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_starts_with_single_star::class);
        $this->assertCount(2, $errors);
        $this->assertSame('No method docblock for totara_core\tests\docblock\method_docblock_starts_with_single_star::foo()', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_has_extra_empty_docblock::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Empty docblock for totara_core\tests\docblock\method_docblock_has_extra_empty_docblock::foo()', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_undocumented::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Undocumented docblock for totara_core\tests\docblock\method_docblock_undocumented::foo()', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_missing_param1::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_missing_param1::foo() docblock missing @param declaration for $bar', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_missing_param2::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_missing_param2::foo() docblock missing @param declaration for $flag', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_missing_param_type::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_missing_param_type::foo() docblock missing @param type for $bar', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_invalid_param_type::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_invalid_param_type::foo() docblock invalid @param type for $bar', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_invalid_param_type_hint1::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_invalid_param_type_hint1::foo() docblock has incorrect @param type for $bar: \'int\' expected', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_invalid_param_type_hint2::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_invalid_param_type_hint2::foo() docblock has incorrect @param type for $bar: \'array\' expected', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_missing_return_type::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_missing_return_type::foo() docblock missing @return declaration', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_incorrect_return_type_hint1::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_incorrect_return_type_hint1::foo() docblock has incorrect @return type: \'float\' expected', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_incorrect_return_type_hint2::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_incorrect_return_type_hint2::foo() docblock has incorrect @return type: \'bool\' expected', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_incorrect_return_type_hint3::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_incorrect_return_type_hint3::foo() docblock has incorrect @return type: \'void\' expected', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_incorrect_return_type_hint4::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_incorrect_return_type_hint4::foo() docblock has incorrect @return type', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_incorrect_return_type_hint5::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_incorrect_return_type_hint5::foo() docblock has incorrect \'@return this\': \'self\' expected', $errors[1]);
        // TODO: Detect 'integer[]|string' as 'mixed' in translate_known_return_type()
        // $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_incorrect_return_type_hint6::class);
        // $this->assertCount(2, $errors);
        // $this->assertSame('Method totara_core\tests\docblock\method_docblock_incorrect_return_type_hint6::foo() docblock has type-mismatched @return declaration', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_incorrect_return_type_hint7::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_incorrect_return_type_hint7::foo() docblock has incorrect @return type', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_incorrect_return_type_hint8::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\method_docblock_incorrect_return_type_hint8::foo() docblock has incorrect @return type: \'collection|DateTime[]\' expected', $errors[1]);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_is_ok::class);
        $this->assertCount(0, $errors);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\method_docblock_inheritdoc_is_ok::class);
        $this->assertCount(0, $errors);
        $errors = $this->inspect_method_docblocks(totara_core\tests\docblock\accepted_with_no_parameter_hint::class);
        $this->assertCount(0, $errors);
    }

    /**
     * Self-test inspect_method_parameter_hints().
     */
    public function test_inspect_method_parameter_hints(): void {
        require_once(__DIR__ . '/fixtures/code_quality_test_classes.php');

        // NOTE: inspect_method_parameter_hints() reports nothing about param_has_no_docblock1 even though its method does not have a docblock.
        $errors = $this->inspect_method_parameter_hints(totara_core\tests\docblock\param_has_no_docblock1::class);
        $this->assertCount(0, $errors);
        $errors = $this->inspect_method_parameter_hints(totara_core\tests\docblock\param_has_no_docblock2::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\param_has_no_docblock2::foo() parameter $bar is missing a type hint', $errors[1]);
        $errors = $this->inspect_method_parameter_hints(totara_core\tests\docblock\param_missing_typehint::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\param_missing_typehint::foo() parameter $bar is missing a type hint', $errors[1]);
        $errors = $this->inspect_method_parameter_hints(totara_core\tests\docblock\accepted_with_no_parameter_hint::class);
        $this->assertCount(0, $errors);
    }

    /**
     * Self-test inspect_method_return_hints().
     */
    public function test_inspect_method_return_hints(): void {
        require_once(__DIR__ . '/fixtures/code_quality_test_classes.php');

        $errors = $this->inspect_method_return_hints(totara_core\tests\docblock\return_missing_type_hint::class);
        $this->assertCount(2, $errors);
        $this->assertSame('Method totara_core\tests\docblock\return_missing_type_hint::foo() is missing a return type hint', $errors[1]);
        $errors = $this->inspect_method_return_hints(totara_core\tests\docblock\return_magic_methods_are_ignored::class);
        $this->assertCount(0, $errors);
        // TODO: distinguish magic methods and non-magic methods
        // $errors = $this->inspect_method_parameter_hints(totara_core\tests\docblock\return_non_magic_methods_are_not_ignored::class);
        // $this->assertCount(2, $errors);
        // $this->assertSame('Method totara_core\tests\docblock\return_non_magic_methods_are_not_ignored::__foo() is missing a return type hint', $errors[1]);
        $errors = $this->inspect_method_return_hints(totara_core\tests\docblock\return_type_is_ok::class);
        $this->assertCount(0, $errors);
        $errors = $this->inspect_method_return_hints(totara_core\tests\docblock\accepted_with_no_parameter_hint::class);
        $this->assertCount(0, $errors);
    }
}
