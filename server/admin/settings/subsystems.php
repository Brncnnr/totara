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

global $DB;

/**
 * @var admin_root $ADMIN
 * @var admin_settingpage $optionalsubsystems
 */

if ($hassiteconfig && isset($optionalsubsystems)) {

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'usecomments',
            new lang_string('enablecomments', 'admin'),
            new lang_string('enablecomments_description', 'admin'),
            1
        )
    );

    $optionalsubsystems->add(
        new admin_setting_configtext(
            'commentsperpage',
            new lang_string('commentsperpage', 'admin'),
            '',
            15,
            PARAM_INT
        )
    );

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'usetags',
            new lang_string('usetags', 'admin'),
            new lang_string('configusetags', 'admin'),
            '1'
        )
    );

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'enablenotes',
            new lang_string('enablenotes', 'notes'),
            new lang_string('configenablenotes', 'notes'),
            1
        )
    );

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'enablestats',
            new lang_string('enablestats', 'admin'),
            new lang_string('configenablestats', 'admin'),
            0
        )
    );

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'enablerssfeeds',
            new lang_string('enablerssfeeds', 'admin'),
            new lang_string('configenablerssfeeds', 'admin'),
            0
        )
    );

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'enablebadges',
            new lang_string('enablebadges', 'badges'),
            new lang_string('configenablebadges', 'badges'),
            1
        )
    );

    // Report caching and global restrictions.
    if (empty($CFG->tenantsenabled) && $DB->is_create_table_from_select_supported()) {
        $optionalsubsystems->add(
            new admin_setting_configcheckbox(
                'enablereportcaching',
                new lang_string('enablereportcaching', 'totara_reportbuilder'),
                new lang_string('configenablereportcaching', 'totara_reportbuilder'),
                0
            )
        );
    }

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'enableglobalrestrictions',
            new lang_string('enableglobalrestrictions', 'totara_reportbuilder'),
            new lang_string('enableglobalrestrictions_desc', 'totara_reportbuilder'),
            0
        )
    );

    $optionalsubsystems->add(
        new totara_core_admin_setting_feature_checkbox(
            'enableuser_reports',
            new lang_string('enableuser_reports', 'totara_reportbuilder'),
            new lang_string('enableuser_reports_desc', 'totara_reportbuilder'),
            advanced_feature::ENABLED
        )
    );

    // Audience visibility.
    $defaultenhanced = 0;
    $setting = new admin_setting_configcheckbox(
        'audiencevisibility',
        new lang_string('enableaudiencevisibility', 'totara_cohort'),
        new lang_string('configenableaudiencevisibility', 'totara_cohort'),
        $defaultenhanced
    );
    $setting->set_updatedcallback('totara_rb_purge_ignored_reports');
    $optionalsubsystems->add($setting);

    $defaultenhanced = 0;
    $setting = new admin_setting_configcheckbox(
        'enableconnectserver',
        new lang_string('enableconnectserver', 'totara_connect'),
        new lang_string('enableconnectserver_desc', 'totara_connect'),
        $defaultenhanced
    );
    $setting->set_updatedcallback('totara_rb_purge_ignored_reports');
    $optionalsubsystems->add($setting);

    // Show Hierarchy shortcodes.
    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'showhierarchyshortnames',
            new lang_string('showhierarchyshortnames', 'totara_hierarchy'),
            new lang_string('configshowhierarchyshortnames', 'totara_hierarchy'),
            0
        )
    );

    $optionalsubsystems->add(
        new totara_core_admin_setting_feature_checkbox(
            'enabletotaradashboard',
            new lang_string('enabletotaradashboard', 'totara_dashboard'),
            new lang_string('configenabletotaradashboard', 'totara_dashboard'),
            advanced_feature::ENABLED
        )
    );

    $optionalsubsystems->add(
        new totara_core_admin_setting_feature_checkbox(
            'enablereportgraphs',
            new lang_string('enablereportgraphs', 'totara_reportbuilder'),
            new lang_string('enablereportgraphsinfo', 'totara_reportbuilder'),
            advanced_feature::ENABLED
        )
    );

    $optionalsubsystems->add(
        new totara_core_admin_setting_feature_checkbox(
            'enablepositions',
        new lang_string('enablepositions', 'totara_hierarchy'),
        new lang_string('enablepositions_desc', 'totara_hierarchy'),
        advanced_feature::ENABLED));

    $optionalsubsystems->add(
        new totara_core_admin_setting_feature_checkbox(
            'enableorganisations',
        new lang_string('enableorganisations', 'totara_hierarchy'),
        new lang_string('enableorganisations_desc', 'totara_hierarchy'),
            advanced_feature::ENABLED));

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'totara_job_allowmultiplejobs',
            new lang_string('setting:allowmultiplejobs', 'totara_job'),
            new lang_string('setting:allowmultiplejobs_description', 'totara_job'),
            1
        )
    );

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'enablesitepolicies',
            new lang_string('enablesitepolicies', 'tool_sitepolicy'),
            new lang_string('configenablesitepolicies', 'tool_sitepolicy'),
            0
        )
    );

    // Catalog type.
    $defaultcatalogtype = 'totara';
    $options = [
        'moodle' => get_string('catalog_old', 'totara_catalog'),
        'enhanced' => get_string('catalog_enhanced', 'totara_catalog'),
        'totara' => get_string('catalog_totara', 'totara_catalog'),
    ];
    $setting = new totara_catalog_admin_setting_catalogtype(
        'catalogtype',
        new lang_string('catalogtype', 'totara_catalog'),
        new lang_string('configcatalogtype', 'totara_catalog'),
        $defaultcatalogtype,
        $options
    );
    $setting->set_updatedcallback('totara_menu_reset_all_caches');
    $optionalsubsystems->add($setting);

    $optionalsubsystems->add(new totara_tenant_admin_setting_enable());

    $optionalsubsystems->add(
        new totara_core_admin_setting_feature_checkbox(
            'enablecompetencies',
            new lang_string('enablecompetencies', 'totara_hierarchy'),
            new lang_string('enablecompetencies_desc', 'totara_hierarchy'),
            advanced_feature::ENABLED
        )
    );

    $optionalsubsystems->add(
        new totara_core_admin_setting_feature_checkbox('enablemyteam',
        new lang_string('enableteam', 'totara_core'),
        new lang_string('enableteam_desc', 'totara_core'),
        advanced_feature::ENABLED
        )
    );

    $optionalsubsystems->add(
        new totara_core_admin_setting_feature_checkbox(
            'enableevidence',
            new lang_string('enable_evidence', 'totara_evidence'),
            new lang_string('enable_evidence_description', 'totara_evidence'),
            advanced_feature::ENABLED
        )
    );

    $optionalsubsystems->add(
        new totara_core_admin_setting_feature_checkbox(
            'enableapi',
            new lang_string('enable_api', 'totara_api'),
            new lang_string('enable_api_description', 'totara_api', "$CFG->wwwroot/$CFG->admin/category.php?category=development"),
            advanced_feature::DISABLED
        )
    );

    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'enablewebservices',
            new lang_string('enablewebservices', 'admin'),
            new lang_string('configenablewebservices', 'admin'),
            0
        )
    );

    // Totara: blogs are disabled in Totara by default since 2.9.2.
    $optionalsubsystems->add(
        new admin_setting_configcheckbox(
            'enableblogs',
            new lang_string('enableblogs', 'admin'),
            new lang_string('configenableblogs', 'admin'),
            0
        )
    );
}