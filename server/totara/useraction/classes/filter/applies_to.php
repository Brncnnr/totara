<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\filter;

use core\entity\cohort;
use core\entity\cohort_member;
use core\entity\user_repository;
use core\orm\collection;
use core\orm\query\sql\query;
use totara_useraction\model\scheduled_rule\execution_data;

/**
 * Filters for the applies_to field.
 */
class applies_to implements filter_contract {
    /**
     * @var collection
     */
    private collection $audiences;

    /**
     * @var bool
     */
    private bool $all_users;

    /**
     * @param bool $all_users
     * @param collection $audiences
     */
    public function __construct(bool $all_users, collection $audiences) {
        $this->all_users = $all_users;
        $this->audiences = $audiences;
    }

    /**
     * @param $input
     * @return static
     */
    public static function create_from_input($input): self {
        if (array_key_exists('audiences', $input) && $input['audiences'] === null) {
            return new self(true, collection::new([]));
        }

        $audience_ids = $input['audiences'] ?? [];
        $audiences = cohort::repository()->where_in('id', $audience_ids)->get();

        return new self(false, $audiences);
    }

    /**
     * @param array $stored
     * @return static
     */
    public static function create_from_stored($stored): self {
        return new self($stored['all_users'], $stored['audiences']);
    }

    /**
     * We let GraphQL handle the transformation of groups.
     *
     * @return array
     */
    public function to_graphql(): array {
        if ($this->all_users) {
            return ['all_users' => true];
        }

        return [
            'audiences' => $this->audiences,
        ];
    }

    /**
     * @inheritDoc
     */
    public function apply(user_repository $user_repository, execution_data $execution_data): user_repository {
        if (!$this->is_all_users()) {
            $audience_ids = $this->get_audiences()->pluck('id');

            if (empty($audience_ids)) {
                $user_repository->where_raw("1 = 2");
            } else {
                $cohort_members_builder = cohort_member::repository()
                    ->select('userid')
                    ->where_in('cohortid', $audience_ids)
                    ->get_builder();

                [$sql, $params] = query::from_builder($cohort_members_builder)->build();
                $user_alias = $user_repository->get_alias();
                $user_repository->where_raw("\"$user_alias\".id in ($sql)", $params);
            }
        }

        return $user_repository;
    }

    /**
     * @return bool
     */
    public function is_all_users(): bool {
        return $this->all_users;
    }

    /**
     * @return collection|cohort[]
     */
    public function get_audiences(): collection {
        return $this->audiences;
    }
}
