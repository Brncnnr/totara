<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module samples
-->
<template>
  <div class="tui-forcedDeliveryChannelsTable">
    <ForceDeliveryChannels
      :default-delivery-channels="deliveryChannels"
      :forced-delivery-channels="forcedDeliveryChannels"
      @update-forced-delivery-channels="forcedDeliveryChannels = $event"
    />

    <hr />

    <p>
      <!-- Note that this is intentionally for developers only-->
      Data Output
    </p>

    <pre>
      {{ forcedDeliveryChannelsString }}
    </pre>
  </div>
</template>

<script>
import ForceDeliveryChannels from 'totara_notification/components/form/field/ForceDeliveryChannels';
export default {
  components: {
    ForceDeliveryChannels,
  },

  data() {
    return {
      forcedDeliveryChannels: ['some_class_1', 'some_class_3'],
      deliveryChannels: [
        {
          component: 'some_class_1',
          label: 'Sample Class 1',
          is_enabled: true,
          is_sub_delivery_channel: false,
          parent_component: null,
        },
        {
          component: 'some_class_2',
          label: 'Sample Class 2',
          is_enabled: false,
          is_sub_delivery_channel: false,
          parent_component: null,
        },
        {
          component: 'some_class_3',
          label: 'Sample Class 3',
          is_enabled: true,
          is_sub_delivery_channel: false,
          parent_component: null,
        },
        {
          component: 'some_class_4',
          label: 'Sample Class 4',
          is_enabled: true,
          is_sub_delivery_channel: true,
          parent_component: 'some_class_3',
        },
        {
          component: 'some_class_5',
          label: 'Sample Class 5',
          is_enabled: true,
          is_sub_delivery_channel: true,
          parent_component: 'some_class_2',
        },
      ],
    };
  },

  computed: {
    forcedDeliveryChannelsString() {
      return JSON.stringify(this.forcedDeliveryChannels);
    },
  },
};
</script>
