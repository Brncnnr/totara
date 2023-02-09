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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package totara_mobile
 */

/*
 * This page takes a JSON language strings from the mobile app project and
 * converts them to Totara language strings.
 *
 * It is provided as a convenience for developers extending the mobile app.
 *
 * Developers can set $CFG->mobile_convert_lang_strings = 1 to enable use.
 */

require('../../../config.php');

// Only allow intentional development use.
if (empty($CFG->mobile_convert_lang_strings)) {
    die();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$PAGE->set_context(\context_system::instance());

function recursive_value(&$langid, $strings) {
    global $lang;
    foreach ($strings as $key => $val) {
        if (is_scalar($val)) {
            $lang[ $langid.':'. $key ] = $val;
        } else {
            $len = strlen($langid);
            $langid .= ':' . $key;
            recursive_value($langid, $val);
            $langid = substr($langid, 0, $len);
        }
    }
}

?>
<html>
<head>
    <title>Convert Strings</title>
</head>
<body>
<h2>Convert Strings</h2>
<p>This utility is for developer-use, to convert language strings from a mobile app project into translatateble,
    customisable strings in a language pack.</p>
<p>See for example: <code>raw/src/totara/locale/languages/en.json</code> in the mobile app repository.</p>
<?php
if (!empty($_POST['strings'])) {
    // Process and print
    $lang = array();
    $langid = 'app';
    $strings = json_decode($_POST['strings']);
    if (empty($strings)) {
        die('<p>Invalid JSON posted.</p>');
    }
    recursive_value($langid, $strings);
    print '<h3>Formatted strings:</h3><pre>';
    foreach ($lang as $key => $val) {
        $hkey = format_string($key);
        $hval = format_string($val, false);
        print '$string[\'' . $hkey . '\'] = \'' . str_replace(array("'", "&#39;"), array("\'", "\&#39;"), $hval) . '\';' . "\n";
    }
    print '</pre>';
}
?>
<form method="post">
    <p><label for="strings">Strings as JSON:</label><br><textarea name="strings" rows="12" cols="80" "></textarea></p>
    <p><input type="submit" value="Convert"></p>
</form>
</body>
</html>