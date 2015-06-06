var elixir = require('laravel-elixir');

elixir(function(mix) {
    mix.styles([
     'normalize.css',
     'main.css',
     'codemirror.css',
     'laravel.css'
    ], 'resources/public/style.css', 'resources/assets/css/');
});

elixir(function(mix) {
    mix.scripts([
     'vendor/plugins.js',
     'vendor/codemirror.js',
     'main.js'
    ], 'resources/public/script.js', 'resources/assets/js/');
});
