/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module totara_playlist
 */

import Adder from 'totara_playlist/components/contribution/Adder';
import { shallowMount } from '@vue/test-utils';

describe('Adder', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(Adder, {
      propsData: {
        playlistId: 5,
        units: 0,
        gridDirection: 'horizontal',
      },
    });
  });

  it('canAdd computed works as expected', () => {
    expect(wrapper.vm.canAdd).toBeFalse();

    wrapper.vm.showWarningModal = true;
    expect(wrapper.vm.canAdd).toBeFalse();

    wrapper.setProps({ showAdder: true });
    expect(wrapper.vm.canAdd).toBeFalse();

    wrapper.vm.showWarningModal = false;
    expect(wrapper.vm.canAdd).toBeTrue();
  });
});
