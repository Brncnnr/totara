<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_comment
 */
define('CLI_SCRIPT', true);
global $CFG, $DB, $USER;

require_once(__DIR__ . "/../../server/config.php");
require_once("{$CFG->dirroot}/lib/clilib.php");

[$options, $unrecognized] = cli_get_params(
    [
        'number'      => 10,
        'component'   => null,
        'help'        => false,
        'user_id'     => null,
        'instance_id' => null,
        'area'        => null
    ],
    [
        'h' => 'help',
        'u' => 'user_id',
        'n' => 'number',
        'c' => 'component',
        'a' => 'area',
        'i' => 'instance_id'
    ]
);

if ($options['help']) {
    echo "
This is a script to generate lots of comments for the instance that is provided via CLI parameters. The number of comments
to be generated will be default to 10. However it can be changed via CLI parameter. For each of comment, the same number will
be apply for the replies within a comment.

Options:
    -u, --user_id           The actor user.
    -n, --number            The number of comments to be generate for the item.
    -c, --component         The component that the instance's id is belonging into.
    -i, --instance_id       The instance's id.
    -a, --area              The area to distinguish the instance within the component.
    -h, --help              Print out this help.

Example:
    php dev/engage/generate_comments.php --component=\"container_workspace\" --area=\"discussion\" --instance_id=5
";

    return 0;
}

if (!isset($options['component'])) {
    echo "Missing the component name\n";
    return 1;
} else if (!isset($options['area'])) {
    echo "Missing the area name\n";
    return 1;
} else if (!isset($options['instance_id'])) {
    echo "Missing the instance_id\n";
    return 1;
}

if (isset($options['user_id'])) {
    $USER = $DB->get_record('user', ['id' => $options['user_id']], '*', MUST_EXIST);
} else {
    $USER = get_admin();
}

$generator = \core\testing\generator::instance();

/** @var \totara_comment\testing\generator $comment_generator */
$comment_generator = $generator->get_plugin_generator('totara_comment');
$total_number = $options['number'];

for ($i = 0; $i < $total_number; $i++) {
    echo "- Create comment '{$i}'\n";
    $comment = $comment_generator->create_comment(
        $options['instance_id'],
        $options['component'],
        $options['area'],
        null,
        FORMAT_JSON_EDITOR
    );

    for ($j = 0; $j < $total_number; $j++) {
        echo "* Create reply '{$j}'\n";
        $comment_generator->create_reply($comment->get_id());
    }
}

echo "Done\n";
return 0;