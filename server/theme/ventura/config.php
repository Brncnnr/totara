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
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @package theme_ventura
 */

defined('MOODLE_INTERNAL' || die());

$THEME->doctype = 'html5';
$THEME->name = 'ventura';
$THEME->parents = ['legacy', 'base'];
$THEME->enable_dock = true;
$THEME->enable_hide = true;
$THEME->minify_css = false;

$THEME->layouts = array(
    // Most backwards compatible layout with blocks on the left - this is the layout used by default in Totara,
    // it is also the fallback when page layout is set too late when initialising page.
    // Standard Moodle themes have base layout without blocks.
    'base' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
    ),
    // Standard layout with blocks, this is recommended for most pages with general information.
    'standard' => array(
        'file' => 'default.php',
        'regions' => array('top', 'bottom', 'side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    // Main course page.
    'course' => array(
        'file' => 'default.php',
        'regions' => array('top', 'bottom', 'side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
        'options' => array('langmenu' => true),
    ),
    'coursecategory' => array(
        'file' => 'default.php',
        'regions' => array('top', 'bottom', 'side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    // Part of course, typical for modules - default page layout if $cm specified in require_login().
    'incourse' => array(
        'file' => 'default.php',
        'regions' => array('top', 'bottom', 'side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    // The site home page.
    'frontpage' => array(
        'file' => 'default.php',
        'regions' => array('top', 'main', 'bottom', 'side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
        'options' => array('nonavbar' => true),
    ),
    // A page with one column that allows top and bottom configurable blocks.
    'columnpage' => array(
        'file' => 'default.php',
        'regions' => array('top', 'bottom'),
        'defaultregion' => 'top',
    ),
    // Server administration scripts.
    'admin' => array(
        'file' => 'default.php',
        'regions' => array('side-pre'),
        'defaultregion' => 'side-pre',
        'options' => array('fluid' => true),
    ),
    // This would be better described as "user profile" but we've left it as mydashboard
    // for backward compatibilty for existing themes. This layout is NOT used by Totara
    // dashboards but is used by user related pages such as the user profile, private files
    // and badges.
    'mydashboard' => array(
        'file' => 'default.php',
        'regions' => array('top', 'bottom', 'side-pre', 'side-post'),
        'defaultregion' => 'side-pre',
    ),
    // The dashboard layout differs from the one above in that it includes a central block region.
    // It is used by Totara dashboards.
    'dashboard' => array(
        'file' => 'dashboard.php',
        'regions' => array('top', 'bottom', 'main', 'side-pre', 'side-post'),
        'defaultregion' => 'main',
        'options' => array('langmenu' => true),
    ),
    // My public page.
    'mypublic' => array(
        'file' => 'default.php',
        'regions' => array('top', 'bottom', 'main', 'side-pre', 'side-post'),
        'defaultregion' => 'main',
    ),
    'login' => array(
        'file' => 'default.php',
        'regions' => array(),
        'options' => array('langmenu' => true, 'nototaramenu' => true, 'nonavbar' => true),
    ),

    // Pages that appear in pop-up windows - no navigation, no blocks, no header.
    'popup' => array(
        'file' => 'popup.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => true),
    ),
    // No blocks and minimal footer - used for legacy frame layouts only!
    'frametop' => array(
        'file' => 'default.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nocoursefooter' => true),
    ),
    // Embeded pages, like iframe/object embeded in moodleform - it needs as much space as possible.
    'embedded' => array(
        'file' => 'embedded.php',
        'regions' => array()
    ),
    // Used during upgrade and install, and for the 'This site is undergoing maintenance' message.
    // This must not have any blocks, links, or API calls that would lead to database or cache interaction.
    // Please be extremely careful if you are modifying this layout.
    'maintenance' => array(
        'file' => 'maintenance.php',
        'regions' => array(),
    ),
    // Should display the content and basic headers only.
    'print' => array(
        'file' => 'default.php',
        'regions' => array(),
        'options' => array('nofooter' => true, 'nonavbar' => false),
    ),
    // The pagelayout used when a redirection is occuring.
    'redirect' => array(
        'file' => 'embedded.php',
        'regions' => array(),
    ),
    // The pagelayout used for reports.
    'report' => array(
        'file' => 'default.php',
        'regions' => array('top', 'bottom', 'side-pre'),
        'defaultregion' => 'side-pre',
    ),
    // The pagelayout used for safebrowser and securewindow.
    'secure' => array(
        'file' => 'secure.php',
        'regions' => array('top', 'bottom', 'side-pre', 'side-post'),
        'defaultregion' => 'side-pre'
    ),
    'noblocks' => array(
        'file' => 'default.php',
        'regions' => array(),
        'options' => array('noblocks' => true, 'langmenu' => true),
    ),
    'vue' => array(
        'file' => 'default.php',
        'regions' => array(),
        'options' => array(
            'noblocks' => true,
            'langmenu' => true,
            'nonavbar' => true,
            'nosubnav' => true,
        ),
    ),
    'webview' => array(
        'file' => 'webview.php',
        'regions' => array(),
    ),
    // This layout can be used for external users accessing the page.
    // This should also be combined with setting no cookies so that
    // the user won't be logged in and wouldn't see the user menu or other
    // related information a normal logged in user sees
    'external' => array(
        'file' => 'default.php',
        'regions' => array(),
        'options' => array(
            'noblocks' => true,
            'langmenu' => true,
            'nonavbar' => true,
            'nosubnav' => true,
            'nofooter' => true,
            'nototaramenu' => true,
            'nologinbutton' => true,
        ),
    )
);
