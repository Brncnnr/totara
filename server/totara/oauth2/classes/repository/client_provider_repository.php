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
 * @package totara_oauth2
 */
namespace totara_oauth2\repository;

use core\orm\entity\repository;
use totara_oauth2\entity\client_provider;

/**
 * @method client_provider|null one(bool $strict = false)
 */
class client_provider_repository extends repository {
    /**
     * @param string $client_id
     * @param bool $strict
     * @param bool $status
     * @return client_provider|null
     */
    public function find_by_client_id(string $client_id, bool $strict = false, bool $status = true): ?client_provider {
        $repository = client_provider::repository();
        $repository->where("client_id", $client_id)
            ->where('status', $status);

        return $repository->one($strict);
    }

    /**
     * @param string $id_number
     * @return bool
     */
    public function exists_for_id_number(string $id_number): bool {
        $repository = client_provider::repository();
        $repository->where("id_number", $id_number);

        return $repository->exists();
    }
}