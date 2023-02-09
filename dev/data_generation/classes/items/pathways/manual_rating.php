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
 * @author Aleksandr Baishev <aleksandr.baishev@totaralearning.com>
 * @package totara_competency
 */

namespace degeneration\items\pathways;

use degeneration\App;
use degeneration\items\competency;
use degeneration\items\user;
use pathway_manual\manual;
use totara_hierarchy\entity\scale_value;

/**
 * Class criterion
 *
 * @method \totara_competency\pathway get_data(?string $property = null)
 *
 * @package degeneration\items\pathways
 */
class manual_rating extends pathway {

    /**
     * Scale value rating to give
     *
     * @var scale_value|null
     */
    protected $scale_value = null;

    /**
     * Add comment to the rating
     *
     * @var string
     */
    protected $comment = null;

    /**
     * Rate as
     *
     * @var int
     */
    protected $as;

    /**
     * Rate user
     *
     * @var user
     */
    protected $target_user;

    /**
     * Rater user
     *
     * @var user
     */
    protected $rater_user;

    /**
     * Add comment
     *
     * @param string $comment
     * @return $this
     */
    public function set_comment(string $comment) {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Rate the competency as
     *
     * @param int $role
     * @return $this
     */
    public function as(int $role) {
        $this->as = $role;

        return $this;
    }

    /**
     * Set rater user
     *
     * @param user $user
     * @return $this
     */
    public function rate_as(user $user) {
        $this->rater_user = $user;

        return $this;
    }

    /**
     * Set user being rated
     *
     * @param user $user
     * @return $this
     */
    public function rate(user $user) {
        $this->target_user = $user;

        return $this;
    }

    /**
     * Set scale value
     *
     * @param scale_value $value
     * @return $this
     */
    public function set_value(scale_value $value) {
        $this->scale_value = $value;

        return $this;
    }

    /**
     * Get the competency this criteria group is for
     *
     * @return competency|null
     */
    public function get_competency(): ?competency {
        return $this->competency;
    }

    /**
     * Check that prerequisites have been met
     *
     * @return $this
     */
    public function check_prerequisites() {
        if (!$this->target_user) {
            throw new \Exception('Target user is required to create a manual rating');
        }

        if (!$this->rater_user) {
            throw new \Exception('Rater user is required to create a manual rating');
        }

        if (!$this->scale_value) {
            throw new \Exception('Value is required to create a manual rating');
        }

        return parent::check_prerequisites();
    }

    /**
     * Get list of properties to be added to the generated item
     *
     * @return array
     */
    public function get_properties(): array {
        if ($this->rater_user->get_data()->id === $this->target_user->get_data()->id && $this->as === null) {
            $this->as = manual::ROLE_SELF;
        }

        if (empty($this->as)) {
            throw new \Exception('You must specify the role you are rating as');
        }

        // I know values doesn't make sense here, it's just for improved readability
        return array_values([
            'competency' => $this->get_competency()->get_data(),
            'target_user' => $this->target_user->get_data(),
            'rater_user' => $this->rater_user->get_data(),
            'as' => $this->as,
            'scale_value' => $this->scale_value,
            'comment' => $this->comment ?? App::faker()->bs,
        ]);
    }
}