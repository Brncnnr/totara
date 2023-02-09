/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

const tui = require('./tui').default;

let root;

if (typeof window !== 'undefined') {
  root = window;
} else if (typeof global !== 'undefined') {
  root = global;
} else {
  root = {};
}

root.tui = tui;

function scan() {
  tui.scan();
}

if (typeof window !== 'undefined') {
  window.addEventListener('DOMContentLoaded', scan);
  document.addEventListener('nodes-updated', e => {
    if (e.detail && e.detail.nodes && Array.isArray(e.detail.nodes)) {
      e.detail.nodes.forEach(node => tui.scan(node));
    } else {
      tui.scan();
    }
  });

  if (process.env.NODE_ENV == 'development') {
    const { handleLoadError } = require('./internal/error_overlay');
    if (window.loadErrors) {
      window.loadErrors.forEach(handleLoadError);
    }
    window.loadErrors = { push: handleLoadError };
  }
}
