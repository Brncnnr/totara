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
 * @module engage_survey
 */

import { shallowMount } from '@vue/test-utils';
import SurveyIcon from 'engage_survey/components/icon/SurveyIcon';

describe('Engage SurveyIcon', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(SurveyIcon, {
      mocks: {
        $str(identifier, component, param) {
          return `[${identifier}, ${component} - ${param}]`;
        },
        $url(str) {
          return str;
        },
      },

      propsData: {
        imageAlt: 'My Alt Text',
      },
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
