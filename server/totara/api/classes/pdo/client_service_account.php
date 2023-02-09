<?php
/**
 * This file is part of Totara Core
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
 * @author Scott Davies <scott.davies@totaralearning.com>
 * @package totara_api
 */

namespace totara_api\pdo;

use context_user;
use core\entity\user;
use totara_api\model\client;

/**
 * A class to represent the 'ServiceAccount' abstraction, i.e. the GraphQL type: totara_api_client_service_account.
 * It is really just like a wrapper around the api_client.user_id entity with extra fields.
 */
class client_service_account {
    /**
     * @var user|null
     */
    private $user;

    /**
     * @var bool
     */
    private bool $is_valid = false;

    /**
     * @var string
     */
    private string $status = '';

    /**
     * @var string
     */
    public const VALID = "VALID";

    /**
     * @var string
     */
    public const NOUSER = "NO_USER";

    /**
     * @var string
     */
    public const DELETED = "DELETED";

    /**
     * @var string
     */
    public const SUSPENDED = "SUSPENDED";

    /**
     * @var string
     */
    public const WRONGTENANT = "WRONG_TENANT";

    /**
     * @var string
     */
    public const GUEST = "GUEST";

    /**
     * @var string
     */
    public const ADMIN = "ADMIN";

    /**
     * @param user|null $user
     * @param int|null $tenant_id
     */
    public function __construct(?user $user = null, ?int $tenant_id = null) {
        if (empty($user) || !($user instanceof user)) {
            $this->status = self::NOUSER;
        } else {
            $status = client::validate_api_user($user, $tenant_id);
            $this->status = $status;
            switch ($status) {
                case self::NOUSER:
                case self::DELETED:
                    break;
                case self::WRONGTENANT:
                    $context = context_user::instance(user::logged_in()->id);
                    if (has_capability('moodle/user:viewalldetails', $context)) {
                        $this->user = $user;
                    }
                    break;
                case self::GUEST:
                case self::ADMIN:
                case self::SUSPENDED:
                    $this->user = $user;
                    break;
                case self::VALID:
                    $this->is_valid = true;
                    $this->user = $user;
                    break;
            }
        }
    }

    /**
     * @return user|null
     */
    public function get_user(): ?user {
        return $this->user;
    }

    /**
     * @return string
     */
    public function get_status(): string {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function get_is_valid(): bool {
        return $this->is_valid;
    }
}