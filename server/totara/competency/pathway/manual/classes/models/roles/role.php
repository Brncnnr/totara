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

use core\orm\entity\repository;
use core\orm\query\builder;
use totara_competency\helpers\capability_helper;

/**
 * A role that is available for a competency.
 *
 * @package pathway_manual\models\roles
 */
abstract class role {

    /**
     * @var int
     */
    private $subject_user;

    /**
     * Set the the subject user that this role is in relation to.
     *
     * @param int $subject_user
     * @return static
     */
    final public function set_subject_user(int $subject_user): self {
        $this->subject_user = $subject_user;

        return $this;
    }

    /**
     * Get the internal system name for this role.
     *
     * @return string
     */
    public static function get_name(): string {
        return (new \ReflectionClass(static::class))->getShortName();
    }

    /**
     * Get the display name for this role.
     *
     * @return string
     */
    abstract public static function get_display_name(): string;

    /**
     * Get the position in which this role should be displayed in a list.
     *
     * @return int Lower number = higher in list, higher number = lower in list.
     */
    abstract public static function get_display_order(): int;

    /**
     * Does the current user have this role for the specified user?
     *
     * @param int $subject_user
     * @return bool
     */
    abstract public static function has_for_user(int $subject_user): bool;

    /**
     * Does the current user have this role for any user across the system?
     *
     * @return bool
     */
    abstract public static function has_for_any(): bool;

    /**
     * The capability required to rate.
     *
     * @return string
     */
    abstract protected static function get_capability_name(): ?string;

    /**
     * Does the current user have the capability to do this?
     *
     * @param int $subject_user
     * @return bool
     */
    final public static function has_capability(int $subject_user): bool {
        return is_null(static::get_capability_name()) // Empty capability name means there is no extra capability needed.
            || has_capability(static::get_capability_name(), \context_user::instance($subject_user));
    }

    /**
     * Require that the current user has the capability to do this.
     *
     * @param int $subject_user
     * @throws \required_capability_exception
     */
    final public static function require_capability(int $subject_user) {
        if (!is_null(static::get_capability_name())) {
            require_capability(static::get_capability_name(), \context_user::instance($subject_user));
        }
    }

    /**
     * Throw an exception if the user doesn't have this role for the specified user.
     *
     * @param int $subject_user
     * @throws \moodle_exception
     */
    final public static function require_for_user(int $subject_user): void {
        if (!static::has_for_user($subject_user)) {
            $return_url = capability_helper::can_view_profile($subject_user) ?
                new \moodle_url('/totara/competency/profile', ['user_id' => $subject_user])
                : new \moodle_url('/');

            throw new \moodle_exception(
                'error_user_lacks_role_for_user',
                'pathway_manual',
                $return_url,
                static::get_display_name()
            );
        }
    }

    /**
     * Does the current user have this role for the specified user?
     *
     * @return bool
     */
    final public function has_role(): bool {
        if (!isset($this->subject_user)) {
            throw new \coding_exception('Must set the subject user with set_subject_user()!');
        }
        return static::has_for_user($this->subject_user);
    }

    /**
     * Filter a repository to users that report to the current user with this role.
     *
     * @param builder|repository $builder
     */
    abstract public static function apply_role_restriction_to_builder($builder);

}
