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

const strings = {
  'foo/bar': 'baz',
  'foo/baz': 'qux',
  'foo/replace': 'hello {$a}',
  'foo/replace_complex': 'hello {$a->name}, today is {$a->weather}',
  'core/save': 'Save',
  'moodle/save': 'Save',
};

export const getString = jest.fn((k, c) => strings[`${c}/${k}`]);

export const hasString = jest.fn((k, c) => !!strings[`${c}/${k}`]);

export const __setString = (k, c, v) => {
  strings[`${c}/${k}`] = v;
};

export const loadStrings = jest.fn(requests => {
  requests.forEach(x => {
    strings[`${x.component}/${x.key}`] = `str:${x.component}/${x.key}`;
  });
});
