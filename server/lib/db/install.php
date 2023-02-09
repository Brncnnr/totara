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
 * This file is executed right after the install.xml
 *
 * For more information, take a look to the documentation available:
 *     - Upgrade API: {@link http://docs.moodle.org/dev/Upgrade_API}
 *
 * @package   core_install
 * @category  upgrade
 * @copyright 2009 Petr Skoda (http://skodak.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Main post-install tasks to be executed after the BD schema is available
 *
 * This function is automatically executed after Moodle core DB has been
 * created at initial install. It's in charge of perform the initial tasks
 * not covered by the {@link install.xml} file, like create initial users,
 * roles, templates, moving stuff from other plugins...
 *
 * Note that the function is only invoked once, at install time, so if new tasks
 * are needed in the future, they will need to be added both here (for new sites)
 * and in the corresponding {@link upgrade.php} file (for existing sites).
 *
 * All plugins within Moodle (modules, blocks, reports...) support the existence of
 * their own install.php file, using the "Frankenstyle" component name as
 * defined at {@link http://docs.moodle.org/dev/Frankenstyle}, for example:
 *     - {@link xmldb_page_install()}. (modules don't require the plugintype ("mod_") to be used.
 *     - {@link xmldb_enrol_meta_install()}.
 *     - {@link xmldb_workshopform_accumulative_install()}.
 *     - ....
 *
 * Finally, note that it's also supported to have one uninstall.php file that is
 * executed also once, each time one plugin is uninstalled (before the DB schema is
 * deleted). Those uninstall files will contain one function, using the "Frankenstyle"
 * naming conventions, like {@link xmldb_enrol_meta_uninstall()} or {@link xmldb_workshop_uninstall()}.
 */
function xmldb_main_install() {
    global $CFG, $DB, $SITE, $OUTPUT;

    // Totara: start using tenant db structures.
    set_config('tenantready', '1');

    // Make sure system context exists
    $syscontext = context_system::instance(0, MUST_EXIST, false);
    if ($syscontext->id != SYSCONTEXTID) {
        throw new moodle_exception('generalexceptionmessage', 'error', '', 'Unexpected new system context id!');
    }

    // Totara: add foreign keys with circular references that could not be installed automatically.
    $table = new xmldb_table('tenant');
    $key = new xmldb_key('cohortid', XMLDB_KEY_FOREIGN_UNIQUE, array('cohortid'), 'cohort', array('id'), 'restrict');
    $DB->get_manager()->add_key($table, $key);

    // Create site course
    if ($DB->record_exists('course', array())) {
        throw new moodle_exception('generalexceptionmessage', 'error', '', 'Can not create frontpage course, courses already exist.');
    }
    $newsite = new stdClass();
    $newsite->fullname     = '';
    $newsite->shortname    = '';
    $newsite->summary      = NULL;
    $newsite->newsitems    = 3;
    $newsite->numsections  = 1;
    $newsite->category     = 0;
    $newsite->format       = 'site';  // Only for this course
    $newsite->timecreated  = time();
    $newsite->timemodified = $newsite->timecreated;

    // Totara: add default container type to 'container_site' - so that it does not fallback to container_course.
    $newsite->containertype = 'container_site';

    if (defined('SITEID')) {
        $newsite->id = SITEID;
        $DB->import_record('course', $newsite);
        $DB->get_manager()->reset_sequence('course');
    } else {
        $newsite->id = $DB->insert_record('course', $newsite);
        define('SITEID', $newsite->id);
    }
    // set the field 'numsections'. We can not use format_site::update_format_options() because
    // the file is not loaded
    $DB->insert_record('course_format_options', array('courseid' => SITEID, 'format' => 'site',
        'sectionid' => 0, 'name' => 'numsections', 'value' => $newsite->numsections));
    $SITE = get_site();
    if ($newsite->id != $SITE->id) {
        throw new moodle_exception('generalexceptionmessage', 'error', '', 'Unexpected new site course id!');
    }
    // Make sure site course context exists
    context_course::instance($SITE->id);
    // Update the global frontpage cache
    $SITE = $DB->get_record('course', array('id'=>$newsite->id), '*', MUST_EXIST);


    // Create default course category
    if ($DB->record_exists('course_categories', array())) {
        throw new moodle_exception('generalexceptionmessage', 'error', '', 'Can not create default course category, categories already exist.');
    }
    $cat = new stdClass();
    $cat->name         = get_string('miscellaneous');
    $cat->depth        = 1;
    $cat->sortorder    = MAX_COURSES_IN_CATEGORY;
    $cat->timemodified = time();
    $catid = $DB->insert_record('course_categories', $cat);
    $DB->set_field('course_categories', 'path', '/'.$catid, array('id'=>$catid));
    // Make sure category context exists
    context_coursecat::instance($catid);


    $defaults = array(
        'rolesactive'           => '0', // marks fully set up system
        'auth'                  => '', // Totara: do not enable email auth by default.
        'auth_pop3mailbox'      => 'INBOX',
        'enrol_plugins_enabled' => 'manual,guest,self,cohort',
        'theme'                 => theme_config::DEFAULT_THEME,
        'filter_multilang_converted' => 1,
        'siteidentifier'        => random_string(32).get_host_from_url($CFG->wwwroot),
        'registrationenabled'   => 1,
        'backup_version'        => 2008111700,
        'backup_release'        => '2.0 dev',
        'sessiontimeout'        => 7200, // must be present during roles installation
        'stringfilters'         => '', // These two are managed in a strange way by the filters
        'filterall'             => 0, // setting page, so have to be initialised here.
        'texteditors'           => 'atto,weka,textarea',
        'antiviruses'           => '',
        'media_plugins_sortorder' => 'videojs,youtube',
        'upgrade_minmaxgradestepignored' => 1, // New installs should not run this upgrade step.
        'upgrade_extracreditweightsstepignored' => 1, // New installs should not run this upgrade step.
        'upgrade_calculatedgradeitemsignored' => 1, // New installs should not run this upgrade step.
        'upgrade_letterboundarycourses' => 1, // New installs should not run this upgrade step.
    );
    foreach($defaults as $key => $value) {
        set_config($key, $value);
    }

    // Create guest record - do not assign any role, guest user gets the default guest role automatically on the fly
    if ($DB->record_exists('user', array())) {
        throw new moodle_exception('generalexceptionmessage', 'error', '', 'Can not create default users, users already exist.');
    }
    $guest = new stdClass();
    $guest->auth        = 'manual';
    $guest->username    = 'guest';
    $guest->password    = hash_internal_user_password('guest');
    $guest->firstname   = get_string('guestuser');
    $guest->lastname    = ' ';
    $guest->email       = 'root@localhost';
    $guest->description = get_string('guestuserinfo');
    $guest->confirmed   = 1;
    $guest->lang        = $CFG->lang;
    $guest->timemodified= time();
    $guest->id = $DB->insert_record('user', $guest);
    if ($guest->id != 1) {
        echo $OUTPUT->notification('Unexpected id generated for the Guest account. Your database configuration or clustering setup may not be fully supported', 'notifyproblem');
    }
    // Store guest id
    set_config('siteguest', $guest->id);
    // Make sure user context exists
    context_user::instance($guest->id);


    // Now create admin user
    $admin = new stdClass();
    $admin->auth         = 'manual';
    $admin->firstname    = get_string('admin');
    $admin->lastname     = get_string('user');
    $admin->username     = 'admin';
    $admin->password     = 'adminsetuppending';
    $admin->email        = '';
    $admin->confirmed    = 1;
    $admin->lang         = $CFG->lang;
    $admin->maildisplay  = 1;
    $admin->timemodified = time();
    $admin->lastip       = CLI_SCRIPT ? '0.0.0.0' : getremoteaddr(); // installation hijacking prevention
    $admin->id = $DB->insert_record('user', $admin);

    // Totara: set the homepage to site for admin via direct DB insert, we do not want them to use dashboard by default.
    $preference = new stdClass();
    $preference->userid = $admin->id;
    $preference->name   = 'user_home_page_preference';
    $preference->value  = HOMEPAGE_SITE;
    $DB->insert_record('user_preferences', $preference);

    if ($admin->id != 2) {
        echo $OUTPUT->notification('Unexpected id generated for the Admin account. Your database configuration or clustering setup may not be fully supported', 'notifyproblem');
    }
    if ($admin->id != ($guest->id + 1)) {
        echo $OUTPUT->notification('Nonconsecutive id generated for the Admin account. Your database configuration or clustering setup may not be fully supported.', 'notifyproblem');
    }

    // Store list of admins
    set_config('siteadmins', $admin->id);
    // Make sure user context exists
    context_user::instance($admin->id);


    // Install the roles system.
    $managerrole        = create_role('', 'manager', '', 'manager');
    $coursecreatorrole  = create_role('', 'coursecreator', '', 'coursecreator');
    $editteacherrole    = create_role('', 'editingteacher', '', 'editingteacher');
    $noneditteacherrole = create_role('', 'teacher', '', 'teacher');
    $studentrole        = create_role('', 'student', '', 'student');
    $guestrole          = create_role('', 'guest', '', 'guest');
    $userrole           = create_role('', 'user', '', 'user');
    $frontpagerole      = create_role('', 'frontpage', '', 'frontpage');
    $staffmanagerrole   = create_role('', 'staffmanager', '', 'staffmanager');
    $workspacecreatorrole = create_role('', 'workspacecreator', '', 'workspacecreator');
    $workspaceownerrole  = create_role('', 'workspaceowner', '', 'workspaceowner');
    $performanceactivitycreator = create_role('', 'performanceactivitycreator', '', 'performanceactivitycreator');
    $performanceactivitymanager = create_role('', 'performanceactivitymanager', '', 'performanceactivitymanager');
    $apiuser = create_role('', 'apiuser', '', 'apiuser');
    $approvalworkflowmanager = create_role('', 'approvalworkflowmanager', '', 'approvalworkflowmanager');
    $approvalworkflowapprover = create_role('', 'approvalworkflowapprover', '', 'approvalworkflowapprover');

    // Now is the correct moment to install capabilities - after creation of legacy roles, but before assigning of roles
    update_capabilities('moodle');


    // Default allow role matrices.
    foreach ($DB->get_records('role') as $role) {
        foreach (array('assign', 'override', 'switch') as $type) {
            $function = 'allow_'.$type;
            $allows = get_default_role_archetype_allows($type, $role->archetype);
            foreach ($allows as $allowid) {
                $function($role->id, $allowid);
            }
        }
    }

    // Set up the context levels where you can assign each role.
    set_role_contextlevels($managerrole,        get_default_contextlevels('manager'));
    set_role_contextlevels($coursecreatorrole,  get_default_contextlevels('coursecreator'));
    set_role_contextlevels($editteacherrole,    get_default_contextlevels('editingteacher'));
    set_role_contextlevels($noneditteacherrole, get_default_contextlevels('teacher'));
    set_role_contextlevels($studentrole,        get_default_contextlevels('student'));
    set_role_contextlevels($guestrole,          get_default_contextlevels('guest'));
    set_role_contextlevels($userrole,           get_default_contextlevels('user'));
    set_role_contextlevels($staffmanagerrole,   get_default_contextlevels('staffmanager'));
    set_role_contextlevels($workspacecreatorrole, get_default_contextlevels('workspacecreatorrole'));
    set_role_contextlevels($workspaceownerrole, get_default_contextlevels('workspaceownerrole'));
    set_role_contextlevels($performanceactivitycreator, get_default_contextlevels('performanceactivitycreator'));
    set_role_contextlevels($performanceactivitymanager, get_default_contextlevels('performanceactivitymanager'));
    set_role_contextlevels($apiuser, get_default_contextlevels('apiuser'));
    set_role_contextlevels($approvalworkflowmanager, get_default_contextlevels('approvalworkflowmanager'));
    set_role_contextlevels($approvalworkflowapprover, get_default_contextlevels('approvalworkflowapprover'));

    // Init theme and JS revisions
    set_config('themerev', time());
    set_config('jsrev', time());

    // No admin setting for this any more, GD is now required, remove in Moodle 2.6.
    set_config('gdversion', 2);

    // Install licenses
    require_once($CFG->libdir . '/licenselib.php');
    license_manager::install_licenses();

    // Init profile pages defaults
    if ($DB->record_exists('my_pages', array())) {
        throw new moodle_exception('generalexceptionmessage', 'error', '', 'Can not create default profile pages, records already exist.');
    }
    $mypage = new stdClass();
    $mypage->userid = NULL;
    $mypage->name = '__default';
    $mypage->private = 0;
    $mypage->sortorder  = 0;
    $DB->insert_record('my_pages', $mypage);
    $mypage->private = 1;
    $DB->insert_record('my_pages', $mypage);

    // Set a sensible default sort order for the most-used question types.
    set_config('multichoice_sortorder', 1, 'question');
    set_config('truefalse_sortorder', 2, 'question');
    set_config('match_sortorder', 3, 'question');
    set_config('shortanswer_sortorder', 4, 'question');
    set_config('numerical_sortorder', 5, 'question');
    set_config('essay_sortorder', 6, 'question');

    require_once($CFG->libdir . '/db/upgradelib.php');
    make_default_scale();
    make_competence_scale();

    require_once($CFG->dirroot . '/badges/upgradelib.php'); // Core install and upgrade related functions only for badges.
    badges_install_default_backpacks();

    // Turn completion on in Totara when upgrading from Moodle.
    set_config('enablecompletion', 1, 'moodlecourse');
    set_config('completionstartonenrol', 1, 'moodlecourse');

    // Disable editing execpaths by default for security.
    set_config('preventexecpath', '1');
    // Then provide default values to prevent them appearing on the upgradesettings page.
    set_config('geoipfile', $CFG->dataroot . 'geoip/GeoLiteCity.dat');
    set_config('location', '', 'enrol_flatfile');
    set_config('filter_tex_pathlatex', '/usr/bin/latex');
    set_config('filter_tex_pathdvips', '/usr/bin/dvips');
    set_config('filter_tex_pathconvert', '/usr/bin/convert');
    set_config('pathtodu', '');
    set_config('pathtoclam', '');
    set_config('aspellpath', '');
    set_config('pathtodot', '');
    set_config('quarantinedir', '');
    set_config('backup_auto_destination', '', 'backup');
    set_config('gspath', '/usr/bin/gs', 'assignfeedback_editpdf');
    set_config('exporttofilesystempath', '', 'reportbuilder');
    set_config('pathlatex', '/usr/bin/latex', 'filter_tex');
    set_config('pathdvips', '/usr/bin/dvips', 'filter_tex');
    set_config('pathconvert', '/usr/bin/convert', 'filter_tex');
    set_config('pathmimetex', '', 'filter_tex');

    // Call this before adding other tags to create default collection
    core_tag_collection::get_collections();

    // Installing default topic collection, as this is required to be done before any other component/plugin
    // being installed. If it is not being installed first, then by the time plugin get to installed, it will not be
    // able to find any topic collection.
    $record = new stdClass();
    $record->name = get_string('pluginname', 'totara_topic');
    $record->isdefault = 1;
    $record->component = 'totara_topic';
    $record->sortorder = 1 + (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM "ttr_tag_coll"');
    $record->searchable = 1;
    set_config('topic_collection_id', $DB->insert_record('tag_coll', $record));
    // Installing default hashtag collection.
    $record = new stdClass();
    $record->name = get_string('hashtag', 'totara_core');
    $record->isdefault = 1;
    $record->component = 'totara_core';
    $record->sortorder = 1 + (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM "ttr_tag_coll"');
    $record->searchable = 1;
    set_config('hashtag_collection_id', $DB->insert_record('tag_coll', $record));

    require_once(__DIR__ . '/../../totara/core/db/upgradelib.php');
    totara_core_upgrade_create_relationship('totara_core\relationship\resolvers\subject', 'subject', 1);
}
