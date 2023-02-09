<?php
/**
 * This file is part of Totara Talent Experience Platform
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
 * @author Cody Finegan <cody.finegan@totara.com>
 * @package totara_useraction
 */

namespace totara_useraction\entity;

use core\entity\cohort;
use core\orm\entity\entity;
use core\orm\entity\relations\belongs_to;

/**
 * Entity representing a scheduled rule audience filter.
 *
 * @property-read int $id
 * @property int $scheduled_rule_id
 * @property int $cohort_id
 * @property-read scheduled_rule $scheduled_rule
 * @property-read cohort $audience
 */
class scheduled_rule_audience_map extends entity {
    public const TABLE = 'totara_useraction_scheduled_rule_audience_map';

    /**
     * @return belongs_to
     */
    public function audience(): belongs_to {
        return $this->belongs_to(cohort::class, 'cohort_id');
    }

    /**
     * @return belongs_to
     */
    public function scheduled_rule(): belongs_to {
        return $this->belongs_to(scheduled_rule::class, 'scheduled_rule_id');
    }
}
