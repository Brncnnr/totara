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

use core_phpunit\testcase;
use mod_approval\data_provider\form\form as form_provider;
use mod_approval\model\form\form;

/**
 * @coversDefaultClass mod_approval\data_provider\form\form
 * @group approval_workflow
 */
class mod_approval_data_provider_form_testcase extends testcase {

    /**
     * @covers ::build_query
     * @covers ::where_active_form_version
     * @covers ::get_active_form_version_limit_sql
     * @covers ::sort_query_by_title
     */
    public function test_sort_by_title(): void {
        form::create('simple', 'Simple form');
        form::create('simple', 'Not simple form');
        form::create('simple', 'Awesome form');
        $archived = form::create('simple', 'Amazing form');
        $archived->get_active_version()->archive();

        [$items] = $this->provide_sorting('title');
        $this->assertCount(3, $items,);
        $this->assertEquals('Awesome form', $items[0]->title);
        $this->assertEquals('Not simple form', $items[1]->title);
        $this->assertEquals('Simple form', $items[2]->title);
    }

    /**
     * @covers ::build_query
     * @covers ::where_active_form_version
     * @covers ::get_active_form_version_limit_sql
     * @covers ::filter_query_by_title
     */
    public function test_filter_query_by_title(): void {
        form::create('simple', 'cool form');
        form::create('simple', 'Cool form');
        form::create('simple', 'Kool form');

        [$items] = $this->provide_filter(['title' => 'cool']);
        $this->assertCount(2, $items);
        $this->assertEquals('cool form', $items[0]->title);
        $this->assertEquals('Cool form', $items[1]->title);
    }

    /**
     * @param array $filters
     * @return array
     */
    private function provide_filter(array $filters): array {
        $provider = new form_provider();
        $provider->add_filters($filters);
        return [
            $provider->get()->all(false)
        ];
    }

    /**
     * @param string $sorting
     * @return array
     */
    private function provide_sorting(string $sorting): array {
        $provider = new form_provider();
        $provider->sort_by($sorting);
        return [
            $provider->get()->all(false)
        ];
    }
}
