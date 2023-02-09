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

  @author Matthias Bonk <matthias.bonk@totaralearning.com>
  @module pathway_manual
-->

<template>
  <Popover
    :triggers="['click']"
    position="bottom-right"
    @open-changed="popoverOpenChanged"
  >
    <h2 class="sr-only">{{ $str('add_rating', 'pathway_manual') }}</h2>
    <div class="tui-bulkManualRatingPopover">
      <div class="tui-bulkManualRatingPopover__scaleValues">
        <label :id="$id('scale-value-heading')">
          {{ $str('select_scale_value', 'pathway_manual') }}
        </label>
        <RadioGroup
          v-model="inputScaleValueId"
          :aria-labelledby="$id('scale-value-heading')"
        >
          <Radio
            v-for="(scaleValue, key) in scale.values"
            :key="key"
            :label="scaleValue.name"
            :value="scaleValue.id"
            :name="$id('scale-radio')"
          >
            <template>
              {{ scaleValue.name }}
            </template>
          </Radio>
          <span class="tui-bulkManualRatingPopover__divider" />
          <Radio :value="noneOptionValue" :name="$id('scale-radio')">
            <template>
              {{ $str('rating_set_to_none', 'pathway_manual') }}
            </template>
          </Radio>
        </RadioGroup>
      </div>
      <div class="tui-bulkManualRatingPopover__comment">
        <label :id="$id('comment-heading')">
          {{ $str('add_comment', 'pathway_manual') }}
        </label>
        <Textarea
          v-model="inputComment"
          class="tui-bulkManualRatingPopover__textarea"
          :aria-labelledby="$id('comment-heading')"
        />
      </div>
    </div>
    <template v-slot:buttons="{ close }">
      <Button
        :disabled="inputScaleValueId == null"
        :styleclass="{ small: true, primary: true }"
        :text="$str('rating_done', 'pathway_manual')"
        @click="updateRating(close)"
      />
      <ButtonIcon
        v-if="hasInput"
        :styleclass="{ small: true, alert: true }"
        :aria-label="$str('delete', 'core')"
        :text="$str('delete', 'core')"
        @click="deleteRating(close)"
      >
        <DeleteIcon />
      </ButtonIcon>
      <Button
        v-else
        :styleclass="{ small: true, primary: false }"
        :text="$str('cancel', 'core')"
        @click="cancelRating(close)"
      />
    </template>
    <template v-slot:trigger="{ isOpen }">
      <slot name="rating-trigger" :is-open="isOpen" />
    </template>
  </Popover>
</template>
<script>
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import DeleteIcon from 'tui/components/icons/Delete';
import Popover from 'tui/components/popover/Popover';
import Radio from 'tui/components/form/Radio';
import RadioGroup from 'tui/components/form/RadioGroup';
import Textarea from 'tui/components/form/Textarea';

import { NONE_OPTION_VALUE } from 'pathway_manual/constants';

export default {
  components: {
    Button,
    ButtonIcon,
    DeleteIcon,
    Popover,
    Radio,
    RadioGroup,
    Textarea,
  },

  props: {
    scale: {
      required: true,
      type: Object,
    },
    compId: {
      required: true,
      type: String,
    },
    scaleValueId: {
      type: String,
    },
    comment: {
      type: String,
    },
  },

  data() {
    return {
      inputScaleValueId: this.scaleValueId,
      inputComment: this.comment,
    };
  },

  computed: {
    /**
     * Has a scale value been selected or a comment typed?
     * @returns {boolean}
     */
    hasInput() {
      return this.inputScaleValueId != null || this.hasInputComment;
    },

    /**
     * Has a comment been typed in?
     * @returns {boolean}
     */
    hasInputComment() {
      return this.inputComment != null;
    },

    /**
     * The comment made, with unnecessary whitespace removed.
     * @returns {String|null}
     */
    trimmedComment() {
      return this.hasInputComment ? this.inputComment.trim() : null;
    },

    /**
     * Dummy value for selecting a null scale value.
     * @returns {string}
     */
    noneOptionValue() {
      return NONE_OPTION_VALUE.toString();
    },
  },

  methods: {
    /**
     * Close the popover and notify the parent that a rating has been made or updated.
     * @param {function} close
     */
    updateRating(close) {
      close();
      this.$emit('update-rating', {
        scale_value_id: this.inputScaleValueId,
        comment: this.trimmedComment,
      });
    },

    /**
     * Close the popover and notify the parent that the rating has been deleted.
     * @param {function} close
     */
    deleteRating(close) {
      close();
      this.$emit('delete-rating');
    },

    /**
     * Close the popover and clear any draft changes.
     * @param {function} close
     */
    cancelRating(close) {
      close();
      this.dismissChanges();
    },

    /**
     * Clear any changes if the popover is no longer open.
     * @param isOpen
     */
    popoverOpenChanged(isOpen) {
      if (!isOpen) {
        this.dismissChanges();
      }
    },

    /**
     * Clear the selected scale value and comment.
     */
    dismissChanges() {
      this.inputComment = this.comment;
      this.inputScaleValueId = this.scaleValueId;
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "cancel",
      "delete"
    ],
    "pathway_manual": [
      "add_comment",
      "add_rating",
      "edit_rating",
      "rate",
      "rating_done",
      "rating_none",
      "rating_set_to_none",
      "select_scale_value"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-bulkManualRatingPopover {
  & > * + * {
    margin-top: var(--gap-4);
  }

  &__comment,
  &__textarea,
  &__divider {
    width: 100%;
  }

  &__divider {
    border-top: 1px solid var(--color-neutral-5);
  }
}
</style>
