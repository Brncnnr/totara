<?php
/**
 * This file is part of Totara Learn
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

use mod_approval\model\workflow\stage_type\provider;

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/filters/select.php');

/**
 * Workflow stage type report builder filter
 */
class rb_filter_workflow_stage_type extends rb_filter_select {

    /**
     * @inheritDoc
     */
    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        $this->options['selectchoices'] = self::get_choices();
    }

    /**
     * @inheritDoc
     */
    public function get_sql_filter($data): array {
        $value = $data['value'];
        if (empty($value)) {
            return [' 1=1 ', []];
        }
        $field = $this->get_field();
        $stage_type_code = provider::get_by_enum($value)::get_code();

        return ["$field = $stage_type_code", []];
    }

    /**
     * Get filter choices
     *
     * @return array
     */
    private static function get_choices(): array {
        $stage_types = provider::get_types();
        $choices = [];

        foreach ($stage_types as $stage_type) {
            $choices[$stage_type::get_enum()] = $stage_type::get_label();
        }

        return $choices;
    }

    /**
     * Filter by workflow stage type
     *
     * @return rb_filter_option
     */
    public static function generate_filter_option(): rb_filter_option {
        return new rb_filter_option(
            'workflow',
            'stage_type',
            get_string('workflow_stage_type', 'rb_source_approval_workflow_applications'),
            'workflow_stage_type',
            [
                'simplemode' => true,
                'selectchoices' => self::get_choices(),
            ]
        );
    }
}
