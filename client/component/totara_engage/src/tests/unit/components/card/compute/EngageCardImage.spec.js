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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @module totara_engage
 */

import { shallowMount } from '@vue/test-utils';
import EngageCardImage from 'totara_engage/components/card/compute/EngageCardImage';

describe('EngageCardImage', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(EngageCardImage, {
      propsData: {
        cardAttribute: {
          imagetuicomponent: 'test-image-component',
          component: 'test-component',
        },
      },
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
