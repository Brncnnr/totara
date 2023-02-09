<?php
/**
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 */

use totara_core\advanced_feature;

defined('MOODLE_INTERNAL') || die();

/**
 * @var bool $has_site_config
 * @var admin_root $ADMIN
 */
if ($hassiteconfig) {

    /** @var admin_settingpage $adv_features_engage */
    $adv_features_engage = $ADMIN->locate('advancedfeatures_engage');
    if ($adv_features_engage) {

        $adv_features_engage->add(
            new totara_core_admin_setting_feature_checkbox('enableengage_resources',
                new lang_string('enable_resources', 'totara_engage'),
                new lang_string('enable_resources_description', 'totara_engage'),
                advanced_feature::DISABLED,
                [
                    'totara_menu_reset_all_caches',
                    'totara_rb_purge_ignored_reports'
                ]
            )
        );

        $adv_features_engage->add(
            new totara_core_admin_setting_feature_checkbox(
                'enablecontainer_workspace',
                new lang_string('enable_workspaces', 'container_workspace'),
                new lang_string('enable_workspaces_description', 'container_workspace'),
                advanced_feature::DISABLED,
                [
                    'totara_menu_reset_all_caches',
                    'totara_rb_purge_ignored_reports'
                ]
            )
        );

        $adv_features_engage->add(
            new totara_core_admin_setting_feature_checkbox(
                'enableml_recommender',
                new lang_string('enable_recommenders', 'ml_recommender'),
                new lang_string('enable_recommenders_description', 'ml_recommender'),
                advanced_feature::DISABLED,
                [
                    [
                        '\core_ml\settings_helper',
                        'recommender_advanced_features_callback'
                    ]
                ]
            )
        );

        $adv_features_engage->add(
            new totara_core_admin_setting_feature_checkbox(
                'enabletotara_msteams',
                new lang_string('enable_msteams', 'totara_msteams'),
                new lang_string('enable_msteams_description', 'totara_msteams'),
                advanced_feature::DISABLED,
                [
                    [
                        '\totara_msteams\settings_helper',
                        'advanced_features_callback'
                    ]
                ]
            )
        );
    }
}