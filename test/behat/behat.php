<?php

if (php_sapi_name() !== "cli") {
    die();
}

/**
 * Return the PATH environment value.
 *
 * @return string[] array of [the_name_of_path, the_value_of_path]
 */
function getenvpath() {
    $env = getenv();
    foreach ($env as $name => $value) {
        // On Windows, PATH is case insensitive, so it could be Path or paTH.
        if (strcasecmp($name, 'PATH') === 0) {
            return [$name, $value];
        }
    }
    // In case PATH is not exported...
    return ['PATH', ''];
}

/**
 * Ported the following code to PHP as escapeshellarg is not what we want on Windows.
 * https://blogs.msdn.microsoft.com/twistylittlepassagesallalike/2011/04/23/everyone-quotes-command-line-arguments-the-wrong-way/
 *
 * @param string $argument
 * @param boolean $force
 * @return string
 */
function argv_quote($argument, $force = false) {
    if ($force == false && !empty($argument) && strpbrk($argument, " \t\n\v\"") === false) {
        return $argument;
    }

    $commandline = '"';
    $len = strlen($argument);
    for ($i = 0; $i < $len; $i++) {
        $numberBackslashes = 0;

        while ($i < $len && substr($argument, $i, 1) === '\\') {
            $i++;
            $numberBackslashes++;
        }

        if ($i === $len) {
            $commandline .= str_repeat('\\', $numberBackslashes * 2);
            break;
        }
        $ch = substr($argument, $i, 1);
        if ($ch === '"') {
            $commandline .= str_repeat('\\', $numberBackslashes * 2 + 1);
            $commandline .= $ch;
        } else {
            $commandline .= str_repeat('\\', $numberBackslashes);
            $commandline .= $ch;
        }
    }
    $commandline .= '"';
    return $commandline;
}

// Update the environment to ensure the same PHP binary is used to run
// the behat init script. This way we can be sure that all forked processes use
// the same binary.
$path = getenvpath();
$pathdelim = DIRECTORY_SEPARATOR === "\\" ? ';' : ':';
putenv($path[0] . '=' . PHP_BINARY . $pathdelim . $path[1]);

// Collect the arguments provided to this script so that we can forward them to the correct script when ready.
$passthrough = $argv;
array_shift($passthrough); // Drop off this script
$script = array_shift($passthrough); // Collect the script that the user wishes to run.

$scripts = [
    'init' => [
        'command' => 'php',
        'arguments' => [realpath(__DIR__ . '/../../server/admin/tool/behat/cli/init.php')],
        'description' => 'Initialises Behat for a single thread, run with --help to see options'
    ],
    'util' => [
        'command' => 'php',
        'arguments' => [realpath(__DIR__ . '/../../server/admin/tool/behat/cli/util.php')],
        'description' => 'Utility script for the Totara Behat implementation, run with --help to see options'
    ],
    'behat_run' => [
        'command' => 'php',
        'arguments' => [realpath(__DIR__ . '/../../server/admin/tool/behat/cli/run.php')],
        'description' => 'Initialises Behat for multiple threads, run with --help to see options'
    ],
    'util_single_run' => [
        'command' => 'php',
        'arguments' => [realpath(__DIR__ . '/../../server/admin/tool/behat/cli/util_single_run.php')],
        'description' => 'Utility script for the Totara Behat implementation, run with --help to see options'
    ],
    'run' => [
        'command' => realpath(__DIR__ . '/vendor/bin/behat'),
        'arguments' => [],
        'description' => 'Runs Behat, run with --help to see options'
    ],
];

// If they didn't ask for anything, or they asked for something that does not exist then print help.
if (!isset($scripts[$script])) {
    echo "Totara Behat implementation\n";
    echo "\n";
    echo "This script makes it easy to interact with Totara's Behat implementation by centralising\n";
    echo "where those scripts are called from. The following options exist:\n";
    echo "\n";
    foreach ($scripts as $script => $details) {
        echo "  {$script}\n";
        echo "    {$details['description']}\n\n";
    }
    echo "\n";
    echo "e.g.\n";
    echo "  php test/behat/behat.php init\n";
    echo "  php test/behat/behat.php util --buildconfig\n";
    echo "  php test/behat/behat.php run\n";
    echo "  php test/behat/behat.php run --name 'User account creation'\n";
    exit(0);
}
// We have a valid script request.

// Establish the command we need to run.
$command = $scripts[$script]['command'];
// Add the required arguments to the command to the start.
$passthrough = array_merge($scripts[$script]['arguments'], $passthrough);
// A little hack to set the behat configuration path when running behat.
$set_config_argument = ($script === 'run');
foreach ($passthrough as &$arg) {
    if ($script === 'run') {
        if ($arg === 'config') {
            $set_config_argument = false;
        }
    }
    if (DIRECTORY_SEPARATOR === "\\") {
        $arg = argv_quote($arg);
    } else {
        $arg = escapeshellarg($arg);
    }
}
// The hack continued...
if ($set_config_argument) {
    if (DIRECTORY_SEPARATOR === "\\") {
        $passthrough[] = argv_quote('--config='.__DIR__.'\\behat.yml');
    } else {
        $passthrough[] = '--config='.escapeshellarg(__DIR__.'/behat.yml');
    }
}

passthru(escapeshellcmd($command) . ' '  . join(' ', $passthrough), $code);
exit($code);
