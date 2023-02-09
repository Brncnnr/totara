/**
 * Prettier support for GraphQL support.
 */
module.exports = {
  singleQuote: true,
  trailingComma: 'es5',
  overrides: [
    {
      files: "*.graphqls",
      options: {
        parser: "graphql"
      }
    }
  ]
};