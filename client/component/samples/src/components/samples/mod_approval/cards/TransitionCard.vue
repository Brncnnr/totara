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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module samples
-->

<template>
  <div class="tui-loader">
    A transition card component
    <SamplesExample>
      <div class="tui-mod_approval-sampleTransitionCard__header">
        {{ $str('transition', 'mod_approval') }}
      </div>
      <TransitionCard :items="transition" :has-hover-shadow="true">
        <template v-slot:left-icon>
          <TransitionIcon />
        </template>
        <template v-slot:buttons>
          <ButtonIcon
            :styleclass="{
              small: true,
              transparent: true,
            }"
            :aria-label="$str('edit', 'core')"
            @click="$emit('edit')"
          >
            <EditIcon />
          </ButtonIcon>
        </template>
      </TransitionCard>
      <div class="tui-mod_approval-sampleTransitionCard__header">
        {{ $str('action', 'mod_approval') }}
      </div>
      <TransitionCard :items="action">
        <template v-slot:left-icon>
          <ActionIcon />
        </template>
        <template v-slot:buttons>
          <ButtonIcon
            :styleclass="{
              small: true,
              transparent: true,
            }"
            :aria-label="$str('edit', 'core')"
            @click="$emit('edit')"
          >
            <EditIcon />
          </ButtonIcon>
          <ButtonIcon
            :styleclass="{
              small: true,
              transparent: true,
            }"
            :aria-label="$str('delete', 'core')"
            @click="$emit('delete')"
          >
            <ClearIcon />
          </ButtonIcon>
        </template>
      </TransitionCard>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import TransitionCard from 'mod_approval/components/cards/TransitionCard';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import EditIcon from 'tui/components/icons/Edit';
import ClearIcon from 'tui/components/icons/Clear';
import ActionIcon from 'tui/components/icons/Action';
import TransitionIcon from 'tui/components/icons/Transition';

export default {
  components: {
    TransitionCard,
    SamplesCode,
    SamplesExample,
    ButtonIcon,
    EditIcon,
    ClearIcon,
    ActionIcon,
    TransitionIcon,
  },
  data() {
    return {
      action: [
        { header: 'Action type:', text: 'Conditional' },
        { header: 'When form field:', text: 'Completion status' },
        { header: 'Value is:', text: 'Yes' },
        { header: 'Do this:', text: 'Record of learning' },
      ],
      transition: [
        { header: 'Transition type:', text: 'Default' },
        { header: 'Move to:', text: 'Completed' },
      ],
      codeTemplate: `<TransitionCard :items="transition">
  <template v-slot:left-icon>
    <TransitionIcon />
  </template>
  <template v-slot:buttons>
    <ButtonIcon
      :styleclass="{
          small: true,
          transparent: true,
        }"
      aria-label="Edit"
      @click="$emit('edit')"
    >
      <EditIcon />
    </ButtonIcon>
  </template>
</TransitionCard>`,
      codeScript: `import TransitionCard from 'mod_approval/components/cards/TransitionCard';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import EditIcon from 'tui/components/icons/Edit';
import TransitionIcon from 'tui/components/icons/Transition';

export default {
  components: {
    TransitionCard,
    ButtonIcon,
    EditIcon,
    TransitionIcon,
  },
  data() {
    return {
      transition: [
        { header: 'Transition type:', text: 'Default' },
        { header: 'Move to:', text: 'Completed' },
      ],
    };
  },
};
`,
    };
  },
};
</script>

<lang-strings>
  {
    "mod_approval": [
      "action",
      "transition"
    ],
    "core": [
      "edit",
      "delete"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-mod_approval-sampleTransitionCard {
  &__header {
    margin: var(--gap-2) 0;
  }
}
</style>
