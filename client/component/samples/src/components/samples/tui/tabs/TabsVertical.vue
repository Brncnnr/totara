<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module samples
-->

<template>
  <div>
    Vertical tabs

    <SamplesExample>
      <Tabs direction="vertical">
        <Tab
          v-for="tab in tabs"
          :id="tab.id"
          :key="tab.id"
          :name="tab.name"
          :disabled="disabledId === tab.id"
        >
          {{ tab.content }}
        </Tab>
      </Tabs>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import Tab from 'tui/components/tabs/Tab';
import Tabs from 'tui/components/tabs/Tabs';

export default {
  components: {
    SamplesCode,
    SamplesExample,
    Tab,
    Tabs,
  },

  data() {
    const content = x => 'tab content ' + x;
    return {
      tabs: [
        { id: 1, name: 'Attendees', content: content(1) },
        { id: 2, name: 'Wait-list', content: content(2) },
        { id: 3, name: 'Cancellations', content: content(3) },
        { id: 4, name: 'Take attendance', content: content(4) },
        { id: 5, name: 'Message users', content: content(5) },
      ],
      selectedId: 1,
      disabledId: '2',
      codeTemplate: `<Tabs direction="vertical">
  <Tab
    v-for="tab in tabs"
    :id="tab.id"
    :key="tab.id"
    :name="tab.name"
    :disabled="tab.disabled"
  >
    {{ tab.content }}
  </Tab>
</Tabs>`,
      codeScript: `import Tab from 'tui/components/tabs/Tab';
import Tabs from 'tui/components/tabs/Tabs';

export default {
  components: {
    Tab,
    Tabs,
  }
}`,
    };
  },
};
</script>
