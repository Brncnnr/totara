<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\formatter;

use core\webapi\formatter\formatter;

/**
 * Formats a totara_competency\helpers\result adapted for the copy pathways
 * process.
 */
class copy_pathway_result extends formatter {
    public const RESULT_ERROR = 'error';
    public const RESULT_SUCCESS = 'success';
    public const RESULT_TOTAL_COUNT = 'copied_count';
    public const RESULT_REVIEW_COUNT = 'need_review_count';

    /**
     * {@inheritdoc}
     */
    protected function get_map(): array {
        return [
            self::RESULT_ERROR => null,
            self::RESULT_SUCCESS => null,
            self::RESULT_TOTAL_COUNT => null,
            self::RESULT_REVIEW_COUNT => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function get_field(string $field) {
        switch ($field) {
            case self::RESULT_ERROR:
                return $this->object->is_failed()
                    ? $this->object->value
                    : null;

            case self::RESULT_SUCCESS:
                return $this->object->is_successful();

            case self::RESULT_TOTAL_COUNT:
                return method_exists($this->object, 'get_total_count') ? $this->object->get_total_count() : 0;

            case self::RESULT_REVIEW_COUNT:
                return method_exists($this->object, 'get_review_count') ? $this->object->get_review_count() : 0;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function has_field(string $field): bool {
        return array_key_exists($field, $this->get_map());
    }
}