<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2022 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totara.com] for more information.

  @author Simon Chester <simon.chester@totara.com>
  @module totara_useraction
-->

<template>
  <div class="tui-totara_useraction-appliesToSelector" role="group">
    <Dropdown>
      <template v-slot:trigger="{ toggle, isOpen }">
        <Button
          :aria-expanded="isOpen.toString()"
          :caret="true"
          :text="currentSelectionLabel"
          @click="toggle"
        />
      </template>
      <DropdownItem @click="$emit('input', null)">All users</DropdownItem>
      <DropdownItem @click="selectAudiences">
        Audiences
      </DropdownItem>
    </Dropdown>

    <div
      v-if="isAudiences"
      class="tui-totara_useraction-appliesToSelector__list"
    >
      <div
        v-for="audience in value"
        :key="audience.id"
        class="tui-totara_useraction-appliesToSelector__audience"
      >
        <a :href="$url('/cohort/view.php', { id: audience.id })">
          {{ audience.name }}
        </a>
        <ButtonIcon
          :styleclass="{ small: true, transparent: true }"
          :aria-label="$str('remove_x', 'totara_useraction', audience.name)"
          @click="removeAudience(audience)"
        >
          <RemoveIcon />
        </ButtonIcon>
      </div>

      <ButtonIcon
        :text="$str('add_audience', 'totara_useraction')"
        :styleclass="{ small: true }"
        @click="selectAudiences"
      >
        <AddIcon />
      </ButtonIcon>
    </div>

    <AudienceAdder
      :open="showAdder"
      :existing-items="addedIds"
      @added="adderUpdate"
      @cancel="adderCancelled"
    />
  </div>
</template>

<script>
import AudienceAdder from 'tui/components/adder/AudienceAdder';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import AddIcon from 'tui/components/icons/Add';
import RemoveIcon from 'tui/components/icons/Remove';

export default {
  components: {
    AudienceAdder,
    Button,
    ButtonIcon,
    Dropdown,
    DropdownItem,
    AddIcon,
    RemoveIcon,
  },

  props: {
    value: Array,
  },

  data() {
    return {
      showAdder: false,
    };
  },

  computed: {
    isAudiences() {
      return Array.isArray(this.value);
    },

    currentSelectionLabel() {
      return this.isAudiences
        ? this.$str(
            'filter_applies_to_audiences_x',
            'totara_useraction',
            this.value.length
          )
        : this.$str('filter_applies_to_all_users', 'totara_useraction');
    },

    addedIds() {
      return this.isAudiences ? this.value.map(x => x.id) : [];
    },
  },

  methods: {
    selectAudiences() {
      if (!this.isAudiences) {
        this.$emit('input', []);
      }
      this.showAdder = true;
    },

    adderUpdate(input) {
      this.$emit(
        'input',
        input.data.map(({ id, name }) => ({ id, name }))
      );
      this.showAdder = false;
    },

    adderCancelled() {
      this.showAdder = false;
    },

    removeAudience(aud) {
      this.$emit(
        'input',
        this.value.filter(x => x.id != aud.id)
      );
    },
  },
};
</script>

<lang-strings>
{
  "totara_useraction": [
    "add_audience",
    "filter_applies_to_all_users",
    "filter_applies_to_audiences_x",
    "remove_x"
  ]
}
</lang-strings>

<style lang="scss">
.tui-totara_useraction-appliesToSelector {
  &__list {
    margin-top: var(--gap-3);
    @include tui-stack-vertical(var(--gap-2));
  }

  &__audience {
    display: flex;
    justify-content: space-between;
    padding: var(--gap-2);
    padding-left: var(--gap-3);
    border: var(--border-width-thin) solid var(--color-neutral-5);
    border-radius: var(--border-radius-normal);
  }
}
</style>
