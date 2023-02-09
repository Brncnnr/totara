/*
 *  This file is part of Totara TXP
 *
 *  Copyright (C) 2022 onwards Totara Learning Solutions LTD
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
define([],
    function() {
        var load = function(target_element, docs_html, docs_css, docs_js, browser_error) {
            var shadowDomSupported = (document.head.createShadowRoot || document.head.attachShadow);

            var container = document.getElementById(target_element);
            if (shadowDomSupported) {
                // Insert docs HTML, CSS and JS in Shadow DOM to isolate styles from rest of page.
                var shadow = container.attachShadow({mode: "open"});

                var shadowRoot = document.createElement('div');

                var css = document.createElement('style');
                css.textContent = docs_css;
                shadowRoot.appendChild(css);

                var js = document.createElement('script');
                js.textContent = docs_js;
                shadowRoot.appendChild(js);

                var html = document.createElement('div');
                html.innerHTML = docs_html;
                shadowRoot.appendChild(html);

                shadow.appendChild(shadowRoot);
            } else {
                // If shadow DOM not supported, render an error in container element instead.
                var browserError = document.createElement('div');
                browserError.innerHTML = browser_error;
                container.appendChild(browserError);
            }
        };

        return {
            load: load
        };
    }
);
