<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Ning Zhou <ning.zhou@totaralearning.com>
 * @package totara_hierarchy
 */

namespace hierarchy_competency\data_providers;

use core\orm\entity\filter\filter_factory;
use core\orm\entity\repository;
use totara_core\data_provider\provider_interface;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_repository;
use totara_core\data_provider\provider;

/**
 * Generic competency data provider class.
 *
 * @property competency_repository $repository
 * @property string $order_by
 * @property int $page_size
 * @property array $filters
 */
class competency_provider extends provider implements provider_interface {

    public const SORT_COMPETENCY_NAME = 'competency_name';
    public const SORT_HIERARCHY = 'hierarchy';
    public const SORT_PATHWAY = 'achievement_path';

    public const SORT_FIELDS = [
        self::SORT_COMPETENCY_NAME => 'fullname',
        self::SORT_HIERARCHY => 'parentid',
        self::SORT_PATHWAY => 'pathway',
    ];

    /**
     * @return static
     */
    public static function create(?filter_factory $filter_factory = null): provider {
        return new self(
            self::get_repository(),
            self::SORT_FIELDS,
            $filter_factory
        );
    }

    /**
     * @return repository
     */
    private static function get_repository(): repository {
        return competency::repository()
            ->with(['pathways', 'parent']);
    }

    public static function get_type(): string {
        return 'competency';
    }

    public static function get_summary_format_select() {
        return 'id';
    }

    /**
     * get all parents for current id.
     *
     * @param $id
     * @return array|competency[]
     */
    public static function get_all_parent_competencies($id): array {
        /** @var competency $competency */
        $competency = competency::repository()->find($id);
        if (empty($competency)) {
            return [
                'current_level' => null,
                'parents' => [],
            ];
        }
        return [
            'current_level' => $competency,
            'parents' => $competency->all_parents,
        ];
    }
}