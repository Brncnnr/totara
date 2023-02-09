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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

namespace contentmarketplace_linkedin\data_provider;

use coding_exception;
use contentmarketplace_linkedin\constants;
use contentmarketplace_linkedin\entity\learning_object as learning_object_entity;
use contentmarketplace_linkedin\entity\learning_object_classification;
use contentmarketplace_linkedin\model\learning_object as learning_object_model;
use contentmarketplace_linkedin\repository\learning_object_repository;
use core\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use core\orm\query\field;
use mod_contentmarketplace\entity\content_marketplace;
use totara_contentmarketplace\data_provider\paginated_provider;

/**
 * Class learning_objects.
 *
 * @package contentmarketplace_linkedin\data_provider
 *
 * @method collection|learning_object_model[] get
 */
class learning_objects extends paginated_provider {

    public const SORT_BY_ALPHABETICAL = 'ALPHABETICAL';
    public const SORT_BY_LATEST = 'LATEST';

    /**
     * @inheritDoc
     */
    protected function get_default_sort_by(): ?string {
        return self::SORT_BY_LATEST;
    }

    /**
     * @return learning_object_repository
     */
    protected function build_query(): repository {
        return learning_object_entity::repository()
            ->with('courses');
    }

    /**
     * @return collection|learning_object_model[]
     */
    protected function process_fetched_items(): collection {
        return $this->items
            ->map_to(learning_object_model::class);
    }

    /**
     * @param learning_object_repository $repository
     * @param string $text
     *
     * @return void
     */
    protected function filter_query_by_search(repository $repository, string $text): void {
        global $CFG;
        $text = trim($text);

        if ($text === '') {
            return;
        }

        $repository->filter_text_like($text);
    }

    /**
     * @param repository $repository
     * @param int[] $subject_ids
     *
     * @return void
     */
    protected function filter_query_by_subjects(repository $repository, array $subject_ids): void {
        if (empty($subject_ids)) {
            // Skip the join if the subject ids are empty.
            return;
        }

        $repository->join([learning_object_classification::TABLE, 'loc'], 'id', 'learning_object_id');
        $repository->where_in('loc.classification_id', $subject_ids);
    }

    /**
     * @param learning_object_repository $repository
     * @param string[] $asset_types
     *
     * @return void
     */
    protected function filter_query_by_asset_type(repository $repository, array $asset_types): void {
        $repository->where(function (builder $builder) use ($asset_types) {
            foreach ($asset_types as $asset_type) {
                constants::validate_asset_type($asset_type);
                $builder->or_where('asset_type', $asset_type);
            }
        });
    }

    /**
     * @param learning_object_repository $repository
     * @param string[] $ranges Array of JSON objects with keys 'min' and 'max', e.g: ['{"min": 60, "max": 120}', '{"min": 3600}']
     *
     * @return void
     */
    protected function filter_query_by_time_to_complete(repository $repository, array $ranges): void {
        $repository->where(function (builder $builder) use ($ranges) {
            foreach ($ranges as $range) {
                $range = json_decode($range, true);
                if ($range === null || (empty($range['min']) && empty($range['max']))) {
                    throw new coding_exception("A min or a max value must be specified for the time_to_complete filter.");
                }

                $builder->or_where(function (builder $builder) use ($range) {
                    if (isset($range['min'])) {
                        $builder->where('time_to_complete', '>=', $range['min']);
                    }
                    if (isset($range['max'])) {
                        $builder->where('time_to_complete', '<=', $range['max']);
                    }
                });
            }
        });
    }

    /**
     * @param learning_object_repository $repository
     * @param string[] $in_catalog
     *
     * @return void
     */
    protected function filter_query_by_in_catalog(repository $repository, array $in_catalog): void {
        if (empty($in_catalog)) {
            // Skip the join if the filter values are empty.
            return;
        }

        if (count($in_catalog) > 1) {
            // Skip the join when both filters selected. Returning all values.
            return;
        }

        if ($in_catalog[0] == 'yes') {
            $subquery = content_marketplace::repository()
                ->where_field('learning_object_id', new field('id', $repository->get_builder()));

            $repository->where_exists($subquery->get_builder());
        } else {
            $repository->left_join([content_marketplace::TABLE, 'cm'], 'id', 'learning_object_id');
            $repository->where_null('cm.id');
        }
    }

    /**
     * @param learning_object_repository $repository
     * @param bool $is_retired
     *
     * @return void
     */
    protected function filter_query_by_is_retired(repository $repository, bool $is_retired): void {
        $repository->where('retired_at', $is_retired ? '!=' : '=', null);
    }

    /**
     * @param learning_object_repository $repository
     * @param bool $avalibity
     */
    protected function filter_query_by_availability(repository $repository, string $avalibity = constants::AVAILABILITY_AVAILABLE): void {
        $repository->where('availability', $avalibity);
    }


    /**
     * @param learning_object_repository $repository
     * @param string $language
     *
     * @return void
     */
    protected function filter_query_by_language(repository $repository, string $language): void {
        $repository->where('locale_language', $language);
    }

    /**
     * @param learning_object_repository $repository
     * @param int[] $ids
     *
     * @return void
     */
    protected function filter_query_by_ids(repository $repository, array $ids): void {
        if (!empty($ids)) {
            $repository->where_in('id', $ids);
        }
    }

    /**
     * @param learning_object_repository $repository
     *
     * @return void
     */
    protected function sort_query_by_alphabetical(repository $repository): void {
        $repository->order_by('title');
    }

    /**
     * @param learning_object_repository $repository
     *
     * @return void
     */
    protected function sort_query_by_latest(repository $repository): void {
        $repository->order_by('last_updated_at', 'DESC');
    }

}
