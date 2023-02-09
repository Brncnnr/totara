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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module totara_engage
 */

import ContributeModal from 'totara_engage/components/modal/ContributeModal';
import { shallowMount } from '@vue/test-utils';

jest.mock('tui/apollo_client', () => null);

describe('Engage ContributeModal', function() {
  let wrapper = null;

  // Mocks the component
  ContributeModal.components['engage_survey'] = {
    render(h) {
      return h('div');
    },
  };

  ContributeModal.components['engage_article'] = {
    render(h) {
      return h('div');
    },
  };

  beforeAll(function() {
    wrapper = shallowMount(ContributeModal, {
      mocks: {
        $str(id, component) {
          return `${id}, ${component}`;
        },
        $apollo: {},
        stubs: ['ButtonIcon'],
      },

      propsData: {
        excludeModals: [],
        adder: {
          text: 'select an existing resource',
          destination: 'some where you like',
        },
      },

      data() {
        return {
          selectedTab: 'engage_survey',
          modals: [
            {
              label: 'cc',
              component: 'CreateArticle',
              expandable: false,
              id: 'engage_article',
            },
            {
              label: 'cd',
              component: 'CreateSurvey',
              expandable: true,
              id: 'engage_survey',
            },
          ],
        };
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.element).toMatchSnapshot();
  });
});
