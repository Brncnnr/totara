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

import { unloadedStrings, loadStrings } from '../i18n';
import { collectStrings } from '../i18n_vue_plugin';
import tui from '../tui';

let loadedOneOffs = false;

/**
 * Component requirement loading
 *
 * @private
 */
export default {
  /**
   * Get requirements from component
   *
   * @private
   * @param {object} component Component definition
   * @returns {object}
   *     Requirements object.
   *     `.any` property can be checked to determine if there are any.
   */
  get(component) {
    const compUnloadedStrings = unloadedStrings(collectStrings(component));
    return {
      unloadedStrings: compUnloadedStrings,
      oneOffs: !loadedOneOffs,
      any: compUnloadedStrings.length > 0 || !loadedOneOffs,
    };
  },

  /**
   * Load the specified requirements
   *
   * @private
   * @param {object} reqs
   * @returns {Promise}
   *     Promise resolving when loading has finished
   *     (whether successful or not)
   */
  load(reqs) {
    const promises = [];
    if (reqs.unloadedStrings) {
      promises.push(wrapReqTry(() => loadStrings(reqs.unloadedStrings)));
    }
    if (reqs.oneOffs) {
      const ErrorBoundary = tui.defaultExport(
        tui.require('tui/components/errors/ErrorBoundary')
      );
      const ErrorPageRender = tui.defaultExport(
        tui.require('tui/components/errors/ErrorPageRender')
      );
      const Loading = tui.defaultExport(
        tui.require('tui/components/loading/ComponentLoading')
      );
      promises.push(
        Promise.all([
          // load error/loading strings
          wrapReqTry(() =>
            loadStrings(
              collectStrings({
                components: {
                  ErrorBoundary,
                  ErrorPageRender,
                  Loading,
                },
              })
            )
          ),
        ]).then(() => {
          loadedOneOffs = true;
        })
      );
    }
    if (promises.length) {
      return Promise.all(promises);
    }
    return Promise.resolve();
  },
};

function logLoadError(e) {
  console.error('Error while loading requirements:', e);
}

// error handling:
// if a requirement fails, try and load it a second time, but if it still fails keep rendering anyway.
function wrapReqTry(fn) {
  return fn().catch(() =>
    // try again one more time
    fn().catch(logLoadError)
  );
}
