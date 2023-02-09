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

import { memoizeWeak } from './util';

/**
 * Create a funtion that will recursively scan a Vue component and its
 * subcomponents to extract values.
 *
 * @param {object} options
 * @param {function} options.extract
 *     Function that takes a component definition and returns data to extract.
 * @param {?boolean} options.cache
 *     Cache the result of the computation for each component?
 * @param {function} options.mergeSubresult
 *     Called with result and subresult for each subcomponent, and returns
 *     processed result.
 *     OK to mutate result in this function.
 * @param {?function} options.postprocess
 *     Called to process result before it is cached/returned.
 *     Takes result and returns processed result.
 *     OK to mutate result in this function.
 * @return {function}
 */
export function makeScanner(options) {
  let scan = component => {
    // handle components defined with Vue.extend()
    if (component.options) {
      component = component.options;
    }

    // extract value from component
    let result = options.extract(component);

    // scan component we are extending
    if (component.extends) {
      result = options.mergeSubresult(result, scan(component.extends));
    }

    // scan child components
    if (component.components) {
      for (const key in component.components) {
        if (!Object.prototype.hasOwnProperty.call(component.components, key)) {
          continue;
        }
        const comp = component.components[key];
        if (comp) {
          result = options.mergeSubresult(result, scan(comp));
        }
      }
    }

    // postprocess - used to deduplicate etc
    if (options.postprocess) {
      result = options.postprocess(result);
    }

    return result;
  };

  if (options.cache) {
    // assign over original reference so recursive usage uses the memoized
    // version of the function too
    scan = memoizeWeak(scan);
  }

  return scan;
}

/**
 * Get the specified option from a component, checking the components it
 * extends from too and merging according to the specified strategy.
 *
 * @param {object} component
 * @param {string} name
 * @param {function=} mergeStrategy
 * @return {*}
 */
export function getInheritedOption(component, name, mergeStrategy) {
  // short circuit:
  // if the component doesn't extend anything don't bother with merging logic
  // etc, just return the value
  if (!component.extends) {
    return component[name];
  }

  const parentValue = getInheritedOption(
    component.extends,
    name,
    mergeStrategy
  );

  const currentValue = component[name];

  if (mergeStrategy) {
    return mergeStrategy(parentValue, currentValue);
  } else {
    // default merge strategy - use current value if exists, otherwise use
    // parent value
    return currentValue !== undefined ? currentValue : parentValue;
  }
}
