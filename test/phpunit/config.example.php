<?php
/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD’s customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core
 */

/** @var core_config $CFG */

/***********************************************************************************************************************
 * Getting started
 ***********************************************************************************************************************/
//
// Copy this file to "config.php" and edit it, providing all settings for PHPUnit test execution.
//

$CFG->dataroot  = '/var/totara/phpunit_data';

$CFG->dbtype = 'pgsql';      // One of pgsql, mariadb, mysqli, sqlsrv
$CFG->dblibrary = 'native';  // Always 'native'
$CFG->dbhost = 'localhost';  // Host, URL or IP address
$CFG->dbname = 'phpunit';    // Database name
$CFG->dbuser = 'username';   // Username for your database username
$CFG->dbpass = 'password';   // Password for your database user
$CFG->prefix = 'phpunit_';   // Prefix to use for all table names
$CFG->dboptions = array(
    // Used to enable persistent database connections.
    'dbpersist' => false,
    // Set to true or to the socket path.
    'dbsocket'  => false,
    // Port to use to connect to the database. When empty the default port for your chosen DB will be used.
    'dbport'    => '',
);

/***********************************************************************************************************************
 * Adding configuration options for advanced tests using external systems
 ***********************************************************************************************************************/

/* auth_ldap tests */

// define('TEST_AUTH_LDAP_HOST_URL', 'ldap://127.0.0.1:389');
// define('TEST_AUTH_LDAP_BIND_DN', 'cn=admin,dc=example,dc=org');
// define('TEST_AUTH_LDAP_BIND_PW', 'admin');
// define('TEST_AUTH_LDAP_DOMAIN', 'dc=example,dc=org');

/* elrol_ldap tests */

// define('TEST_ENROL_LDAP_HOST_URL', 'ldap://127.0.0.1:389');
// define('TEST_ENROL_LDAP_BIND_DN', 'cn=admin,dc=example,dc=org');
// define('TEST_ENROL_LDAP_BIND_PW', 'admin');
// define('TEST_ENROL_LDAP_DOMAIN', 'dc=example,dc=org');
