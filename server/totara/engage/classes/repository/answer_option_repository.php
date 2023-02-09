<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_engage
 */
namespace totara_engage\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use totara_engage\entity\answer_option;

final class answer_option_repository extends repository {
    /**
     * @param int $questionid
     * @return answer_option[]
     */
    public function get_options(int $questionid): array {
        $builder = builder::table(static::get_table());
        $builder->map_to(answer_option::class);
        $builder->where('questionid', $questionid);
        $builder->order_by('timecreated');

        return $builder->fetch();
    }

    /**
     * @param int $questionid
     * @param int $optionid
     *
     * @return answer_option
     */
    public function get_option(int $questionid, int $optionid): ?answer_option {
        $builder = builder::table(static::get_table());
        $builder->map_to(answer_option::class);

        $builder->where('questionid', $questionid);
        $builder->where('optionid', $optionid);

        /** @var answer_option|null $entity */
        $entity = $builder->one();
        return $entity;
    }
}