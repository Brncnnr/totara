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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_certification
 */

namespace totara_certification\entity\filter;

use core\orm\entity\filter\filter;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;
use core\orm\entity\filter\filter_factory;

/**
 * Convenience filters to use with the entities.
 */
class certification_filter_factory implements filter_factory {

    /**
     * @inheritDoc
     */
    public function create(string $key, $value, ?int $user_id = null): ?filter {
        switch ($key) {
            case 'user_id':
                return $this->create_user_filter($value);
            case 'ids':
                return $this->create_ids_filter($value);
            case 'search':
                return $this->create_search_filter($value);
            case 'progress':
                return $this->create_progress_filter($value, $user_id);
        }
        return null;
    }

    /**
     * @param int $user_id
     *
     * @return filter
     */
    protected function create_user_filter(int $user_id): filter {
        return (new user_certifications())
            ->set_value($user_id);
    }

    /**
     * @param array $ids
     *
     * @return filter
     */
    protected function create_ids_filter(array $ids): filter {
        return (new in('id'))
            ->set_value($ids);
    }

    /**
     * @param string $value
     *
     * @return filter
     */
    protected function create_search_filter(string $value): filter {
        return (new like('fullname'))
            ->set_value($value);
    }

    /**
     * @param string $value
     *
     * @return filter
     */
    protected function create_progress_filter(string $value, ?int $user_id = null): filter {
        return (new certification_progress())
            ->set_value([
                'progress' => $value,
                'user_id' => $user_id,
            ]);
    }

}
