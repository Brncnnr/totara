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
 * @author  Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\webapi\resolver\query;

use totara_contentmarketplace\interactor\catalog_import_interactor;
use context_helper;
use core\orm\query\builder;
use core\webapi\execution_context;
use core\webapi\middleware\require_login;
use core\webapi\query_resolver;
use coursecat;
use moodle_recordset;
use totara_contentmarketplace\webapi\middleware\require_content_marketplace;

class catalog_import_course_categories extends query_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(array $args, execution_context $ec) {
        (new catalog_import_interactor())->require_view_catalog_import_page();

        $category_items = coursecat::make_categories_list('totara/contentmarketplace:add');
        self::preload_categories_context($category_items);

        $categories = [];
        foreach ($category_items as $id => $name) {
            $categories[] = (object) ['id' => $id, 'name' => $name];
        }

        // clear some memory to make it faster.
        unset($category_items);

        return $categories;
    }

    /**
     * @param array $category_items Array<int, string> where integer is
     *                              the category's id and string is its associated name.
     * @return void
     */
    private static function preload_categories_context(array $category_items): void {
        global $USER;
        $category_ids = array_keys($category_items);

        $builder = builder::table('context', 'c');
        $builder->select_raw(context_helper::get_preload_record_columns_sql('c'));
        $builder->where('contextlevel', CONTEXT_COURSECAT);

        if (is_siteadmin($USER->id)) {
            // It is the site admin, hence we dont want a big SQL IN statement, but just get
            // almost as much everything as we would want as long as it is under the range
            // of CONTEXT_CACHE_MAX_SIZE.
            $builder->offset(0);
            $builder->limit(CONTEXT_CACHE_MAX_SIZE);

            $record_set = $builder->get_lazy();
            self::preload_context_from_records($record_set);
        } else {
            // Finding the maximum of records that we would want to prime cache
            // before resolving the category record(s).
            $db = builder::get_db();
            $maximum = $db->get_max_in_params();

            if ($maximum > CONTEXT_CACHE_MAX_SIZE) {
                // If the CONTEXT_CACHE_MAX_SIZE is bigger than the number of max_in_params
                // then we can do it by batches with max_in_params. Otherwise, just one time loaded
                // with CONTEXT_CACHE_MAX_SIZE.
                $category_ids = array_slice($category_ids, 0, CONTEXT_CACHE_MAX_SIZE);
                $maximum = CONTEXT_CACHE_MAX_SIZE;
            }

            while (!empty($category_ids)) {
                // We pre-cache context records by chunks.
                $to_fetch_ids = array_splice($category_ids, 0, $maximum);

                $builder->where_in('instanceid', $to_fetch_ids);
                $record_set = $builder->get_lazy();

                self::preload_context_from_records($record_set);
            }
        }
    }

    /**
     * @param moodle_recordset $record_set
     * @return void
     */
    private static function preload_context_from_records(moodle_recordset $record_set): void {
        try {
            foreach ($record_set as $record) {
                context_helper::preload_from_record($record);
            }
        } finally {
            $record_set->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_login(),
            new require_content_marketplace('linkedin'),
        ];
    }

}