<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package pathway_manual
 */

namespace pathway_manual\models\roles;

use core\entity\user;
use core\orm\entity\repository;
use core\orm\query\builder;
use totara_job\entity\job_assignment;

class appraiser extends role {

    /**
     * Get the display name for this role.
     *
     * @return string
     */
    public static function get_display_name(): string {
        return get_string('appraiser', 'totara_job');
    }

    /**
     * Get the position in which the appraiser role should be displayed in a list.
     *
     * @return int
     */
    public static function get_display_order(): int {
        return 30; // 3rd in list.
    }

    /**
     * Is the current user the appraiser of the specified user?
     *
     * @param int $subject_user
     * @return bool
     */
    public static function has_for_user(int $subject_user): bool {
        return job_assignment::repository()
            ->where('userid', $subject_user)
            ->where('appraiserid', user::logged_in()->id)
            ->exists();
    }

    /**
     * Is the current user the appraiser for any users?
     *
     * @return bool
     */
    public static function has_for_any(): bool {
        return job_assignment::repository()
            ->where('appraiserid', user::logged_in()->id)
            ->exists();
    }

    /**
     * The capability system does not support appraisers, so there is no capability.
     *
     * @return string
     */
    protected static function get_capability_name(): ?string {
        return null;
    }

    /**
     * Filter a repository to just users that the current user is the appraiser of.
     *
     * @param builder|repository $builder
     */
    public static function apply_role_restriction_to_builder($builder) {
        if (!$builder->has_join(job_assignment::TABLE, 'appraised_ja')) {
            $builder->join([job_assignment::TABLE, 'appraised_ja'], 'id', 'userid');
        }

        $builder->where('appraised_ja.appraiserid', user::logged_in()->id);
    }

}
