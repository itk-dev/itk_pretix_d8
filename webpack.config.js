var Encore = require('@symfony/webpack-encore');

Encore
    // the project directory where all compiled assets will be stored
    .setOutputPath('build/')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/build')

    // will create .js
    .addEntry('itk_pretix_dawa', './assets/js/dawa.js')

    .configureTerserPlugin(
      (options) => {
        options.terserOptions = {
          output: {
            // Comments in output breaks dawa-autocomplete2.
            comments: false,
          },
        };
      }
    )

    .disableSingleRuntimeChunk()
    .autoProvidejQuery()

    // enable source maps during development
    .enableSourceMaps(!Encore.isProduction())

    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()
;

// export the final configuration
module.exports = Encore.getWebpackConfig();
