<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package core
 */

namespace core\entity;

use core\orm\entity\filter\equal;
use core\orm\entity\filter\in;
use core\orm\entity\filter\like;
use core\orm\entity\repository;

class course_repository extends repository {
    /**
     * Filter courses by name
     *
     * @param string|null $name
     * @return $this
     */
    public function filter_by_name(?string $name = null) {
        if (!empty($name)) {
            $this->set_filter(
                (new like([
                    'shortname',
                    'fullname',
                ]))->set_value($name)
            );
        }

        return $this;
    }

    /**
     * Filter values by selected category id
     *
     * @param int|null $category_id
     * @return $this
     */
    public function filter_by_category(?int $category_id = null) {
        if (!empty($category_id)) {
            $this->set_filter((new equal('category'))->set_value($category_id));
        }

        return $this;
    }

    /**
     * Filter values by ids
     *
     * @param array|null $key
     * @return $this
     */
    public function filter_by_ids(?array $ids = null) {
        if (!empty($ids)) {
            $this->set_filter((new in('id'))->set_value($ids));
        }

        return $this;
    }

    /**
     * Select only shortname and fullname for display purposes
     *
     * @return $this
     */
    public function select_names_only() {
        $this->select(['id', 'shortname', 'fullname']);

        return $this;
    }
}
