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

use core\orm\entity\entity;
use core\orm\entity\filter\filter;
use core\orm\entity\model;
use core\orm\entity\repository;
use core\webapi\formatter\formatter;
use core\webapi\middleware;
use core\webapi\mutation_resolver;
use core\webapi\query_resolver;
use mod_approval\data_provider\application\applications_for_others;
use mod_approval\data_provider\application\capability_map\capability_map_base;
use mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_any;
use mod_approval\data_provider\application\capability_map\view_draft_in_dashboard_application_user;
use mod_approval\data_provider\application\capability_map\view_in_dashboard_application_any;
use mod_approval\data_provider\application\capability_map\view_in_dashboard_application_user;
use mod_approval\data_provider\application\capability_map\view_in_dashboard_pending_application_any;
use mod_approval\data_provider\application\capability_map\view_in_dashboard_pending_application_user;
use mod_approval\model\application\action\action;
use mod_approval\form_schema\form_schema;
use mod_approval\form_schema\form_schema_field;
use mod_approval\form_schema\form_schema_section;
use mod_approval\language\local_lang_generator;
use mod_approval\language\substitutor;
use mod_approval\model\application\activity\activity;
use mod_approval\model\application\application_workflow_stage;
use mod_approval\model\application\application_state;
use mod_approval\model\assignment\assignment_approver_resolver;
use mod_approval\model\assignment\assignment_resolver;
use mod_approval\model\form\approvalform_base;
use mod_approval\model\form\form_contents;
use mod_approval\model\form\form_data;
use mod_approval\model\form\merger\form_data_merger;
use mod_approval\model\form\merger\form_schema_merger;
use mod_approval\model\workflow\interaction\condition\interaction_condition;
use mod_approval\model\workflow\interaction\transition\transition_base;
use mod_approval\model\workflow\ordinal\operation;
use mod_approval\model\workflow\ordinal\ordinal;
use mod_approval\plugininfo\approvalform;
use mod_approval\testing\generator;
use mod_approval\testing\generator_behat;
use mod_approval\testing\graphqls_parser;
use mod_approval\testing\php_parser;
use totara_mvc\controller;

require_once(__DIR__ . '/../../../totara/core/tests/code_quality_testcase.php');

/**
 * Good old code quality test
 * @group approval_workflow
 * @group approval_workflow_check
 */
class mod_approval_code_quality_testcase extends totara_core_code_quality_testcase_base {
    /**
     * @var string[]
     */
    protected $tested_classes = [
        approvalform::class,
        assignment_resolver::class,
        assignment_approver_resolver::class,
        application_workflow_stage::class,
        approvalform_base::class,

        form_contents::class,
        form_data::class,
        form_schema::class,
        form_schema_field::class,
        form_schema_section::class,

        interaction_condition::class,

        applications_for_others::class,
        capability_map_base::class,
        view_in_dashboard_application_any::class,
        view_draft_in_dashboard_application_any::class,
        view_in_dashboard_pending_application_any::class,
        view_in_dashboard_application_user::class,
        view_draft_in_dashboard_application_user::class,
        view_in_dashboard_pending_application_user::class,

        local_lang_generator::class,
        substitutor::class,
        graphqls_parser::class,
        php_parser::class,
        generator::class,
        generator_behat::class,

        // self checking
        mod_approval_code_quality_testcase::class,
    ];

    /**
     * @inheritDoc
     */
    protected function get_classes_to_test(): array {
        $tested_classes = $this->tested_classes;
        self::add_inherited_classes($tested_classes, null, controller::class, 'classes/controllers');
        self::add_inherited_classes($tested_classes, null, filter::class, 'classes/data_provider');
        self::add_inherited_classes($tested_classes, 'interactor', null, 'classes/interactor');
        self::add_inherited_classes($tested_classes, 'observer', null, 'classes/observer');
        self::add_inherited_classes($tested_classes, null, formatter::class, 'classes/formatter');
        self::add_inherited_classes($tested_classes, null, entity::class, 'classes/entity');
        self::add_inherited_classes($tested_classes, null, repository::class, 'classes/entity');
        self::add_inherited_classes($tested_classes, null, model::class, 'classes/model');
        self::add_inherited_classes($tested_classes, null, action::class, 'classes/model/application/action');
        self::add_inherited_classes($tested_classes, null, activity::class, 'classes/model/application/activity');
        self::add_inherited_classes($tested_classes, null, application_state::class, 'classes/model/application');
        self::add_inherited_classes($tested_classes, null, form_data_merger::class, 'classes/model/form/merger');
        self::add_inherited_classes($tested_classes, null, form_schema_merger::class, 'classes/model/form/merger');
        self::add_inherited_classes($tested_classes, null, operation::class, 'classes/model/workflow/ordinal');
        self::add_inherited_classes($tested_classes, null, ordinal::class, 'classes/model/workflow/ordinal');
        self::add_inherited_classes($tested_classes, null, middleware::class, 'classes/webapi');
        self::add_inherited_classes($tested_classes, null, mutation_resolver::class, 'classes/webapi');
        self::add_inherited_classes($tested_classes, null, query_resolver::class, 'classes/webapi');
        self::add_inherited_classes($tested_classes, 'webapi\\resolver\\type', null, 'classes/webapi/resolver/type');
        self::add_inherited_classes($tested_classes, 'webapi\\resolver\\union', null, 'classes/webapi/resolver/union');
        self::add_inherited_classes($tested_classes, 'webapi\\schema_object', null, 'classes/webapi/schema_object');
        self::add_matching_classes($tested_classes, '/^mod_approval\\\\.+_(type|status)$/', 'classes');
        self::add_inherited_classes($tested_classes, null, transition_base::class, 'classes/webapi');

        // Remove classes whose docblock is known to fail
        $tested_classes = array_flip($tested_classes);
        unset($tested_classes[controller::class]);
        unset($tested_classes[entity::class]);
        unset($tested_classes[repository::class]);
        unset($tested_classes[filter::class]);
        unset($tested_classes[model::class]);
        unset($tested_classes[mutation_resolver::class]);
        unset($tested_classes[query_resolver::class]);
        return array_keys($tested_classes);
    }

    /**
     * @inheritDoc
     * TODO: remove with sf-182 plugin
     */
    protected function get_whitelist_crlf(): array {
        return [
            'form/sf182/sf182.pdf',
            'form/sf182/sf182.json',
        ];
    }

    /**
     * Ensure each parameter of model::create, load, etc. has a parameter description
     */
    public function test_model_methods(): void {
        $errors = [];
        $models = [];
        self::add_inherited_classes($models, null, model::class, 'classes/model');
        foreach ($models as $model) {
            if (strpos($model, 'mod_approval\\') !== 0) {
                continue;
            }
            $classerrors = [];
            $rc = new ReflectionClass($model);
            foreach ($rc->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
                if (!preg_match('/^(create|load|fetch|save|update|delete|exist|can)/', $method->getName())) {
                    continue;
                }
                if ($method->getDeclaringClass()->getName() !== $rc->getName()) {
                    continue;
                }
                $docblock = $method->getDocComment();
                foreach ($method->getParameters() as $param) {
                    $reparam = preg_quote('$' . $param->getName());
                    if (!preg_match("/\\* +@param +[^ ]+ +{$reparam} +([^\\r\\n]{2,})/", $docblock, $match)) {
                        $classerrors[] = "{$rc->getShortName()}::{$method->getName()}() parameter \${$param->getName()} " .
                            "does not provide a description";
                    } else if (!preg_match('/^[\\*_~]*[A-Z0-9]/', $match[1])) {
                        $classerrors[] = "{$rc->getShortName()}::{$method->getName()}() parameter \${$param->getName()} " .
                            "does not provide a description in a Sentence case";
                    }
                }
            }
            if (!empty($classerrors)) {
                $errors[] = sprintf(
                    "[[ %d problem(s) with %s at %s:%d ]]",
                    count($classerrors), $rc->getName(), $rc->getFileName(), $rc->getStartLine()
                );
                array_push($errors, ...$classerrors);
            }
        }
        if (!empty($errors)) {
            $this->fail(implode(PHP_EOL, $errors));
        }
    }
}
