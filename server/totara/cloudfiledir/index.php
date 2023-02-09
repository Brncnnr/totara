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


require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/tablelib.php');

admin_externalpage_setup('totara_cloudfiledir_stores');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'totara_cloudfiledir'));

if (!file_exists(__DIR__ . '/../../../libraries/optional/autoload.php')) {
    // NOTE: do not translate this warning for admins,
    //       they need to understand English basics to administer servers.
    $optionalmissing = <<<OET
Optional libraries are not installed in /libraries/ directory, cloud storage of content files is not available. 
OET;
    echo $OUTPUT->notification(markdown_to_html($optionalmissing), \core\output\notification::NOTIFY_WARNING);
}

$table = new flexible_table('totara_cloudfiledir_stores');

$columns = [
    'idnumber' => get_string('idnumber'),
    'description' => get_string('description'),
    'provider' => get_string('provider', 'totara_cloudfiledir'),
    'bucket' => get_string('bucket', 'totara_cloudfiledir'),
    'maxinstantuploadsize' => get_string('maxinstantuploadsize', 'totara_cloudfiledir'),
    'add' => get_string('add'),
    'delete' => get_string('delete'),
    'restore' => get_string('restore'),
    'active' => get_string('active'),
];

$table->define_columns(array_keys($columns));
$table->define_headers(array_values($columns));
$table->define_baseurl($PAGE->url);
$table->set_attribute('class', 'admintable generaltable');
$table->setup();

$stryes = get_string('yes');
$strno = get_string('no');

$stores = totara_cloudfiledir\local\store::get_stores();
foreach ($stores as $store) {
    $maxinstantuploadsize = $store->get_maxinstantuploadsize();
    $maxinstantuploadsize = ($maxinstantuploadsize < 0) ? get_string('none') : display_size($maxinstantuploadsize);
    $row = [
        $store->get_idnumber(),
        $store->get_description(),
        $store->get_provider(),
        $store->get_bucket(),
        $maxinstantuploadsize,
        $store->add_enabled() ? $stryes : $strno,
        $store->delete_enabled() ? $stryes : $strno,
        $store->restore_enabled() ? $stryes : $strno,
        $store->is_active() ? $stryes : $strno,
    ];
    $table->add_data($row);
}
$table->print_html();

echo $OUTPUT->footer();
