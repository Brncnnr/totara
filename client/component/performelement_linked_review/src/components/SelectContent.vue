<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
  @author Marco Song <marco.song@totaralearning.com>
  @module performelement_linked_review
-->

<template>
  <div class="tui-linkedReviewSelectedContent">
    <!-- Preview of selected content -->
    <div class="tui-linkedReviewSelectedContent__items">
      <div
        v-for="content in selectedContent"
        :key="getId(content)"
        class="tui-linkedReviewSelectedContent__item"
      >
        <Card
          class="tui-linkedReviewSelectedContent__item-card"
          :no-border="true"
        >
          <div class="tui-linkedReviewSelectedContent__item-cardContent">
            <slot name="content-preview" :content="content" />
          </div>

          <div class="tui-linkedReviewSelectedContent__item-cardActions">
            <CloseButton
              :aria-label="removeText"
              :size="300"
              @click="deleteContent(getId(content))"
            />
          </div>
        </Card>
      </div>
    </div>

    <!-- Content adder  -->
    <div v-if="canShowAdder">
      <ButtonIcon
        :aria-label="addBtnText"
        :text="addBtnText"
        @click="adderOpen"
      >
        <AddIcon />
      </ButtonIcon>

      <component
        :is="adder"
        :open="showAdder"
        :existing-items="selectedIds"
        :user-id="userId"
        @added="adderUpdate"
        @cancel="adderClose"
      />
    </div>
    <div v-else>
      {{ cantAddText }}
    </div>

    <!-- Confirm selection button -->
    <div
      v-if="selectedContent.length > 0"
      class="tui-linkedReviewSelectedContent__confirm"
    >
      <Button
        :text="$str('confirm_selection', 'mod_perform')"
        :styleclass="{ primary: true }"
        @click="confirmSelectedIds"
      />
    </div>

    <!-- Display validation error when field is required and no selection made -->
    <FormField :name="$id('contentAdder')" :validations="validations" />
  </div>
</template>

<script>
// tui
import AddIcon from 'tui/components/icons/Add';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Card from 'tui/components/card/Card';
import CloseButton from 'tui/components/buttons/CloseIcon';
import { FormField } from 'tui/components/uniform';
import { notify } from 'tui/notifications';
import { v as validation } from 'tui/validation';

// GraphQL
import updateReviewContentMutation from 'performelement_linked_review/graphql/update_linked_review_content';

export default {
  components: {
    AddIcon,
    Button,
    ButtonIcon,
    Card,
    CloseButton,
    FormField,
  },

  props: {
    addBtnText: {
      type: String,
      required: true,
    },
    adder: Object,
    canShowAdder: {
      type: Boolean,
      required: true,
    },
    cantAddText: {
      type: String,
      required: true,
    },
    isDraft: Boolean,
    participantInstanceId: {
      type: [String, Number],
      required: true,
    },
    removeText: String,
    required: Boolean,
    sectionElementId: String,
    userId: Number,
    additionalContent: Array,
    getId: {
      type: Function,
      default: content => ('id' in content ? content.id : null),
    },
  },

  data() {
    return {
      selectedContent: [],
      selectedIds: [],
      showAdder: false,
    };
  },

  computed: {
    id() {
      return this.$id();
    },

    /**
     * An array of validation rules for the element.
     * The rules returned depend on if we are saving as draft or if a response is required or not.
     *
     * @return {(function|object)[]}
     */
    validations() {
      if (this.isDraft) {
        return [];
      }

      if (this.selectedIds.length) {
        return [validation.required()];
      }

      if (!this.required) {
        return [];
      }

      return [validation.required()];
    },
  },

  watch: {
    selectedIds(value) {
      this.$emit('unsaved-plugin-change', {
        key: this.id,
        hasChanges: value.length ? true : false,
      });
    },
  },

  methods: {
    /**
     * Close the adder
     */
    adderClose() {
      this.showAdder = false;
    },

    /**
     * Open the adder
     */
    adderOpen() {
      this.showAdder = true;
    },

    /**
     * Update selected content items
     *
     * @param {Object} selection selected data returned from adder
     */
    adderUpdate(selection) {
      this.selectedContent = selection.data;
      this.selectedIds = selection.ids;
      this.adderClose();
    },

    /**
     * Save selected content
     */
    async confirmSelectedIds() {
      try {
        await this.saveContent();
        this.selectedIds = [];
      } catch (e) {
        this.showMutationErrorNotification();
      }
    },

    /**
     * Remove item from selected content
     *
     * @param {Number} contentId ID of item to be removed
     */
    deleteContent(contentId) {
      this.selectedContent = this.selectedContent.filter(
        item => this.getId(item) !== contentId
      );
      this.selectedIds = this.selectedIds.filter(e => e !== contentId);
    },

    /**
     * Save selected content in the repository
     */
    async saveContent() {
      await this.$apollo
        .mutate({
          mutation: updateReviewContentMutation,
          variables: {
            input: {
              content: JSON.stringify(this.prepareContent()),
              participant_instance_id: this.participantInstanceId,
              section_element_id: this.sectionElementId,
            },
          },
          refetchAll: false, // Don't refetch all the data again
        })
        .then(({ data }) => {
          this.$emit('unsaved-plugin-change', {
            key: this.id,
            hasChanges: false,
          });
          if (!data.data.validation_info.can_update) {
            this.$emit('update', data.data.validation_info.description);
          } else {
            this.$emit('update');
          }
        });
    },

    /**
     * prepare additional content to send to the backend
     */
    prepareContent() {
      return this.selectedContent.map(content => {
        // We always want the id
        let newContent = {
          id: content.id,
        };

        if (this.additionalContent) {
          this.additionalContent.forEach(additional => {
            if (additional in content) {
              newContent[additional] = content[additional];
            }
          });
        }

        return newContent;
      });
    },

    /**
     * Show a generic saving error toast.
     */
    showMutationErrorNotification() {
      notify({
        message: this.$str('error', 'core'),
        type: 'error',
      });
    },
  },
};
</script>

<style lang="scss">
.tui-linkedReviewSelectedContent {
  & > * + * {
    margin-top: var(--gap-4);
  }

  &__items {
    & > * + * {
      margin-top: var(--gap-4);
    }
  }

  &__item {
    & > * + * {
      margin-top: var(--gap-4);
    }

    &-card {
      max-width: 1200px;
      background: var(--color-neutral-3);
    }

    &-cardContent {
      width: 100%;
      padding: var(--gap-4);
    }

    &-cardActions {
      display: flex;
      align-items: flex-start;
      width: var(--gap-9);
      margin-top: var(--gap-2);
    }
  }
}
</style>

<lang-strings>
{
  "core": [
    "error"
  ],
  "mod_perform": [
    "confirm_selection"
  ],
  "performelement_linked_review": [
    "can_not_select_content_message"
  ]
}
</lang-strings>
