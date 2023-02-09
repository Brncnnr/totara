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

const path = require('path');
const { rootDir } = require('../lib/common');
const { resolveStaticAlias } = require('../lib/resolution');

class TuiAliasPlugin {
  apply(resolver) {
    const target = resolver.ensureHook('resolve');
    resolver
      .getHook('described-resolve')
      .tapAsync('TuiAliasPlugin', (data, resolveContext, callback) => {
        const result = resolveStaticAlias(data.request);
        if (result) {
          return resolver.doResolve(
            target,
            { ...data, request: path.join(rootDir, result) },
            null,
            resolveContext,
            callback
          );
        }

        callback();
      });
  }
}

module.exports = TuiAliasPlugin;
