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
  @module totara_core
-->

<template>
  <div>
    <p>
      Examples of form layouts possible with form components.
    </p>
    <h4>Activity</h4>
    <Uniform :initial-values="{ type: 'Appraisal' }">
      <FormRow label="Activity title" required>
        <FormText name="activityTitle" char-length="50" />
      </FormRow>

      <FormRow label="Description">
        <FormTextarea name="description" char-length="30" />
      </FormRow>

      <FormRow label="Type" required>
        <FormSelect name="type" :options="['Appraisal']" />
      </FormRow>

      <FormRow v-slot="{ id }" label="Hovercraft">
        <InputSet char-length="full">
          <FormText :id="id" name="hovercraft" />
          <Button text="Fill with eels" :styleclass="{ small: true }" />
        </InputSet>
      </FormRow>

      <FormRow v-slot="{ id }" label="Eel capacity">
        <InputSet char-length="full">
          <FormText :id="id" name="max" char-length="5" />
          <InputSizedText>(empty = unlimited)</InputSizedText>
        </InputSet>
      </FormRow>
    </Uniform>

    <hr />

    <h4>External participants</h4>
    <Uniform :initial-values="externalParticipants">
      <FormRow label="Peer" required>
        <FormText name="peer" :char-length="30" />
      </FormRow>

      <FormRow v-slot="{ labelId }" label="External respondent">
        <FieldArray v-slot="{ items, push, remove }" path="externalRespondents">
          <Repeater
            :rows="items"
            :min-rows="1"
            :delete-icon="true"
            :allow-deleting-first-items="false"
            :aria-labelledby="labelId"
            @add="push(createRespondent())"
            @remove="(item, i) => remove(i)"
          >
            <template v-slot="{ index }">
              <InputSet split :stack-below="30" char-length="30">
                <FormText
                  :name="[index, 'name']"
                  char-length="full"
                  :aria-label="`External respondent ${index + 1}'s name`"
                  placeholder="Name"
                />
                <FormText
                  :name="[index, 'email']"
                  char-length="full"
                  :aria-label="`External respondent ${index + 1}'s name`"
                  placeholder="Email address"
                />
              </InputSet>
            </template>
          </Repeater>
        </FieldArray>
      </FormRow>
    </Uniform>

    <hr />

    <h4>Custom rating scale</h4>
    <Uniform :initial-values="customRatingScale">
      <FormRow label="Question" required>
        <FormText name="question" :char-length="30" />
      </FormRow>

      <FormRow v-slot="{ labelId }" label="Custom rating options" required>
        <FieldArray v-slot="{ items, push, remove }" path="options">
          <Repeater
            :rows="items"
            :min-rows="2"
            :delete-icon="true"
            :allow-deleting-first-items="false"
            :aria-labelledby="labelId"
            @add="push(createRatingOption())"
            @remove="(item, i) => remove(i)"
          >
            <template v-slot:header>
              <InputSet split char-length="30">
                <InputSetCol units="5">
                  <Label label="Text label" subfield />
                </InputSetCol>
                <InputSetCol>
                  <Label label="Score" subfield />
                </InputSetCol>
              </InputSet>
            </template>
            <template v-slot="{ index }">
              <!--
                Alternatively, you could not use `split` and set an
                char-length on each input instead of units.
                However it wouldn't match the width of question exactly.
              -->
              <InputSet split char-length="30">
                <InputSetCol units="5">
                  <FormText
                    :name="[index, 'label']"
                    :aria-label="`Option ${index + 1} text label`"
                  />
                </InputSetCol>
                <InputSetCol>
                  <FormNumber
                    :name="[index, 'score']"
                    :aria-label="`Option ${index + 1} score`"
                  />
                </InputSetCol>
              </InputSet>
            </template>
          </Repeater>
        </FieldArray>
      </FormRow>

      <FormRow label="Reporting ID">
        <FormText name="reportingId" :char-length="15" />
      </FormRow>

      <FormCheckbox name="responseRequired">
        Response required
      </FormCheckbox>
    </Uniform>

    <hr />

    <h4>Address</h4>
    <Uniform>
      <FormRow label="Address">
        <InputSet vertical char-length="30">
          <InputSet split :stack-below="30" char-length="full">
            <FormRow label="First name" vertical subfield>
              <FormText name="firstName" />
            </FormRow>
            <FormRow label="Last name" vertical subfield>
              <FormText name="lastName" />
            </FormRow>
          </InputSet>
          <FormRow label="Email" vertical subfield>
            <FormText name="email" char-length="full" />
          </FormRow>
          <FormRow label="Address" vertical subfield>
            <FormText name="address" char-length="full" />
          </FormRow>
          <FormRow label="Address 2" vertical subfield>
            <FormText name="address2" char-length="full" />
          </FormRow>
          <InputSet split :stack-below="30" char-length="full">
            <InputSetCol units="2">
              <FormRow label="Country" vertical subfield>
                <FormText name="country" />
              </FormRow>
            </InputSetCol>
            <InputSetCol units="2">
              <FormRow label="Region" vertical subfield>
                <FormText name="region" />
              </FormRow>
            </InputSetCol>
            <InputSetCol>
              <FormRow label="Postcode" vertical subfield>
                <FormText name="postcode" />
              </FormRow>
            </InputSetCol>
          </InputSet>
        </InputSet>
      </FormRow>
    </Uniform>
  </div>
</template>

<script>
import {
  Uniform,
  FieldArray,
  FormRow,
  FormText,
  FormTextarea,
  FormNumber,
  FormCheckbox,
  FormSelect,
} from 'tui/components/uniform';
import Label from 'tui/components/form/Label';
import InputSet from 'tui/components/form/InputSet';
import InputSetCol from 'tui/components/form/InputSetCol';
import InputSizedText from 'tui/components/form/InputSizedText';
import Repeater from 'tui/components/form/Repeater';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Uniform,
    FieldArray,
    FormRow,
    FormText,
    FormTextarea,
    FormNumber,
    FormSelect,
    Label,
    Repeater,
    FormCheckbox,
    InputSet,
    InputSetCol,
    InputSizedText,
    Button,
  },

  data() {
    return {
      externalParticipants: {
        externalRespondents: [this.createRespondent(), this.createRespondent()],
      },
      customRatingScale: {
        options: [this.createRatingOption(), this.createRatingOption()],
      },
    };
  },

  methods: {
    createRespondent() {
      return { name: '', email: '' };
    },
    createRatingOption() {
      return { label: '', score: null };
    },
  },
};
</script>
