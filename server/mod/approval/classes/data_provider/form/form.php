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
 * @package @mod_approval
 */

namespace mod_approval\data_provider\form;

use core\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\raw_field;
use core\orm\query\sql\query;
use core\pagination\offset_cursor as cursor;
use mod_approval\data_provider\offset_cursor_paginator_trait;
use mod_approval\data_provider\provider;
use mod_approval\data_provider\form\filter\title;
use mod_approval\entity\form\form as form_entity;
use mod_approval\entity\form\form_version;
use mod_approval\model\status;
use mod_approval\model\form\form as form_model;
use sqlsrv_native_moodle_database;

/**
 * Form data provider
 *
 * @package mod_approval\data_provider\form
 */
class form extends provider {

    use offset_cursor_paginator_trait;

    /**
     * Form table alias.
     *
     * @var string
     */
    private $form_table_alias = 'form';

    /**
     * Form version table alias.
     * @var string
     */
    private $form_version_table_alias = 'form_version';

    /**
     * @inheritDoc
     */
    protected function build_query(): repository {
        return form_entity::repository()
            ->as($this->form_table_alias)
            ->join(
                [form_version::TABLE, $this->form_version_table_alias],
                function (builder $builder) {
                    $field = $this->where_active_form_version();
                    $builder->where_field("{$this->form_version_table_alias}.id", $field);
                }
            )
            ->where('active', true)
            ->select("$this->form_table_alias.*");
    }

    /**
     * @return raw_field
     */
    private function where_active_form_version(): raw_field {
        $out_alias = $this->form_table_alias;
        $in_alias = 'joining_active_form_version';
        $status = status::ACTIVE;
        $builder = builder::table(form_version::TABLE, $in_alias)
            ->where_raw("{$in_alias}.form_id = {$out_alias}.id")
            ->where_raw("status = {$status}")
            ->order_by("{$in_alias}.id", 'DESC')
            ->limit(1)
            ->offset(0)
            ->select_raw("{$in_alias}.id");
        [$sql, $params] = query::from_builder($builder)->build();
        // Add limit & offset to the query.
        $sql .= ' ' . $this->get_active_form_version_limit_sql();
        return raw_field::raw("({$sql})", $params);
    }

    /**
     * Limit part of sql used to pick the latest active version.
     *
     * @return string
     */
    private function get_active_form_version_limit_sql(): string {
        return builder::get_db() instanceof sqlsrv_native_moodle_database
            ? "OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY"
            : "LIMIT 1";
    }

    /**
     * Map the application entities to their respective model class.
     *
     * @return collection|form_model[]
     */
    protected function process_fetched_items(): collection {
        return $this->items->map_to(form_model::class);
    }

    /**
     * Filter by title or form id.
     *
     * @param repository $repository
     * @param string $title
     */
    protected function filter_query_by_title(repository $repository, string $title) {
        $repository->set_filter((new title($this->form_table_alias))->set_value($title));
    }

    /**
     * Sort by title.
     *
     * @param repository $repository
     *
     * @return void
     */
    protected function sort_query_by_title(repository $repository): void {
        $repository->order_by_raw("UPPER($this->form_table_alias.title) ASC")->order_by("$this->form_table_alias.id", 'DESC');
    }

    /**
     * Get page of form.
     *
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    public function get_page(int $page, int $limit): array {
        $cursor = cursor::create([
            'page' => $page,
            'limit' => $limit,
        ]);

        $paginator = $this->get_paginator($cursor);
        $next_cursor = $paginator->get_next_cursor();
        $forms = $paginator->get_items()->map_to(form_model::class);

        return [
            'items' => $forms,
            'total' => $paginator->get_total(),
            'next_cursor' => $next_cursor === null
                ? ''
                : $next_cursor->encode(),
        ];
    }
}