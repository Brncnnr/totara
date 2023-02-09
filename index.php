<?php
/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD’s customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package core
 */

// This script should never be executed in a properly configured web server,
// so let's print out an error for the admin to fix their misconfigured web server.
// Note that this page is used with plain ENGLISH, because it is meant for admin only.

header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Error</title>
    </head>

    <body style="text-align: center;">
        <h1>
            Your webserver is misconfigured
        </h1>
        <h2>
            Please contact your administrator to reconfigure the webserver
        </h2>
    </body>
</html>