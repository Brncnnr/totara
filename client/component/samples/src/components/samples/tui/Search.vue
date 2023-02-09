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
  <div style="width: 500px;">
    <h2>Standalone</h2>
    <SearchBox
      v-model="searchBox"
      aria-label="Search for item"
      :disabled="disabled"
      placeholder="Searching"
      @submit="searchSubmit"
    />
    <br />
    <h2>In form</h2>
    <Form :class="'tui-testForm'">
      <FormRow v-slot="{ id, label }" :label="'Search data'">
        <SearchBox
          :id="id"
          v-model="searchBoxRow"
          :drop-label="true"
          :disabled="disabled"
          :placeholder="'Search here'"
          @input="searchInput"
          @submit="searchSubmit"
        />
      </FormRow>
    </Form>
    <br />
    <ButtonGroup>
      <Button text="disable" @click="disableFormInputs" />
      <Button text="enable" @click="enableFormInputs" />
    </ButtonGroup>
  </div>
</template>

<script>
// Components
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import SearchBox from 'tui/components/form/SearchBox';

export default {
  components: {
    Button,
    ButtonGroup,
    Form,
    FormRow,
    SearchBox,
  },

  data() {
    return {
      disabled: false,
      searchBox: '',
      searchBoxRow: '',
      searchFilter: '',
      searchFilterRow: '',
    };
  },

  methods: {
    /**
     * Disable input fields
     *
     */
    disableFormInputs() {
      this.disabled = true;
    },

    /**
     * Enable input fields
     *
     */
    enableFormInputs() {
      this.disabled = false;
    },

    /**
     * Search input change
     *
     */
    searchInput() {
      console.log(this.searchBoxRow);
    },

    /**
     * Triggered search submit
     *
     */
    searchSubmit() {
      this.disableFormInputs();
    },
  },
};
</script>
