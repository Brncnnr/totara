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

use core\orm\entity\repository;
use core\orm\query\sql\query;
use core_phpunit\testcase;
use mod_approval\data_provider\workflow\workflow as workflow_provider;
use mod_approval\entity\workflow\workflow_version as workflow_version_entity;
use mod_approval\exception\model_exception;
use mod_approval\model\assignment\assignment_type;
use mod_approval\model\form\form;
use mod_approval\model\status;
use mod_approval\model\workflow\workflow;
use mod_approval\model\workflow\workflow_type;

/**
 * @coversDefaultClass mod_approval\data_provider\workflow\workflow
 * @group approval_workflow
 */
class mod_approval_data_provider_workflow_testcase extends testcase {
    /** @var workflow_type */
    private $workflow_type;
    /** @var form */
    private $form;

    public function setUp(): void {
        parent::setUp();
        $this->setAdminUser();
        $this->workflow_type = workflow_type::create('Test workflow type');
        $this->form = form::create('simple', 'Test form');
    }

    public function tearDown(): void {
        $this->workflow_type = $this->form = null;
        parent::tearDown();
    }

    /**
     * @covers ::build_query
     * @covers ::where_latest_workflow_version
     * @covers ::get_latest_workflow_version_limit_sql
     * @covers ::filter_query_by_status
     */
    public function test_filter_by_status(): void {
        $this->create_workflow('Workflow 11 (draft)', [status::DRAFT]);
        $this->create_workflow('Workflow 12 (active)', [status::DRAFT, status::ACTIVE]);
        $this->create_workflow('Workflow 13 (archived)', [status::DRAFT, status::ACTIVE, status::ARCHIVED]);
        $this->create_workflow('Workflow 21 (draft)', [status::DRAFT]);
        $this->create_workflow('Workflow 22 (active)', [status::DRAFT, status::ACTIVE]);
        $this->create_workflow('Workflow 23 (archived)', [status::DRAFT, status::ARCHIVED]);
        $this->create_workflow('Workflow 31 (draft)', [status::DRAFT, status::ARCHIVED, status::DRAFT]);
        $this->create_workflow('Workflow 32 (active)', [status::ACTIVE]);
        $this->create_workflow('Workflow 33 (archived)', [status::ARCHIVED]);

        [$items, $sql] = $this->provide_filter([]);
        $this->assertCount(9, $items, $sql);
        $this->assertEquals('Workflow 33 (archived)', $items[0]->name, $sql);
        $this->assertEquals('Workflow 32 (active)', $items[1]->name, $sql);
        $this->assertEquals('Workflow 31 (draft)', $items[2]->name, $sql);
        $this->assertEquals('Workflow 23 (archived)', $items[3]->name, $sql);
        $this->assertEquals('Workflow 22 (active)', $items[4]->name, $sql);
        $this->assertEquals('Workflow 21 (draft)', $items[5]->name, $sql);
        $this->assertEquals('Workflow 13 (archived)', $items[6]->name, $sql);
        $this->assertEquals('Workflow 12 (active)', $items[7]->name, $sql);
        $this->assertEquals('Workflow 11 (draft)', $items[8]->name, $sql);

        [$items, $sql] = $this->provide_filter(['status' => status::DRAFT_ENUM]);
        $this->assertCount(3, $items, $sql);
        $this->assertEquals('Workflow 31 (draft)', $items[0]->name, $sql);
        $this->assertEquals('Workflow 21 (draft)', $items[1]->name, $sql);
        $this->assertEquals('Workflow 11 (draft)', $items[2]->name, $sql);

        [$items, $sql] = $this->provide_filter(['status' => status::ACTIVE_ENUM]);
        $this->assertCount(3, $items, $sql);
        $this->assertEquals('Workflow 32 (active)', $items[0]->name, $sql);
        $this->assertEquals('Workflow 22 (active)', $items[1]->name, $sql);
        $this->assertEquals('Workflow 12 (active)', $items[2]->name, $sql);

        [$items, $sql] = $this->provide_filter(['status' => status::ARCHIVED_ENUM]);
        $this->assertCount(3, $items, $sql);
        $this->assertEquals('Workflow 33 (archived)', $items[0]->name, $sql);
        $this->assertEquals('Workflow 23 (archived)', $items[1]->name, $sql);
        $this->assertEquals('Workflow 13 (archived)', $items[2]->name, $sql);

        try {
            $this->provide_filter(['status' => 'boohoo']);
            $this->fail('model_exception expected');
        } catch (model_exception $ex) {
            $this->assertStringContainsString('Invalid status enum provided.', $ex->getMessage());
        }
    }

    /**
     * @covers ::build_query
     * @covers ::where_latest_workflow_version
     * @covers ::get_latest_workflow_version_limit_sql
     * @covers ::sort_query_by_name
     */
    public function test_sort_by_name(): void {
        $this->create_workflow('P', [status::DRAFT]);
        $this->create_workflow('p', [status::DRAFT]);
        $this->create_workflow('A', [status::DRAFT]);

        [$items, $sql] = $this->provide_sorting('name');
        $this->assertCount(3, $items, $sql);
        $this->assertEquals('A', $items[0]->name, $sql);
        $this->assertEquals('p', $items[1]->name, $sql);
        $this->assertEquals('P', $items[2]->name, $sql);
    }

    /**
     * @covers ::build_query
     * @covers ::where_latest_workflow_version
     * @covers ::get_latest_workflow_version_limit_sql
     * @covers ::filter_query_by_name
     */
    public function test_filter_query_by_name(): void {
        $this->create_workflow('cool workflow', [status::DRAFT]);
        $this->create_workflow('Cool workflow', [status::DRAFT]);
        $this->create_workflow('Kool workflow', [status::DRAFT]);

        [$items, $sql] = $this->provide_filter(['name' => 'cool']);
        $this->assertCount(2, $items, $sql);
        $this->assertEquals('Cool workflow', $items[0]->name, $sql);
        $this->assertEquals('cool workflow', $items[1]->name, $sql);
    }

    /**
     * @param string $name
     * @param array $statuses
     * @return workflow
     */
    private function create_workflow(string $name, array $statuses): workflow {
        $workflow = workflow::create(
            $this->workflow_type,
            $this->form,
            $name,
            '',
            assignment_type\cohort::get_code(),
            $this->getDataGenerator()->create_cohort()->id,
            $name
        );
        // Delete workflow version created by workflow_model::create()
        workflow_version_entity::repository()->where('workflow_id', $workflow->id)->delete();
        foreach ($statuses as $status) {
            $workflow_version = new workflow_version_entity();
            $workflow_version->workflow_id = $workflow->id;
            $workflow_version->form_version_id = $this->form->latest_version->id;
            $workflow_version->status = $status;
            $workflow_version->save();
        }
        $workflow->refresh(true);
        return $workflow;
    }

    /**
     * @param array $filters
     * @return array
     */
    private function provide_filter(array $filters): array {
        $provider = new workflow_provider();
        $provider->add_filters($filters)->sort_by('updated');
        return [
            $provider->get()->all(false),
            self::get_query_of($provider)
        ];
    }

    /**
     * @param string $sorting
     * @return array
     */
    private function provide_sorting(string $sorting): array {
        $provider = new workflow_provider();
        $provider->sort_by($sorting);
        return [
            $provider->get()->all(false),
            self::get_query_of($provider)
        ];
    }

    /**
     * @param workflow_provider $provider
     * @return string
     */
    private static function get_query_of(workflow_provider $provider): string {
        $provider = clone $provider;
        $fetched = new ReflectionProperty($provider, 'fetched');
        $fetched->setAccessible(true);
        $build_query = new ReflectionMethod($provider, 'build_query');
        $build_query->setAccessible(true);
        $apply_query_filters = new ReflectionMethod($provider, 'apply_query_filters');
        $apply_query_filters->setAccessible(true);
        $apply_query_sorting = new ReflectionMethod($provider, 'apply_query_sorting');
        $apply_query_sorting->setAccessible(true);
        $fetched->setValue($provider, false);
        /** @var repository $query */
        $query = $build_query->invoke($provider);
        $apply_query_filters->invoke($provider, $query);
        $apply_query_sorting->invoke($provider, $query);
        [$sql, $params] = query::from_builder($query->get_builder())->build();
        // replace :param with value
        $keys = array_keys($params);
        usort($keys, function ($x, $y) {
            return strlen($y) <=> strlen($x);
        });
        foreach ($keys as $key) {
            $param = $params[$key];
            if (preg_match('/^(\d+|\d+\.\d*)$/', $param)) {
                $sql = str_replace(':' . $key, $param, $sql);
            } else {
                $sql = str_replace(':' . $key, '"' . addslashes($param) . '"', $sql);
            }
        }
        return $sql;
    }
}
