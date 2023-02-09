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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

use totara_competency\admin_setting_continuous_tracking;
use totara_competency\admin_setting_legacy_aggregation_method;
use totara_competency\admin_setting_unassign_behaviour;
use totara_competency\controllers\assignment\edit_proficiency_value;
use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die;

/** @var admin_settingpage $settings passed from admin/settings/plugins.php */
/** @var admin_root $ADMIN */

global $CFG;

$ADMIN->add(
    'modules',
    new admin_category(
        'totara_competency',
        get_string('pluginname', 'totara_competency'),
        !advanced_feature::is_enabled('competency_assignment')
    )
);

$ADMIN->add(
    'competencies',
    new admin_externalpage(
        'competency_assignment',
        get_string('title_index', 'totara_competency'),
        "{$CFG->wwwroot}/totara/competency/assignments/index.php",
        "totara/competency:manage_assignments",
        !advanced_feature::is_enabled('competency_assignment')
    )
);
$ADMIN->add(
    'competencies',
    new admin_externalpage(
        'competency_assignment_users',
        get_string('title_users', 'totara_competency'),
        "{$CFG->wwwroot}/totara/competency/assignments/users.php",
        "totara/competency:manage_assignments",
        !advanced_feature::is_enabled('competency_assignment')
    )
);
$ADMIN->add(
    'competencies',
    new admin_externalpage(
        'competency_assignment_create',
        get_string('title_create', 'totara_competency'),
        "{$CFG->wwwroot}/totara/competency/assignments/create.php",
        "totara/competency:manage_assignments",
        !advanced_feature::is_enabled('competency_assignment')
    )
);
$ADMIN->add(
    'competencies',
    new admin_externalpage(
        edit_proficiency_value::ADMIN_EXTERNAL_PAGE_NAME,
        get_string('edit_proficiency_value_by_assignment', 'totara_competency'),
        "{$CFG->wwwroot}/totara/competency/assignments/edit_proficiency_value.php",
        'totara/competency:manage_assignments',
        !advanced_feature::is_enabled('competency_assignment')
    )
);

if ($hassiteconfig) {
    $settings_page = \hierarchy_competency\admin_settings::load_or_create_settings_page($ADMIN);
    if (!is_array($settings_page->req_capability)) {
        $settings_page->req_capability = [$settings_page->req_capability];
    }
    $settings_page->req_capability[] = 'totara/competency:manage_assignments';
    $settings_page->req_capability = array_unique($settings_page->req_capability);

    if ($ADMIN->fulltree) {
        $hidden = advanced_feature::is_disabled('competencies') || advanced_feature::is_disabled('competency_assignment');

        if (!$hidden) {
            // You can't hide headings, if you don't want them, you don't add them.
            $settings_page->add(new admin_setting_heading(
                'totara_competency/heading',
                new lang_string('settings_unassignment_header', 'totara_competency'),
                new lang_string('settings_unassignment_text', 'totara_competency')
            ));

            $setting = new admin_setting_unassign_behaviour(
                admin_setting_unassign_behaviour::NAME,
                new lang_string('settings_unassign_behaviour', 'totara_competency'),
                new lang_string('settings_unassign_behaviour_description', 'totara_competency')
            );
            $settings_page->add($setting);

            $setting = new admin_setting_continuous_tracking(
                'totara_competency/continuous_tracking',
                new lang_string('settings_continuous_tracking', 'totara_competency'),
                new lang_string('settings_continuous_tracking_description', 'totara_competency')
            );
            $settings_page->add($setting);
        }

        if (advanced_feature::is_disabled('competency_assignment')) {
            $setting = new admin_setting_legacy_aggregation_method(
                admin_setting_legacy_aggregation_method::NAME,
                new lang_string('settings_legacy_aggregation_method', 'totara_competency'),
                new lang_string('settings_legacy_aggregation_method_description', 'totara_competency')
            );
            $settings_page->add($setting);
        }
    }
}

