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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module samples
-->

<template>
  <div>
    A notification banner is usually displayed at the top of the page content
    and added on page load.

    <SamplesExample>
      <NotificationBanner
        :dismissable="dismissable"
        :message="message"
        :type="type"
        :self-dismiss="selfDismiss"
        @dismiss="dismiss"
      />

      <h3>Notification banner with custom slot content</h3>
      <NotificationBanner
        :dismissable="dismissable"
        :type="type"
        :self-dismiss="selfDismiss"
        @dismiss="dismiss"
      >
        <template v-slot:body>
          <Card :no-border="true" class="tui-sampleNotificationBannerCard">
            <div>{{ message }}</div>
            <Button text="button" />
          </Card>
        </template>
      </NotificationBanner>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Type">
        <RadioGroup v-model="type" :horizontal="true">
          <Radio value="info">Info</Radio>
          <Radio value="success">Success</Radio>
          <Radio value="warning">Warning</Radio>
          <Radio value="error">Error</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{ id, label }" label="Message">
        <InputText :id="id" v-model="message" :placeholder="label" />
      </FormRow>

      <FormRow label="Dismissable">
        <RadioGroup v-model="dismissable" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Self-dismiss">
        <RadioGroup v-model="selfDismiss" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
    </SamplesPropCtl>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import Card from 'tui/components/card/Card';
import NotificationBanner from 'tui/components/notifications/NotificationBanner';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import FormRow from 'tui/components/form/FormRow';
import RadioGroup from 'tui/components/form/RadioGroup';
import Radio from 'tui/components/form/Radio';
import InputText from 'tui/components/form/InputText';

export default {
  components: {
    Button,
    Card,
    NotificationBanner,
    SamplesExample,
    SamplesPropCtl,
    SamplesCode,
    FormRow,
    RadioGroup,
    Radio,
    InputText,
  },

  data() {
    return {
      type: 'info',
      message: 'Every time you lick a stamp, you consume 1/10 of a calorie.',
      dismissable: false,
      selfDismiss: false,
      codeTemplate: `<NotificationBanner type="info" message="Message."/>`,
      codeScript: `import Loader from 'tui/components/notifications/NotificationBanner';

export default {
  components: {
    NotificationBanner,
  },
};`,
    };
  },

  methods: {
    dismiss() {
      console.log('notification dismissed');
    },
  },
};
</script>

<style lang="scss">
.tui-sampleNotificationBannerCard {
  align-items: center;
  justify-content: center;
  padding: var(--gap-4);

  & > * + * {
    margin-left: var(--gap-2);
  }
}
</style>
