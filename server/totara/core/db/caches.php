<?php
/*
 * This file is part of Totara LMS
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
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

$definitions = array(
    // Cache for the list of hook callbacks.
    'hookwatchers' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 2,
    ),

    // Cache for flex_icons mappings.
    'flex_icons' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 10
    ),

    // Cache for completion progressinfo
    'completion_progressinfo' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 10
    ),

    // Cache for quickaccess menu item preference per user
    // Keys are user id's and values are from the quickaccess_preferences table value field.
    'quickaccessmenu' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 10
    ),

    // Cache for quickaccess menu items for an individual users complete menu.
    // Keys are user id's and values are from the quickaccess_preferences table value field.
    // This is stored as an application cache because it is is potentially very large, and the session
    // size must remain small.
    'quickaccessmenu_complete' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'requireidentifiers' => [
            'userid', // It must belong to a user.
            'lang', // It is also bound to their language.
        ],
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 2,
        'ttl' => 600, // TTL of 600 seconds / 10 minutes.
    ),

    // A request lifetime cache for the outcome of totara_course_is_viewable for the current user.
    'totara_course_is_viewable' => array(
        'mode' => cache_store::MODE_REQUEST,
        'simplekeys' => true,
        'simpledata' => true
    ),

    // Cache of course categories and courses,programs,certifications that are visible to the user.
    'visible_content' => array(
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 5,
        'ttl' => 600
    ),
);
