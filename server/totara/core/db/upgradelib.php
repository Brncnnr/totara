<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2014 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */




/**
 * Create a relationship and corresponding relationship resolver record for a relationship class.
 *
 * Please note that if you refactor/move a relationship resolver class, you will need to
 * update all corresponding relationship resolver table rows that use that class_name!
 *
 * @param string|array $resolver_classes
 * @param string $idnumber Unique identifier.
 * @param int $sort_order
 * @param int $type Optional type identifier - defaults to 0.
 * @param string $component Plugin that the relationship is exclusive to. Defaults to being available for all.
 *
 * @since Totara 13.0
 */
function totara_core_upgrade_create_relationship($resolver_classes, $idnumber = null, $sort_order = 1, $type = 0, $component = null) {
    global $DB;

    $resolver_classes = is_array($resolver_classes)
        ? $resolver_classes
        : [$resolver_classes];

    // Checks if idnumber already exists, then updates the relationship.
    if ($idnumber) {
        $sql = "idnumber = :idnumber OR idnumber = :resolver_class";
        $params = ['idnumber' => $idnumber, 'resolver_class' => $resolver_classes[0]];
        $relationship = $DB->get_record_select(
            'totara_core_relationship',
            $sql,
            $params
        );

        // Update the sort order, type & component if the relationship already exists.
        if ($relationship) {
            $relationship->idnumber = $idnumber;
            // Conditionally add properties if they exist as a db column.
            if (isset($relationship->sort_order)) {
                $relationship->sort_order = $sort_order;
            }
            if (isset($relationship->type)) {
                $relationship->type = $type;
            }
            if (isset($relationship->component)) {
                $relationship->component = $component;
            }
            totara_core_update_relationship($relationship, $resolver_classes);
            return;
        }
    }

    if (!$idnumber) {
        $idnumber = $resolver_classes[0];
    }
    // Creates the new relationship with the resolver classes.
    totara_core_create_relationship($resolver_classes, $idnumber, $sort_order, $type, $component);
}

/**
 * Creates a totara relationship with the resolvers.
 *
 * @param array $resolver_classes
 * @param string $idnumber
 * @param int $sort_order
 * @param int $type
 * @param string|null $component
 */
function totara_core_create_relationship(array $resolver_classes, string $idnumber, int $sort_order = 1, int $type = 0, string $component = null): void {
    global $DB;
    $DB->transaction(static function() use ($DB, $resolver_classes, $idnumber, $type, $component, $sort_order) {
        $relationship_id = $DB->insert_record(
            'totara_core_relationship',
            [
                'idnumber' => $idnumber ? $idnumber : $resolver_classes[0],
                'type' => $type,
                'component' => $component,
                'sort_order' => $sort_order,
                'created_at' => time(),
            ]
        );

        foreach ($resolver_classes as $resolver_class) {
            $DB->insert_record('totara_core_relationship_resolver', [
                'relationship_id' => $relationship_id,
                'class_name' => $resolver_class,
            ]);
        }
    });
}

/**
 * Updates a relationship's properties and resolvers.
 *
 * @param $relationship
 * @param array $resolvers
 */
function totara_core_update_relationship ($relationship, array $resolvers) {
    global $DB;

    $DB->update_record( 'totara_core_relationship', $relationship);
    $existing_resolvers = $DB->get_records(
        'totara_core_relationship_resolver',
        [
            'relationship_id' => $relationship->id
        ]
    );
    $resolver_classes = array_column($existing_resolvers, 'class_name');

    foreach ($resolvers as $resolver) {
        if (!in_array($resolver, $resolver_classes, true)) {
            $DB->insert_record(
                'totara_core_relationship_resolver',
                [
                    'relationship_id' => $relationship->id,
                    'class_name' => $resolver
                ]
            );
        }
    }
}

/**
 * Uninstall plugins that were removed after Totara 14 branching.
 */
function totara_core_upgrade_delete_removed_plugins() {
    global $DB;

    // NOTE: this should match \core_plugin_manager::is_deleted_standard_plugin() data.

    $deleteplugins = array(
        'media_swf',
        'tool_premigration',
        'auth_mnet',
        'block_mnet_hosts',
        'enrol_mnet',
        'portfolio_mahara',
        'mnetservice_enrol',
    );

    foreach ($deleteplugins as $deleteplugin) {
        list($plugintype, $pluginname) = explode('_', $deleteplugin, 2);
        $dir = core_component::get_plugin_directory($plugintype, $pluginname);
        if ($dir and file_exists("$dir/version.php")) {
            // This should not happen, this is not a standard distribution!
            continue;
        }
        if (!get_config($deleteplugin, 'version')) {
            // Not installed.
            continue;
        }
        uninstall_plugin($plugintype, $pluginname);
    }
}

/**
 * This function should be called if the $CFG->defaultrequestcategory value is not currently valid
 * it will first try to reset the value to misc, then to another valid category, before recreating misc.
 *
 * @return bool
 */
function totara_core_refresh_default_category() {
    global $DB, $CFG;

    if (!empty($CFG->defaultrequestcategory)) {
        $default = $DB->get_record('course_categories', ['id' => $CFG->defaultrequestcategory]);
        if (!empty($default) && !$default->issystem) {
            return false; // Default category looks good, nothing to do here.
        }
    }

    // First check if we still have a valid misc category to fall back on.
    $name = get_string('miscellaneous');
    $misc = $DB->get_record('course_categories', ['name' => $name]);
    if (!empty($misc) && !$misc->issystem) {
        // We have a valid misc category, use that and nevermind the rest.
        set_config('defaultrequestcategory', $misc->id);
        return false; // Don't want to resort this category.
    }

    // Next check if we have another valid category we can use.
    $sql = "SELECT cc.*
              FROM {course_categories} cc
             WHERE cc.issystem = 0
          ORDER BY cc.depth, cc.sortorder";
    $cats = $DB->get_records_sql($sql);
    if (!empty($cats) && $cat = array_shift($cats)) {
        // We have an existing valid category to use, lets use it.
        set_config('defaultrequestcategory', $cat->id);
        return false; // Don't want to resort this category.
    }

    // Okay neither of those worked, lets re-make misc and use that.
    $default = new stdClass();
    $default->name = empty($misc) ? $name : $name . time(); // Highly unlikely there is a system level misc category, but just in case.
    $default->descriptionformat = FORMAT_MOODLE;
    $default->description = '';
    $default->idnumber = '';
    $default->theme = '';
    $default->parent = 0; // Top level category.
    $default->depth = 1; // Top level category.
    $default->visible = 1;
    $default->visibleold = 1;
    $default->sortorder = 0; // We'll fix this later on.
    $default->timemodified = time();
    $default->issystem = 0;

    $default->id = $DB->insert_record('course_categories', $default);

    // Update path (only possible after we know the category id).
    $path = '/' . $default->id;
    $DB->set_field('course_categories', 'path', $path, array('id' => $default->id));

    // We should mark the context as dirty.
    context_coursecat::instance($default->id)->mark_dirty();

    set_config('defaultrequestcategory', $default->id);
    return true;
}

/**
 * Note: This was designed to be used in conjunction with totara_core_fix_category_sortorder
 * Fix any course sortorders that are out of bounds of the new category sortorders
 *
 * @return bool
 */
function totara_core_fix_course_sortorder($verbose = false) {
    global $DB;

    // First move any categories that are not sorted yet to the end.
    if ($unsorted = $DB->get_records('course_categories', ['sortorder' => 0])) {
        $DB->set_field('course_categories', 'sortorder', MAX_COURSES_IN_CATEGORY * MAX_COURSE_CATEGORIES, ['sortorder' => 0]);
    }

    // Then get all the top level categories.
    $topcats = $DB->get_records('course_categories', ['depth' => 1], 'sortorder, id', 'id, sortorder, parent, issystem, depth, path');

    $sortorder = 0;
    totara_core_fix_category_sortorder($topcats, $sortorder, 0, false, $verbose);


    // Make sure course sortorder is within the new category bounds.
    $sql = "SELECT DISTINCT cc.id, cc.sortorder
              FROM {course_categories} cc
              JOIN {course} c ON c.category = cc.id
             WHERE c.sortorder < cc.sortorder OR c.sortorder > cc.sortorder + " . MAX_COURSES_IN_CATEGORY;

    if ($fixcategories = $DB->get_records_sql($sql)) {
        //fix the course sortorder ranges
        foreach ($fixcategories as $cat) {
            $sql = "UPDATE {course}
                       SET sortorder = " . $DB->sql_modulo('sortorder', MAX_COURSES_IN_CATEGORY) . " + :catsort
                     WHERE category = :catid";
            $DB->execute($sql, ['catsort' => $cat->sortorder, 'catid' => $cat->id]);
        }

        // Reset the caches by event.
        cache_helper::purge_by_event('changesincourse');
    }
    unset($fixcategories);

    return true;
}

/**
 * Note: This was designed to be used in conjunction with totara_core_fix_course_sortorder
 * Move any/all top level system categories to the back of the sortorder so they don't get used as defaults.
 * And if we do make top level changes, make sure the changes flow down to the sub-categories and courses.
 *
 * @param array $categories
 * @param int   $sortorder
 * @param int   $parent
 * @param bool  $changesmade
 * @param bool  $verbose
 * @return bool
 */
function totara_core_fix_category_sortorder($categories, &$sortorder, $parent, $changesmade = false, $verbose = false) {
    global $DB;

    // First move any system categories to the back.
    $cats = [];
    $syscats = [];
    $syschanged = false;
    foreach ($categories as $cat) {
        if ($cat->issystem) {
            $syschanged = true;
            $syscats[] = $cat;
        } else {
            // If we hit a regular cat after a sys one, we're making a change.
            if ($syschanged) {
                $changesmade = true;
            }
            $cats[] = $cat;
        }
    }
    $categories = array_merge($cats, $syscats); // System categories at the back.
    unset($cats);
    unset($syscats);

    if ($changesmade) {
        // The top level ordering has changed, so lets follow through.
        foreach ($categories as $cat) {
            $sortorder = $sortorder + MAX_COURSES_IN_CATEGORY;

            // Override and update the record if necessary.
            if ($sortorder != $cat->sortorder) {
                $cat->sortorder = $sortorder;
                $DB->update_record('course_categories', $cat, true);

                $context = context_coursecat::instance($cat->id)->reset_paths(false);
            }

            // Update any sub-categories as well.
            $subcats = $DB->get_records('course_categories', ['parent' => $cat->id], 'sortorder, id');
            totara_core_fix_category_sortorder($subcats, $sortorder, $cat->id, $changesmade);
        }

        // Reset the caches by event.
        cache_helper::purge_by_event('changesincoursecat');

        // When we're all done, rebuild the context paths.
        if ($parent === 0) {
            $start = time();
            if ($verbose) {
                // Implement our own smaller version of verbose rather than using the build all paths verbose,
                // As we aren't doing a forced run it shouldn't take any where near as long, so we just give a small warning.
                mtrace(str_pad(userdate($start, '%H:%M:%S'), 10) . "Updating context paths, this may take a minute...\n");
            }
            context_helper::build_all_paths(false, false); // Not forced, not verbose.
            $duration = time()  - $start;
            $seconds = $duration % 60;
            $minutes = (int)floor($duration / 60);
            if ($verbose) {
                mtrace(str_pad(userdate(time(), '%H:%M:%S'), 10) . "... done, duration {$minutes}:{$seconds}\n");
            }
        }
    }
    unset($categories);

    return true;
}

/**
 * Add type values to the existing issuers in the oauth2_issuers table.
 * Can just determine this from the image field.
 *
 * @since Totara 15.0
 */
function upgrade_oauth2_issuers_add_types() {
    global $DB;

    $issuer_types = [
        'google',
        'microsoft',
        'facebook',
        'nextcloud',
    ];

    foreach ($issuer_types as $issuer_type) {
        $issuer_like = $DB->sql_like('image', ':issuer_type');
        $DB->execute("
            UPDATE {oauth2_issuer}
            SET type = '{$issuer_type}'
            WHERE {$issuer_like}
        ", ['issuer_type' => "%{$issuer_type}%"]);
    }
}
