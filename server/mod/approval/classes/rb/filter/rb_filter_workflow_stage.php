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

use mod_approval\model\workflow\workflow;


global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/filters/select.php');

/**
 * Workflow stage report builder filter
 */
class rb_filter_workflow_stage extends rb_filter_select {

    /**
     * @inheritDoc
     */
    public function __construct($type, $value, $advanced, $region, $report, $defaultvalue) {
        parent::__construct($type, $value, $advanced, $region, $report, $defaultvalue);

        $choices = [];
        if ($report->embedobj && !empty($report->embedobj->embeddedparams['workflow'])) {
            /** @var workflow $workflow */
            $workflow = $report->embedobj->embeddedparams['workflow'];

            foreach ($workflow->versions as $version) {
                foreach ($version->stages as $stage) {
                    $choices[$stage->id] = $stage->name;
                }
            }
        }
        $this->options['selectchoices'] = $choices;
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

        return ["$field = $value", []];
    }

    /**
     * Filter by workflow stage
     *
     * @return rb_filter_option
     */
    public static function generate_filter_option(): rb_filter_option {
        return new rb_filter_option(
            'workflow',
            'stage_id',
            get_string('workflow_stage', 'rb_source_approval_workflow_applications'),
            'workflow_stage',
            [
                'simplemode' => true,
                'selectchoices' => [],
            ]
        );
    }
}
