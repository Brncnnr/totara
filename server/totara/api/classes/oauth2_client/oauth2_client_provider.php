<?php
/**
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Qingyang Liu <qingyang.liu@totaralearning.com>
 * @package
 */

namespace totara_api\oauth2_client;

use totara_core\advanced_feature;
use totara_oauth2\client\base;
use totara_oauth2\entity\client_provider as entity;


/**
 * Class oauth2_client_provider extends oauth2 provider internally to integrate with totara oauth2 plugin.
 */
class oauth2_client_provider extends base {
    /**
     * @inheritDocs
     */
    public function can_create_token(entity $client_provider): bool {
        return advanced_feature::is_enabled('api');
    }
}