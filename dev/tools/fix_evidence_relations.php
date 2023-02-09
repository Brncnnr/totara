<?php
define('CLI_SCRIPT', true);
define('NO_OUTPUT_BUFFERING', true);

// No logging.
define('LOG_MANAGER_CLASS', '\core\log\dummy_manager');

require(__DIR__ . '/../../server/config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(
    [
        'count' => false,
        'list'  => false,
        'fix'   => false,
        'help'  => false,
        'check' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if (!$options['count'] and !$options['fix'] and !$options['list'] and !$options['check']) {
    $help =
"Find, and potentially fix learning plan evidence relationships that were incorrectly migrated during upgrade.
NOTE: Before you run this script, please ensure that you have imported the original learning plan evidence data from
your pre-upgrade backup. Use --check to ensure this has been done correctly, and/or print further instructions.

Please backup your database first!
Please contact Totara Support before using this script if you have any questions or concerns.

Options:
-h, --help            Print out this help
--count               Count all incorrectly migrated evidence
--list                List all incorrectly migrated evidence
--check               Checks that the t12 data has been imported
--fix                 Fix all incorrectly migrated evidence
";

    echo $help;
    exit(0);
}

function check_t12_backup_exists() {
    global $DB;

    if (!($DB->get_manager()->table_exists('t12_dp_plan_evidence')
        and $DB->get_manager()->table_exists('t12_dp_plan_evidence_relation'))) {
        $help =
"Backup t12 evidence tables do not exist. Please export the necessary tables from Totara 12 backup, rename, and import
them into your Totara 13 database before running the script, using the following steps:

For MySQL/MariaDB:
-----
mysqldump ... --skip-opt --column-statistics=0 t12_database_name mdl_dp_plan_evidence mdl_dp_plan_evidence_relation > t12_database_name_backup.sql

On Linux:
sed -i 's/mdl_dp/mdl_t12_dp/g' t12_database_name_backup.sql
On MacOS:
sed -i '' 's/mdl_dp/mdl_t12_dp/' t12_database_name_backup.sql

mysql ... t13_database_name < t12_database_name_backup.sql
-----

For PostgreSQL:
-----
pg_dump ... -d t12_database_name --inserts -t mdl_dp_plan_evidence -t mdl_dp_plan_evidence_relation >/tmp/t12_database_name_backup.sql

On Linux:
sed -i 's/mdl_dp/mdl_t12_dp/g' t12_database_name_backup.sql
On MacOS:
sed -i '' 's/mdl_dp/mdl_t12_dp/' t12_database_name_backup.sql

psql ... -d t13_database_name < t12_database_name_backup.sql
-----

";
        echo $help . PHP_EOL;
        exit;
    }
}

$sql =
    "SELECT  t12_rel.id,
			u.username, 
            dp.name AS plan, 
            t12_rel.component, 
            t12_rel.itemid, 
			t12_rel.evidenceid AS t12_evidenceid,
            t12_ev.name AS t12_evidence,
            t12_ev.userid AS t12_ev_userid,
            t12_ev.usermodified AS t12_ev_usermodified,
            t12_ev.timecreated AS t12_ev_timecreated,
			t13_rel.evidenceid AS t13_evidenceid,
            t13_ev.name AS t13_evidence
       FROM {t12_dp_plan_evidence_relation} t12_rel
       JOIN {t12_dp_plan_evidence} t12_ev
         ON t12_ev.id = t12_rel.evidenceid

       JOIN {dp_plan} AS dp 
         ON dp.id = t12_rel.planid
       JOIN {user} AS u 
         ON u.id = dp.userid
             
       JOIN {dp_plan_evidence_relation} AS t13_rel
         ON t13_rel.id = t12_rel.id
       JOIN {totara_evidence_item} t13_ev
         ON t13_ev.id = t13_rel.evidenceid
      WHERE t12_ev.userid != t13_ev.user_id 
         OR t12_ev.name != t13_ev.name
         OR t13_ev.user_id != dp.userid";

if ($options['count']) {
    global $DB;

    check_t12_backup_exists();

    $count_sql =
        "SELECT COUNT(id)
           FROM ($sql) AS q";
    echo $DB->count_records_sql($count_sql) . ' evidence records found which were not correctly migrated.' . PHP_EOL;
}

if ($options['list']) {
    check_t12_backup_exists();

    $relations = $DB->get_recordset_sql($sql);
    $relations = $relations->to_array();
    if (count($relations)) {
        $first = (array)reset($relations);
        $keys = array_keys ((array)$first);
        echo implode(', ', $keys) . PHP_EOL;
    } else {
        echo "No evidence records were found to be incorrectly migrated." . PHP_EOL;
    }

    foreach ($relations as $relation) {
        echo implode(', ', (array)$relation) . PHP_EOL;
    }
}

if ($options['fix']) {
    check_t12_backup_exists();

    echo 'Fixing ...' . PHP_EOL;
    $sql .= " ORDER BY t12_evidenceid";

    $update_sql = "
        UPDATE {dp_plan_evidence_relation} 
        SET evidenceid = :newitemid
        WHERE id = :id";

    $cur_t12_evidenceid = 0;
    $cur_t13_evidenceid = 0;
    $relations = $DB->get_recordset_sql($sql);

    $errors = ['notfound' => [], 'multi' => []];

    // t12.dp_evidence_type.usermodified and timemodified are not nullable - so will be copied over and the same
    // t12.dp_plan_evidence.usermodified and timemodified are nullable - so will try with it first.
    // If we can't find it, will try without it.
    // If we find more than one - we simply report it as we can't programmatically determine the right one
    $correct_evidence_sql =
        "SELECT t13_ev.id
           FROM {totara_evidence_item} t13_ev
           WHERE t13_ev.user_id = :userid
             AND t13_ev.name = :evidencename";

    $with_ev_modified_sql = $correct_evidence_sql .
        " AND t13_ev.created_by = :ev_usercreated
          AND t13_ev.created_at = :ev_timecreated";

    foreach ($relations as $relation) {
        echo '     ' . implode(', ', (array)$relation) . PHP_EOL;

        if ($relation->t12_evidenceid != $cur_t12_evidenceid) {
            // Next group
            // First try to find with the type and evidence user and time
            $params = [
                'userid' => $relation->t12_ev_userid,
                'evidencename' => $relation->t12_evidence,
                'ev_usercreated' => $relation->t12_ev_usermodified,
                'ev_timecreated' => $relation->t12_ev_timecreated,
            ];

            $cur_t12_evidenceid = $relation->t12_evidenceid;

            try {
                $cur_t13_evidenceid = $DB->get_field_sql($with_ev_modified_sql, $params, MUST_EXIST);
            } catch (dml_missing_record_exception $ex) {
                $cur_t13_evidenceid = 'notfound';
            } catch (dml_multiple_records_exception $ex) {
                $cur_t13_evidenceid = 'multi';
            }

            if ($cur_t13_evidenceid == 'notfound') {
                // Search without the evidence modified user and time
                unset($params['ev_usercreated']);
                unset($params['ev_timecreated']);

                $rows = $DB->get_records_sql($correct_evidence_sql, $params);
                if (count($rows) > 0) {
                    $cur_t13_evidenceid = 'multi';
                } else if (!empty($rows)) {
                    $row = reset($rows);
                    $cur_t13_evidenceid = $row->id;
                }
            }
        }

        if (is_numeric($cur_t13_evidenceid)) {
            $update_params = [
                'newitemid' => $cur_t13_evidenceid,
                'id' => $relation->id,
            ];
            $DB->execute($update_sql, $update_params);
        } else {
            $errors[$cur_t13_evidenceid][] = $relation;
        }
    }

    $relations->close();

    if (!empty($errors['notfound']) || !empty($errors['multi'])) {
        echo PHP_EOL . 'The following relations could not be fixed automatically:' . PHP_EOL;

        if (!empty($errors['notfound'])) {
            echo '    No equivalent new migrated evidence item:' . PHP_EOL;

            $keys = array_keys ((array)$errors['notfound'][0]);
            echo implode(', ', $keys) . PHP_EOL;

            foreach ($errors['notfound'] as $relation) {
                echo '        ' . implode(', ', (array)$relation) . PHP_EOL;
            }
        }

        if (!empty($errors['multi'])) {
            echo '    Multiple new evidence items:' . PHP_EOL;

            $keys = array_keys ((array)$errors['multi'][0]);
            echo implode(', ', $keys) . PHP_EOL;

            foreach ($errors['multi'] as $relation) {
                echo '        ' . implode(', ', (array)$relation) . PHP_EOL;
            }
        }
    }
}

if ($options['check']) {
    check_t12_backup_exists();
    echo 'All good to go.' . PHP_EOL;
}