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
 * This script prints basic CSS for the installer
 *
 * @package    core
 * @subpackage install
 * @copyright  2011 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (file_exists(__DIR__.'/../../config.php')) {
    // already installed
    error_log('install/css.php :: Installation has been completed, install styles no longer served.');
    die('// Installation has been completed, install styles no longer served.');
}

$content = file_get_contents(__DIR__.'/../theme/legacy/style/totara.css');

$content .= <<<EOF

.text-ltr {
    direction: ltr !important;
}

#header {
    background-color: rgb(61, 68, 75);
}

.headermain {
    padding: 15px;
    color: #FFF;
}
.headermain img {
    height: 40px;
    margin: 0 15px;
    display: inline;
    vertical-align:top;
}

#installdiv {
    max-width: 1200px;
    margin-left:auto;
    margin-right:auto;
    padding: 5px;
    margin-bottom: 15px;
}

#installdiv dt {
    font-weight: bold;
}

#installdiv dd {
    padding-bottom: 0.5em;
}

.configphp {
    text-align: left;
    direction: ltr;
}

.page-footer-poweredby {
    margin-top: 20px;
}

.page-footer-poweredby img {
    height: 10px;
}

#nav_buttons,
.fitem {
    clear:both;
    padding: 8px;
    margin: 0 -15px;
}

.fitemtitle {
    display: inline-block;
    width: 25%;
}
label {
    margin: 5px;
}

.fitemelement {
    display: inline-block;
    width: 75%;
}

.totara-navbar-container {
    height: 40px;
    background-color:#FFF;
}

.breadcrumb {
    padding: 8px 15px;
    margin: 0 0 20px;
    list-style: none;
}

.breadcrumb > li {
    display: inline-block;
    text-shadow: 0 1px 0 #fff;
    line-height: 20px;
}

[dir=ltr] #nav_buttons input:first-child {
    margin-left: 25%;
}
[dir=rtl] #nav_buttons input:first-child {
    margin-right: 25%;
}

EOF;

@header('Content-Disposition: inline; filename="css.php"');
@header('Cache-Control: no-store, no-cache, must-revalidate');
@header('Cache-Control: post-check=0, pre-check=0', false);
@header('Pragma: no-cache');
@header('Expires: Mon, 20 Aug 1969 09:23:00 GMT');
@header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
@header('Accept-Ranges: none');
@header('Content-Type: text/css; charset=utf-8');

echo $content;
