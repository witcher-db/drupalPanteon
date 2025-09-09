let gulp = require('gulp'),

  sass = require('gulp-sass')(require('sass')),
  sourcemaps = require('gulp-sourcemaps'),
  cached = require('gulp-cached'),
  newer = require('gulp-newer'),
  $ = require('gulp-load-plugins')(),
  cleanCss = require('gulp-clean-css'),
  rename = require('gulp-rename'),
  postcss = require('gulp-postcss'),
  autoprefixer = require('autoprefixer'),
  postcssInlineSvg = require('postcss-inline-svg'),
  browserSync = require('browser-sync').create(),
  pxtorem = require('postcss-pxtorem'),
  postcssProcessors = [
    postcssInlineSvg({
      removeFill: true,
      paths: ['./node_modules/bootstrap-icons/icons'],
    }),
    pxtorem({
      propList: [
        'font',
        'font-size',
        'line-height',
        'letter-spacing',
        '*margin*',
        '*padding*',
      ],
      mediaQuery: true,
    }),
  ];

const paths = {
  scss: {
    src: './src/scss/style.scss',
    dest: './dest/css',
    watch: './src/scss/**/*.scss',
    bootstrap: './node_modules/bootstrap/scss/bootstrap.scss',
  },
  js: {
    bootstrap: './node_modules/bootstrap/dist/js/bootstrap.min.js',
    popper: './node_modules/@popperjs/core/dist/umd/popper.min.js',
    base: '../../contrib/bootstrap/js/base.js',
    src: './src/js/custom.js',
    dest: './dest/js',
  },
  component : {
    src: './components/**/src/*.scss',
    watch: './components/**/src/*.scss',
    dest: './components/',
  }
};

function component() {
  return gulp
    .src(paths.component.src)
    .pipe(sass().on('error', sass.logError))
    .pipe(rename(function(path) {
      path.dirname = path.dirname.replace('/src', '');
    }))
    .pipe(gulp.dest(paths.component.dest));
}

// Compile sass into CSS & auto-inject into browsers
function styles() {
  return gulp
    .src([paths.scss.bootstrap, paths.scss.src])
    .pipe(sourcemaps.init())
    .pipe(cached('styles'))
    .pipe(newer(paths.scss.dest))
    .pipe(
      sass({
        includePaths: [
          './node_modules/bootstrap/scss',
          '../../contrib/bootstrap/scss',
        ],
      }).on('error', sass.logError)
    )
    .pipe($.postcss(postcssProcessors))
    .pipe(
      postcss([
        autoprefixer({
          browsers: [
            'Chrome >= 35',
            'Firefox >= 38',
            'Edge >= 12',
            'Explorer >= 10',
            'iOS >= 8',
            'Safari >= 8',
            'Android 2.3',
            'Android >= 4',
            'Opera >= 12',
          ],
        }),
      ])
    )
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(cleanCss())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest(paths.scss.dest))
    .pipe(browserSync.stream());
}

// Move the javascript files into our js folder
function js() {
  return gulp
    .src([paths.js.bootstrap, paths.js.popper, paths.js.base, paths.js.src])
    .pipe(gulp.dest(paths.js.dest))
    .pipe(browserSync.stream());
}

// Static Server + watching scss/html files
function serve() {
  gulp
    .watch([paths.scss.watch, paths.scss.bootstrap], styles)
    .on('change', browserSync.reload);
  gulp
    .watch(paths.component.watch, component)
    .on('change', browserSync.reload);

}

const build = gulp.series(styles, gulp.parallel(js, serve, component));

exports.styles = styles;
exports.js = js;
exports.serve = serve;
exports.component = component;

exports.default = build;
