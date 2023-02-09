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
// the phpunit init script. This way we can be sure that all forked processes use
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
        'arguments' => [realpath(__DIR__ . '/../../server/admin/tool/phpunit/cli/init.php')],
        'description' => 'Initialises PHPUnit for a single thread, run with --help to see options'
    ],
    'util' => [
        'command' => 'php',
        'arguments' => [realpath(__DIR__ . '/../../server/admin/tool/phpunit/cli/util.php')],
        'description' => 'Utility script for the Totara PHPUnit implementation, run with --help to see options'
    ],
    'parallel_init' => [
        'command' => 'php',
        'arguments' => [realpath(__DIR__ . '/../../server/admin/tool/phpunit/cli/parallel_init.php')],
        'description' => 'Initialises PHPUnit for multiple threads, run with --help to see options'
    ],
    'parallel_run' => [
        'command' => 'php',
        'arguments' => [realpath(__DIR__ . '/../../server/admin/tool/phpunit/cli/parallel_run.php')],
        'description' => 'Run multiple parallel PHPUnit threads'
    ],
    'run' => [
        'command' => realpath(__DIR__ . '/vendor/bin/phpunit'),
        'arguments' => [],
        'description' => 'Runs PHPUnit, run with --help to see options'
    ],
];

// If they didn't ask for anything, or they asked for something that does not exist then print help.
if (!isset($scripts[$script])) {
    echo "Totara PHPUnit implementation\n";
    echo "\n";
    echo "This script makes it easy to interact with Totara's PHPUnit implementation by centralising\n";
    echo "where those scripts are called from. The following options exist:\n";
    echo "\n";
    foreach ($scripts as $script => $details) {
        echo "  {$script}\n";
        echo "    {$details['description']}\n\n";
    }
    echo "\n";
    echo "e.g.\n";
    echo "  php test/phpunit/phpunit.php init\n";
    echo "  php test/phpunit/phpunit.php util --buildconfig\n";
    echo "  php test/phpunit/phpunit.php run\n";
    echo "  php test/phpunit/phpunit.php run --filter test_default_environment server/lib/phpunit/tests/advanced_test.php\n";
    exit(0);
}
// We have a valid script request.

// Establish the command we need to run.
$command = $scripts[$script]['command'];
// Add the required arguments to the command to the start.
$passthrough = array_merge($scripts[$script]['arguments'], $passthrough);
// A little hack to set the phpunit configuration path when running phpunit.
$set_config_argument = ($script === 'run' || $script === 'parallel_run');
foreach ($passthrough as &$arg) {
    if ($script === 'run') {
        if ($arg === 'c' || $arg === '--configuration' || $arg === '--no-configuration') {
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
    $passthrough[] = '-c';
    if (DIRECTORY_SEPARATOR === "\\") {
        $passthrough[] = argv_quote(realpath(__DIR__ . '/phpunit.xml'));
    } else {
        $passthrough[] = escapeshellarg(realpath(__DIR__ . '/phpunit.xml'));
    }
}

passthru(escapeshellcmd($command) . ' '  . join(' ', $passthrough), $code);
exit($code);
