<?php
/**
 * This file is part of Totara LMS
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package totara_core
 */

namespace totara_core\repository;

use core\orm\entity\repository;
use core\orm\query\builder;
use totara_core\entity\mention;

/**
 * Class mention_repository
 * @package totara_core\repository
 */
final class mention_repository extends repository {
    /**
     * @param int $userid
     * @param int $instanceid
     * @param string $component
     * @param string $area
     * @return mention|null
     */
    public function find_mention(int $userid, int $instanceid, string $component, string $area): ?mention {
        $builder = builder::table(mention::TABLE);
        $builder->map_to(mention::class);

        $builder->where('userid', $userid);
        $builder->where('instanceid', $instanceid);
        $builder->where('component', $component);
        $builder->where('area', $area);

        /** @var mention|null $mention */
        $mention = $builder->one();

        if (!$mention) {
            return null;
        }

        return $mention;
    }
}
