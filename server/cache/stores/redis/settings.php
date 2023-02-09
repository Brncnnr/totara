<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Redis Cache Store - Settings
 *
 * @package   cachestore_redis
 * @copyright 2013 Adam Durana
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (core\redis\sentinel::is_supported()) {
    $settings->add(
        new admin_setting_configtext(
            'cachestore_redis/test_sentinelhosts',
            get_string('test_sentinelhosts', 'cachestore_redis'),
            get_string('test_sentinelhosts_desc', 'cachestore_redis'),
            '',
            PARAM_RAW,
            60
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'cachestore_redis/test_sentinelmaster',
            get_string('test_sentinelmaster', 'cachestore_redis'),
            get_string('test_sentinelmaster_desc', 'cachestore_redis'),
            '',
            PARAM_RAW,
            60
        )
    );
    $settings->add(
        new admin_setting_configpasswordunmask(
            'cachestore_redis/test_sentinelpassword',
            get_string('test_sentinelpassword', 'cachestore_redis'),
            get_string('test_sentinelpassword_desc', 'cachestore_redis'),
            ''
        )
    );
}

$settings->add(
    new admin_setting_configtext(
        'cachestore_redis/test_server',
        get_string('test_server', 'cachestore_redis'),
        get_string('test_server_desc', 'cachestore_redis'),
        '',
        PARAM_RAW,
        16
    )
);
$settings->add(
    new admin_setting_configtext(
        'cachestore_redis/test_database',
        get_string('test_database', 'cachestore_redis'),
        get_string('test_database_desc', 'cachestore_redis'),
        '0',
        PARAM_INT
    )
);
$settings->add(
    new admin_setting_configpasswordunmask(
        'cachestore_redis/test_password',
        get_string('test_password', 'cachestore_redis'),
        get_string('test_password_desc', 'cachestore_redis'),
        ''
    )
);

$settings->add(
    new admin_setting_configtext(
        'cachestore_redis/test_read_server',
        get_string('test_read_server', 'cachestore_redis'),
        get_string('test_read_server_desc', 'cachestore_redis'),
        '',
        PARAM_RAW,
        16
    )
);
$settings->add(
    new admin_setting_configpasswordunmask(
        'cachestore_redis/test_read_password',
        get_string('test_read_password', 'cachestore_redis'),
        get_string('test_read_password_desc', 'cachestore_redis'),
        ''
    )
);

// Totara: do not use optional extension classes for default settings,
//         if they ever decide to change the constant values the db values would break anyway.
$options = array(1 => get_string('serializer_php', 'cachestore_redis')); // Redis::SERIALIZER_PHP == 1

if (class_exists('Redis') && defined('Redis::SERIALIZER_IGBINARY')) {
    $options[Redis::SERIALIZER_IGBINARY] = get_string('serializer_igbinary', 'cachestore_redis');
}

$settings->add(new admin_setting_configselect(
        'cachestore_redis/test_serializer',
        get_string('test_serializer', 'cachestore_redis'),
        get_string('test_serializer_desc', 'cachestore_redis'),
        1, // Redis::SERIALIZER_PHP == 1
        $options
    )
);
