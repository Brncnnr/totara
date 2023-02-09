/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

// form
export { default as Uniform } from 'tui/components/uniform/Uniform';
export { default as FormField } from 'tui/components/uniform/FormField';
export { default as FieldArray } from 'tui/components/reform/FieldArray';
export { default as FormScope } from 'tui/components/reform/FormScope';
export { default as FormRow } from 'tui/components/form/FormRow';
export { default as FormRowStack } from 'tui/components/form/FormRowStack';

// inputs
export { default as FormCheckbox } from 'tui/components/uniform/FormCheckbox';
export { default as FormCheckboxGroup } from 'tui/components/uniform/FormCheckboxGroup';
export { default as FormDateSelector } from 'tui/components/uniform/FormDateSelector';
export { default as FormEmail } from 'tui/components/uniform/FormEmail';
export { default as FormImageUpload } from 'tui/components/uniform/FormImageUpload';
export { default as FormNumber } from 'tui/components/uniform/FormNumber';
export { default as FormCurrency } from 'tui/components/uniform/FormCurrency';
export { default as FormPassword } from 'tui/components/uniform/FormPassword';
export { default as FormRadioGroup } from 'tui/components/uniform/FormRadioGroup';
export { default as FormRadioWithInput } from 'tui/components/uniform/FormRadioWithInput';
export { default as FormSearch } from 'tui/components/uniform/FormSearch';
export { default as FormSelect } from 'tui/components/uniform/FormSelect';
export { default as FormColor } from 'tui/components/uniform/FormColor';
export { default as FormToggleSwitch } from 'tui/components/uniform/FormToggleSwitch';
export { default as FormTel } from 'tui/components/uniform/FormTel';
export { default as FormText } from 'tui/components/uniform/FormText';
export { default as FormTextarea } from 'tui/components/uniform/FormTextarea';
export { default as FormUrl } from 'tui/components/uniform/FormUrl';
export { default as FormRange } from 'tui/components/uniform/FormRange';

// util
export { createUniformInputWrapper } from './util';
