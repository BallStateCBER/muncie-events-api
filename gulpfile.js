var gulp = require('gulp');
var less = require('gulp-less');
var notify = require("gulp-notify");
var LessPluginCleanCSS = require('less-plugin-clean-css');
var jshint = require('gulp-jshint');
var stylish = require('jshint-stylish');
var phpcs = require('gulp-phpcs');
var phpunit = require('gulp-phpunit');
var _ = require('lodash');

function customNotify(message) {
	return notify({
        title: 'Muncie Events API',
        message: function(file) {
            return message + ': ' + file.relative;
        }
    })
}

phpCS = function (cb) {
    return gulp.src(['src/**/*.php', 'config/*.php', 'tests/*.php', 'tests/**/*.php', 'config/**/*.php'])
    // Validate files using PHP Code Sniffer
        .pipe(phpcs({
            bin: '.\\vendor\\bin\\phpcs.bat',
            standard: '.\\vendor\\cakephp\\cakephp-codesniffer\\CakePHP',
            errorSeverity: 1,
            warningSeverity: 1
        }))
        // Log all problems that was found
        .pipe(phpcs.reporter('log'));
};

gulp.task('default', phpCS);



/**************
 *    PHP     *
 **************/

gulp.task('php_cs', phpCS);

function testNotification(status, pluginName, override) {
    var options = {
        title:   ( status === 'pass' ) ? 'Tests Passed' : 'Tests Failed',
        message: ( status === 'pass' ) ? 'All tests have passed!' : 'One or more tests failed',
        icon:    __dirname + '/node_modules/gulp-' + pluginName +'/assets/test-' + status + '.png'
    };
    options = _.merge(options, override);
    return options;
}

gulp.task('php_unit', function() {
    gulp.src('phpunit.xml')
        .pipe(phpunit('', {notify: true}))
        .on('error', notify.onError(testNotification('fail', 'phpunit')))
        .pipe(notify(testNotification('pass', 'php_unit')));
});



/**************
 * Javascript *
 **************/
var srcJsFiles = [
    'webroot/js/script.js'
];

gulp.task('js_lint', function () {
    return gulp.src(srcJsFiles)
        .pipe(jshint())
        .pipe(jshint.reporter(stylish))
        .pipe(customNotify('JS linted'));
});



/**************
 *    LESS    *
 **************/
var srcLessFiles = [
    'webroot/css/style.less'
];

gulp.task('less', function () {
    var cleanCSSPlugin = new LessPluginCleanCSS({advanced: true});
    gulp.src(srcLessFiles)
        .pipe(less({plugins: [cleanCSSPlugin]}))
        .pipe(gulp.dest('webroot/css'))
        .pipe(customNotify('LESS compiled'));
});


/********************
 * NPM DEPENDENCIES *
 ********************/
gulp.task('copy_npm_dep', function () {
    gulp.src('./node_modules/flatpickr/dist/flatpickr.min.css')
        .pipe(gulp.dest('./webroot/flatpickr/'));

    gulp.src('./node_modules/flatpickr/dist/flatpickr.min.js')
        .pipe(gulp.dest('./webroot/flatpickr/'))
        .pipe(customNotify('Files copied'));
});
