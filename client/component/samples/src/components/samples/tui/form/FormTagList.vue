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

  @author Jack Humphrey <jack.humphrey@totaralearning.com>
  @module tui
-->

<template>
  <Uniform
    :initial-values="value"
    :validate="validate"
    @change="handleChange"
    @submit="submit"
  >
    <FormRow label="User" required>
      <FormTagList
        name="user"
        char-length="30"
        :validations="v => [v.required()]"
        :items="users"
        :filter="userSearchItem"
        single-select
        close-on-click
        @filter="userFilter"
      >
        <template v-slot:item="{ item }">
          <div>{{ item.text }}</div>
        </template>
      </FormTagList>
    </FormRow>

    <FormRow label="Pets">
      <FormTagList
        name="pets"
        char-length="30"
        :items="pets"
        :filter="petsSearchItem"
        @filter="petsFilter"
      >
        <template v-slot:item="{ item }">
          <div>{{ item.text }}</div>
        </template>
      </FormTagList>
    </FormRow>
    <FormRowActionButtons />

    <h4 v-if="value">Current value</h4>
    <pre v-if="value">{{ value }}</pre>

    <h4 v-if="result">Result</h4>
    <pre v-if="result">{{ result }}</pre>
  </Uniform>
</template>

<script>
import { Uniform, FormRow } from 'tui/components/uniform';
import FormTagList from 'tui/components/uniform/FormTagList';
import FormRowActionButtons from 'tui/components/form/FormRowActionButtons';

export default {
  components: {
    Uniform,
    FormRow,
    FormTagList,
    FormRowActionButtons,
  },

  data() {
    return {
      value: {
        user: {
          id: 1002,
          text: 'John',
        },
        pets: [],
      },
      result: null,
      userSearchItem: '',
      petsSearchItem: '',
      availableUsers: [
        {
          id: 1001,
          text: 'Mike',
        },
        {
          id: 1002,
          text: 'John',
        },
        {
          id: 1003,
          text: 'Eric',
        },
        {
          id: 1004,
          text: 'George',
        },
      ],
      availablePets: [
        {
          id: 101,
          text: 'Dog',
        },
        {
          id: 102,
          text: 'Cat',
        },
        {
          id: 103,
          text: 'Snake',
        },
        {
          id: 104,
          text: 'Iguana',
        },
      ],
    };
  },

  computed: {
    users() {
      const selectedUser = this.value.user;
      const users = selectedUser
        ? this.availableUsers.filter(user => user.id !== selectedUser.id)
        : this.availableUsers;
      if (this.userSearchItem === '') {
        return [...users];
      }
      return users.filter(user =>
        user.text.toUpperCase().includes(this.userSearchItem.toUpperCase())
      );
    },

    pets() {
      const selectedPets = this.value.pets;
      const pets = this.availablePets.filter(
        pet => !selectedPets.some(tag => pet.id === tag.id)
      );
      if (this.petsSearchItem === '') {
        return [...pets];
      }
      return pets.filter(pet =>
        pet.text.toUpperCase().includes(this.petsSearchItem.toUpperCase())
      );
    },
  },

  methods: {
    /**
     * @param {object} value
     */
    handleChange(value) {
      this.value = value;
    },

    /**
     * @param {object} value
     */
    submit(value) {
      this.result = value;
    },

    /**
     * @param {string} value
     */
    userFilter(value) {
      this.userSearchItem = value;
    },

    /**
     * @param {string} value
     */
    petsFilter(value) {
      this.petsSearchItem = value;
    },

    /**
     * @param {object} value
     */
    validate(value) {
      const errors = {};

      if (value.user && value.user.text === 'Eric') {
        errors.user = 'Please do not select Eric';
      }
      if (value.pets.some(pet => pet.text === 'Dog')) {
        errors.pets = 'No dogs allowed';
      }

      return errors;
    },
  },
};
</script>
