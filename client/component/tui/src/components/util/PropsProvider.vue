<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module tui
-->

<script>
import { cloneVNode, mergeListeners } from '../../js/internal/vnode';

/**
 * Functional component that modifies vnodes of its children, adding props and
 * event listeners.
 *
 * Note: native elements are ignored (not modified).
 *
 * Use cases:
 *   * Tightly coupled parent->child component relationships, for example
 *     <Radio>s in a <RadioGroup>.
 *   * Applying native listeners to components from renderless components
 *     (e.g. Draggable), until Vue 3 arrives and solves this.
 */
export default {
  functional: true,

  props: {
    provide: {
      type: [Function, Object],
      required: true,
    },
  },

  render(h, { props, children }) {
    return children.map(vnode => {
      vnode = cloneVNode(vnode);
      const opts = vnode.componentOptions;
      if (!opts) return vnode;
      const info = { props: opts.propsData || {} };
      const provided =
        typeof props.provide === 'function'
          ? props.provide(info)
          : props.provide;

      // set provided props...
      if (provided.props) {
        opts.propsData = Object.assign({}, provided.props, opts.propsData);
      }

      if (provided.listeners) {
        opts.listeners = mergeListeners(opts.listeners, provided.listeners);
      }

      if (provided.nativeListeners) {
        // vue event listeners go on `componentOptions.listeners`
        // native listeners go on `data.on`
        // https://github.com/vuejs/vue/blob/b9de23b1008b52deca7e7df40843e318a42f3f53/src/core/vdom/create-component.js#L166
        vnode.data.on = mergeListeners(vnode.data.on, provided.nativeListeners);
      }

      return vnode;
    });
  },
};
</script>
