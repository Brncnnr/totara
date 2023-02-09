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
namespace totara_oauth2\wrapper\league;

use core\webapi\formatter\field\string_field_formatter;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use totara_oauth2\entity\client_provider;

class client_entity implements ClientEntityInterface {
// phpcs:disable Totara.NamingConventions
    /**
     * @var client_provider
     */
    private $client_provider_entity;

    /**
     * @param client_provider $client_provider
     */
    public function __construct(client_provider $client_provider) {
        $this->client_provider_entity = $client_provider;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string {
        return $this->client_provider_entity->client_id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string {
        return $this->client_provider_entity->name;
    }

    /**
     * @return string|string[]
     */
    public function getRedirectUri() {
        return "";
    }

    /**
     * @return bool
     */
    public function isConfidential(): bool {
        return true;
    }

    /**
     * @param string $secret
     * @return string
     */
    public function verify(string $secret): string {
        return hash_equals($this->client_provider_entity->client_secret, $secret);
    }

    /**
     * @return client_provider
     */
    public function get_client_provider(): client_provider {
        return $this->client_provider_entity;
    }
// phpcs:enable
}