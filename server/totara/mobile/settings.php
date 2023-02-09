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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_mobile
 */

defined('MOODLE_INTERNAL') || die();

/* @var admin_root $ADMIN */

if ($hassiteconfig) {
    $ADMIN->add('modules', new admin_category('totaramobile', new lang_string('settingscategory', 'totara_mobile')));

    // General settings.
    $settingspage = new admin_settingpage(
        'totaramobilesettings',
        new lang_string('settingspage', 'totara_mobile'),
        'moodle/site:config',
        false
    );

    $settingspage->add(
        new admin_setting_configcheckbox(
            'totara_mobile/enable',
            new lang_string('enabletotaramobile', 'totara_mobile'),
            new lang_string('enabletotaramobile_desc', 'totara_mobile'),
            0
        )
    );

    $settingspage->add(
        new admin_setting_configtext(
            'totara_mobile/urlscheme',
            new lang_string('urlscheme', 'totara_mobile'),
            new lang_string('urlscheme_desc', 'totara_mobile'),
            ''
        )
    );

    $choices = array();
    $choices['1'] = get_string('yes');
    $choices['0'] = get_string('no');
    $settingspage->add(
        new admin_setting_configselect(
            'totara_mobile/coursecompat',
            new lang_string('coursecompat', 'totara_mobile'),
            new lang_string('coursecompat_help', 'totara_mobile'),
            '1',
            $choices
        )
    );

    $ADMIN->add('totaramobile', $settingspage);

    // Authentication settings.
    $authenticationpage = new admin_settingpage(
        'totaramobileauthentication',
        new lang_string('authenticationpage', 'totara_mobile'),
        'moodle/site:config',
        false
    );

    $authtypes = [
        'native' => new lang_string('authtype_choice_native', 'totara_mobile'),
        'webview' => new lang_string('authtype_choice_webview', 'totara_mobile'),
        'browser' => new lang_string('authtype_choice_browser', 'totara_mobile'),
    ];
    $authenticationpage->add(new admin_setting_configselect(
        'totara_mobile/authtype',
        new lang_string('authtype', 'totara_mobile'),
        new lang_string('authtype_desc', 'totara_mobile'), 'native', $authtypes)
    );

    $timeouts = [
        '0' => new lang_string('timeout_choice_0', 'totara_mobile'),
        '1' => new lang_string('timeout_choice_1', 'totara_mobile'),
        '30' => new lang_string('timeout_choice_30', 'totara_mobile'),
        '60' => new lang_string('timeout_choice_60', 'totara_mobile'),
        '90' => new lang_string('timeout_choice_90', 'totara_mobile'),
    ];
    $authenticationpage->add(new admin_setting_configselect(
        'totara_mobile/timeout',
        new lang_string('timeout', 'totara_mobile'),
        new lang_string('timeout_desc', 'totara_mobile'), '0', $timeouts)
    );

    $ADMIN->add('totaramobile', $authenticationpage);

    // Theme settings
    $themepage = new admin_settingpage(
        'totaramobiletheme',
        new lang_string('themepage', 'totara_mobile'),
        'moodle/site:config',
        false
    );

    // Mobile logo file setting.
    $setting = new admin_setting_configstoredfile(
        "totara_mobile/logo",
        new lang_string('themesetting_logo', 'totara_mobile'),
        new lang_string('themesetting_logodesc', 'totara_mobile'),
        'logo',
        0,
        ['accepted_types' => 'web_image']
    );
    $themepage->add($setting);

    // Mobile text colour - default totara green.
    $default = '#69BD45';
    $setting = new admin_setting_configcolourpicker(
        "totara_mobile/primarycolour",
        new lang_string('themesetting_primarycolour', 'totara_mobile'),
        new lang_string('themesetting_primarycolourdesc', 'totara_mobile'),
        $default,
        null,
        false
    );
    $themepage->add($setting);

    // Mobile text colour - defaults to black text.
    $options = [];
    $options['#000000'] = new lang_string('colour_black', 'totara_mobile');
    $options['#FFFFFF'] = new lang_string('colour_white', 'totara_mobile');
    $setting = new admin_setting_configselect(
        "totara_mobile/textcolour",
        new lang_string('themesetting_textcolour', 'totara_mobile'),
        new lang_string('themesetting_textcolourdesc', 'totara_mobile'),
        '#FFFFFF',
        $options
    );
    $themepage->add($setting);

    $ADMIN->add('totaramobile', $themepage);
}
