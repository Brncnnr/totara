<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
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
 * @package tool_langimport
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('localecheck');

echo $OUTPUT->header();

// NOTE: this page is not necessary to be localised

echo $OUTPUT->heading(get_string('localecheck', 'tool_langimport'));

$info = <<<EOT
Totara language packs include name of operating system locale that is used for formatting of dates
using PHP function strftime(). If a server locale name is different to lang pack locale name,
it might lead to errors when showing dates in Totara.

Use this tool to check if your Totara lang packs locale names are compatible with your server.
EOT;

if ($CFG->ostype === 'WINDOWS') {
    $info .= <<<EOT


You can fix any errors by overriding lang pack locally. Go to "Language customisation",
select component "langconfig.php" and alter the value of "localewin".
EOT;
} else {
    $info .= <<<EOT


You can fix any errors by overriding lang pack locally. Go to "Language customisation",
select component "langconfig.php" and alter the value of "locale" string
using the equivalent locale name as it appears in “Available server locales” list.
EOT;
}

echo $OUTPUT->notification(markdown_to_html($info), 'info');

echo $OUTPUT->heading('Lang pack locales test results', 3);

$stringmanager = get_string_manager();

$langs = $stringmanager->get_list_of_translations(false);
ksort($langs);
$en = $langs['en'];
unset($langs['en']);
$langs = ['en' => $en] + $langs;

$table = new html_table();
$table->head  = ['Lang code', 'Name', 'Locale', 'Locale test', 'Sample date'];
$table->attributes['class'] = 'admintable generaltable';
$table->id = 'langlocales';
$table->data  = [];

$SESSION->lang = 'en';
if ($CFG->ostype === 'WINDOWS') {
    $enlocale = get_string('localewin', 'core_langconfig');
} else {
    $enlocale = get_string('locale', 'core_langconfig');
}

$time = 1592891100;
$timeformat = '%A, %d %B %Y, %H:%M';

foreach ($langs as $lang => $unused) {
    $SESSION->lang = $lang;
    $langname = get_string('thislanguage', 'core_langconfig');
    if ($CFG->ostype === 'WINDOWS') {
        $locale = get_string('localewin', 'core_langconfig');
    } else {
        $locale = get_string('locale', 'core_langconfig');
    }

    // Reset to known values.
    setlocale(LC_ALL, $enlocale);
    moodle_setlocale($enlocale);

    $result = setlocale(LC_TIME, $locale);
    if ($result === false) {
        // Bloody fallback hack from moodle_setlocale();
        if (stripos($locale, '.UTF-8') !== false) {
            $newlocale = str_ireplace('.UTF-8', '.UTF8', $locale);
            $result = setlocale(LC_TIME, $newlocale);
        } else if (stripos($locale, '.UTF8') !== false) {
            $newlocale = str_ireplace('.UTF8', '.UTF-8', $locale);
            $result = setlocale(LC_TIME, $newlocale);
        }
        if ($result !== false) {
            $locale = $newlocale;
        }
    }

    if ($result === false) {
        $localetest = '<span style="color:red">Error</span>';
    } else {
        $localetest = 'OK';
    }

    $sampledate = userdate($time, $timeformat, 'UTC');

    $row = [$lang, $langname, $locale, $localetest, $sampledate];
    $table->data[] = $row;
    unset($SESSION->lang);
}

echo html_writer::table($table);

if ($CFG->ostype !== 'WINDOWS') {
    echo $OUTPUT->heading('Available server locales', 3);
    exec('locale -a', $locales);
    foreach ($locales as $k => $locale) {
        if (stripos($locale, 'UTF') === false) {
            unset($locales[$k]);
        }
    }
    asort($locales);
    echo '<p>' . implode(', ', $locales) . '</p>';
}

echo $OUTPUT->footer();
