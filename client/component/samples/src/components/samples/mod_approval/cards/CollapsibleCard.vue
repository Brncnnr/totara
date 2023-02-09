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
    A collapsible card component
    <SamplesExample>
      <CollapsibleCard v-model="expanded" label="Hello">
        <template v-slot:actions>
          <div class="tui-mod_approval-sampleCollapsibleCard__actions">
            <ButtonIcon
              :styleclass="{ small: true }"
              :aria-label="$str('add_action', 'mod_approval')"
              :text="$str('add_action', 'mod_approval')"
              @click="$emit('add-action')"
            >
              <ActionIcon size="200" />
            </ButtonIcon>

            <ButtonIcon
              :styleclass="{ small: true }"
              :aria-label="$str('add_transition', 'mod_approval')"
              :text="$str('add_transition', 'mod_approval')"
              @click="$emit('add-transition')"
            >
              <TransitionIcon size="200" />
            </ButtonIcon>
            <Dropdown position="bottom-right">
              <template v-slot:trigger="{ toggle }">
                <MoreButton
                  :aria-label="$str('more_actions', 'mod_approval')"
                  @click="toggle"
                />
              </template>
              <DropdownItem @click="$emit('delete')">
                {{ $str('remove_interaction', 'mod_approval') }}
              </DropdownItem>
            </Dropdown>
          </div>
        </template>
        <template v-slot:content>Hello again</template>
      </CollapsibleCard>
    </SamplesExample>

    <SamplesCode>
      <template v-slot:template>{{ codeTemplate }}</template>
      <template v-slot:script>{{ codeScript }}</template>
      <template v-slot:style>{{ codeStyle }}</template>
    </SamplesCode>
  </div>
</template>

<script>
import CollapsibleCard from 'mod_approval/components/cards/CollapsibleCard';
import SamplesCode from 'samples/components/sample_parts/misc/SamplesCode';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ActionIcon from 'tui/components/icons/Action';
import TransitionIcon from 'tui/components/icons/Transition';
import MoreButton from 'tui/components/buttons/MoreIcon';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';

export default {
  components: {
    CollapsibleCard,
    SamplesCode,
    SamplesExample,
    ButtonIcon,
    ActionIcon,
    TransitionIcon,
    MoreButton,
    Dropdown,
    DropdownItem,
  },

  data() {
    return {
      expanded: false,
      codeTemplate: `<CollapsibleCard v-model="expanded" label="Hello">
  <template v-slot:actions>
    <div class="tui-mod_approval-sampleCollapsibleCard__actions">
      <ButtonIcon
        :styleclass="{ small: true }"
        aria-label="Add action"
        text="Add action"
        @click="$emit('add-action')"
      >
        <ActionIcon size="200" />
      </ButtonIcon>

      <ButtonIcon
        :styleclass="{ small: true }"
        aria-label="Add transition"
        text="Add transition"
        @click="$emit('add-transition')"
      >
        <TransitionIcon size="200" />
      </ButtonIcon>
      <Dropdown position="bottom-right">
        <template v-slot:trigger="{ toggle }">
          <MoreButton aria-label="More actions" @click="toggle" />
        </template>
        <DropdownItem @click="$emit('delete')">
          Delete
        </DropdownItem>
      </Dropdown>
    </div>
  </template>
  <template v-slot:content>Hello again</template>
</CollapsibleCard>`,
      codeScript: `import CollapsibleCard from 'mod_approval/components/cards/CollapsibleCard';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ActionIcon from 'tui/components/icons/Action';
import TransitionIcon from 'tui/components/icons/Transition';
import MoreButton from 'tui/components/buttons/MoreIcon';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';

export default {
  components: {
    CollapsibleCard,
    SamplesCode,
    SamplesExample,
    ButtonIcon,
    ActionIcon,
    TransitionIcon,
    MoreButton,
    Dropdown,
    DropdownItem,
  },

  data() {
    return {
      expanded: false,
    }
  }
}`,
      codeStyle: `.tui-mod_approval-sampleCollapsibleCard {
  &__actions {
    @include tui-stack-horizontal(var(--gap-4));
    display: flex;
    align-items: center;
  }
}`,
    };
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "add_action",
    "add_transition",
    "more_actions",
    "remove_interaction"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-sampleCollapsibleCard {
  &__actions {
    @include tui-stack-horizontal(var(--gap-4));
    display: flex;
    align-items: center;
  }
}
</style>
