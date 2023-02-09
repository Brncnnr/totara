<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package contentmarketplace_linkedin
 */
namespace contentmarketplace_linkedin\repository;

use contentmarketplace_linkedin\entity\user_progress;
use contentmarketplace_linkedin\model\user_progress as user_progress_model;
use core\orm\collection;
use core\orm\entity\repository;

/**
 * @method user_progress one(bool $strict = false)
 * @method collection|user_progress[] get(bool $unkeyed = false)
 */
class user_progress_repository extends repository {

    /**
     * Filter query to just completed entries.
     *
     * @return $this
     */
    public function filter_by_complete(): self {
        return $this->where('progress', '>=', user_progress_model::PROGRESS_COMPLETE);
    }

}
