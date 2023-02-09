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

const rimraf = require('rimraf');
const path = require('path');
const fs = require('fs');
const { clientDir } = require('../lib/common');

function clean(opts = {}) {
  const componentDir = path.join(clientDir, 'component');
  const components = fs.readdirSync(componentDir).filter(x => {
    return !opts.components || opts.components.includes(x);
  });
  components.forEach(component => {
    const buildDir = path.join(componentDir, component, 'build');
    if (fs.existsSync(buildDir)) {
      rimraf.sync(buildDir, { disableGlob: true });
    }
  });
}

module.exports = {
  clean,
};