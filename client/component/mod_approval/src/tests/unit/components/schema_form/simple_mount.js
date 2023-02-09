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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module mod_approval
 */
import { uniqueId } from 'tui/util';
import { mount } from '@vue/test-utils';

export default function simpleMount(component, propsData) {
  return mount(component, {
    propsData,
    mocks: {
      $apollo: {
        addSmartQuery: function() {},
        loading: false,
      },

      $_tui_uid: null,

      uid() {
        return this.$_tui_uid || (this.$_tui_uid = 'uid-' + uniqueId());
      },

      $id(id) {
        return id ? this.uid() + '-' + id : this.uid();
      },
    },
    provide: () => ({
      reformScope: {
        register: jest.fn(),
        unregister: jest.fn(),
        getValue: jest.fn(),
        updateRegistration: jest.fn(),
        getError: jest.fn(),
        getInputName: jest.fn(),
      },
    }),
  });
}
