<?php
// This file keeps track of upgrades to
// the glossary module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the methods of database_manager class
//
// Please do not forget to use upgrade_set_timeout()
// before any action that may take longer time to finish.

defined('MOODLE_INTERNAL') || die();

function xmldb_glossary_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2021011900) {

        // Define field definitiontrust to be dropped from glossary_entries.
        $table = new xmldb_table('glossary_entries');
        $field = new xmldb_field('definitiontrust');

        // Conditionally launch drop field definitiontrust.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Glossary savepoint reached.
        upgrade_mod_savepoint(true, 2021011900, 'glossary');
    }

    return true;
}
