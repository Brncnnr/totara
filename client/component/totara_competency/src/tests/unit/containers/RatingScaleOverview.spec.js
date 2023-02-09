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
 * @module totara_competency
 */

import { shallowMount } from '@vue/test-utils';
import component from 'totara_competency/components/RatingScaleOverview';
import { mocks } from './mocks';

const props = {
  reverseValues: false,
  scale: {
    values: [
      {
        id: '6',
        name: 'Extremely competent',
        proficient: true,
        description: '<b>No doubt this fella is competent</b>',
      },
      {
        id: '7',
        name: 'Competent',
        proficient: true,
        description: '<i>There is some merit co call it competent</i>',
      },
      {
        id: '8',
        name: 'Competent on Tuesdays',
        proficient: true,
        description: 'Competent, but only on Tuesdays, do not ask why.',
      },
      {
        id: '9',
        name: 'Not competent on Tuesdays',
        proficient: true,
        description: 'Not competent, but only on Tuesdays, do not ask why.',
      },
      {
        id: '10',
        name: 'Below average',
        proficient: false,
        description:
          'We can not call it competent for just yet, maybe wait for Tuesday.',
      },
      {
        id: '11',
        name: 'Not competent',
        proficient: false,
        description: 'Why does this value even exist?',
      },
    ],
  },
};

describe('RatingScaleOverview', () => {
  it('Checks snapshot - with descriptions', () => {
    let wrapper = shallowMount(component, {
      mocks: mocks,
      propsData: Object.assign(props, {
        showDescriptions: true,
      }),
    });
    expect(wrapper.element).toMatchSnapshot('withDescriptions');
  });
  it('Checks snapshot - without descriptions', () => {
    let wrapper = shallowMount(component, {
      mocks: mocks,
      propsData: Object.assign(props, {
        showDescriptions: false,
      }),
    });
    expect(wrapper.element).toMatchSnapshot('withoutDescriptions');
  });
});
