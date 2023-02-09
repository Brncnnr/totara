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
 * @module engage_survey
 */

import SurveyResultBody from 'engage_survey/components/card/SurveyResultBody';
import { shallowMount } from '@vue/test-utils';

describe('Engage SurveyCardBody', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(SurveyResultBody, {
      propsData: {
        name: '',
        questions: [{ votes: 5, id: 5, options: [], answertype: 'blah' }],
        access: 'PUBLIC',
        labelId: 'lbl',
        resourceId: '5',
        url: 'url',
      },
      computed: {
        voteMessage() {
          return 'nah';
        },
      },
      mocks: {
        $url(x) {
          return x;
        },
      },
    });
  });

  it('displayName works as expected', () => {
    expect(wrapper.vm.displayName).toBe('');

    let name = 'short name';
    wrapper.setProps({ name });
    expect(wrapper.vm.displayName).toBe(name);

    name = 'longer name that should be logn enough to trigger a larger value';
    wrapper.setProps({
      name,
    });
    expect(wrapper.vm.displayName).toContain(name.substr(0, 30));
    expect(wrapper.vm.displayName).toContain(String.fromCharCode(8230));
    expect(wrapper.vm.displayName).not.toContain(name.substr(35, 10));
  });
});
