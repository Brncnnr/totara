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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module totara_engage
 */

import { mount } from '@vue/test-utils';
import RecipientsSelector from 'totara_engage/components/form/access/RecipientsSelector';

describe('Engage RecipientsSelector', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = mount(RecipientsSelector, {
      mocks: {
        $str(id, component) {
          return `${id}, ${component}`;
        },
        $id() {
          return 'abc123';
        },
      },
      propsData: {
        itemId: 0,
        component: 'engage_resource',
        access: 'PUBLIC',
        selectedRecipients: [],
        owned: false,
      },
    });
  });

  it('matches snapshot', () => {
    expect(wrapper).toMatchSnapshot();
  });

  it('selecting a user works as expected', () => {
    let item = {
      area: 'user',
      alreadyshared: false,
      user: {
        card_display: {
          display_fields: [
            {
              label: 'Full name',
              value: 'User 1',
            },
          ],
        },
      },
    };
    wrapper.vm.select(item);
    expect(wrapper.emitted()['pick-recipient'].length).toBe(1);

    // If a user is already selected then they should not be selected again
    let item2 = {
      area: 'user',
      alreadyshared: true,
      user: {
        card_display: {
          display_fields: [
            {
              label: 'Full name',
              value: 'User 2',
            },
          ],
        },
      },
    };
    wrapper.vm.select(item2);
    expect(wrapper.emitted()['pick-recipient'].length).toBe(1);

    // Test other recipient.
    let item3 = {
      area: 'other',
      alreadyshared: false,
      other: {
        fullname: 'Other 1',
      },
    };
    wrapper.vm.select(item3);
    expect(wrapper.emitted()['pick-recipient'].length).toBe(2);
  });
});
