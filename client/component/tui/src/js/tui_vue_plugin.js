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
import { uniqueId, totaraUrl } from './util';
import { trapFocusOnTab } from './dom/focus';

const hasOwnProperty = Object.prototype.hasOwnProperty;

export default {
  install(Vue) {
    // Remove unneccesary production mode note in console
    Vue.config.productionTip = false;

    Object.defineProperties(Vue.prototype, {
      /**
       * Get a unique ID for the current component instance
       */
      uid: {
        get() {
          // lazily generate unique ids when a component needs one
          return this.$_tui_uid || (this.$_tui_uid = 'uid-' + uniqueId());
        },
        enumerable: true,
        configurable: true,
      },
    });

    /**
     * Get a globally unique ID string scoped to within this component.
     *
     * Equivalent to this.uid + '-' + id.
     *
     * @param {string=} id
     */
    Vue.prototype.$id = function(id) {
      return id ? this.uid + '-' + id : this.uid;
    };

    /**
     * Get a reference (#) to an ID string.
     *
     * Prepends # to the result of $id().
     *
     * @param {string=} id
     */
    Vue.prototype.$idRef = function(id) {
      return '#' + this.$id(id);
    };

    Vue.prototype.$url = totaraUrl;

    // expose window as $window to avoid using a global
    /* istanbul ignore else */
    if (typeof window !== 'undefined') {
      Vue.prototype.$window = window;
    }

    // trap focus inside the specified element
    const trapFocusHandlers = new WeakMap();
    Vue.directive('trap-focus', {
      inserted(el) {
        const handler = e => {
          if (e.key == 'Tab') {
            trapFocusOnTab(el, e);
          }
        };
        document.addEventListener('keydown', handler);
        trapFocusHandlers.set(el, handler);
      },

      unbind(el) {
        const handler = trapFocusHandlers.get(el);
        document.removeEventListener('keydown', handler);
        trapFocusHandlers.delete(el);
      },
    });

    // toggle class if inner node has focus
    const focusHandlers = new WeakMap();
    const focusHandlerClass = 'tui-focusWithin';
    Vue.directive('focus-within', {
      bind(el, binding) {
        if (hasOwnProperty.call(binding, 'value') && !binding.value) {
          return;
        }
        let hasFocusWithin = false;
        const inHandler = () => {
          let olds = document.getElementsByClassName(focusHandlerClass);

          Array.prototype.forEach.call(olds, old => {
            if (!el.contains(old)) {
              focusHandlers.get(old).hasFocusWithin = false;
              old.classList.remove(focusHandlerClass);
            }
          });
          el.classList.add(focusHandlerClass);
          focusHandlers.get(el).hasFocusWithin = true;
        };
        const outHandler = () => {
          el.classList.remove(focusHandlerClass);
          focusHandlers.get(el).hasFocusWithin = false;
        };

        el.addEventListener('focusin', inHandler);
        el.addEventListener('focusout', outHandler);
        focusHandlers.set(el, { inHandler, outHandler, hasFocusWithin });
      },

      // Class is removed when updating component, readd if it was active
      componentUpdated: function(el) {
        if (!focusHandlers.has(el)) {
          return;
        }
        if (focusHandlers.get(el).hasFocusWithin) {
          el.classList.add(focusHandlerClass);
        }
      },

      unbind(el) {
        if (!focusHandlers.has(el)) {
          return;
        }
        const elEvents = focusHandlers.get(el);
        el.removeEventListener('focusin', elEvents.inHandler);
        el.removeEventListener('focusout', elEvents.outHandler);
        focusHandlers.delete(el);
      },
    });

    // Passthrough component, renders its default slot without any wrapping
    // element.
    // Useful for e.g. <component :is="var ? 'MyWrapper' : 'passthrough'">
    Vue.component('passthrough', {
      functional: true,
      render(h, { scopedSlots }) {
        return scopedSlots.default && scopedSlots.default();
      },
    });

    Vue.component('render', {
      functional: true,
      // eslint-disable-next-line vue/require-prop-types
      props: ['vnode'],
      render: (h, { props }) => props.vnode,
    });
  },
};
