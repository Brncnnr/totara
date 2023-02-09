<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package totara_competency
 */

define('CLI_SCRIPT', 1);

require __DIR__ . '/../../server/config.php';

/** @var core_config $CFG */

require_once($CFG->dirroot . '/totara/plan/lib.php');

global $DB;

$USER = get_admin();

$generator = \core\testing\generator::instance();
$hierarchy_generator = \totara_hierarchy\testing\generator::instance();
$plan_generator = \totara_plan\testing\generator::instance();

/****************************
 * START PARAMETERS
 ***************************/

// How many frameworks will be created in each hierarchy type
$number_of_framweworks = 4;
// Number of items in each framework per hierarchy level
$number_of_items_per_level = 5;
// Maximal depth of items in the hierarchy
$max_depth_of_items = 3;
// Number of types per framework
$number_of_types = 3;

/****************************
 * END PARAMETERS
 ***************************/

$hierarchies = [
    'pos' => 'position',
    'org' => 'organisation',
    'comp' => 'competency',
    'goal' => 'goal'
];

$customfield_values_menu = ['1234', '2345', '3456', '4567'];
$customfield_values_multiselect = ['a', 'b', 'c', 'd'];

$customfield_types = [
    'menu' => function () use ($customfield_values_menu) {
        return array_rand($customfield_values_menu);
    },
    'text' => function () {
        return random_string(20);
    },
    'datetime' => function () {
        $random_time = mt_rand(
            mktime(0, 0, 0, 1, 1, date('Y') - 1),
            mktime(23, 59, 59, 12, 31, date('Y') + 5)
        );
        return $random_time;
    },
    'checkbox' => function () {
        $vls = [0, 1];
        return $vls[array_rand($vls)];
    },
    'location' => function () {
        return [
            'address' => mt_rand(1, 100).' '.random_string(15).'Street, '.random_string(15)
        ];
    },
    'multiselect' => function () use ($customfield_values_multiselect){
        return [1, 1, 0, 0];
    },
    'textarea' => function () {
        return [
            'text' => random_string(60),
            'format' => 1
        ];
    }
];

//$transaction = $DB->start_delegated_transaction();

foreach ($hierarchies as $hierarchy_prefix => $hierarchy) {
    mtrace("Truncating tables for hierarchy type '$hierarchy'");
    $DB->execute('TRUNCATE {'.$hierarchy_prefix.'_type_info_data_param}');
    $DB->execute('TRUNCATE {'.$hierarchy_prefix.'_type_info_data}');
    $DB->execute('TRUNCATE {'.$hierarchy_prefix.'_type_info_field}');
    $DB->execute('TRUNCATE {'.$hierarchy_prefix.'_type}');
    $DB->execute('TRUNCATE {'.$hierarchy_prefix.'}');
    $DB->execute('TRUNCATE {'.$hierarchy_prefix.'_framework}');

    for ($i = 0; $i < $number_of_types; $i++) {
        $typeidnumber = $hierarchy . 'type'.$i;
        $hierarchy_type_id = $hierarchy_generator->create_hierarchy_type(
            $hierarchy, [
                'fullname' => 'Hierarchy type '.$hierarchy.' '.$i,
                'idnumber' => $typeidnumber
            ]
        );

        foreach ($customfield_types as $customfield_type => $value) {
            $value = $value() ?? '';

            // Special cases for menus
            if ($customfield_type == 'menu') {
                $value = $customfield_values_menu[$value];
            }
            if ($customfield_type == 'multiselect') {
                $value = '';
            }
            $customfield_data = [
                'hierarchy' => $hierarchy,
                'typeidnumber' => $typeidnumber,
                'value' => $value,
                'defaultdata' => ''
            ];
            $methodname = "create_hierarchy_type_$customfield_type";
            if (!method_exists($hierarchy_generator, $methodname)) {
                throw new Exception("Method to create customfield of type $customfield_type does not exist!");
            }
            $hierarchy_generator->$methodname($customfield_data);
        }
    }

    for ($i = 0; $i < $number_of_framweworks; $i++) {
        $framework = $hierarchy_generator->create_framework($hierarchy);
        mtrace("Created framework {$framework->id} for hierarchy type '$hierarchy'");
        mtrace("Creating items for hierarchy type '$hierarchy' and framework {$framework->id}");
        create_hierarchy_items($hierarchy, $framework->id, $hierarchy_generator, $number_of_items_per_level, 0, 0, $max_depth_of_items);
    }
}

//$transaction->allow_commit(new Exception('fail deliberately'));

mtrace("Finished creating test data");

/**
 * Recursively creates hierarchy items in a number of levels
 *
 * @param string $hierarchy_type
 * @param int $frameworkid
 * @param \totara_hierarchy\testing\generator $hierarchy_generator
 * @param int $amount
 * @param int $parentid
 * @param int $level
 * @param int $maxdepth
 * @param int $numberofcompetencies
 * @return int
 */
function create_hierarchy_items(
    string $hierarchy_type,
    int $frameworkid,
    \totara_hierarchy\testing\generator $hierarchy_generator,
    int $amount,
    int $parentid = 0,
    int $level = 0,
    int $maxdepth = 0,
    int $numberofcompetencies = 0
): int {
    global $customfield_types;
    global $number_of_types;

    if ($level >= $maxdepth) {
        return $numberofcompetencies;
    }

    $totalamount = 0;
    for ($i = $maxdepth; $i > 0; $i--) {
        $totalamount += $amount ** $i;
    }

    $level++;

    for ($i = 0; $i < $amount; $i++) {
        $data = ['frameworkid' => $frameworkid];
        if ($parentid > 0) {
            $data['parentid'] = $parentid;
        }
        $hierarchy_item = $hierarchy_generator->create_hierarchy($frameworkid, $hierarchy_type, $data);

        $typeidnumber = $hierarchy_type.'type'.mt_rand(0, $number_of_types - 1);
        foreach ($customfield_types as $customfield_type => $value) {
            $value = $value();
            if ($value !== null) {
                $customfield_data = [
                    'hierarchy' => $hierarchy_type,
                    'typeidnumber' => $typeidnumber,
                    'idnumber' => $hierarchy_item->idnumber,
                    'field' => $customfield_type,
                    'value' => $value
                ];
                $hierarchy_generator->create_hierarchy_type_assign($customfield_data);
            }
        }

        $numberofcompetencies++;
        $numberofcompetencies = create_hierarchy_items(
            $hierarchy_type,
            $frameworkid,
            $hierarchy_generator,
            $amount,
            (int)$hierarchy_item->id,
            $level,
            $maxdepth,
            $numberofcompetencies
        );
    }

    $percentage = round(($numberofcompetencies / $totalamount) * 100, 0);
    mtrace("Creating hierarchy items for hierarchy type '$hierarchy_type'' ... $percentage % ($numberofcompetencies / $totalamount) completed");

    return $numberofcompetencies;
}
