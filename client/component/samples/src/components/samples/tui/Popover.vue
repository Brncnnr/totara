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
  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module samples
-->

<template>
  <div class="tui-samplePopover">
    The popover component is a flexible component for displaying content within
    a popover which supports multiple types of triggers.

    <SamplesExample>
      <div class="tui-samplePopover__buttonRow">
        <!-- Hover Popover -->
        <Popover
          :closeable="closeable"
          :position="position"
          :size="size"
          :title="title"
          :triggers="['hover']"
        >
          <template v-slot:trigger>
            <Button text="Hover" />
          </template>

          {{ content }}

          <template v-if="hasButtons" v-slot:buttons="{ close }">
            <Button
              :styleclass="{ primary: true, small: true }"
              text="Primary"
              @click="close"
            />
            <Button :styleclass="{ small: true }" text="Secondary" />
          </template>
        </Popover>

        <!-- Click Popover -->
        <Popover
          :closeable="closeable"
          :position="position"
          :size="size"
          :title="title"
          :triggers="['click']"
        >
          <template v-slot:trigger>
            <Button text="Click" />
          </template>

          {{ content }}

          <template v-if="hasButtons" v-slot:buttons="{ close }">
            <Button
              :styleclass="{ primary: true, small: true }"
              text="Primary"
              @click="close"
            />
            <Button :styleclass="{ small: true }" text="Secondary" />
          </template>
        </Popover>

        <!-- Focus Popover -->
        <Popover
          :closeable="closeable"
          :position="position"
          :size="size"
          :title="title"
          :triggers="['focus']"
        >
          <template v-slot:trigger>
            <Button text="Focus" />
          </template>

          {{ content }}

          <template v-if="hasButtons" v-slot:buttons="{ close }">
            <Button
              :styleclass="{ primary: true, small: true }"
              text="Primary"
              @click="close"
            />
            <Button :styleclass="{ small: true }" text="Secondary" />
          </template>
        </Popover>

        <!-- Inside a modal Popover -->
        <Button text="Inside a modal" @click="showModal" />
        <ModalPresenter :open="modalOpen" @request-close="modalRequestClose">
          <Modal size="normal" :aria-labelledby="$id('title')">
            <ModalContent
              title="Hello"
              :title-id="$id('title')"
              :close-button="true"
            >
              <div class="tui-samplePopover__buttonRow">
                <Popover
                  :closeable="closeable"
                  :position="position"
                  :size="size"
                  :title="title"
                  :triggers="['hover', 'click']"
                >
                  <template v-slot:trigger>
                    <Button text="Trigger" />
                  </template>

                  {{ content }}

                  <template v-if="hasButtons" v-slot:buttons="{ close }">
                    <Button
                      :styleclass="{ primary: true, small: true }"
                      text="Primary"
                      @click="close"
                    />
                    <Button :styleclass="{ small: true }" text="Secondary" />
                  </template>
                </Popover>
              </div>
            </ModalContent>
          </Modal>
        </ModalPresenter>
      </div>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Set width">
        <RadioGroup v-model="size" :horizontal="true">
          <Radio :value="null">None</Radio>
          <Radio value="sm">Small</Radio>
          <Radio value="md">Medium</Radio>
          <Radio value="lg">Large</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow v-slot="{ id, label }" label="Title">
        <InputText :id="id" v-model="title" :placeholder="label" />
      </FormRow>

      <FormRow v-slot="{ id, label }" label="Content">
        <InputText :id="id" v-model="content" :placeholder="label" />
      </FormRow>

      <FormRow label="has buttons">
        <RadioGroup v-model="hasButtons" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Position">
        <RadioGroup v-model="position">
          <Radio value="bottom">Bottom</Radio>
          <Radio value="top">Top</Radio>
          <Radio value="left">Left</Radio>
          <Radio value="right">Right</Radio>
          <Radio value="bottom-left">Bottom left</Radio>
          <Radio value="bottom-right">Bottom right</Radio>
          <Radio value="top-left">Top left</Radio>
          <Radio value="top-right">Top right</Radio>
          <Radio value="left-top">Left top</Radio>
          <Radio value="left-bottom">Left bottom</Radio>
          <Radio value="right-top">Right top</Radio>
          <Radio value="right-bottom">Right bottom</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="closeable (click trigger only)">
        <RadioGroup v-model="closeable" :horizontal="true">
          <Radio :value="true">True</Radio>
          <Radio :value="false">False</Radio>
        </RadioGroup>
      </FormRow>
    </SamplesPropCtl>

    <SamplesCode>
      <template v-slot:template>{{
        sampleCode && sampleCode.template
      }}</template>
      <template v-slot:script>{{ sampleCode && sampleCode.script }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import Modal from 'tui/components/modal/Modal';
import ModalContent from 'tui/components/modal/ModalContent';
import ModalPresenter from 'tui/components/modal/ModalPresenter';
import Popover from 'tui/components/popover/Popover';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    Button,
    FormRow,
    InputText,
    Modal,
    ModalContent,
    ModalPresenter,
    Popover,
    Radio,
    RadioGroup,
    SamplesCode,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    return {
      closeable: false,
      content:
        'Content. Popovers have optional titles and form fields/action buttons. Action buttons should be small. Use popovers for supporting contextual information.',
      hasButtons: false,
      modalOpen: false,
      position: 'bottom',
      size: null,
      title: '',
    };
  },

  methods: {
    showModal() {
      this.modalOpen = true;
    },

    modalRequestClose() {
      this.modalOpen = false;
    },
  },
};
</script>

<style lang="scss">
.tui-samplePopover {
  &__buttonRow {
    display: flex;
    > * + * {
      margin-left: var(--gap-4);
    }
  }
}
</style>

<sample-template>
<Popover
    :closeable="true"
    :position="'bottom'"
    :title="'title'"
    :triggers="['click']"
  >
  <template v-slot:trigger>
    <Button text="Click" />
  </template>

  This is the popover content

  <template v-slot:buttons="{ close }">
    <Button
      text="Primary"
      :styleclass="{ primary: true, small: true }"
      @click="close"
    />
    <Button text="Secondary" :styleclass="{ small: true }" />
  </template>
</Popover>
</sample-template>

<sample-script>
import Button from 'tui/components/buttons/Button';
import Popover from 'tui/components/popover/Popover';

export default {
  components: {
    Button,
    Popover,
  },
}
</sample-script>
