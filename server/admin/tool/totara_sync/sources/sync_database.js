/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @package totara
 * @subpackage totara_sync
 */

M.totara_syncdatabaseconnect = M.totara_syncdatabaseconnect || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args){
        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // if defined, parse args into this module's config object
        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.totara_syncdatabaseconnect.init()-> jQuery dependency required for this module to function.');
        }

        $('#id_database_dbtest').click(function(event) {
            var dbtype = $('#id_database_dbtype').val();
            var dbname = $('#id_database_dbname').val();
            var dbhost = $('#id_database_dbhost').val();
            var dbuser = $('#id_database_dbuser').val();
            var dbpass = $('#id_database_dbpass').val();
            var dbport = $('#id_database_dbport').val();
            // Encode certain characters.
            dbpass = encodeURIComponent(dbpass);
            // Assemble url
            var url = M.cfg.wwwroot + '/admin/tool/totara_sync/sources/databasecheck.php';

            var data = {
                dbtype: dbtype,
                dbname: dbname,
                dbhost: dbhost,
                dbuser: dbuser,
                dbpass: dbpass,
                dbport: dbport,
                sesskey: M.cfg.sesskey
            };

            var button = $('#id_database_dbtest');
            var message = $('.db_connect_message');
            if (!message.length) {
                message = $('<p>', {'class': 'db_connect_message'}).insertAfter(button);
            }
            message.html(M.util.get_string('dbtestconnecting', 'tool_totara_sync'));
            button.prop('disabled', true);

            // Run script to check DB connectivity and display success or failure message
            $.post(url, data, 'json')
                // Make sure dbname is not blank, and both dbuser and dbpass can only be blank on MSSQL. This is to get around an issue
                // with MySQL where success is reported when passing no params to connect
                // function of database layer
                .done(function(data) {
                    if ((dbname != '' && dbuser != '') || (dbtype == 'sqlsrv' && dbname != '' && dbuser == '' && dbpass == '')) {
                        if (data.success) {
                            message.html(M.util.get_string('dbtestconnectsuccess', 'tool_totara_sync'));
                        } else {
                            message.html(M.util.get_string('dbtestconnectfail', 'tool_totara_sync'));
                        }
                    } else {
                        message.html(M.util.get_string('dbtestconnectfail', 'tool_totara_sync'));
                    }
                })
                .fail(function() {
                    message.html(M.util.get_string('dbtestconnectfail', 'tool_totara_sync'));
                })
                .always(function() {
                    button.prop('disabled', false);
                });
        });
    },
};
