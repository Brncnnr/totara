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
 * @module totara_core
 */

const fs = require('fs');
const path = require('path');
const hooks = require('../lib/hooks');
const { rootDir } = require('../lib/common');

hooks.hooks.forEach(hook => {
  const hookPath = path.join(rootDir, '.git/hooks/' + hook);
  let content;
  try {
    content = fs.readFileSync(hookPath, 'utf-8');
  } catch (e) {
    // no hook, skip
    return;
  }
  if (content.includes('# totara hook')) {
    console.log('Removing ' + hook + ' hook');
    fs.unlinkSync(hookPath);
  } else {
    console.log(hook + ' hook was installed by another tool, skipping');
  }
});
