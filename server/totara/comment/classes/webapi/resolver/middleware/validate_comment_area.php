<?php
/**
 * This file is part of Totara Learn
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
namespace totara_comment\webapi\resolver\middleware;

use Closure;
use coding_exception;
use core\webapi\middleware;
use core\webapi\resolver\payload;
use core\webapi\resolver\result;
use totara_comment\comment;

/**
 * Class validate_comment_area
 * @package totara_comment\webapi\resolver\middleware
 */
class validate_comment_area implements middleware {
    /**
     * @var string
     */
    private $area_key;

    /**
     * validate_comment_area constructor.
     * @param string $area_key
     */
    public function __construct(string $area_key) {
        $this->area_key = $area_key;
    }

    /**
     * @param payload $payload
     * @param Closure $next
     *
     * @return result
     */
    public function handle(payload $payload, Closure $next): result {
        if (!$payload->has_variable($this->area_key)) {
            throw new coding_exception("Cannot find area key '{$this->area_key}' in the payload");
        }

        $comment_area = $payload->get_variable($this->area_key);

        if (!in_array(strtolower($comment_area), [comment::COMMENT_AREA, comment::REPLY_AREA])) {
            throw new coding_exception("Invalid comment area: {$comment_area}");
        }

        return $next->__invoke($payload);
    }
}