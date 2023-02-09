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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module container_workspace
-->
<!--
  A Form to create a workspace. It will start with fresh/empty data set.
-->

<template>
  <Form class="tui-workspaceForm" :vertical="true" input-width="full">
    <div class="tui-workspaceForm__container">
      <div class="tui-workspaceForm__inputs">
        <FormRow
          v-slot="{ id }"
          :label="$str('space_name_label', 'container_workspace')"
          class="tui-workspaceForm__formRow"
        >
          <InputText
            :id="id"
            v-model="name"
            :maxlength="75"
            :disabled="submitting"
          />
        </FormRow>

        <FormRow
          :label="$str('description_label', 'container_workspace')"
          class="tui-workspaceForm__formRow"
        >
          <template v-slot:default="{ id }">
            <div class="tui-workspaceForm__editor">
              <UnsavedChangesWarning
                v-if="!description.isEmpty && !submitting"
                :value="description"
              />
              <Weka
                :id="id"
                v-model="description"
                :context-id="contextId"
                :compact="true"
                :usage-identifier="{
                  component: 'container_workspace',
                  area: 'description',
                  instanceId: workspaceId,
                }"
                variant="standard"
                :extra-extensions="['hashtag', 'mention']"
                @ready="editorReady = true"
              />

              <div class="tui-workspaceForm__editor-tip">
                <span class="tui-workspaceForm__editor-tip-text">
                  {{ $str('hashtag_tip', 'container_workspace') }}
                </span>
                <InfoIconButton
                  :is-help-for="$str('hashtags', 'totara_engage')"
                >
                  {{ $str('hashtag_tip_help', 'container_workspace') }}
                </InfoIconButton>
              </div>
            </div>
          </template>
        </FormRow>

        <FormRow
          v-if="showPrivateBox"
          v-slot="{ id }"
          :label="$str('workspace_type', 'container_workspace')"
          :helpmsg="$str('workspace_type_help', 'container_workspace')"
          class="tui-workspaceForm__formRow"
        >
          <RadioGroup
            :id="id"
            v-model="innerWorkspacePrivate"
            name="workspace-access"
            :horizontal="true"
            :required="true"
          >
            <Radio v-if="canSetPublic" :value="false">
              {{ $str('public', 'container_workspace') }}
            </Radio>

            <Radio v-if="canSetPrivate" :value="true">
              {{ $str('private', 'container_workspace') }}
            </Radio>
          </RadioGroup>
        </FormRow>

        <!--
          Form row for hidden setting. We will either show this form row when the settings outside of the
          form is telling it to be shown and the private setting must be set.
        -->
        <FormRow
          v-if="showHiddenCheckBox && innerWorkspacePrivate"
          v-slot="{ id }"
          :label="$str('hidden_workspace_text', 'container_workspace')"
          :hidden="true"
        >
          <Checkbox
            :id="id"
            v-model="innerWorkspaceHidden"
            name="workspace-hidden"
            :disabled="!innerWorkspacePrivate"
            :aria-disabled="!innerWorkspacePrivate"
          >
            {{ $str('hidden_workspace_text', 'container_workspace') }}
          </Checkbox>
        </FormRow>

        <FormRow
          v-if="showUnhiddenCheckBox"
          :label="$str('unhidden_workspace_label', 'container_workspace')"
          class="tui-workspaceForm__unhiddenRow"
        >
          <template v-slot:default="{ id }">
            <!--
              This checkbox works completely reverse of the hidden workspace checkbox.
              Meaning that when this checkbox is ticked, the otherbox should go to untick and vice versa.
             -->
            <div class="tui-workspaceForm__unhiddenRow-box">
              <Checkbox
                :id="id"
                v-model="unhideWorkspaceValue"
                name="workspace-unhidden"
              >
                {{ $str('unhide_workspace', 'container_workspace') }}
              </Checkbox>

              <p class="tui-workspaceForm__unhiddenRow-helpText">
                {{ $str('unhidden_workspace_help', 'container_workspace') }}
              </p>
            </div>
          </template>
        </FormRow>
      </div>

      <FormRow class="tui-workspaceForm__imagePicker">
        <template v-slot="{ id }">
          <SpaceImagePicker
            :id="id"
            :draft-id="draftId"
            :workspace-id="workspaceId"
            @ready="draftId = $event.itemId"
            @upload-error="error.upload = $event"
            @clear-error="error.upload = null"
          />

          <FieldError :error="error.upload" />
        </template>
      </FormRow>
    </div>

    <ButtonGroup class="tui-workspaceForm__buttonGroup">
      <LoadingButton
        :loading="submitting || !editorReady"
        :disabled="submitting || disableSubmit"
        :text="submitButtonLabel"
        :aria-label="$str('submit', 'core')"
        :primary="true"
        type="submit"
        @click.prevent="submit"
      />

      <Button
        :text="$str('cancel', 'core')"
        :disabled="submitting"
        @click.prevent="$emit('cancel')"
      />
    </ButtonGroup>
  </Form>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import InputText from 'tui/components/form/InputText';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import Button from 'tui/components/buttons/Button';
import LoadingButton from 'totara_engage/components/buttons/LoadingButton';
import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';
import SpaceImagePicker from 'container_workspace/components/form/upload/SpaceImagePicker';
import FieldError from 'tui/components/form/FieldError';
import { FORMAT_JSON_EDITOR } from 'tui/format';
import RadioGroup from 'tui/components/form/RadioGroup';
import Radio from 'tui/components/form/Radio';
import Checkbox from 'tui/components/form/Checkbox';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';

export default {
  components: {
    LoadingButton,
    InputText,
    Form,
    FormRow,
    ButtonGroup,
    Button,
    Weka,
    SpaceImagePicker,
    FieldError,
    RadioGroup,
    Radio,
    Checkbox,
    InfoIconButton,
    UnsavedChangesWarning,
  },

  props: {
    submitting: Boolean,
    workspaceName: {
      type: String,
      default: '',
    },

    workspaceDescription: {
      type: String,
      default: null,
    },

    workspaceDescriptionFormat: {
      type: [String, Number],
      default() {
        return FORMAT_JSON_EDITOR;
      },
    },
    showHiddenCheckBox: {
      type: Boolean,
      default: true,
    },
    showPrivateBox: {
      type: Boolean,
      default: true,
    },
    showUnhiddenCheckBox: Boolean,
    canSetPublic: {
      type: Boolean,
      default: true,
    },
    canSetPrivate: {
      type: Boolean,
      default: true,
    },
    workspacePrivate: Boolean,
    workspaceHidden: Boolean,
    workspaceId: [String, Number],
    /**
     * A fallback props when workspace's id is not provided.
     * It should either be a workspace's context id or course category context's id.
     * Do NOT pass user's context id here.
     */
    contextId: [String, Number],
    submitButtonLabel: {
      type: String,
      default() {
        return this.$str('create', 'core');
      },
    },
  },

  data() {
    return {
      name: this.workspaceName,
      description: WekaValue.empty(),
      descriptionFormat: this.workspaceDescriptionFormat,
      draftId: null,
      innerWorkspacePrivate: this.workspacePrivate || !this.canSetPublic,
      innerWorkspaceHidden: this.workspaceHidden || false,
      error: {
        upload: null,
      },

      // This is for the form to check whether it is ready or not.
      editorReady: false,
    };
  },

  computed: {
    /**
     * @return {Boolean}
     */
    disableSubmit() {
      return 0 === this.name.length;
    },

    unhideWorkspaceValue: {
      /**
       * @return {Boolean}
       */
      get() {
        return !this.innerWorkspaceHidden;
      },

      /**
       * Set the reverse of whatever the checkbox is.
       * @param {Boolean} value
       */
      set(value) {
        this.innerWorkspaceHidden = !value;
      },
    },
  },

  watch: {
    /**
     * @param {String} value
     */
    workspaceName(value) {
      if (value !== this.name) {
        this.name = value;
      }
    },

    /**
     * @param {Number} value
     */
    workspaceDescriptionFormat(value) {
      if (value !== this.descriptionFormat) {
        this.descriptionFormat = value;
      }
    },

    workspaceDescription: {
      /**
       * @param {String} value
       */
      handler(value) {
        if (!value) {
          return;
        }

        try {
          this.description = WekaValue.fromDoc(JSON.parse(value));
        } catch (e) {
          this.description = WekaValue.empty();
        }
      },

      immediate: true,
    },

    /**
     * @param {Number|Boolean} value
     */
    innerWorkspacePrivate(value) {
      if (!value) {
        // Reset the workspace hidden.
        this.innerWorkspaceHidden = false;
      }
    },
  },

  methods: {
    submit() {
      let description = null;
      if (!this.description.isEmpty) {
        description = JSON.stringify(this.description.getDoc());
      }

      const params = {
        name: this.name,
        description: description,
        draftId: this.draftId,
        descriptionFormat: this.workspaceDescriptionFormat,
        isPrivate: !!this.innerWorkspacePrivate,
        isHidden: !!this.innerWorkspaceHidden,
      };

      this.$emit('submit', params);
    },
  },
};
</script>

<lang-strings>
  {
    "container_workspace": [
      "space_name_label",
      "description_label",
      "workspace_type",
      "public",
      "private",
      "hidden_workspace",
      "hidden_workspace_text",
      "unhidden_workspace_label",
      "unhidden_workspace_help",
      "unhide_workspace",
      "workspace_type_help",
      "hashtag_tip",
      "hashtag_tip_help"
    ],
    "totara_engage": [
      "hashtags"
    ],
    "core": [
      "create",
      "cancel",
      "submit"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-workspaceForm {
  display: flex;
  flex-direction: column;
  justify-content: space-between;

  &__container {
    display: flex;
    flex-direction: column-reverse;
    align-items: stretch;

    @media (min-width: $tui-screen-sm) {
      flex-direction: row;
    }
  }

  &__inputs {
    display: flex;
    flex-direction: column;

    @media (min-width: $tui-screen-sm) {
      width: 66%;
    }
  }

  &__unhiddenRow {
    margin-top: var(--gap-8);
    &-box {
      display: flex;
      flex-direction: column;
      flex-grow: 1;
      width: 100%;
    }

    &-helpText {
      @include tui-font-body-small();
      margin: 0;
      margin-top: var(--gap-2);
    }
  }

  &__formRow {
    // Overriding the margin
    &.tui-formRow {
      margin-bottom: 0;

      &:not(:first-child) {
        margin-top: var(--gap-8);
      }
    }
  }

  &__editor {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    width: 100%;

    &-tip {
      display: flex;
      margin-top: var(--gap-2);

      &-text {
        @include tui-font-body-small();
      }
    }
  }

  &__imagePicker {
    // This will let us to have our custom FORM input :)
    width: 15rem;
    height: 15rem;

    @media (min-width: $tui-screen-sm) {
      width: calc(100% - (66% + var(--gap-4)));
      height: 30.8rem;
    }

    &.tui-formRow {
      // Reset margin
      margin-top: var(--gap-2);
      margin-bottom: var(--gap-8);

      @media (min-width: $tui-screen-sm) {
        margin: 0;
        margin-left: var(--gap-4);
      }

      .tui-formRow {
        &__desc {
          // Hiding description part.
          display: none;
        }

        &__action {
          display: flex;
          flex-direction: column;
          flex-grow: 1;
          height: 100%;
        }
      }
    }
  }

  &__buttonGroup {
    display: flex;
    justify-content: flex-end;

    // This button group need to have the same width as the inputs div
    width: 66%;
    margin-top: var(--gap-8);

    &.tui-formBtnGroup {
      // Overriding the margin
      & > :not(:first-child) {
        margin: 0;
        margin-left: var(--gap-4);
      }
    }
  }
}
</style>
