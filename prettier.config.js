// prettier.config.js
module.exports = {
  tabWidth: 2,
  singleQuote: true,
  printWidth: 100,
  trailingComma: 'all',
  parser: 'flow',
  overrides: [
    {
      files: ['composer.json'],
      options: {
        parser: 'json',
        tabWidth: 4,
        singleQuote: false,
      },
    },
  ],
};
