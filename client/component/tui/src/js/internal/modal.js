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

import Vue from 'vue';
import { memoize } from '../util';

const getModalPresenter = memoize(() =>
  tui.defaultExport(tui.require('tui/components/modal/ModalPresenter'))
);

/**
 * Show a modal.
 *
 * @param {object} opts
 * @param {*} opts.component Vue component for modal.
 * @param {object} opts.props
 * @param {function} opts.onClose Called when modal closes.
 * @param {function} opts.onCloseComplete Called when modal has finished closing.
 * @returns {{ close: Function }}
 */
export async function showModal({
  component,
  props,
  onClose,
  onCloseComplete,
}) {
  const el = document.createElement('span');
  document.body.appendChild(el);

  const container = { component, props };
  Object.preventExtensions(container);

  let vm;
  let cancelled = false;

  const modalInterface = {
    close() {
      if (vm) {
        vm.requestClose();
      } else {
        cancelled = true;
        if (onClose) onClose();
        if (onCloseComplete) onCloseComplete();
      }
    },
  };

  tui.loadRequirements(component).then(() => {
    if (cancelled) {
      return;
    }

    vm = new ModalPresenterWrap({
      propsData: { container },
    });
    vm.$mount(el);

    vm.show();

    vm.$on('close', e => {
      if (onClose) onClose(e);
    });

    vm.$on('close-complete', e => {
      vm.$destroy();
      if (onCloseComplete) onCloseComplete(e);
    });
  });

  return modalInterface;
}

const ModalPresenterWrap = Vue.extend({
  props: { container: { type: Object } },
  data: () => ({ result: null, open: false }),
  methods: {
    show() {
      this.open = true;
    },
    requestClose(e) {
      this.result = e;
      this.open = false;
      this.$emit('close', e);
    },
    closeComplete() {
      this.$emit('close-complete', this.result);
    },
  },
  render(h) {
    return h(
      getModalPresenter(),
      {
        props: { open: this.open },
        on: {
          'request-close': this.requestClose,
          'close-complete': this.closeComplete,
        },
      },
      [h(this.container.component, { props: this.container.props })]
    );
  },
});
