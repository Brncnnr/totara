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
 * @module editor_weka
 */

import {
  joinUp,
  joinDown,
  lift,
  selectParentNode,
} from 'ext_prosemirror/commands';
import { undo, redo } from 'ext_prosemirror/history';
import { undoInputRule } from 'ext_prosemirror/inputrules';

const isMac =
  typeof navigator != 'undefined' ? /Mac/.test(navigator.platform) : false;

export function buildKeymap(schema, extensionFns) {
  let keys = {};

  function bind(key, cmd) {
    keys[key] = cmd;
  }

  bind('Mod-z', undo);
  bind('Shift-Mod-z', redo);
  bind('Backspace', undoInputRule);
  if (!isMac) bind('Mod-y', redo);

  bind('Alt-ArrowUp', joinUp);
  bind('Alt-ArrowDown', joinDown);
  bind('Mod-BracketLeft', lift);
  bind('Escape', selectParentNode);

  const context = { schema, isMac };

  extensionFns.forEach(extensionFn => {
    extensionFn(bind, context);
  });

  return keys;
}
