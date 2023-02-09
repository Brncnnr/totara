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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package engage_survey
 */
namespace engage_survey\webapi\resolver\type;

use core\webapi\execution_context;
use core\webapi\type_resolver;
use engage_survey\formatter\question_result_formatter;
use engage_survey\local\helper;
use engage_survey\result\question as question_stat;
use totara_engage\answer\answer_type;

/**
 * Class question_result
 * @package engage_survey\webapi\resolver\type
 */
class question_result extends type_resolver {
    /**
     * @param string            $field
     * @param question_stat     $question_stat
     * @param array             $args
     * @param execution_context $ec
     * @return mixed|void
     */
    public static function resolve(string $field, $question_stat, array $args, execution_context $ec) {
        if (!($question_stat instanceof question_stat)) {
            throw new \coding_exception("Invalid parameter being passed to the type resolver");
        }

        if ('options' === $field) {
            $type = $question_stat->get_type();
            $options = $question_stat->get_option_stats();

            return $options;
        }

        $context = $ec->get_relevant_context();
        $formatter = new question_result_formatter($question_stat, $context);

        $format = null;
        if (isset($args['format'])) {
            $format = $args['format'];
        }

        return $formatter->format($field, $format);
    }
}

