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
 * @author  Michael Ivanov <michael.ivanov@totaralearning.com>
 * @package totara_webapi
 */

namespace totara_webapi\hook;

use totara_core\hook\base;
use totara_webapi\endpoint_type\base as endpoint_type;

class api_hook extends base {

    /** @var string[] */
    public $middleware;

    /** @var endpoint_type */
    public $endpoint_type;

    /** @var string|null */
    public $component;

    /** @var string|null */
    public $resolver;

    /**
     * @param array $middleware
     * @param endpoint_type $endpoint_type
     * @param string|null $component
     * @param string|null $resolver
     */
    public function __construct(
        array $middleware,
        endpoint_type $endpoint_type,
        ?string $component,
        ?string $resolver
    ) {
        $this->middleware = $middleware;
        $this->endpoint_type = $endpoint_type;
        $this->component = $component;
        $this->resolver = $resolver;
    }
}