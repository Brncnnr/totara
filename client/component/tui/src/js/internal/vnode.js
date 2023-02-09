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

import { pick } from '../../js/util';

const DATA_KEYS = [
  'class',
  'staticClass',
  'style',
  'staticStyle',
  'attrs',
  'props',
  'domProps',
  'on',
  'nativeOn',
  'directives',
  'scopedSlots',
  'slot',
  'ref',
  'key',
];

/**
 * Extract data obect from VNode
 *
 * @param {VNode} vnode
 * @param {boolean} isComp Is component (vs DOM node)
 */
function extractData(vnode, isComp) {
  const data = pick(vnode.data || {}, DATA_KEYS);
  if (isComp) {
    const cOpts = vnode.componentOptions;
    Object.assign(data, {
      props: cOpts.propsData,
      on: cOpts.listeners,
    });
  }

  return data;
}

/**
 * Create a shallow clone of a Vue VNode
 *
 * @param {VNode} vnode
 */
export function cloneVNode(vnode, { cloneChildren = false } = {}) {
  // use the context that the original vnode was created in.
  const h = vnode.context && vnode.context.$createElement;
  const isComp = !!vnode.componentOptions;
  const isText = !vnode.tag;
  const children = isComp ? vnode.componentOptions.children : vnode.children;

  if (isText) return vnode.text;

  const data = extractData(vnode, isComp);

  const tag = isComp ? vnode.componentOptions.Ctor : vnode.tag;

  const childNodes = cloneChildren
    ? children
      ? children.map(c => cloneVNode(c))
      : undefined
    : children;

  const cloned = h(tag, data, childNodes);

  if (isComp) {
    cloned.componentOptions.tag = vnode.componentOptions.tag;
  }

  return cloned;
}

/**
 * Normalize listener to common format (array)
 *
 * @param {Function|Function[]} val
 * @returns {Function[]}
 */
export function normalizeListenerValue(val) {
  if (typeof val === 'object' && val.fns) {
    return normalizeListenerValue(val.fns);
  }
  return Array.isArray(val) ? val : [val];
}

/**
 * Merge two listener values
 *
 * @param {Function|Function[]} a
 * @param {Function|Function[]} b
 */
function mergeListenerValues(a, b) {
  return normalizeListenerValue(a).concat(normalizeListenerValue(b));
}

/**
 * Merge two listeners objects.
 *
 * @param {?Object} a
 * @param {?Object} b
 * @return {Object}
 */
export function mergeListeners(a, b) {
  let final = a ? Object.assign({}, a) : {};
  if (b) {
    for (var key in b) {
      if (final[key]) {
        final[key] = mergeListenerValues(final[key], b[key]);
      } else {
        final[key] = b[key];
      }
    }
  }
  return final;
}
