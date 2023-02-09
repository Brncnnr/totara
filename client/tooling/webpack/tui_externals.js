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

const { clientDir } = require('../lib/common');
const path = require('path');
const { resolveStaticAlias, resolveRequest } = require('../lib/resolution');
const coreTuiJson = require(path.join(
  clientDir + 'component/tui/src/tui.json'
));

/**
 * Make a webpack externals specifier for the provided request that resolves in
 * to the Tui module store.
 *
 * https://webpack.js.org/configuration/externals/#externals
 *
 * @param {string} request
 * @returns {string}
 */
function makeTuiExternal(request) {
  return `root tui.require(${JSON.stringify(request)})`;
}

/**
 * Webpack externals entry to redirect module resolution to Tui module store.
 *
 * https://webpack.js.org/configuration/externals/#function
 *
 * @returns {import('webpack/declarations/WebpackOptions').ExternalItem}
 */
module.exports = function tuiExternals() {
  return ({ context, request }, callback) => {
    // non-relative module request, without any loader options
    if (request[0] != '.' && !request.includes('?') && !request.includes('!')) {
      let nativeResolve = false;

      // always redirect imports for code exposed through tui core
      // exposeNativeModules (e.g. vue)
      if (
        !/[/\\]client[/\\]component[/\\]tui[/\\]/.test(context) &&
        coreTuiJson.exposeNodeModules.includes(request)
      ) {
        return callback(null, makeTuiExternal(request));
      }

      // Only core can use code from node_modules
      if (
        /[/\\]client[/\\]component[/\\](?:tui|tui_\w+|ext_\w+)[/\\]src(?:[/\\]|$)/.test(
          context
        ) &&
        !resolveRequest(request)
      ) {
        nativeResolve = true;
      }

      // code in node_modules or thirdparty always uses native resolver
      if (/[/\\](?:node_modules|thirdparty)[/\\]/.test(context)) {
        nativeResolve = true;
      }

      // allow babel runtime through
      if (/^@babel[/\\]/.test(request)) {
        nativeResolve = true;
      }

      if (resolveStaticAlias(request)) {
        nativeResolve = true;
      }

      // if not resolving natively, transform to a tui.require() call
      // this supports our bundle exports plus `exposeNodeModules`
      if (!nativeResolve) {
        return callback(null, makeTuiExternal(request));
      }
    }

    callback();
  };
};
