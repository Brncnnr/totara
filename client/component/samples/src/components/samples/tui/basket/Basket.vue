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
    <SamplesExample>
      <Basket :items="items" :bulk-actions="bulkActions">
        <template v-slot:status="{ empty }">
          <ButtonIcon
            v-if="viewingSelected && !empty"
            :styleclass="{ small: true }"
            text="Clear selection"
            @click="clear"
          >
            <ClearIcon />
          </ButtonIcon>
        </template>
        <template v-slot:actions="{ empty }">
          <Button
            v-if="!viewingSelected && !empty"
            :styleclass="{ transparent: true }"
            text="View selected"
            @click="viewSelected"
          />
          <Button
            v-if="viewingSelected && !empty"
            :styleclass="{ transparent: true }"
            text="Go back to all"
            @click="showAll"
          />
        </template>
      </Basket>
    </SamplesExample>

    <SamplesPropCtl>
      <FormRow label="Items">
        <RadioGroup v-model="selectedItems" :horizontal="true">
          <Radio :value="0">None</Radio>
          <Radio :value="1">One</Radio>
          <Radio :value="10">Ten</Radio>
        </RadioGroup>
      </FormRow>

      <FormRow label="Bulk actions">
        <RadioGroup v-model="bulkActions" :horizontal="true">
          <Radio :value="actionOptions[0]">None</Radio>
          <Radio :value="actionOptions[1]">One</Radio>
          <Radio :value="actionOptions[2]">Three</Radio>
        </RadioGroup>
      </FormRow>
    </SamplesPropCtl>
  </div>
</template>

<script>
import Basket from 'tui/components/basket/Basket';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import ClearIcon from 'tui/components/icons/Clear';
import FormRow from 'tui/components/form/FormRow';
import RadioGroup from 'tui/components/form/RadioGroup';
import Radio from 'tui/components/form/Radio';
import SamplesExample from 'samples/components/sample_parts/misc/SamplesExample';
import SamplesPropCtl from 'samples/components/sample_parts/misc/SamplesPropCtl';

export default {
  components: {
    Basket,
    Button,
    ButtonIcon,
    ClearIcon,
    FormRow,
    RadioGroup,
    Radio,
    SamplesExample,
    SamplesPropCtl,
  },

  data() {
    const actionOptions = [
      [],
      [{ label: 'Activate', action: this.action }],
      [
        { label: 'Activate', action: this.action },
        { label: 'Archive', action: this.action },
        { label: 'Delete', action: this.action },
      ],
    ];
    return {
      actionOptions,
      items: ['hello'],
      bulkActions: actionOptions[2],
      viewingSelected: false,
    };
  },

  computed: {
    selectedItems: {
      get() {
        return this.items.length;
      },

      set(length) {
        // hacky way to generate an array of `length`
        this.items = Array.apply(null, Array(length)).map(() => 1);
      },
    },
  },

  methods: {
    action() {
      setTimeout(() => alert('action!'), 20);
    },

    viewSelected() {
      this.viewingSelected = true;
    },

    showAll() {
      this.viewingSelected = false;
    },

    clear() {
      this.items = [];
    },
  },
};
</script>
