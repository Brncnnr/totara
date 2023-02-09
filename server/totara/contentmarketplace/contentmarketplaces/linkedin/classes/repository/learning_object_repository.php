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

namespace contentmarketplace_linkedin\repository;

use contentmarketplace_linkedin\entity\learning_object;
use core\orm\collection;
use core\orm\entity\filter\keywords;
use core\orm\entity\repository;
use core\orm\lazy_collection;

/**
 * Class learning_object_repository
 *
 * @method collection|learning_object[] get(bool $unkeyed = false)
 * @method lazy_collection|learning_object[] get_lazy()
 * @method learning_object|null one(bool $strict = false)
 *
 * @package contentmarketplace_linkedin\entity
 */
class learning_object_repository extends repository {
    /**
     * @return array
     */
    protected function get_default_filters(): array {
        return [
            "text" => new keywords(["title", "description", "short_description"])
        ];
    }

    /**
     * Apply search filter keywords for the text.
     *
     * @param string $text
     * @return learning_object_repository
     */
    public function filter_text_like(string $text): learning_object_repository {
        $this->set_filter("text", $text);
        return $this;
    }

    /**
     * @param string $urn
     * @return learning_object|null
     */
    public function find_by_urn(string $urn): ?learning_object {
        return learning_object::repository()
            ->where('urn', $urn)
            ->one();
    }

    /**
     * @param array $learning_object_ids
     * @return collection|learning_object[]
     */
    public function get_in(array $learning_object_ids): collection {
        $repository = learning_object::repository();
        $repository->where_in('id', $learning_object_ids);

        return $repository->get();
    }

    /**
     * Get the list of names from $learning_object_ids.
     *
     * @param array $learning_object_ids
     * @return string[]
     */
    public function get_titles_of(array $learning_object_ids): array {
        $collection = $this->get_in($learning_object_ids);
        return $collection->pluck('title');
    }
}