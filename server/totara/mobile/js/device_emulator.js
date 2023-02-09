/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * Totara App Emulator for mimicking key parts of Totara App in a web browser.
 *
 */
var deviceEmulator = {

    version: "1.0.0",
    wwwRoot: false,
    output: document.getElementById("Output"),
    webview: document.getElementById("WebView"),
    authType: false,
    loginSecret: false,
    setupSecret: false,
    apiKey: false,
    apiUrl: false,
    logCounter: 0,
    formCounter: 0,
    linkCounter: 0,

    init: function() {
        this.wwwRoot = window.location.href.substring(0, window.location.href.lastIndexOf('/totara/mobile'));
        if (!this.output) {
            return;
        }
        if (!this.webview) {
            return;
        }
        this.log("Initialised.");

        // NEXT: Make device request.
        this.siteInfoRequest();
    },

    siteInfoRequest: function() {
        this.log("Making site info request with app version " + this.version + "...");

        // Send a POST request with our special header.
        var myData = {'version': this.version};
        var myHeaders = new Headers({
            'X-TOTARA-DEVICE-EMULATION': '1'
        });
        var myInit = {
            method: 'POST',
            headers: myHeaders,
            body: JSON.stringify(myData)
        };
        var myRequest = new Request(deviceEmulator.wwwRoot + '/totara/mobile/site_info.php', myInit);
        M.util.js_pending('site_info');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                deviceEmulator.log('Site info HTTP ok.');
                return response.json();
            } else {
                deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('site_info');
                throw new Error("Bad request");
            }
        }).then(function(jd) {
            if (jd.data && jd.data.auth) {
                deviceEmulator.log("Site info response: <pre id='site_info_response'>" + JSON.stringify(jd, null, '  ') + "</pre>", true);
                deviceEmulator.authType = jd.data.auth;
                deviceEmulator.log("Authentication method: " + deviceEmulator.authType);
                deviceEmulator.log("API Version: " + jd.data.version);
                switch (deviceEmulator.authType) {
                    case 'native':
                        // NEXT: emulate a native login
                        deviceEmulator.loginSetup();
                        break;
                    case 'webview':
                    case 'browser':
                        // NEXT: make sure logout is done.
                        deviceEmulator.doLogout(false);
                        break;
                    default:
                        throw new Error("Unimplemented auth type");
                }
            } else {
                deviceEmulator.log("Unexpected site info response: " + JSON.stringify(jd));
            }
            M.util.js_complete('site_info');
        });
    },

    loginSetup: function() {
        this.log("Making login_setup request....");

        // Send a GET request with our special header. Use same-origin to pass session cookie.
        var myHeaders = new Headers({
            'X-TOTARA-MOBILE-DEVICE-REGISTRATION': 'device_emulator.js 1.0'
        });
        var myInit = {
            method: 'GET',
            headers: myHeaders,
            credentials: 'same-origin'
        };
        var myRequest = new Request(deviceEmulator.wwwRoot + '/totara/mobile/login_setup.php', myInit);
        M.util.js_pending('login_setup');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                deviceEmulator.log('Login setup request HTTP ok.');
                return response.json();
            } else {
                deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('login_setup');
                throw new Error("Bad request");
            }
        }).then(function(jd) {
            deviceEmulator.loginSecret = jd.data.loginsecret;
            deviceEmulator.log("Login secret: " + deviceEmulator.loginSecret);
            deviceEmulator.loginForm();
            M.util.js_complete('login_setup');
        });
    },

    submitLogin: function(e) {
        e.preventDefault();
        var frm = e.target;
        var username = frm[0];
        var password = frm[1];
        frm.previousSibling.innerHTML += " (removed on submit)";
        deviceEmulator.output.removeChild(frm);
        deviceEmulator.log("Login submitted: <pre>" + username.value + "</pre>", true);

        // Send a POST to the mobile endpoint
        var data = {"username": username.value, "password": password.value, "loginsecret": deviceEmulator.loginSecret};
        try {
            if (data.username && data.password && data.loginsecret) {
                // Good!
            } else {
                throw new Error('Missing username or password or loginsecret.');
            }
        } catch (err) {
            deviceEmulator.log(err.name + " " + err.message);
            deviceEmulator.loginForm();
            return;
        }

        var myHeaders = new Headers({
            'Content-Type': 'application/json',
        });
        var myInit = {
            method: 'POST',
            headers: myHeaders,
            body: JSON.stringify(data)
        };
        deviceEmulator.log("Sending login request to " + deviceEmulator.wwwRoot + '/totara/mobile/login.php');
        var myRequest = new Request(deviceEmulator.wwwRoot + '/totara/mobile/login.php', myInit);
        M.util.js_pending('login_request');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                deviceEmulator.log('Login request HTTP ok.');
                return response.json();
            } else if (response.status == '401') {
                deviceEmulator.log('Login request authentication error: ' + response.status + ' ' + response.statusText);
                deviceEmulator.loginSetup();
                M.util.js_complete('login_request');
                throw new Error("Bad request");
            } else {
                deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('login_request');
                throw new Error("Bad request");
            }
        }).then(function(jd) {
            deviceEmulator.log('Native login OK: ' + JSON.stringify(jd));
            deviceEmulator.setupSecret = jd.data.setupsecret;
            deviceEmulator.log('Setup secret is ' + deviceEmulator.setupSecret);
            M.util.js_complete('login_request');
            // NEXT: Register the device.
            deviceEmulator.deviceRegister();
        });
    },

    deviceLogin: function() {
        if (deviceEmulator.authType == 'webview') {
            deviceEmulator.log("Emulating webview login.");

            // Send a GET to login
            var myHeaders = new Headers({
                'X-TOTARA-MOBILE-DEVICE-REGISTRATION': 'device_emulator.js 1.0',
                'X-TOTARA-DEVICE-EMULATION': '1'
            });
            var myInit = {
                method: 'GET',
                headers: myHeaders,
                credentials: 'same-origin'
            };

            var myRequest = new Request(deviceEmulator.wwwRoot + '/login/index.php', myInit);
            M.util.js_pending('login_request');

            fetch(myRequest).then(function (response) {
                if (response.ok) {
                    deviceEmulator.log('Login request HTTP ok.');
                    return response.text();
                } else if (response.status == '500') {
                    deviceEmulator.log('Login request HTTP error: ' + response.status + ' ' + response.statusText);
                    return response.text();
                } else {
                    deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                    M.util.js_complete('login_request');
                    throw new Error("Bad request");
                }
            }).then(function (txt) {
                deviceEmulator.webview.srcdoc = txt;
                deviceEmulator.log("Login response is in frame. <a href=\"#\" onclick=\"javascript: deviceEmulator.captureSecret(); return false;\">Capture setup secret</a>", true);
                M.util.js_complete('login_request');
            });
        } else {
            deviceEmulator.log("Mobile browser login is not testable in the device emulator.");
        }
    },

    captureSecret: function() {
      M.util.js_pending('capture_secret');
      this.log("Attempting to capture setup secret, hope you logged in but did NOT click Continue yet.");
      var secret = deviceEmulator.webview.contentDocument.getElementById('totara_mobile-setup-secret').getAttribute('data-totara-mobile-setup-secret');
      this.log("Secret is " + secret);
      deviceEmulator.setupSecret = secret;
      deviceEmulator.webview.contentDocument.forms[0].submit();
      deviceEmulator.deviceRegister();
      M.util.js_complete('capture_secret');
    },

    deviceRequest: function() {
        this.log("Making device request....");

        // Send a GET request with our special header. Use same-origin to pass session cookie.
        var myHeaders = new Headers({
            'X-TOTARA-MOBILE-DEVICE-REGISTRATION': 'device_emulator.js 1.0',
            'X-TOTARA-DEVICE-EMULATION': '1'
        });
        var myInit = {
            method: 'GET',
            headers: myHeaders,
            credentials: 'same-origin'
        };
        var myRequest = new Request(deviceEmulator.wwwRoot + '/totara/mobile/device_request.php', myInit);
        M.util.js_pending('device_request');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                deviceEmulator.log('Device request HTTP ok.');
                return response.text();
            } else {
                deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('device_request');
                throw new Error("Bad request");
            }
        }).then(function(html) {
            // Parse the response.
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, "text/html");

            // Find the setup secret...
            var secretspan = doc.getElementById('totara_mobile-setup-secret');
            if (secretspan) {
                deviceEmulator.setupSecret = secretspan.getAttribute('data-totara-mobile-setup-secret');
                deviceEmulator.log('Setup secret is ' + deviceEmulator.setupSecret);

                // NEXT: Register the device.
                deviceEmulator.deviceRegister();
            } else {
                // Nope, something went wrong.
                deviceEmulator.log('Setup secret not found. We probably need to log in again.');
                var pc = doc.getElementById('page-content');
                if (pc) {
                    deviceEmulator.log('Page content is: ' + pc.innerText);
                }
            }
            M.util.js_complete('device_request');
        });
    },

    deviceRegister: function() {
        this.log("Registering device...");
        if (!deviceEmulator.setupSecret) {
            this.log("...except no setup secret? Must call deviceRequest() first.");
            return;
        }

        // Send a POST with the setup secret in JSON object/
        var myData = {'setupsecret': deviceEmulator.setupSecret};
        var myHeaders = new Headers({
            'Content-Type': 'application/json'
        });
        var myInit = {
            method: 'POST',
            headers: myHeaders,
            body: JSON.stringify(myData)
        };
        var myRequest = new Request(deviceEmulator.wwwRoot + '/totara/mobile/device_register.php', myInit);
        M.util.js_pending('device_register');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                deviceEmulator.log('Device registration HTTP ok.');
                return response.json();
            } else {
                deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('device_register');
                throw new Error("Bad request");
            }
        }).then(function(jd) {
            if (jd.data && jd.data.apikey && jd.data.apiurl) {
                deviceEmulator.apiKey = jd.data.apikey;
                deviceEmulator.apiUrl = jd.data.apiurl;
                deviceEmulator.log("API key: " + deviceEmulator.apiKey);
                deviceEmulator.log("API URL: " + deviceEmulator.apiUrl);
                deviceEmulator.log("Mobile API Version: " + jd.data.version);

                // NEXT: Setup graphQL browser
                deviceEmulator.setupGraphql();
            } else {
                deviceEmulator.log("Unexpected registration response: " + JSON.stringify(jd));
            }
            M.util.js_complete('device_register');
        });
    },

    doLogout: function(relogin = true) {
        this.log("Logging out of Totara...");

        // Send a second GET request to device_request. Use same-origin to pass session cookie.
        var myHeaders = new Headers({
            'X-TOTARA-DEVICE-EMULATION': '1'
        });
        var myInit = {
            method: 'GET',
            headers: myHeaders,
            credentials: 'same-origin'
        };
        var myRequest = new Request(deviceEmulator.wwwRoot + '/login/logout.php', myInit);
        M.util.js_pending('device_logout');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                deviceEmulator.log('Logout request HTTP ok.');
                return response.text();
            } else {
                deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('device_logout');
                throw new Error("Bad request");
            }
        }).then(function(html) {
            if ( relogin ) {
                // Parse the response.
                var parser = new DOMParser();
                var doc = parser.parseFromString(html, "text/html");
                var pc = doc.getElementById('page-content');
                if (pc) {
                    deviceEmulator.log('Page content is: ' + pc.innerText);
                }

                // WEBVIEW: Show login screen
                deviceEmulator.webview.src = deviceEmulator.wwwRoot + '/login/index.php';
                deviceEmulator.webview.onload = function () {
                    deviceEmulator.webview.contentDocument.getElementById('login').target = '_top';
                    deviceEmulator.webview.contentDocument.getElementById('loginbtn').value = 'Log In and Refresh';
                };

                // NEXT: Setup graphQL browser
                deviceEmulator.setupGraphql();
            } else {
                deviceEmulator.log("...logout complete.");
                // NEXT: Do deviceLogin
                deviceEmulator.deviceLogin();
            }
            M.util.js_complete('device_logout');
        });
    },

    submitGraphql: function(e) {
        e.preventDefault();
        var frm = e.target;
        var data = frm[0];
        frm.previousSibling.innerHTML += " (removed on submit)";
        deviceEmulator.output.removeChild(frm);
        deviceEmulator.log("Graphql submitted: <pre>" + data.value + "</pre>", true);

        // Send a POST to the mobile endpoint
        var myData = {};
        try {
            myData = JSON.parse(data.value);
            if (myData.operationName && myData.variables) {
                // Good!
            } else {
                throw new Error('Missing operationName or variables.');
            }
        } catch (err) {
            if (err instanceof SyntaxError) {
                deviceEmulator.log(err.name + " " + err.message + " (line: " + err.lineNumber + ", column: " + err.columnNumber + ")");
            } else {
                deviceEmulator.log(err.name + " " + err.message);
            }
            deviceEmulator.setupGraphql();
            return;
        }

        var myHeaders = new Headers({
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + deviceEmulator.apiKey
        });
        var myInit = {
            method: 'POST',
            headers: myHeaders,
            body: JSON.stringify(myData)
        };
        var myRequest = new Request(deviceEmulator.apiUrl, myInit);
        M.util.js_pending('graphql_request');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                deviceEmulator.log('GraphQL request HTTP ok.');
                return response.json();
            } else if (response.status == '500') {
                deviceEmulator.log('GraphQL request HTTP error: ' + response.status + ' ' + response.statusText);
                return response.json();
            } else {
                deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('graphql_request');
                throw new Error("Bad request");
            }
        }).then(function(jd) {
            deviceEmulator.log("GraphQL response " + deviceEmulator.formCounter + ": <pre id='response" + deviceEmulator.formCounter + "'>" + deviceEmulator.formatResult(jd) + "</pre>", true);
            deviceEmulator.setupGraphql(myData);
            // Special for webview
            if (typeof jd.data.create_webview != 'undefined') {
                deviceEmulator.createWebview(jd.data.create_webview);
            }
            M.util.js_complete('graphql_request');
        });
    },

    loginForm: function() {
        this.log("Setting up new login form:");
        this.formCounter++;
        var un = document.createElement('input');
        un.id = 'username' + this.formCounter;
        un.name = 'username';
        un.title = 'username';
        un.type = 'text';
        var br = document.createElement('br');
        var pw = document.createElement('input');
        pw.id = 'password' + this.formCounter;
        pw.name = 'password';
        pw.title = 'password';
        pw.type = 'password';
        var br2 = document.createElement('br');
        var btn = document.createElement('button');
        btn.type = 'submit';
        btn.innerHTML = 'Submit Credentials ' + this.formCounter;
        var frm = document.createElement('form');
        frm.id = "form" + this.formCounter;
        frm.style.padding = '1em 0';
        frm.appendChild(un);
        frm.appendChild(br);
        frm.appendChild(pw);
        frm.appendChild(br2);
        frm.appendChild(btn);
        frm.onsubmit = deviceEmulator.submitLogin;
        this.output.appendChild(frm);
    },

    setupGraphql: function(queryObject) {
        this.log("Setting up new GraphQL browser:");
        this.formCounter++;
        if (!queryObject || queryObject == '') {
            queryObject = {'operationName': 'totara_mobile_me', 'variables': {}};
        }
        var ta = document.createElement('textarea');
        ta.id = 'jsondata' + this.formCounter;
        ta.name = 'jsondata';
        ta.cols = "80";
        ta.rows = "8";
        ta.innerHTML = JSON.stringify(queryObject, null, '  ');
        var br = document.createElement('br');
        var btn = document.createElement('button');
        btn.type = 'submit';
        btn.innerHTML = 'Submit Request ' + this.formCounter;
        var frm = document.createElement('form');
        frm.id = "form" + this.formCounter;
        frm.style.padding = '1em 0';
        frm.appendChild(ta);
        frm.appendChild(br);
        frm.appendChild(btn);
        frm.onsubmit = deviceEmulator.submitGraphql;
        this.output.appendChild(frm);
    },


    fetchFile: function(url) {
        deviceEmulator.log("Fetch file: " + url);

        // Send a GET request to url with API token as Authorization: Bearer
        var myHeaders = new Headers({
            'Authorization': 'Bearer ' + deviceEmulator.apiKey
        });
        var myInit = {
            method: 'GET',
            headers: myHeaders
        };

        var myRequest = new Request(url, myInit);
        M.util.js_pending('file_request');

        fetch(myRequest).then(function(response) {
            if (response.ok) {
                deviceEmulator.log('File request HTTP ok.');
                deviceEmulator.log('File received ' + response.headers.get('Content-Type'));
                return response.blob();
            } else {
                deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('file_request');
                throw new Error("Bad request");
            }
        }).then(function(blob) {
            deviceEmulator.log("File response " + blob.size + " bytes");
            M.util.js_complete('file_request');
        });

        return false;
    },

    createWebview: function(webview_secret) {
        deviceEmulator.log("Creating a webview!");

        // Send a GET to the webview endpoint
        var myHeaders = new Headers({
            'X-TOTARA-DEVICE-EMULATION': '1',
            'X-TOTARA-MOBILE-WEBVIEW-SECRET': webview_secret
        });
        var myInit = {
            method: 'GET',
            headers: myHeaders
        };

        var myRequest = new Request(deviceEmulator.wwwRoot + '/totara/mobile/device_webview.php', myInit);
        M.util.js_pending('webview_request');

        fetch(myRequest).then(function (response) {
            if (response.ok) {
                deviceEmulator.log('Webview request HTTP ok.');
                return response.text();
            } else if (response.status == '500') {
                deviceEmulator.log('Webview request HTTP error: ' + response.status + ' ' + response.statusText);
                return response.text();
            } else {
                deviceEmulator.log('Network response was not ok: ' + response.status + ' ' + response.statusText);
                M.util.js_complete('webview_request');
                throw new Error("Bad request");
            }
        }).then(function (txt) {
            deviceEmulator.webview.srcdoc = txt;
            deviceEmulator.log("Webview response is in frame. <a onclick=\"javascript: deviceEmulator.webview.removeAttribute('srcdoc'); deviceEmulator.webview.src='" + deviceEmulator.wwwRoot + '/login/logout.php' + "'; return false;\">Log out</a>", true);
            M.util.js_complete('webview_request');
        });
    },

    log: function(msg, isHtml = false) {
        var ele = document.createElement('p');
        ele.id = "message" + this.logCounter;
        if (isHtml) {
            ele.innerHTML = this.logCounter + ") " + msg;
        } else {
            ele.innerText = this.logCounter + ") " + msg;
        }
        this.output.appendChild(ele);
        this.logCounter++;
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
        var result = deviceEmulator.htmlspecialchars(JSON.stringify(jd, null, '  '));
        var pattern = new RegExp(deviceEmulator.wwwRoot + '/totara/mobile/pluginfile.php/[a-zA-Z0-9/_\\-%.]*', 'g');
        result = result.replace(pattern, function(match, offset, string) {
           var linkindex = deviceEmulator.linkCounter++;
           var nextform = deviceEmulator.formCounter + 1;
           return "<a title=\"link" + linkindex + "\" onclick=\"deviceEmulator.fetchFile('" + match + "');\" href=\"#form" + nextform + "\">" + match + "</a>";
        });
        return result;
    }

};

window.addEventListener('DOMContentLoaded', function() {
    deviceEmulator.init();
});
