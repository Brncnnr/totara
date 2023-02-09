<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\webapi\middleware;

use Closure;
use core\collection;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use invalid_parameter_exception;
use totara_hierarchy\entity\competency;

/**
 * Interceptor that checks if there are competency details in an incoming graphql
 * payload.
 */
class require_competency implements middleware {
    /**
     * Keys.
     */
    public const KEY_ID = 'id';
    public const KEY_COMPETENCY = 'competency';

    /**
     * @var string key to use to get an initial object from the payload.
     */
    private $main_key;

    /**
     * @var collection<string> subkeys to use on the payload object to get the
     * final id.
     */
    private $sub_keys;

    /**
     * @var string key under which a competency entity is stored in a payload.
     */
    private $storage_key;

    /**
     * Virtual constructor.
     *
     * @param string $id_key key to use when extracting the competency id from a
     *        graphql payload. This can be a compound key eg 'a.b.c' - in which
     *        case the id(s) is extracted as payload['a']['b']['c']. If not
     *        specified, uses self::KEY_ID.
     * @param string $competency_key stores a retrieved competency entity in the
     *        payload under this key. If unspecified, uses self::KEY_COMPETENCY.
     */
    public static function create(
        ?string $id_key = null,
        ?string $competency_key = null
    ) {
        $id = $id_key ?? require_competency::KEY_ID;
        $keys = collection::new(explode('.', $id));

        return new self(
            $keys->shift(),
            $keys,
            $competency_key ?? require_competency::KEY_COMPETENCY
        );
    }

    /**
     * Default constructor.
     *
     * @param string $main_key key for getting initial object from the payload.
     * @param collection<string> sub_keys to use on the payload object to get a
     *        final competency id.
     * @param string $storage_key stores the retrieved competency entity in the
     *        payload under this key.
     */
    private function __construct(
        string $main_key,
        collection $sub_keys,
        string $storage_key
    ) {
        $this->main_key = $main_key;
        $this->sub_keys = $sub_keys;
        $this->storage_key = $storage_key;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        $id = $this->parse($payload);
        if (!$id) {
            throw new invalid_parameter_exception('no competency id in payload');
        }

        if (!is_int($id)) {
            throw new invalid_parameter_exception('non int id in payload');
        }

        $competency = competency::repository()
            ->where('id', $id)
            ->one();

        if (!$competency) {
            throw new invalid_parameter_exception('competency does not exist');
        }

        $payload->set_variable($this->storage_key, $competency);

        return $next($payload);
    }

    /**
     * Returns the competency id from the incoming payload.
     *
     * @param payload $payload payload to parse.
     *
     * @return the extracted competency id if it was found.
     */
    private function parse(payload $payload) {
        return $this->sub_keys->reduce(
            function ($target, string $key) {
                return $target[$key] ?? null;
            },
            $payload->get_variable($this->main_key)
        );
    }
}