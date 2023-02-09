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
 * @var admin_root $ADMIN
 * @var bool $hassiteconfig
 */

if ($hassiteconfig) {

    /** @var admin_settingpage $adv_features_learn */
    $adv_features_learn = $ADMIN->locate('advancedfeatures_learn');
    if ($adv_features_learn) {

        $adv_features_learn->add(
            new admin_setting_configcheckbox(
                'enableportfolios',
                new lang_string('enabled', 'portfolio'),
                new lang_string('enableddesc', 'portfolio'),
                0
            )
        );

        // Conditional activities: completion and availability
        $adv_features_learn->add(
            new admin_setting_configcheckbox(
                'enablecompletion',
                new lang_string('enablecompletion', 'completion'),
                new lang_string('configenablecompletion', 'completion'),
                1
            )
        );

        $options = [
            1 => get_string('completionactivitydefault', 'completion'),
            0 => get_string('completion_none', 'completion')
        ];
        $adv_features_learn->add(
            new admin_setting_configselect(
                'completiondefault',
                new lang_string('completiondefault', 'completion'),
                new lang_string('configcompletiondefault', 'completion'),
                1,
                $options
            )
        );

        $checkbox = new admin_setting_configcheckbox(
            'enableavailability',
            new lang_string('enableavailability', 'availability'),
            new lang_string('enableavailability_desc', 'availability'),
            1
        );
        $checkbox->set_affects_modinfo(true);
        $adv_features_learn->add($checkbox);

        $adv_features_learn->add(
            new admin_setting_configcheckbox(
                'enablecourserpl',
                new lang_string('enablecourserpl', 'completion'),
                new lang_string('configenablecourserpl', 'completion'),
                1
            )
        );

        // Module RPLs
        // Get module list
        $modules = $DB->get_records("modules");
        if ($modules) {
            // Some modules are not for courses per se.
            $excluded = [
                'approval',
                'perform'
            ];

            $defaultmodules = [];
            $modulebyname = [];
            foreach ($modules as $module) {
                if (in_array($module->name, $excluded)) {
                    continue;
                }

                $strmodulename = get_string("modulename", "$module->name");
                // Deal with modules which are lacking the language string
                if ($strmodulename == '[[modulename]]') {
                    $strmodulename = $module->name;
                }
                $modulebyname[$module->id] = $strmodulename;
                $defaultmodules[$module->id] = 1;
            }
            asort($modulebyname, SORT_LOCALE_STRING);

            $adv_features_learn->add(
                new admin_setting_configmulticheckbox(
                    'enablemodulerpl',
                    new lang_string('enablemodulerpl', 'completion'),
                    new lang_string('configenablemodulerpl', 'completion'),
                    $defaultmodules,
                    $modulebyname
                )
            );
        }

        $adv_features_learn->add(
            new admin_setting_configcheckbox(
                'enableplagiarism',
                new lang_string('enableplagiarism', 'plagiarism'),
                new lang_string('configenableplagiarism', 'plagiarism'),
                0
            )
        );

        $adv_features_learn->add(
            new admin_setting_configcheckbox(
                'enablecontentmarketplaces',
                new lang_string('enablecontentmarketplaces', 'totara_contentmarketplace'),
                new lang_string('enablecontentmarketplacesdesc', 'totara_contentmarketplace'),
                1
            )
        );

        $adv_features_learn->add(
            new admin_setting_configcheckbox(
                'enableprogramextensionrequests',
                new lang_string('enableprogramextensionrequests', 'totara_core'),
                new lang_string('enableprogramextensionrequests_help', 'totara_core'),
                1
            )
        );

        $adv_features_learn->add(
            new totara_core_admin_setting_feature_checkbox(
                'enablelearningplans',
                new lang_string('enablelearningplans', 'totara_plan'),
                new lang_string('configenablelearningplans', 'totara_plan'),
                1,
                [
                    'totara_menu_reset_all_caches',
                    'totara_rb_purge_ignored_reports',
                    [
                        'enrol_totara_learningplan_util',
                        'feature_setting_updated_callback'
                    ]
                ]
            )
        );

        $adv_features_learn->add(
            new totara_core_admin_setting_feature_checkbox(
                'enableprograms',
                new lang_string('enableprograms', 'totara_program'),
                new lang_string('configenableprograms', 'totara_program'),
                advanced_feature::ENABLED,
                [
                    'totara_menu_reset_all_caches',
                    'totara_rb_purge_ignored_reports',
                    ['enrol_totara_program_util', 'feature_setting_updated_callback']
                ]
            )
        );

        $adv_features_learn->add(
            new totara_core_admin_setting_feature_checkbox(
                'enablecertifications',
                new lang_string('enablecertifications', 'totara_program'),
                new lang_string('configenablecertifications', 'totara_program'),
                advanced_feature::ENABLED
            )
        );

        $adv_features_learn->add(
            new totara_core_admin_setting_feature_checkbox(
                'enablerecordoflearning',
                new lang_string('enablerecordoflearning', 'totara_plan'),
                new lang_string('enablerecordoflearninginfo', 'totara_plan'),
                advanced_feature::ENABLED
            )
        );

        $defaultenhanced = 0;
        $setting = new admin_setting_configcheckbox(
            'enableprogramcompletioneditor',
            new lang_string('enableprogramcompletioneditor', 'totara_program'),
            new lang_string('enableprogramcompletioneditor_desc', 'totara_program'),
            $defaultenhanced
        );
        $setting->set_updatedcallback('totara_rb_purge_ignored_reports');
        $adv_features_learn->add($setting);

        $adv_features_learn->add(
            new admin_setting_configcheckbox(
                'enableoutcomes',
                new lang_string('enableoutcomes', 'grades'),
                new lang_string('enableoutcomes_help', 'grades'),
                0
            )
        );

        $adv_features_learn->add(
            new admin_setting_configcheckbox(
                'enablelegacyprogramassignments',
                new lang_string('enablelegacyprogramassignments', 'totara_program'),
                new lang_string('enablelegacyprogramassignments_help', 'totara_program'),
                0
            )
        );

        $adv_features_learn->add(
            new totara_core_admin_setting_feature_checkbox(
                'enablecompletionimport',
                new lang_string('enablecompletionimport', 'totara_completionimport'),
                new lang_string('enablecompletionimport_help', 'totara_completionimport'),
                advanced_feature::ENABLED
            )
        );
    }
}