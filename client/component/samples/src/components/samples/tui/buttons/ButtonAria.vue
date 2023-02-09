<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

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
    <p>
      The ButtonAria component is a way to render a button with no built-in
      styling. This is useful for implementing custom buttons.
    </p>
    <p>
      This component itself does not render anything, it only adds additional
      attributes and event handlers to the element you pass in the slot in order
      to make it accessible.
    </p>
    <p>
      For regular usage, see the standard
      <a href="?component=tui/buttons/Button">Button</a> component.
    </p>

    <SamplesExample>
      <h4>Unstyled</h4>
      <ButtonAria>
        <div @click="handleClick">
          I'm a button
        </div>
      </ButtonAria>

      <h4>Simple styling</h4>
      <ButtonAria>
        <div class="tui-samples-buttonAriaSimple" @click="handleClick">
          I'm a button
        </div>
      </ButtonAria>
      <ButtonAria>
        <div class="tui-samples-buttonAriaSimple" disabled @click="handleClick">
          I'm a disabled button
        </div>
      </ButtonAria>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
      <template v-slot:style>{{ codeStyle }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import { notify } from 'tui/notifications';
import ButtonAria from 'tui/components/buttons/ButtonAria';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';

export default {
  components: {
    ButtonAria,
    SamplesCode,
    SamplesExample,
  },

  data() {
    return {
      codeTemplate: `<ButtonAria>
  <div class="tui-my_plugin-myButton" @click="handleClick">
    I'm a button
  </div>
</ButtonAria>`,
      codeScript: `import ButtonAria from 'tui/components/buttons/ButtonAria';

export default {
  components: {
    ButtonAria,
  },

  methods: {
    handleClick() {
      console.log('clicked');
    },
  },
}`,
      codeStyle: `.tui-my_plugin-myButton {
  display: inline-flex;
  align-items: center;
  padding: var(--gap-1) var(--gap-2);
  color: var(--color-neutral-1);
  background: var(--color-neutral-6);
  border-radius: 4px;
  cursor: pointer;
  user-select: none;

  &:hover {
    background: var(--color-neutral-7);
  }

  &:focus {
    @include tui-focus();
  }

  &[aria-disabled=true] {
    background: var(--color-neutral-5);
    cursor: default;
  }
}`,
    };
  },

  methods: {
    handleClick() {
      notify({ message: 'Clicked', duration: 1500 });
    },
  },
};
</script>

<style lang="scss">
.tui-samples-buttonAriaSimple {
  display: inline-flex;
  align-items: center;
  padding: var(--gap-1) var(--gap-2);
  color: var(--color-neutral-1);
  background: var(--color-neutral-6);
  border-radius: 4px;
  cursor: pointer;
  user-select: none;

  &:hover,
  &:active {
    background: var(--color-neutral-7);
  }

  &:focus {
    @include tui-focus();
  }

  &[aria-disabled='true'] {
    background: var(--color-neutral-5);
    cursor: default;
  }
}
</style>
