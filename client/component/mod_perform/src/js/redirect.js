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
 * @author Jaron Steenson <jaron.steenson@totaralearning.com>
 * @module mod_perform
 * @deprecated since Totara 17.0
 */

/**
 * Perform a redirect with via post.
 * There is no real way to do a post request redirect in js
 * This creates a hidden form and submits it.
 *
 * @param {String} url
 * @param {Object} params
 * @deprecated since Totara 17.0, use the method in tui/src/js/dom/form.js instead
 */
export function redirectWithPost(url, params) {
  const hiddenForm = document.createElement('form');
  hiddenForm.style.display = 'hidden';
  hiddenForm.action = url;
  hiddenForm.method = 'post';

  Object.entries(params).forEach(entry => {
    const name = entry[0];
    const value = entry[1];

    const input = document.createElement('input');
    input.name = name;

    if (typeof value === 'boolean') {
      input.type = 'checkbox';
      input.checked = value;
    } else {
      input.type = 'text';
      input.value = value;
    }

    hiddenForm.appendChild(input);
  });

  document.body.appendChild(hiddenForm);

  hiddenForm.submit();
}
