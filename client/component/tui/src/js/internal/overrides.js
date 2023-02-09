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

// eslint-disable-next-line no-unused-vars
import TotaraModuleStore from './TotaraModuleStore';

/**
 * Process inheritance for component override.
 *
 * This does not make any changes to the module store.
 * This method is intended to be called from the build system.
 *
 * @private
 * @param {TotaraModuleStore} modules Module store.
 * @param {object} component Component export
 * @param {string} parent ID of parent
 */
export function processComponentOverride(modules, component, parent) {
  if (typeof parent == 'string') {
    let parentName = parent;
    if (modules.isEvaluating(parentName)) {
      parent = modules._requirePrevious(parentName);
      if (!parent) {
        throw new Error(
          'Attempting to override component that does not exist: ' + parentName
        );
      }
    } else {
      parent = modules.require(parentName);
    }
    if (parent && parent.__esModule) {
      parent = parent.default;
    }
  }

  // inheritable false: the entire component will be overridden rather than
  // inheriting
  if (parent.inheritable === false) {
    return;
  }

  // components defined with Vue.extend cannot be inherited from.
  // it may not be possible to support Vue.extend, as it establishes an
  // inheritance chain.
  if (typeof parent == 'function') {
    return;
  }

  if (!component.__hasBlocks) {
    throw new Error(
      'components must be processed by tui-vue-loader to be able to ' +
        'override other components.'
    );
  }

  // inheritance (other than style inheritance) is not allowed when a
  // script block is specified
  if (component.__hasBlocks.script && !component.__extends) {
    return;
  }

  // use Vue's built in "extends" option to implement inheritance
  // see: https://vuejs.org/v2/api/#extends
  if (!component.extends) {
    component.extends = parent;
  }
}
