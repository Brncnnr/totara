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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module totara_playlist
-->

<template>
  <Form :vertical="true" input-width="full" class="tui-playlistForm">
    <FormRow
      v-slot="{ id }"
      :label="$str('playlisttitle', 'totara_playlist')"
      :hidden="true"
      :required="true"
      class="tui-playlistForm__title"
    >
      <InputText
        :id="id"
        v-model="playlist.name"
        name="playlist-title"
        :maxlength="75"
        :placeholder="$str('entertitle', 'totara_playlist')"
        :required="true"
      />
    </FormRow>

    <div class="tui-playlistForm__description">
      <FormRow
        v-slot="{ id }"
        :hidden="true"
        :label="$str('playlistdescription', 'totara_playlist')"
        class="tui-playlistForm__description-formRow"
      >
        <Weka
          :id="id"
          v-model="summary"
          :compact="true"
          :usage-identifier="{
            component: 'totara_playlist',
            area: 'summary',
          }"
          variant="standard"
          :extra-extensions="['hashtag', 'mention']"
          :placeholder="$str('adddescription', 'totara_playlist')"
          class="tui-playlistForm__description-textArea"
        />
      </FormRow>

      <div class="tui-playlistForm__description-tip">
        <p>{{ $str('contributetip', 'totara_engage') }}</p>
        <InfoIconButton :is-help-for="$str('hashtags', 'totara_engage')">
          {{ $str('contributetip_help', 'totara_engage') }}
        </InfoIconButton>
      </div>
    </div>

    <ButtonGroup class="tui-playlistForm__buttons">
      <Button
        :styleclass="{ primary: 'true' }"
        :disabled="disabled"
        :aria-label="$str('createplaylistshort', 'totara_playlist')"
        :text="$str('next', 'core')"
        @click="submit"
      />

      <CancelButton @click="cancel" />
    </ButtonGroup>

    <UnsavedChangesWarning
      v-if="hasUnsavedChanges"
      :value="{ hasUnsavedChanges }"
    />
  </Form>
</template>

<script>
import Button from 'tui/components/buttons/Button';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import InputText from 'tui/components/form/InputText';
import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';
import { FORMAT_JSON_EDITOR } from 'tui/format';

export default {
  components: {
    Button,
    ButtonGroup,
    CancelButton,
    Form,
    FormRow,
    InfoIconButton,
    InputText,
    UnsavedChangesWarning,
    Weka,
  },

  props: {
    playlist: {
      type: Object,
      default() {
        return {
          name: '',
          summary: '',
        };
      },
    },
  },

  data() {
    return {
      description: this.$id('engageContribute-description'),
      summary: WekaValue.empty(),
      hasUnsavedChanges: false,
    };
  },

  computed: {
    disabled() {
      return this.playlist.name.length === 0;
    },
    name() {
      return this.playlist.name;
    },
    hasFormData() {
      return this.playlist.name.length > 0 || !this.summary.isEmpty;
    },
  },

  watch: {
    hasFormData(value) {
      if (value) {
        this.hasUnsavedChanges = true;
        this.$emit('unsaved-changes');
      }
    },
  },

  methods: {
    submit() {
      const params = {
        name: this.playlist.name,
        summary: this.summary.isEmpty
          ? null
          : JSON.stringify(this.summary.getDoc()),
        summary_format: FORMAT_JSON_EDITOR,
      };
      this.hasUnsavedChanges = false;
      this.$emit('next', params);
    },
    cancel() {
      this.hasUnsavedChanges = false;
      // Emit event on next tick so the unload handler can be removed first.
      this.$nextTick(() => {
        this.$emit('cancel');
      });
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "next"
    ],
    "totara_core": [
      "save"
    ],
    "totara_playlist": [
      "createplaylistshort",
      "entertitle",
      "adddescription",
      "playlisttitle",
      "playlistdescription"
    ],
    "totara_engage": [
      "contributetip",
      "contributetip_help",
      "hashtags"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-playlistForm {
  display: flex;
  flex: 1;
  flex-direction: column;

  &__description {
    display: flex;
    flex: 2;
    flex-direction: column;
    margin-top: var(--gap-8);

    &-tip {
      position: relative;
      display: flex;
      margin-top: var(--gap-2);
    }

    &-formRow {
      // Making the form row to be expanded
      flex: 1;

      .tui-formRow {
        &__action {
          display: flex;
          flex: 1;
          flex-direction: column;
        }
      }
    }

    &-textArea {
      flex: 1;
    }
  }

  &__buttons {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-8);
  }
}
</style>
