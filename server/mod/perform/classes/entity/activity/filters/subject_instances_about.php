<?php
/*
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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\entity\activity\filters;

use coding_exception;
use core\collection;
use core\orm\entity\filter\filter;

/**
 * @deprecated replaced by subject_instances_about_role filter.
 */
class subject_instances_about extends filter {

    public const VALUE_ABOUT_SELF = 'SELF';
    public const VALUE_ABOUT_OTHERS = 'OTHERS';
    /**
     * @var string
     */
    protected $subject_instance_alias;

    public function __construct(int $participant_id, string $subject_instance_alias = 'si') {
        parent::__construct([$participant_id]);
        $this->subject_instance_alias = $subject_instance_alias;
    }

    /**
     * @param string[] $about_values an array of strings with one or more value constants
     * @return filter
     * @see subject_instances_about::VALUE_ABOUT_SELF
     * @see subject_instances_about::VALUE_ABOUT_OTHERS
     */
    public function set_value($about_values): filter {
        parent::set_value(new collection($about_values));

        $this->validate_value();

        return $this;
    }

    public function apply(): void {
        if ($this->is_only_about_target()) {
            $this->builder->where("{$this->subject_instance_alias}.subject_user_id", '=', $this->get_participant_id());
            return;
        }

        if ($this->is_only_about_someone_else()) {
            $this->builder->where("{$this->subject_instance_alias}.subject_user_id", '!=',  $this->get_participant_id());
            return;
        }
    }

    protected function validate_value(): void {
        foreach ($this->value as $param) {
            if (!$this->is_allowed_value_option($param)) {
                throw new coding_exception("{$param} is not a valid subject instances about filter parameter");
            }
        }
    }

    protected function is_allowed_value_option($option): bool {
        return $option === self::VALUE_ABOUT_SELF || $option === self::VALUE_ABOUT_OTHERS;
    }

    protected function get_participant_id() {
        return $this->params[0] ?? null;
    }

    private function is_only_about_target(): bool {
        return $this->is_about(self::VALUE_ABOUT_SELF) &&
            !$this->is_about(self::VALUE_ABOUT_OTHERS);
    }

    private function is_only_about_someone_else(): bool {
        return $this->is_about(self::VALUE_ABOUT_OTHERS) &&
            !$this->is_about(self::VALUE_ABOUT_SELF);
    }

    private function is_about(string $about_option): bool {
        $found =  $this->value->find(function (string $about_param) use ($about_option) {
            return $about_param === $about_option;
        });

        return (bool) $found;
    }
}