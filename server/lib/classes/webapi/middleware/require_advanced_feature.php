<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTDvs
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
 * @author  Murali Nair <murali.nair@totaralearning.com>
 * @package core
 */

namespace core\webapi\middleware;

use Closure;

use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;

use totara_core\advanced_feature;

/**
 * Interceptor that checks if an advanced feature is enabled before allowing
 * further graphql operations.
 */
class require_advanced_feature implements middleware {
    /**
     * @var string feature that needs to be looked up.
     */
    private $feature = null;

    /**
     * Default constructor.
     *
     * @param string $feature feature to look up.
     */
    public function __construct(string $feature) {
        $this->feature = $feature;
    }

    /**
     * @inheritDoc
     */
    public function handle(payload $payload, Closure $next): result {
        advanced_feature::require($this->feature);
        return $next($payload);
    }
}
