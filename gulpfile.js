'use strict';

// Defines
const gulp = require('gulp'),
    util = require('gulp-util'),
    merge = require('merge-stream'),
    concat = require('gulp-concat'),
    sass = require('gulp-sass'),
    sassGlob = require('gulp-sass-glob'),
    del = require('del'),
    sourcemaps = require('gulp-sourcemaps'),
    autoprefixer = require('gulp-autoprefixer'),
    uglifyjs = require('gulp-uglify'),
    uglifycss = require('gulp-uglifycss'),
    livereload = require('gulp-livereload'),
    filter = require('gulp-filter'),
    bower = require('gulp-bower'),
    bowerFiles = require('main-bower-files'),
    ngAnnotate = require('gulp-ng-annotate'),
    notify = require('gulp-notify');

/*
 * Enable production by passing --production to the gulp command
 *
 * Example: `gulp build --production`
 *
 * @todo: Also check the SYMFONY_ENV environment parameter?
 */
var production = !!util.env.production;

// Error handler
var errorHandler = function(error) {
    var lineNumber = (error.lineNumber) ? 'LINE ' + error.lineNumber + ' -- ' : '';

    notify({
        title: 'Task Failed [' + error.plugin + ']',
        message: lineNumber + 'See console.',
        sound: 'Sosumi' // old school macintosh sound! LOVE IT!
        // NO IT'S THE WORST WHY WOULD YOU DO THIS TO ME ADAM
    }).write(error);

    util.beep(); // Beep 'sosumi' again // NO, DON'T.

    // Pretty error reporting
    var report = '';
    var chalk = util.colors.white.bgRed;

    report += chalk('Task:') + ' [' + error.plugin + ']\n';
    report += chalk('Message:') + ' ' + error.message + '\n';
    if (error.lineNumber) {
        report += chalk('LINE:') + ' ' + error.lineNumber + '\n';
    }

    if (error.fileName) {
        report += chalk('FILE:') + ' ' + error.fileName + '\n';
    }

    console.error(report);

    // Prevent the 'watch' task from stopping
    this.emit('end');
};

// Start Tasks
gulp.task('clean', function() {
    return del.sync([
        'web/css',
        'web/js',
        'web/img',
        'web/fonts'
    ]);
});

gulp.task('bower-install', function() {
    return bower();
});

gulp.task('bower-clean', function() {
    return del.sync('web/components');
});

gulp.task('copy-fonts', function() {
    return gulp.src([
        'node_modules/font-awesome/fonts/*',
        'app/Resources/public/fonts/**/*.*',
        '!app/Resources/public/fonts/.gitkeep'
    ])
        .pipe(gulp.dest('web/fonts'));
});

gulp.task('copy-images', function() {
    return gulp.src([
        'app/Resources/public/img/**/*.*',
        '!app/Resources/public/img/.gitkeep'
    ])
        .pipe(gulp.dest('web/img'));
});

gulp.task('build-global-css', ['bower-install'], function() {
    return gulp.src('app/Resources/public/scss/styles.scss')
        .pipe(!production ? sourcemaps.init() : util.noop())
        .pipe(sassGlob())
        .pipe(sass().on('error', errorHandler))
        .pipe(autoprefixer())
        .pipe(concat('styles.css'))
        .pipe(production ? uglifycss() : sourcemaps.write('.'))
        .pipe(gulp.dest('web/css'))
        .pipe(filter('web/css/styles.css'))
        .pipe(livereload());
});

gulp.task('build-vendor-css', ['bower-install'], function() {
    // Add future vendor css task here
});

gulp.task('build-global-js', ['bower-install'], function() {
    return gulp.src('app/Resources/public/js/global/*.js')
        .pipe(concat('global.js'))
        .pipe(ngAnnotate())
        .pipe(production ? uglifyjs() : util.noop())
        .pipe(gulp.dest('web/js'))
        .pipe(filter('web/js/global.js'))
        .pipe(livereload());
});

gulp.task('build-page-specific-js', ['bower-install'], function() {
    return gulp.src([
        'app/Resources/public/js/local/**/*.js',
        '!app/Resources/public/js/local/.gitkeep'
    ])
        .pipe(ngAnnotate())
        .pipe(production ? uglifyjs() : util.noop())
        .pipe(gulp.dest('web/js/local'))
        .pipe(livereload());
});

gulp.task('build-vendor-js', ['bower-install'], function() {
    return gulp.src(bowerFiles(/[.]js/).concat([
        // Link to bundle JS or custom vendor scripts here
        'web/bundles/fosjsrouting/js/router.js'
    ]))
        .pipe(concat('vendor.js'))
        .pipe(ngAnnotate())
        .pipe(production ? uglifyjs() : util.noop())
        .pipe(gulp.dest('web/js'))
        .pipe(filter('web/js/vendor.js'));
});

gulp.task('watch', ['build'], function() {
    livereload.listen();

    gulp.watch('app/Resources/public/scss/**/*.scss', ['build-global-css']);
    gulp.watch('app/Resources/public/js/**/*.js', ['build-js']);
    gulp.watch('app/Resources/public/img/**/*.*', ['copy-images']);

    // Watch for changes in twig templates and trigger a livereload
    gulp.watch([
        'src/AppBundle/Resources/views/**/*.twig'
    ]).on('change', function (file) {
        livereload.changed(file.path);
        util.log(util.colors.yellow('Twig Template changed' + ' (' + file.path + ')'));
    });
});

// Simplified Tasks
gulp.task('build-css', ['build-global-css', 'build-vendor-css']);
gulp.task('build-js', ['build-global-js', 'build-page-specific-js', 'build-vendor-js']);
gulp.task('build', ['copy-fonts', 'copy-images', 'build-css', 'build-js']);

// Setup the default task. We don't want to use watch on prod.
gulp.task('default', (production ? ['build'] : ['watch']));
