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
 * @author Steve Barnett <steve.barnett@totaralearning.com>
 * @module totara_notification
 */

import { shallowMount } from '@vue/test-utils';
import NotificationPreferenceModal from 'totara_notification/components/modal/NotificationPreferenceModal';
import { SCHEDULE_TYPES } from '../../../../internal/notification_preference';

let wrapper;

describe('NotificationPreferenceModal', () => {
  beforeEach(() => {
    wrapper = shallowMount(NotificationPreferenceModal, {
      propsData: {
        title: 'Modal title',
        contextId: 1,
        resolverClassName: 'eventClassName',
        validScheduleTypes: [SCHEDULE_TYPES.ON_EVENT],
        availableRecipients: [
          {
            class_name: 'test_class',
            name: 'test',
          },
        ],
        defaultDeliveryChannels: [
          {
            component: 'email',
            label: 'Email',
            is_enabled: true,
            parent_component: null,
            is_sub_delivery_channel: false,
          },
        ],
      },
    });
  });

  it('should handleSubmit with empty formValues', () => {
    let formValue = {};

    wrapper.vm.handleSubmit(formValue);

    expect(wrapper.emitted('form-submit')).toEqual([
      [
        {
          resolver_class_name: 'eventClassName',
        },
      ],
    ]);
  });

  it('should handleSubmit with existing formValues', () => {
    let formValue = { another: 'value' };

    wrapper.vm.handleSubmit(formValue);

    expect(wrapper.emitted('form-submit')).toEqual([
      [
        {
          another: 'value',
          resolver_class_name: 'eventClassName',
        },
      ],
    ]);
  });
});
