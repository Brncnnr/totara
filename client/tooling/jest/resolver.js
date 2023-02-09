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

const { resolveRequest } = require('../lib/resolution');

module.exports = (request, options) => {
  // handle frankenstyle imports
  if (request[0] != '.') {
    const req = resolveRequest(request);
    if (req) {
      return options.defaultResolver(req, options);
    }
  }
  return options.defaultResolver(request, options);
};
