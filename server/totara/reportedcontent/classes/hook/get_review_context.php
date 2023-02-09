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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package totara_reportedcontent
 */

namespace totara_reportedcontent\hook;

use totara_core\hook\base;

/**
 * Hook designed to fetch context & content of a particular reported item
 *
 * @package totara_reportedcontent\hook
 */
class get_review_context extends base {
    /**
     * @var string
     */
    public $component;

    /**
     * @var string|null
     */
    public $area;

    /**
     * @var int
     */
    public $item_id;

    /**
     * @var int
     */
    public $context_id;

    /**
     * @var string
     */
    public $content;

    /**
     * @var int
     */
    public $format;

    /**
     * @var int
     */
    public $time_created;

    /**
     * @var int
     */
    public $user_id;

    /**
     * @var bool
     */
    public $success;

    /**
     * @param string $component
     * @param string|null $area
     * @param int $item_id
     */
    public function __construct(string $component, ?string $area, int $item_id) {
        $this->component = $component;
        $this->area = $area;
        $this->item_id = $item_id;
        $this->success = false;
    }
}