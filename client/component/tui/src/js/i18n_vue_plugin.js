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

import { unique } from './util';
import { getString, hasString } from './i18n';
import { makeScanner, getInheritedOption } from './vue_requirements';

/**
 * Vue plugin to enable i18n support through language strings
 */
const i18nPlugin = {
  install(Vue) {
    Vue.prototype.$str = function(key, totaraComponent, param) {
      totaraComponent = defaultTotaraComponent(totaraComponent);
      if (process.env.NODE_ENV !== 'production') {
        checkStringUsage(this, key, totaraComponent);
      }
      return getString(key, totaraComponent, param);
    };

    Vue.prototype.$tryStr = function(key, totaraComponent, param) {
      totaraComponent = defaultTotaraComponent(totaraComponent);
      return hasString(key, totaraComponent)
        ? getString(key, totaraComponent, param)
        : null;
    };

    Vue.prototype.$hasStr = function(key, totaraComponent) {
      totaraComponent = defaultTotaraComponent(totaraComponent);
      return hasString(key, totaraComponent);
    };

    Vue.config.optionMergeStrategies.langStrings = mergeLangStrings;
    Vue.config.optionMergeStrategies.__langStrings = mergeLangStrings;
  },
};

export default i18nPlugin;

/**
 * Collect all language string requirement definitions from the provided
 * component and its dependencies.
 *
 * The result of this function is cached, so is fine to call it multiple
 * times.
 *
 * @function
 * @param {object} component Component definition.
 * @return {object}
 */
const collectStringDefinitions = makeScanner({
  extract(component) {
    // langStrings is specified on the component manually.
    // __langStrings is added by the build system after parsing the
    // <lang-strings> block.
    const langStrings = getInheritedOption(
      component,
      'langStrings',
      mergeLangStrings
    );
    const __langStrings = getInheritedOption(
      component,
      '__langStrings',
      mergeLangStrings
    );
    if (langStrings || __langStrings) {
      return mergeLangStrings(langStrings, __langStrings);
    }
    return {};
  },
  mergeSubresult: mergeLangStrings,
  postprocess(result) {
    // deduplicate
    result = Object.assign({}, result);
    for (const key in result) {
      result[key] = unique(result[key]);
    }
    return result;
  },
  cache: true,
});

/**
 * Collect all language strings required by the provided component and its
 * dependencies.
 *
 * The result of this function is cached, so is fine to call it multiple
 * times.
 *
 * @function
 * @param {object} component Component definition.
 * @return {array}
 */
export function collectStrings(component) {
  return convertToArray(collectStringDefinitions(component));
}

function defaultTotaraComponent(component) {
  return component == null ? 'core' : component;
}

/**
 * Convert strings from obj format `{ totaraComponent: ['foo'] }` to array
 * format `[{ component: 'totaraComponent', key: 'foo' }]` as used by i18n.js
 *
 * @param {object} requests
 * @returns {object[]}
 */
function convertToArray(requests) {
  const objRequests = [];
  for (const comp in requests) {
    const keys = requests[comp];
    for (let i = 0; i < keys.length; i++) {
      objRequests.push({ component: comp, key: keys[i] });
    }
  }
  return objRequests;
}

/**
 * Merge two language string usage definitions.
 *
 * @param {?object} parentVal
 * @param {?object} childVal
 * @return {?object}
 */
function mergeLangStrings(parentVal, childVal) {
  if (!childVal) {
    return parentVal;
  }
  if (!parentVal) {
    return childVal;
  }

  return assignMergedLangStrings(Object.assign({}, parentVal), childVal);
}

/**
 * Merge lang strings in each category, assigning the result back on target.
 *
 * @param {object} target
 * @param {object} obj
 * @returns {object} target
 */
function assignMergedLangStrings(target, obj) {
  for (const key in obj) {
    if (target[key]) {
      target[key] = target[key].concat(obj[key]);
    } else {
      target[key] = obj[key];
    }
  }
  return target;
}

/**
 * Check if the specified lang string is listed in the lang string usage
 * definition.
 *
 * @param {?object} langStrings
 * @param {string} key
 * @param {string} totaraComponent
 * @returns {boolean}
 */
const hasLangString = (langStrings, key, totaraComponent) =>
  !!langStrings &&
  langStrings[totaraComponent] !== undefined &&
  langStrings[totaraComponent].indexOf(key) !== -1;

/**
 * Check if the specified lang string was declared as a dependency on the
 * vue instance and warn in the console if not.
 *
 * @param {Vue} vm
 * @param {string} key
 * @param {string} totaraComponent
 */
/* istanbul ignore next */
let checkStringUsage = () => {};

/* istanbul ignore else */
if (process.env.NODE_ENV !== 'production') {
  checkStringUsage = (vm, key, totaraComponent) => {
    if (
      !hasLangString(vm.$options.langStrings, key, totaraComponent) &&
      !hasLangString(vm.$options.__langStrings, key, totaraComponent)
    ) {
      console.warn(
        `$str('${key}', '${totaraComponent}'): the specified language ` +
          'string is not listed in the <lang-strings> block on the ' +
          'current component. Add it to <lang-strings> if it is a ' +
          'fixed string, or use another API such as getString from ' +
          'totara_core/i18n if it is manually loaded.'
      );
    }
  };
}
