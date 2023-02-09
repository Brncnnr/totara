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
use totara_engage\entity\answer_choice;
use core\orm\query\builder;
use totara_engage\entity\answer_option;

final class answer_choice_repository extends repository {
    /**
     * @param int $questionid
     * @param int $userid
     *
     * @return answer_choice[]
     */
    public function get_choices(int $questionid, int $userid): array {
        $builder = builder::table(static::get_table());
        $builder->map_to(answer_choice::class);

        $builder->where('questionid', $questionid);
        $builder->where('userid', $userid);

        return $builder->fetch();
    }

    /**
     * @param int $questionid
     * @return answer_choice[]
     */
    public function get_answers(int $questionid): array {
        $builder = builder::table(static::get_table());
        $builder->map_to(answer_choice::class);

        $builder->where('questionid', $questionid);
        return $builder->fetch();
    }

    /**
     * @param int $questionid
     * @return bool
     */
    public function has_answers(int $questionid): bool {
        $builder = builder::table(static::get_table());
        $builder->where('questionid', $questionid);

        return $builder->exists();
    }


    /**
     * @param int $questionid
     * @return answer_choice[]
     */
    public function get_answers_for_user(int $questionid, int $user_id): array {
        $builder = builder::table(static::get_table(), 'ac')
            ->select_raw('o.value')
            ->join([answer_option::TABLE, 'o'], 'o.id', 'ac.optionid')
            ->where('ac.questionid', $questionid)
            ->where('ac.userid', $user_id)
            ->results_as_arrays();

        return $builder->fetch();
    }

    /**
     * @param int $user_id
     */
    public function delete_by_userid(int $user_id): void {
        builder::table(static::get_table())
            ->where('userid', $user_id)
            ->delete();
    }
}