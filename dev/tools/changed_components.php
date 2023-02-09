<?php
/**
 * This file is part of Totara TXP
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 */

define('CLI_SCRIPT', true);

require_once(__DIR__.'/../../server/config.php');

/** @var core_config $CFG */

require_once($CFG->dirroot . '/lib/clilib.php');

global $options;
[$options, $cli_unrecognized] = cli_get_params([
    'help' => false,
    'clean' => false,
    'hash' => false,
]);

$script_title = "Git change summary script";
$help_message = "Use this script to create an overview how many commits affect certain components since a specific commit hash.

This can give you an indication in which areas the most changes where made since the last major release.

Usage: php dev/tools/changed_components.php --hash=30d78c42

Options:

  --hash  Provide a hash to compare against, i.e. search for branching Totara XX commits to find when we started the current release branch
  --clean Clean up the temporary files created by this script
  --help  Show this screen

";

cli_heading($script_title);

if ($options['help']) {
    cli_writeln($help_message);
    exit(1);
}

$temporary_dir = $CFG->tempdir.'/changed_components';

// Clean up the temporary directory
if ($options['clean']) {
    $rmdirr = function ($dir) use (& $rmdirr) {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $rmdirr("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    };
    if (file_exists($temporary_dir)) {
        $rmdirr($temporary_dir);
    }
    cli_writeln("All temporary files cleaned up.");
    exit(0);
}

if (empty($options['hash']) || $options['hash'] == 1) {
    cli_separator();
    cli_problem("Error: You need to provide a commit hash to compare against.");
    cli_separator();
    cli_writeln("Use the --help option to get more details on how to use this script.");
    cli_writeln($help_message);
    exit(1);
}

$commit_hash = $options['hash'];

$frankenstyle = [
    'client/.' => 'client_tui_tooling',
    'client/component/samples/' => 'client_tui_samples',
    'client/component/tui/' => 'client_tui',
    'client/tooling/' => 'client_tui_tooling',
    'dev/' => 'dev',
    'extensions/ml_recommender/' => 'core_ml',
    'libraries/' => 'core_libs',
    'server/.' => 'core',
    'server/backup/' => 'core_backup',
    'server/iplookup/' => 'core_iplookup',
    'server/mnet/' => 'core_mnet',
    'server/lang/' => 'core',
    'server/lib/phpunit/' => 'core_phpunit',
    'server/lib/' => 'core',
    'server/login/' => 'core',
    'server/theme/' => 'core_theme',
    'server/version.php' => 'core',
    'test/phpunit/' => 'core_phpunit'
];

$changes = [
    'client_tui' => [],
    'client_tui_samples' => [],
    'client_tui_tooling' => [],
    'core' => [],
    'core_backup' => [],
    'core_iplookup' => [],
    'core_libs' => [],
    'core_ml' => [],
    'core_mnet' => [],
    'core_phpunit' => [],
    'core_theme' => [],
    'dev' => [],
];

$basedirlength = strlen($CFG->srcroot) + 1;

foreach (core_component::get_core_subsystems() as $name => $directory) {
    if ($directory === null) {
        continue;
    }
    $frankenstyle[substr($directory, $basedirlength) . DIRECTORY_SEPARATOR] = 'core_' . $name;
    $frankenstyle['client/component/' . 'core_' . $name . DIRECTORY_SEPARATOR] = 'core_' . $name;
    $changes['core_' . $name] = [];
}

foreach (core_component::get_plugin_types() as $type => $typedirectory) {
    foreach (core_component::get_plugin_list($type) as $plugin => $plugindirectory) {
        $frankenstyle[substr($plugindirectory, $basedirlength) . DIRECTORY_SEPARATOR] = $type . '_' . $plugin;
        $frankenstyle['client/component/' . $type . '_' . $plugin . DIRECTORY_SEPARATOR] = $type . '_' . $plugin;
        $changes[$type . '_' . $plugin] = [];
    }
}

exec('git log --pretty=\'format:%H\' '.escapeshellcmd($commit_hash).'...HEAD', $output);

$count = 0;
$total = count($output);
cli_writeln("Processing {$total} commit");
cli_writeln("\r" . str_pad(round($count / $total * 100) . "% ({$count} / {$total})", 10, ' ', STR_PAD_LEFT));

if (!file_exists($temporary_dir)) {
    mkdir($temporary_dir);
}

$commitchanges = [];
foreach ($output as $hash) {
    $count ++;
    cli_writeln("\r" . str_pad(round($count / $total * 100) . "% ({$count} / {$total})", 10, ' ', STR_PAD_LEFT));

    $cachefile = $temporary_dir.'/commits-' . $hash;
    if (file_exists($cachefile)) {
        // If cache file is not older than 10 minutes
        if (time() - filemtime($cachefile) <= 60 * 10) {
            $addition = json_decode(file_get_contents($cachefile));
            if (is_object($addition)) {
                $addition = (array)$addition;
            }
            if (!is_array($addition)) {
                var_dump($addition);
                cli_error('Invalid cache file content detected');
            }
            foreach ($addition as $tl => $components) {
                $commitchanges[$tl] = $components;
            }
            continue;
        }
    }

    $changedfiles = [];
    $commitmsg = [];
    exec('git diff --name-status '.escapeshellcmd($hash).'^1...'.escapeshellcmd($hash).' | cut -f2', $changedfiles);
    exec('git log -1 --pretty=\'format:%s\' ' . escapeshellcmd($hash), $commitmsg);
    $tl = 'Unknown';
    if (preg_match('#(TL-\d+)#', $commitmsg[0], $matches)) {
        $tl = $matches[1];
    }

    cli_writeln("\r" . str_pad(round($count / $total * 100) . "% ({$count} / {$total})", 10, ' ', STR_PAD_LEFT) . ' ' . $tl . ' ' . $hash);

    $commitchanges[$tl] = [];
    foreach ($changedfiles as $file) {
        $found = false;
        foreach ($frankenstyle as $directory => $component) {
            if (strpos($file, $directory) === 0) {
                $found = true;
                if (!in_array($component, $commitchanges[$tl])) {
                    $commitchanges[$tl][] = $component;
                }
                break 1;
            }
        }
        if ($found === false) {
            if (strpos($file, '/') === false) {
                $commitchanges[$tl][] = 'core';
            } else {
                $commitchanges[$tl][] = 'unknown';
                cli_writeln("\nMissing: " . $file . "\nProgress...");
            }
        }
    }

    file_put_contents($cachefile, json_encode([$tl => $commitchanges[$tl]]));
}
cli_writeln("\r" . str_pad(round($count / $total * 100) . "% ({$count} / {$total})", 10, ' ', STR_PAD_LEFT));
cli_writeln("\n");

// Flip the array.
$changesbycomponent = [];
foreach ($commitchanges as $key => $components) {
    foreach ($components as $component) {
        $changesbycomponent[$component] = isset($changesbycomponent[$component])
            ?  $changesbycomponent[$component] + 1
            : 1;
    }
}

arsort($changesbycomponent);

foreach ($changesbycomponent as $component => $count) {
    if ($count === 1) {
        continue;
    }
    echo str_pad($component, 40) . " => {$count}\n";
}

