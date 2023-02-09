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

namespace pathway_manual\models;

use core\entity\user;
use core\orm\entity\entity;
use moodle_url;
use pathway_manual\entity\rating;
use pathway_manual\models\roles\role;
use pathway_manual\models\roles\self_role;
use totara_hierarchy\entity\competency;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * Class role_rating
 *
 * @package pathway_manual\models
 */
class role_rating {

    /**
     * @var competency
     */
    protected $competency;

    /**
     * @var user
     */
    protected $user;

    /**
     * @var role
     */
    protected $role;

    public function __construct(competency $competency, user $user, role $role) {
        $this->user = $user;
        $this->role = $role;
        $this->competency = $competency;
    }

    /**
     * @return role
     */
    public function get_role(): role {
        return $this->role;
    }

    /**
     * Get the default picture to display for this role.
     * Useful if there is no rating and therefore no user picture to show, so load a placeholder.
     *
     * @return moodle_url
     */
    public function get_default_picture(): moodle_url {
        global $PAGE, $OUTPUT;
        if ($this->role instanceof self_role) {
            $user_picture = new user_picture((object) $this->user->to_array());
            $user_picture->size = 1; // Size f1.
            return $user_picture->get_url($PAGE);
        }

        return $OUTPUT->image_url('u/f1');
    }

    /**
     * Get the role name for display purposes.
     * This is necessary in order to display custom role text for the 'self' role.
     *
     * @return string
     */
    public function get_role_display_name(): string {
        if ($this->role instanceof self_role) {
            if ($this->user->is_logged_in()) {
                return get_string('your_rating', 'pathway_manual');
            } else {
                return $this->user->fullname;
            }
        }

        return $this->role::get_display_name();
    }

    /**
     * Get the most recent rating made by a user with this role.
     *
     * @return rating|entity|null
     */
    public function get_latest_rating(): ?rating {
        return rating::repository()
            ->where('competency_id', $this->competency->id)
            ->where('user_id', $this->user->id)
            ->where('assigned_by_role', $this->role::get_name())
            ->order_by('date_assigned', 'desc')
            ->first();
    }

}
