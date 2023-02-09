<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Simon Player <simon.player@totaralms.com>
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package totara_customfield
 */

namespace totara_customfield\prefix;

use moodle_url;
use totara_core\advanced_feature;
use totara_evidence\customfield_area\evidence;
use totara_evidence\models;

defined('MOODLE_INTERNAL') || die();

/**
 * Class evidence_type
 */
class evidence_type extends type_base {

    use unique_type;

    public function __construct($prefix, $context, $extrainfo = []) {
        global $PAGE;
        parent::__construct($prefix, evidence::get_base_table(), evidence::get_area_name(), $context, $extrainfo);

        // Check that we are actually allowed to view this custom fields area
        $type = models\evidence_type::load_by_id($extrainfo['typeid']);
        $type::can_manage(true);
        if (!$type->can_modify()) {
            print_error(
                'error_notification_edit_type',
                'totara_evidence',
                new moodle_url('/totara/evidence/type/index.php'),
                $type->get_display_name()
            );
        }
        $PAGE->navbar->add(get_string('edit_x_type', 'totara_evidence', $type->get_display_name()));
    }

    public function is_feature_type_disabled() {
        return advanced_feature::is_disabled('evidence');
    }

    public function get_capability_managefield() {
        return 'totara/evidence:managetype';
    }

    public function get_page_url(): string {
        return new moodle_url('/totara/evidence/type/fields.php');
    }
}
