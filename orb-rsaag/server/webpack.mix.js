const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .webpackConfig({
        resolve: {
            alias: {
                '@': `${__dirname}/resources`
            }
        }
    })
    .extract([
        "@tinymce/tinymce-vue",
        'axios',
        'lodash',
        'popper.js',
        "tinymce",
        'vue',
        'jquery',
        'date-fns',
        'vee-validate',
        'vue-carousel',
        'vue-multiselect',
        'vue-notification',
        'vue-qr',
        'vue-recaptcha',
        'vue-router',
        'vue2-editor',
        'vuex'
    ])
    .sass('resources/sass/app.scss', 'public/css')
    .copy("node_modules/tinymce/skins", "public/js/skins")
    .copy('resources/images', 'public/images');
//.browserSync('http://127.0.0.1:8000'); //@todo: временная версия, просто хардкод! надо придумать, как подставлять значение прокси динамически.

if (mix.inProduction() || process.env.MIX_NEED_VERSION) {
    mix.version()
}

if (process.env.MIX_NEED_SOURCE) {
    mix.sourceMaps(false)
}