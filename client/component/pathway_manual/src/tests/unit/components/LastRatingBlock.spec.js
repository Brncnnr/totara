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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @module pathway_manual
 */

import component from 'pathway_manual/components/LastRatingBlock';
import { mocks } from './mocks';
import { shallowMount } from '@vue/test-utils';

const props = {
  currentUserId: 2,
};
const todayDate = new Date();

describe('LastRatingBlock', () => {
  it('Checks snapshot - for same user', () => {
    let wrapper = shallowMount(component, {
      mocks: mocks,
      propsData: Object.assign(props, {
        latestRating: {
          rater: { id: props.currentUserId.toString(), fullname: 'Same User' },
          date: '30 January 2020',
          date_iso8601: '2020-01-30T16:14:42+1300',
          scale_value: { id: '5', name: 'Not competent' },
        },
      }),
    });
    expect(wrapper.element).toMatchSnapshot('sameUser');
  });
  it('Checks snapshot - for different user', () => {
    let wrapper = shallowMount(component, {
      mocks: mocks,
      propsData: Object.assign(props, {
        latestRating: {
          rater: { id: '3', fullname: 'Different User' },
          date: '30 January 2020',
          date_iso8601: '2020-01-30T16:14:42+1300',
          scale_value: { id: '5', name: 'Not competent' },
        },
      }),
    });
    expect(wrapper.element).toMatchSnapshot('differentUser');
  });
  it('Checks snapshot - for date that is today', () => {
    let wrapper = shallowMount(component, {
      mocks: mocks,
      propsData: Object.assign(props, {
        latestRating: {
          rater: { id: '3', fullname: 'Different User' },
          date: '30 January 2020',
          date_iso8601: todayDate,
          scale_value: { id: '5', name: 'Not competent' },
        },
      }),
    });
    expect(wrapper.element).toMatchSnapshot('dateToday');
  });
  it('Checks snapshot - for empty scale value', () => {
    let wrapper = shallowMount(component, {
      mocks: mocks,
      propsData: Object.assign(props, {
        latestRating: {
          rater: { id: '3', fullname: 'Different User' },
          date: '30 January 2020',
          date_iso8601: todayDate,
          scale_value: null,
        },
      }),
    });
    expect(wrapper.element).toMatchSnapshot('emptyScaleValue');
  });
});
