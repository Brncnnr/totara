<?php

use totara_competency\entity\competency_achievement;
use totara_competency\entity\pathway_achievement;
use totara_competency\pathway;

/**
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
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package totara_competency
 */

define('CLI_SCRIPT', 1);

require __DIR__ . '/../../server/config.php';

/** @var core_config $CFG */

require_once($CFG->libdir . '/clilib.php');

global $DB;

$USER = get_admin();

$help =
"Competency Criteria test achievement data generator.
Records are created for all user indicating that they achieved all
For 'manual', the minimum proficiency value is assigned by admin to the user in the 'manager' role
For 'criteria group', the associated scalevalue is achieved'
TODO - randomize achievements 

Options:
-u, --user=STRING        List of comma separated user ids. Required if --numusers is not specified
-n, --numusers=NUMBER    Number of users to create. Required if --user is not specified
-c, --competency=STRING  List of comma separated competency ids. Default = all competencies with pathways
-h, --help               Print out this help
";

list($options, $unrecognized) = cli_get_params(
    array(
        'user'       => '',
        'numusers'   => 0,
        'competency' => '',
        'help'       => false
    ),
    array(
        'u' => 'user',
        'n' => 'numusers',
        'c' => 'competency',
        'h' => 'help'
    )
);

if ($options['help']) {
    echo $help;
    die;
}

if (empty($options['user']) && empty($options['numusers'])) {
    printf("--user OR --numusers is required\n\n");
    echo $help;
    die;
}

$generator = \core\testing\generator::instance();

$user_ids = explode(',', $options['user'] ?? []);
$num_users = $options['numusers'];
$competency_ids = explode(',', $options['competency'] ?? []);

printf("Creating test achievement data for competency criteria:\n");
if (!empty($options['user'])) {
    printf("\tuser ids: [%s]\n", $options['user']);
} else {
    printf("\t%d new users\n", $num_users);
}
printf("\tcompetency ids: [%s]\n", $options['competency'] ?? 'all');

// TODO: Create proper data generators  - for now simply using brute force creation and hardcoded types and values

// Users
if (!empty($num_users)) {
    $user_ids = create_users($num_users);
}

create_item_records($user_ids, $competency_ids);
create_manual_ratings($user_ids, $competency_ids);
create_pathway_achievements($user_ids, $competency_ids);
create_competency_achievements($user_ids, $competency_ids);

function create_users($num_users): array {
    global $DB, $generator;

    printf("\n");

    $users = [];
    for ($i = 1; $i <= $num_users; $i++) {
        $record = [
            'firstname' => "Test$i",
            'lastname' => 'User',
            'username' => "testuser$i",
        ];

        printf("Creating user: [%s] - id:", $record['username']);
        $user = $DB->get_record('user', $record);
        if (!$user) {
            $user = $generator->create_user($record);
        }
        printf("%d\n", $user->id);
        $users[] = $user->id;
    }

    return $users;
}


function create_item_records($user_ids, $competency_ids) {
    global $DB;

    printf("\nCreating item records:\n");

    if (!empty($competency_ids)) {
        [$comp_id_sql, $comp_id_params] = $DB->get_in_or_equal($competency_ids, SQL_PARAMS_NAMED);
        $comp_id_sql = ' WHERE comp.id ' . $comp_id_sql;
    } else {
        $comp_id_sql = '';
        $comp_id_params = [];
    }

    $now = time();

    $item_sql =
          "FROM {totara_criteria_item} tci
           JOIN {pathway_criteria_group_criterion} pcgc
             ON pcgc.criterion_id = tci.criterion_id
           JOIN {totara_competency_pathway} tcp
             ON tcp.path_instance_id = pcgc.criteria_group_id
            AND tcp.path_type = :pathtype
            AND tcp.status = :pathstatus
           JOIN {comp} comp
             ON comp.id = tcp.competency_id
           {$comp_id_sql}";
    $item_params = array_merge(
        [
            'pathtype' => 'criteria_group',
            'pathstatus' => pathway::PATHWAY_STATUS_ACTIVE,
        ],
        $comp_id_params
    );

    $exist_sql =
        "SELECT tcir.id
           FROM {totara_criteria_item_record} tcir
           WHERE user_id = :userid
             AND criterion_met = :criterionmet
             AND criterion_item_id IN (
                 SELECT tci.id
                 {$item_sql})";
    $exist_params = array_merge(['criterionmet' => 1], $item_params);

    foreach ($user_ids as $user_id) {
        printf("\tUser: %d\n", $user_id);
        $user_params = array_merge($exist_params, ['userid' => $user_id]);

        // For now not creating any new item_records if any exists
        $rows = $DB->get_records_sql($exist_sql, $user_params);
        if (empty($rows)) {
            $insert_sql =
                "INSERT INTO {totara_criteria_item_record}
                (user_id, criterion_item_id, criterion_met, timeevaluated)
                SELECT :userid, tci.id, :criterionmet, :timeevaluated
                {$item_sql}";
            $user_params['timeevaluated'] = $now;

            $DB->execute($insert_sql, $user_params);
        }
    }
}

function create_manual_ratings($user_ids, $competency_ids) {
    global $DB;

    printf("\nCreating manual ratings:\n");

    if (!empty($competency_ids)) {
        [$comp_id_sql, $comp_id_params] = $DB->get_in_or_equal($competency_ids, SQL_PARAMS_NAMED);
        $comp_id_sql = ' WHERE comp.id ' . $comp_id_sql;
    } else {
        $comp_id_sql = '';
        $comp_id_params = [];
    }

    $now = time();

    // TODO - better scale value selection - for now using min proficient value
    //        Multiple runs will result in multiple ratings
    $insert_sql =
        "INSERT INTO {pathway_manual_rating}
        (user_id, competency_id, scale_value_id, date_assigned, assigned_by, assigned_by_role)
        SELECT :userid, scalecomp.id, scale.minproficiencyid, :dateassigned, :assignedby, :assignedbyrole
          FROM {comp} scalecomp
          JOIN {comp_scale_assignments} csa
            ON csa.frameworkid = scalecomp.frameworkid
          JOIN {comp_scale} scale
            ON scale.id = csa.scaleid
         WHERE scalecomp.id IN (
                SELECT comp.id
                  FROM {comp} comp
                  JOIN {totara_competency_pathway} tcp      
                    ON tcp.competency_id = comp.id
                   AND tcp.path_type = :pathtype
                   AND tcp.status = :pathstatus
                {$comp_id_sql})";
    $params = array_merge(
        [
            'dateassigned' => $now,
            'assignedby' => 1,
            'assignedbyrole' => 'manager',
            'pathtype' => 'manual',
            'pathstatus' => pathway::PATHWAY_STATUS_ACTIVE,
        ],
        $comp_id_params
    );

    foreach ($user_ids as $user_id) {
        printf("\tUser: %d\n", $user_id);
        $DB->execute($insert_sql, array_merge($params, ['userid' => (int)$user_id]));
    }
}

function create_pathway_achievements($user_ids, $competency_ids) {
    global $DB;

    printf("\nCreating pathway achievements:\n");

    if (!empty($competency_ids)) {
        [$comp_id_sql, $comp_id_params] = $DB->get_in_or_equal($competency_ids, SQL_PARAMS_NAMED);
        $comp_id_sql = 'comp.id ' . $comp_id_sql;
    } else {
        $comp_id_sql = '';
        $comp_id_params = [];
    }

    $now = time();

    // TODO - better scale value selection - for now using min proficient value
    //        Other pathway types - just doing manual and criteria_group at the moment
    //        Related info ???
    $manual_sql =
        "INSERT INTO {totara_competency_pathway_achievement}
        (pathway_id, user_id, scale_value_id, date_achieved, last_aggregated, status)
        SELECT tcp.id, :userid, scale.minproficiencyid, :dateachieved, :lastaggregated, :currentstatus
          FROM {totara_competency_pathway} tcp
          JOIN {comp} comp
            ON comp.id = tcp.competency_id
          JOIN {comp_scale_assignments} csa
            ON csa.frameworkid = comp.frameworkid
          JOIN {comp_scale} scale
            ON scale.id = csa.scaleid
         WHERE tcp.path_type = :pathtype
           AND tcp.status = :pathstatus
           AND {$comp_id_sql}
           AND tcp.id NOT IN (     
                SELECT pathway_id
                  FROM {totara_competency_pathway_achievement} tcpa      
                 WHERE user_id = :achievementuser
                   AND tcpa.status = :currentstatus2)";
    $params = array_merge(
        [
            'dateachieved' => $now,
            'lastaggregated' => $now,
            'currentstatus' => pathway_achievement::STATUS_CURRENT,
            'pathstatus' => pathway::PATHWAY_STATUS_ACTIVE,
            'currentstatus2' => pathway_achievement::STATUS_CURRENT,
        ],
        $comp_id_params
    );

    $cg_sql =
        "INSERT INTO {totara_competency_pathway_achievement}
        (pathway_id, user_id, scale_value_id, date_achieved, last_aggregated, status)
        SELECT tcp.id, :userid, pcg.scale_value_id, :dateachieved, :lastaggregated, :currentstatus
          FROM {totara_competency_pathway} tcp
          JOIN {comp} comp
            ON comp.id = tcp.competency_id
          JOIN tst_pathway_criteria_group pcg
            ON pcg.id = tcp.path_instance_id
         WHERE tcp.path_type = :pathtype
           AND tcp.status = :pathstatus
           AND {$comp_id_sql}
           AND tcp.id NOT IN (     
                SELECT pathway_id
                  FROM {totara_competency_pathway_achievement} tcpa      
                 WHERE user_id = :achievementuser
                   AND tcpa.status = :currentstatus2)";

    foreach ($user_ids as $user_id) {
        printf("\tUser: %d\n", $user_id);
        $DB->execute($manual_sql,
            array_merge($params, ['pathtype' => 'manual', 'userid' => (int)$user_id, 'achievementuser' => (int)$user_id])
        );
        $DB->execute($cg_sql,
            array_merge($params,
                ['pathtype' => 'criteria_group', 'userid' => (int)$user_id, 'achievementuser' => (int)$user_id]
            )
        );
    }
}

function create_competency_achievements($user_ids, $competency_ids) {
    global $DB;

    printf("\nCreating competency achievements:\n");

    if (!empty($competency_ids)) {
        [$comp_id_sql, $comp_id_params] = $DB->get_in_or_equal($competency_ids, SQL_PARAMS_NAMED);
        $comp_id_sql = 'tcp.competency_id ' . $comp_id_sql;
    } else {
        $comp_id_sql = '';
        $comp_id_params = [];
    }

    $now = time();

    // For now relying on the pathway achievements being created first

    // TODO - assignment - default to 1 at the moment
    //        proficiency - marked as proficient for all manual but not checked for criteria_group at the moment
    $sql =
        "INSERT INTO {totara_competency_achievement}
        (competency_id, user_id, assignment_id, scale_value_id, proficient, status, time_created, time_status)
        SELECT minscale.competency_id, minscale.user_id, :assignment_id, minscale.scale_value_id, :proficient, :status, :timecreated, :timestatus
          FROM (
               SELECT tcp.competency_id, tcpa.user_id, MIN(tcpa.scale_value_id) as scale_value_id
		         FROM {totara_competency_pathway_achievement} tcpa
		         JOIN {totara_competency_pathway} tcp
                   ON tcp.id = tcpa.pathway_id
   	            WHERE tcpa.status = :tcpa_status
                  AND tcpa.user_id = :tcpa_userid
             GROUP BY tcp.competency_id, tcpa.user_id) minscale      
     LEFT JOIN {totara_competency_achievement} tca
            ON tca.competency_id = minscale.competency_id
           AND tca.status = :tca_status
           AND tca.user_id = :tca_userid     
         WHERE tca.id IS NULL";
    $params = array_merge(
        [
            'assignment_id' => 1,
            'proficient' => 0,
            'status' => competency_achievement::ACTIVE_ASSIGNMENT,
            'timecreated' => $now,
            'timestatus' => $now,
            'tca_status' => competency_achievement::ACTIVE_ASSIGNMENT,
            'tcpa_status' => pathway_achievement::STATUS_CURRENT,
        ],
        $comp_id_params
    );

    foreach ($user_ids as $user_id) {
        printf("\tUser: %d\n", $user_id);
        $DB->execute($sql, array_merge($params, ['tcpa_userid' => (int)$user_id, 'tca_userid' => (int)$user_id]));
    }

    // TODO: update statement to set proficient
}

