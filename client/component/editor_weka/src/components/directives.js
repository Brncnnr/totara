/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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

function ignore(e) {
  e.wekaIgnore = true;
}

// prevent keyboard and mouse events from being handled by the editor
export const wekaNodeUI = {
  bind(el) {
    el.addEventListener('keydown', ignore);
    el.addEventListener('keypress', ignore);
    el.addEventListener('keyup', ignore);
    el.addEventListener('mousedown', ignore);
    el.addEventListener('mouseup', ignore);
    el.addEventListener('click', ignore);
  },
  unbind(el) {
    el.removeEventListener('keydown', ignore);
    el.removeEventListener('keypress', ignore);
    el.removeEventListener('keyup', ignore);
    el.removeEventListener('mousedown', ignore);
    el.removeEventListener('mouseup', ignore);
    el.removeEventListener('click', ignore);
  },
};
