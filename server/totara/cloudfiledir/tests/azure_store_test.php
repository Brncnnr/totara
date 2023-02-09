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
 * @package totara_cloudfiledir
 */

use totara_cloudfiledir\local\provider\azure;

require_once(__DIR__ . '/store_test.php');

defined('MOODLE_INTERNAL') || die();

/**
 * Test Azure Blob store.
 */
final class totara_cloudfiledir_azure_store_testcase extends totara_cloudfiledir_store_testcase {
    protected function prepare_store_config(array $config): array {
        if (!file_exists(__DIR__ . '/../../../../libraries/optional/autoload.php')) {
            $this->markTestSkipped('Missing cloud client libraries');
        }

        require_once(__DIR__ . '/azure_provider_test.php');
        $config['provider'] = 'azure';
        $config['options'] = \totara_cloudfiledir_azure_provider_testcase::get_provider_options();
        $config['bucket'] = \totara_cloudfiledir_azure_provider_testcase::get_provider_bucket();

        if (!$config['options'] or !$config['bucket']) {
            $this->markTestSkipped('Missing Azure test configuration constants');
        }
        return $config;
    }

    protected function get_provider(array $config): totara_cloudfiledir\local\provider\base {
        return new azure($config['options'], $config['idnumber'], $config['bucket'], '');
    }
}
