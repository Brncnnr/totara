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
 * @author Matthias Bonk <matthias.bonk@totaralearning.com>
 * @module pathway_manual
 */

import { shallowMount } from '@vue/test-utils';
import component from 'pathway_manual/components/RatingPopover';
import { mocks } from './mocks';
let wrapper;

const props = {
  scale: {
    values: [
      {
        id: '123',
        name: 'Competent',
      },
    ],
  },
  compId: '321',
  scaleValueId: '1',
  comment: 'Test comment',
};

describe('RatingPopover', () => {
  it('Checks snapshot', () => {
    wrapper = shallowMount(component, { mocks: mocks, propsData: props });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks updateRating method', () => {
    const vueHandler = jest.fn();
    const closeFn = jest.fn();
    const wrapper = shallowMount(component, {
      mocks: mocks,
      propsData: props,
      listeners: {
        'update-rating': vueHandler,
      },
    });
    wrapper.vm.updateRating(closeFn);
    expect(vueHandler).toHaveBeenCalled();
    expect(vueHandler.mock.calls[0][0]).toEqual({
      scale_value_id: '1',
      comment: 'Test comment',
    });
    expect(closeFn).toHaveBeenCalled();
  });

  it('Checks deleteRating method', () => {
    const vueHandler = jest.fn();
    const closeFn = jest.fn();
    const wrapper = shallowMount(component, {
      mocks: mocks,
      propsData: props,
      listeners: {
        'delete-rating': vueHandler,
      },
    });
    wrapper.vm.deleteRating(closeFn);
    expect(vueHandler).toHaveBeenCalled();
    expect(closeFn).toHaveBeenCalled();
  });
});
