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

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @module pathway_manual
-->

<template>
  <div class="tui-bulkManualRatingRoleSelector">
    <div
      :id="$id('rating-as')"
      class="tui-bulkManualRatingRoleSelector__ratingAs"
    >
      {{
        roleSpecified
          ? $str(`rating_as_${roleSpecified}`, 'pathway_manual')
          : $str('rating_as_a', 'pathway_manual')
      }}
    </div>
    <div
      v-if="!roleSpecified"
      class="tui-bulkManualRatingRoleSelector__selectRole"
    >
      <Select
        :aria-labelledby="$id('rating-as')"
        :options="roleOptions"
        :value="defaultSelectedValue"
        @input="selectRoleWithWarning"
      />
    </div>
    <ConfirmationModal
      :open="modalVisible"
      :title="$str('modal_confirm_update_role_title', 'pathway_manual')"
      @confirm="selectRole"
      @cancel="hideModal"
    >
      <span v-html="$str('modal_confirm_update_role_body', 'pathway_manual')" />
    </ConfirmationModal>
  </div>
</template>

<script>
import ConfirmationModal from 'tui/components/modal/ConfirmationModal';
import RolesQuery from 'pathway_manual/graphql/roles';
import Select from 'tui/components/form/Select';

const UNSET_OPTION = -1;

export default {
  components: {
    ConfirmationModal,
    Select,
  },

  props: {
    userId: {
      type: Number,
    },
    specifiedRole: {
      type: String,
    },
    showWarning: {
      type: Boolean,
      default: false,
    },
    hasDefaultSelected: {
      type: Boolean,
      default: false,
    },
  },

  data() {
    return {
      roles: [],
      confirmedRole: null,
      selectedRole: this.specifiedRole,
      modalVisible: false,
    };
  },

  computed: {
    /**
     * The role that has been specified, either from URL params or because there is only one role.
     * @returns {string|null}
     */
    roleSpecified() {
      if (this.specifiedRole != null) {
        return this.specifiedRole;
      }

      if (this.roles.length === 1) {
        return this.roles[0].name;
      }

      return null;
    },

    /**
     * The role that has been selected by the user, or otherwise predetermined.
     * @returns {null|string}
     */
    role() {
      if (this.confirmedRole != null) {
        return this.confirmedRole;
      }
      return this.roleSpecified;
    },

    /**
     * Roles that can be selected for the dropdown.
     * @returns {{id: *, label: *}[]}
     */
    roleOptions() {
      let roles = this.roles.map(role => {
        return {
          id: role.name,
          label: role.display_name,
        };
      });

      if (!this.hasDefaultSelected) {
        roles.unshift({
          id: UNSET_OPTION,
          label: this.$str('select...', 'pathway_manual'),
          disabled: true,
        });
      }

      return roles;
    },

    /**
     * The value that is selected by default.
     * @returns {number|*}
     */
    defaultSelectedValue() {
      if (this.hasDefaultSelected && this.roles.length > 0) {
        return this.roles[0].name;
      } else {
        return UNSET_OPTION;
      }
    },
  },

  methods: {
    /**
     * Confirm that the user wants to change the screen before actually selecting the role.
     * @param role
     */
    selectRoleWithWarning(role) {
      this.selectedRole = role;

      if (this.confirmedRole != null && this.showWarning) {
        this.showModal();
      } else {
        this.selectRole();
      }
    },

    /**
     * Confirm the role selection and notify the parent.
     */
    selectRole() {
      this.hideModal();
      this.confirmedRole = this.selectedRole;
      this.$emit('role-selected', this.confirmedRole);
    },

    /**
     * Show the role selection confirmation modal.
     */
    showModal() {
      this.modalVisible = true;
    },

    /**
     * Hide the role selection confirmation modal.
     */
    hideModal() {
      this.modalVisible = false;
    },
  },

  apollo: {
    roles: {
      query: RolesQuery,
      variables() {
        if (this.userId == null) {
          return {};
        }
        return {
          subject_user: this.userId,
        };
      },
      update({ pathway_manual_roles: roles }) {
        if (roles.length === 1) {
          this.$emit('role-selected', roles[0].name);
        } else if (this.hasDefaultSelected && roles.length > 0) {
          this.$emit('role-selected', roles[0].name);
        }
        return roles;
      },
    },
  },
};
</script>

<lang-strings>
  {
    "pathway_manual": [
      "modal_confirm_update_role_body",
      "modal_confirm_update_role_title",
      "rating_as_a",
      "rating_as_appraiser",
      "rating_as_manager",
      "select..."
    ]
  }
</lang-strings>

<style lang="scss">
.tui-bulkManualRatingRoleSelector {
  display: flex;
  align-items: center;

  &__ratingAs {
    @include tui-font-heading-x-small;
  }

  &__selectRole {
    margin-left: var(--gap-2);
  }
}
</style>
