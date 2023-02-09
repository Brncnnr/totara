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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package performelement_numeric_rating_scale
 */

defined('MOODLE_INTERNAL') || die();

$string['default_number_label'] = 'Default value';
$string['high_value_label'] = 'Maximum numeric value';
$string['low_value_label'] = 'Minimum numeric value';
$string['name'] = 'Rating scale: Numeric';
$string['no_response_submitted'] = 'No response submitted';
$string['pluginname'] = 'Rating scale element';
$string['scale_description'] = 'Scale description';
$string['scale_numeric_values'] = 'Numeric values';

// Help messages
$string['numeric_max_value_help'] = "A whole number representing the highest point in the scale’s range. The scale steps in intervals of 1, with a minimum of 3 steps (so there must be a difference of at least 2 between minimum and maximum numeric values).";
$string['numeric_min_value_help'] = "A whole number representing the lowest point in the scale’s range. The scale steps in intervals of 1, with a minimum of 3 steps (so there must be a difference of at least 2 between minimum and maximum numeric values).";
$string['default_value_help_text'] = "A whole number within the range of the minimum and maximum numeric values. It represents the starting position of the sliding marker on the scale before the respondent has selected a value.";

// Errors
$string['error:answer_invalid'] = 'Answer is not valid';
$string['error:answer_required'] = 'Answer is required';

// Deprecated in 13
$string['default_value_help'] = "Default value is a whole number between the numeric values. It is automatically set in proximity to the scale numeric median.
For example, on a scale of 0 - 21 the default value is 10.

Use this setting to customise default value.";
$string['error:question_length_exceeded'] = 'Question text is too long';
$string['error:question_required'] = 'Question is required';
$string['numeric_values_help'] = "Enter minimum and maximum numeric values.

Use whole numbers.

The scale steps in intervals of 1, with a minimum of 3 steps. For example, from 1 to 3.";
$string['preview'] = 'Preview';
$string['preview_help'] = "The slider position represents the default value, a whole number in proximity to the scale numeric values median. For example, on a scale of 0 - 21 the default value is 10.";
$string['question_label'] = 'Question';
$string['question_placeholder'] = 'Type a question that prompts a numerical rating';
$string['response_required_help'] = "Rating scale questions always require an answer.";
