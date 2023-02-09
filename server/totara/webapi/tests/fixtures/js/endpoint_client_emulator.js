/**
 * This file is part of Totara Learn
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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package totara_webapi
 */

/*
 * Totara API Endpoint Client Emulator for testing key parts of Totara API in a web browser.
 */
var clientEmulator = {

    version: "1.0.0",
    wwwRoot: false,
    output: document.getElementById("Output"),
    oauthToken: false,
    logCounter: 0,
    formCounter: 0,
    linkCounter: 0,

    init: function() {
        this.wwwRoot = window.location.href.substring(0, window.location.href.lastIndexOf('/totara/webapi'));
        if (!this.output) {
            return;
        }
        clientEmulator.log("Initialised.");

        this.tokenForm();
    },

    submitTokenForm: function(e) {
        e.preventDefault();
        let frm = e.target;
        const clientId = frm[0];
        const clientSecret = frm[1];
        const grantType = frm[2];
        frm.previousSibling.innerHTML += " (removed on submit)";
        clientEmulator.output.removeChild(frm);
        clientEmulator.log("Token request submitted", true);

        try {
            if (!clientId.value || !clientSecret.value || !grantType.value) {
                throw new Error('Missing client_id or client_secret or grant_type.');
            }
        } catch (err) {
            clientEmulator.log(err.name + " " + err.message);
            clientEmulator.tokenForm();
            return;
        }

        const formData = new FormData();
        formData.append('client_id', clientId.value);
        formData.append('client_secret', clientSecret.value);
        formData.append('grant_type', grantType.value);

        const myInit = {
            method: 'POST',
            body: formData
        };

        clientEmulator.log("Sending token request to " + clientEmulator.wwwRoot + '/totara/oauth2/token.php');
        var myRequest = new Request(clientEmulator.wwwRoot + '/totara/oauth2/token.php', myInit);
        M.util.js_pending('token_request');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                clientEmulator.log('Token request HTTP ok.');
                return response.json();
            } else {
                clientEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('token_request');
                throw new Error("Bad request");
            }
        }).then(function(jd) {
            // Confirm that response contains no errors.
            if (jd.error) {
                clientEmulator.log('Error: ' + jd.error + ": " + jd.error_description);
                M.util.js_complete('token_request');
                throw new Error("Error");
            }

            clientEmulator.oauthToken = jd.access_token;
            clientEmulator.log('OAuth2 token retrieved successfully');
            M.util.js_complete('token_request');

            // NEXT: show GraphQL request form.
            clientEmulator.graphQLForm();
        });
    },

    submitGraphql: function(e) {
        e.preventDefault();
        let frm = e.target;
        const data = frm[0];
        const auth = frm[1];

        frm.previousSibling.innerHTML += " (removed on submit)";
        clientEmulator.output.removeChild(frm);
        clientEmulator.log("Graphql submitted: <pre>" + data.value + "</pre>", true);
        clientEmulator.log("Authorization header included: " + auth.checked, true);

        // Send a POST to the external endpoint
        var myData = {};
        try {
            myData = JSON.parse(data.value);
            if (!myData.query || !myData.variables) {
                throw new Error('Missing query or variables.');
            }
        } catch (err) {
            if (err instanceof SyntaxError) {
                clientEmulator.log(
                    err.name + " " + err.message + " (line: " + err.lineNumber + ", column: " + err.columnNumber + ")"
                );
            } else {
                clientEmulator.log(err.name + " " + err.message);
            }
            clientEmulator.setupGraphql();
            return;
        }

        var myHeaders = new Headers(Object.assign(
            {},
            {'Content-Type': 'application/json'},
            auth.checked ? {'Authorization': 'Bearer ' + clientEmulator.oauthToken} : {}
        ));

        var myInit = {
            method: 'POST',
            headers: myHeaders,
            body: JSON.stringify(myData)
        };

        var myRequest = new Request(clientEmulator.wwwRoot + '/api/graphql.php', myInit);
        M.util.js_pending('graphql_request');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                clientEmulator.log('GraphQL request HTTP ok.');
                return response.json();
            } else if (response.status == '500') {
                clientEmulator.log('GraphQL request HTTP error: ' + response.status + ' ' + response.statusText);
                return response.json();
            } else {
                clientEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('graphql_request');
                throw new Error("Bad request");
            }
        }).then(function(jd) {
            clientEmulator.log("GraphQL response " + clientEmulator.formCounter + ": <pre id='response"
                + clientEmulator.formCounter + "'>" + clientEmulator.formatResult(jd) + "</pre>", true);
            clientEmulator.setupGraphql(myData);
            M.util.js_complete('graphql_request');
        });
    },

    tokenForm: function() {
        clientEmulator.log("Setting up new oauth2 token form:");
        this.formCounter++;

        // Client ID.
        var ci = document.createElement('input');
        ci.id = 'client_id' + this.formCounter;
        ci.name = 'client_id';
        ci.title = 'client_id';
        ci.type = 'text';
        var br = document.createElement('br');

        // Client Secret.
        var cs = document.createElement('input');
        cs.id = 'client_secret' + this.formCounter;
        cs.name = 'client_secret';
        cs.title = 'client_secret';
        cs.type = 'text';
        var br2 = document.createElement('br');

        // Grant type.
        var gt = document.createElement('input');
        gt.id = 'grant_type' + this.formCounter;
        gt.name = 'grant_type';
        gt.title = 'grant_type';
        gt.type = 'text';
        var br3 = document.createElement('br');

        var btn = document.createElement('button');
        btn.type = 'submit';
        btn.innerHTML = 'Submit Credentials ' + this.formCounter;
        var frm = document.createElement('form');
        frm.id = "form" + this.formCounter;
        frm.style.padding = '1em 0';
        frm.appendChild(ci);
        frm.appendChild(br);
        frm.appendChild(cs);
        frm.appendChild(br2);
        frm.appendChild(gt);
        frm.appendChild(br3);
        frm.appendChild(btn);
        frm.onsubmit = clientEmulator.submitTokenForm;
        this.output.appendChild(frm);
    },

    graphQLForm: function(queryObject) {
        clientEmulator.log("Setting up new GraphQL form");
        this.formCounter++;
        if (!queryObject || queryObject == '') {
            queryObject = {
                'operationName': null,
                'query': 'query {totara_webapi_status {status}}',
                'variables': {}
            };
        }

        // GraphQL textarea.
        let ta = document.createElement('textarea');
        ta.id = 'jsondata' + this.formCounter;
        ta.name = 'jsondata';
        ta.cols = "80";
        ta.rows = "8";
        ta.innerHTML = JSON.stringify(queryObject, null, '  ');

        // Add space.
        const br1 = document.createElement('br');

        // Enable/disable authorization header.
        let al = document.createElement('label');
        al.htmlFor = "authorization";
        al.innerHTML = 'Include authorization';
        let ah = document.createElement('input');
        ah.setAttribute('type', 'checkbox');
        ah.id = 'authorization';
        ah.name = 'authorization';
        ah.checked = true;

        // Add space.
        const br2 = document.createElement('br');

        let btn = document.createElement('button');
        btn.type = 'submit';
        btn.innerHTML = 'Submit Request ' + this.formCounter;

        // Create form with all elements.
        let frm = document.createElement('form');
        frm.id = "form" + this.formCounter;
        frm.style.padding = '1em 0';
        frm.appendChild(ta);
        frm.appendChild(br1);
        frm.appendChild(al);
        frm.appendChild(ah);
        frm.appendChild(br2);
        frm.appendChild(btn);
        frm.onsubmit = clientEmulator.submitGraphql;

        // Add form to the output element.
        this.output.appendChild(frm);
    },

    log: function(msg, isHtml = false) {
        var ele = document.createElement('p');
        ele.id = "message" + clientEmulator.logCounter;
        if (isHtml) {
            ele.innerHTML = clientEmulator.logCounter + ") " + msg;
        } else {
            ele.innerText = clientEmulator.logCounter + ") " + msg;
        }
        this.output.appendChild(ele);
        clientEmulator.logCounter++;
    },

    // HTML-escaping function
    htmlspecialchars: function(string) {

        // A collection of special characters and their entities.
        var specialchars = [
            [ '&', '&amp;' ],
            [ '<', '&lt;' ],
            [ '>', '&gt;' ],
            [ '"', '&quot;' ]
        ];

        // Our finalized string will start out as a copy of the initial string.
        var escapedString = string;

        // For each of the special characters,
        var len = specialchars.length;
        for (var x = 0; x < len; x++) {
            // Replace all instances of the special character with its entity.
            escapedString = escapedString.replace(
                new RegExp(specialchars[x][0], 'g'),
                specialchars[x][1]
            );
        }

        // Return the escaped string.
        return escapedString;
    },

    // Response-formatting function
    formatResult: function(jd) {
        return clientEmulator.htmlspecialchars(JSON.stringify(jd, null, '  '));
    }

};

window.addEventListener('DOMContentLoaded', function() {
    clientEmulator.init();
});
