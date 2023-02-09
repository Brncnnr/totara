<?php

require_once($CFG->dirroot.'/user/filters/lib.php');

if (!defined('MAX_BULK_USERS')) {
    define('MAX_BULK_USERS', 2000);
}

function add_selection_all($ufiltering) {
    global $SESSION, $DB, $CFG;

    list($sqlwhere, $params) = $ufiltering->get_sql_filter("id<>:exguest AND deleted <> 1", array('exguest'=>$CFG->siteguest));

    $rs = $DB->get_recordset_select('user', $sqlwhere, $params, 'fullname', 'id,'.$DB->sql_fullname().' AS fullname');
    foreach ($rs as $user) {
        if (!isset($SESSION->bulk_users[$user->id])) {
            $SESSION->bulk_users[$user->id] = $user->id;
        }
    }
    $rs->close();
}

function get_selection_data($ufiltering) {
    global $SESSION, $DB, $CFG;

    // get the SQL filter
    list($sqlwhere, $params) = $ufiltering->get_sql_filter("id<>:exguest AND deleted <> 1", array('exguest'=>$CFG->siteguest));

    $total  = $DB->count_records_select('user', "id<>:exguest AND deleted <> 1", array('exguest'=>$CFG->siteguest));
    $acount = $DB->count_records_select('user', $sqlwhere, $params);
    $scount = count($SESSION->bulk_users);

    $userlist = array('acount'=>$acount, 'scount'=>$scount, 'ausers'=>false, 'susers'=>false, 'total'=>$total);
    $allusernames = get_all_user_name_fields(true);
    $ausers = $DB->get_records_select('user', $sqlwhere, $params, '', 'id,'.$allusernames, 0, MAX_BULK_USERS);

    if ($scount) {
        if ($scount < MAX_BULK_USERS) {
            $bulkusers = $SESSION->bulk_users;
        } else {
            $bulkusers = array_slice($SESSION->bulk_users, 0, MAX_BULK_USERS, true);
        }
        list($in, $inparams) = $DB->get_in_or_equal($bulkusers);
        $susers = $DB->get_records_select('user', "id $in", $inparams, '', 'id,'.$allusernames);
    }

    // Loop through ausers list and sort it by fullname.
    if (!empty($ausers)) {
        $userlist['ausers'] = user_sorted_by_fullname($ausers);
    }

    // Loop through susers list and sort it by fullname.
    if (!empty($susers)) {
        $userlist['susers'] = user_sorted_by_fullname($susers);
    }

    return $userlist;
}

/**
 * Given an array of user objects, returns an array of users sorted by fullname.
 *
 * @param $users Array of users. Needs to have user ID and all usernames to get the fullname
 * @return array Array in the form of user ID as key and fullname as value, sorted by fullname.
 */
function user_sorted_by_fullname(array $users) {
    $sorted_users = array();

    // Get the user's fullname.
    foreach ($users as $user) {
        $sorted_users[$user->id] = fullname($user);
    }

    // Sort users by fullname keeping the user key.
    asort($sorted_users);

    return $sorted_users;
}
