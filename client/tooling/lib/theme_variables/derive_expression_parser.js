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

const {
  isFail,
  parserState,
  match,
  lit,
  any,
  sequence,
  many,
  optional,
  map,
  def,
} = require('../parser_combinator');

const expr = def(() => any(call, ref, literal));

// parse literals
const percentLiteral = map(match(/^(-?[\d.]+)%/), r => ({
  type: 'percent',
  value: Number(r[1]),
}));
const numberLiteral = map(match(/^(-?[\d.]+)/), r => ({
  type: 'number',
  value: Number(r[1]),
}));
const ident = map(match(/^[A-Za-z][\w-]+/), r => r[0]);
const atom = map(ident, r => ({ type: 'atom', name: r }));
const literal = any(percentLiteral, numberLiteral, atom);

const varRef = map(match(/^var\(--([A-Za-z][\w-]+)\)/), r => ({
  type: 'var-ref',
  name: r[1],
}));
const ref = varRef;

// parse args
const argName = map(sequence(ident, match(/^:\s+/)), ([name]) => name);
const arg = sequence(optional(argName), expr);
const args = map(
  sequence(arg, many(sequence(match(/^,\s+/), arg))),
  ([firstArg, restArgs]) => {
    const allArgs = [firstArg, ...restArgs.map(x => x[1])];
    return {
      positional: allArgs.filter(([name]) => !name).map(([, value]) => value),
      named: allArgs.reduce((acc, [name, value]) => {
        if (name) {
          acc[name] = value;
        }
        return acc;
      }, {}),
    };
  }
);

// parse calls
const call = map(
  sequence(ident, lit('('), optional(args), lit(')')),
  ([name, , args]) => {
    const val = {
      type: 'call',
      name,
      args: args && args.positional,
    };
    if (args && Object.keys(args.named).length > 0) {
      val.namedArgs = args.named;
    }
    return val;
  }
);

/**
 * Parse derive expression (from theme:derive comment).
 *
 * @param {string} str
 * @returns {}
 */
function parseDerive(str) {
  const val = parserState(str);
  const result = expr(val);
  if (isFail(result)) {
    throw new Error('Invalid derive expression: ' + str);
  }
  return result;
}

module.exports = { parseDerive };
