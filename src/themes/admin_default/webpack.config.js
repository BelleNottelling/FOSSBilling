const fs = require("node:fs");
const Encore = require('@symfony/webpack-encore');
const SpriteLoaderPlugin = require('svg-sprite-loader/plugin');
const ImageMinimizerPlugin = require("image-minimizer-webpack-plugin");

if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  .setOutputPath('./build/')
  .setPublicPath('/themes/admin_default/build')

  .configureFilenames( {
    js: 'js/[name]-bundle.[contenthash:6].js',
    css: 'css/[name]-bundle.[contenthash:6].css'
  })

  .addEntry('fossbilling', './assets/fossbilling.js')

  .autoProvidejQuery()
  .enableIntegrityHashes()
  .disableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .configureBabel((config) => {
    config.plugins.push('@babel/plugin-proposal-class-properties');
    config.plugins.push('@babel/plugin-proposal-object-rest-spread');
  })
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = 'usage';
    config.corejs = 3;
  })
  .enableSassLoader()
  .enablePostCssLoader()
  .configureTerserPlugin((options) => {
    if (Encore.isProduction()) {
      options.extractComments = false;
      options.terserOptions = {
        format: {
          comments: false,
        },
      };
    }
  })
  .configureCssLoader((config) => {
    config.url = {
      filter: (url) => {
        if(!fs.existsSync(url)) {
          // replace css url path
          let path = url.replace(/^(\.\.\/){2}/g, './');
          // if still does not resolve, ignore that url
          return fs.existsSync(path) ? path : false;
        }
      }
    }
  })
  .addLoader({
    test: /\.svg$/,
    exclude: /node_modules/,
    use: [
      {
        loader: 'svg-sprite-loader',
        options: {
          spriteFilename: 'icons-sprite.svg',
          publicPath: 'symbol/',
          extract: true,
        }
      },
      'svgo-loader'
    ]
  })
  .addLoader({
    test: /\.svg$/,
    use: [
      {
        loader: ImageMinimizerPlugin.loader,
        options: {
          minimizer: {
            implementation: ImageMinimizerPlugin.svgoMinify,
            options: {
              encodeOptions: {
                plugins: [
                  "preset-default",
                ],
              },
            },
          },
        }
      }
    ]
  })
  .addLoader({
    //test: /\.(jpe?g|png|gif|webp|avif)$/i,
    test: /\.(jpe?g|png|webp|avif)$/i,
    use: [
      {
        loader: ImageMinimizerPlugin.loader,
        options: {
          minimizer: {
            implementation: ImageMinimizerPlugin.sharpMinify,
            options: {
              encodeOptions: {
                jpeg: {
                  quality: 100,
                },
                webp: {
                  lossless: true,
                },
                avif: {
                  lossless: true,
                },
                png: {},
                gif: {},
              },
            },
          },
        }
      }
    ]
  })
  .addPlugin(new SpriteLoaderPlugin({ plainSprite: true }))
;

module.exports = Encore.getWebpackConfig();
