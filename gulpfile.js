/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2018, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.4.0
 * ---------------------------------------------------------------------------- */

// Gulp instance and plugins.
const gulp = require('gulp');
const fs = require('fs-extra');
const zip = require('zip-dir');
const plugins = require('gulp-load-plugins')();
const {execSync} = require('child_process');
const del = require('del');

// Gulp error handling.
const source = gulp.src;
gulp.src = function () {
    return source.apply(gulp, arguments)
        .pipe(plugins.plumber({
            errorHandler: plugins.notify.onError('Error: <%= error.message %>')
        }));
};

gulp.task('package', (done) => {
    // The archive name follows the runtime EA version (Config\Deployment::$version),
    // so releases are bumped in one place only.
    const deploymentPhp = fs.readFileSync('app/Config/Deployment.php', 'utf8');
    const version = deploymentPhp.match(/public string \$version = '([^']+)';/)[1];

    if ( ! version)
    {
        throw new Error('Deployment version not found.');
    }

    const archive = `easyappointments-${version}.zip`;

    fs.removeSync('build');
    fs.removeSync(archive);

    fs.mkdirsSync('build');
    fs.copySync('app', 'build/app');
    fs.copySync('public', 'build/public');
    fs.copySync('engine', 'build/engine');
    if (fs.existsSync('docs/security-ops.md')) {
        fs.copySync('docs/security-ops.md', 'build/docs/security-ops.md');
    }

    // CI4 runtime trees: writable/ (server-guarded) + storage/ (rate-limit files
    // and logs keep their CI3-era home here), placeholders ship so dirs land.
    const writableDirs = ['cache', 'debugbar', 'logs', 'session', 'uploads'];

    writableDirs.forEach(function (dir) {
        fs.ensureDirSync('build/writable/' + dir);
        fs.copySync('writable/' + dir + '/index.html', 'build/writable/' + dir + '/index.html');
    });
    fs.copySync('writable/.htaccess', 'build/writable/.htaccess');

    fs.ensureDirSync('build/storage/backups');
    fs.copySync('storage/backups/.htaccess', 'build/storage/backups/.htaccess');
    fs.copySync('storage/backups/index.html', 'build/storage/backups/index.html');
    fs.ensureDirSync('build/storage/cache');
    fs.copySync('storage/cache/index.html', 'build/storage/cache/index.html');

    fs.copySync('spark', 'build/spark');
    fs.copySync('preload.php', 'build/preload.php');
    fs.copySync('.env.example', 'build/.env.example');
    fs.copySync('composer.json', 'build/composer.json');
    fs.copySync('composer.lock', 'build/composer.lock');
    fs.copySync('CHANGELOG.md', 'build/CHANGELOG.md');
    fs.copySync('README.md', 'build/README.md');
    fs.copySync('LICENSE', 'build/LICENSE');

    execSync('cd build && composer install --no-interaction --no-dev --no-scripts --optimize-autoloader', function (err, stdout, stderr) {
        console.log(stdout);
        console.log(stderr);
    });

    del.sync('**/.DS_Store');

    fs.removeSync('build/composer.lock');

    del.sync('**/.DS_Store');

    del.sync('build/vendor/codeigniter4/framework/user_guide');

    zip('build', {saveTo: archive}, function (err) {
        if (err) {
            console.log('Zip Error', err);
        }

        done();
    });
});

gulp.task('clean', (done) => {
    fs.removeSync('public/assets/js/**/*.min.js');
    fs.removeSync('public/assets/css/**/*.min.css');
    done();
});

gulp.task('docs', (done) => {
    fs.removeSync('docs/apigen/html');
    fs.removeSync('docs/jsdoc/html');
    fs.removeSync('docs/plato/html');

    fs.mkdirSync('docs/apigen/html');
    fs.mkdirSync('docs/jsdoc/html');
    fs.mkdirSync('docs/plato/html');

    const commands = [
        'npx jsdoc "engine" -d "docs/apigen/html"',

        'npx jsdoc "public/assets/js" -d "docs/jsdoc/html"',

        'npx plato -r -d "docs/plato/html" "public/assets/js"'
    ];

    commands.forEach(function (command) {
        execSync(command, function (err, stdout, stderr) {
            console.log(stdout);
            console.log(stderr);
        });
    });

    done();
});

gulp.task('scripts', (done) => {
    return gulp.src([
        'public/assets/js/**/*.js',
        '!public/assets/js/**/*.min.js'
    ])
        .pipe(plugins.changed('public/assets/js/**/*'))
        .pipe(plugins.uglify().on('error', console.log))
        .pipe(plugins.rename({suffix: '.min'}))
        .pipe(gulp.dest('public/assets/js'));
});

gulp.task('styles', () => {
    return gulp.src([
        'public/assets/css/**/*.css',
        '!public/assets/css/**/*.min.css'
    ])
        .pipe(plugins.changed('public/assets/css/**/*'))
        .pipe(plugins.cleanCss())
        .pipe(plugins.rename({suffix: '.min'}))
        .pipe(gulp.dest('public/assets/css'));
});

gulp.task('watch', (done) => {
    gulp.watch([
        'public/assets/js/**/*.js',
        '!public/assets/js/**/*.min.js'
    ], gulp.parallel('scripts'));

    gulp.watch([
        'public/assets/css/**/*.css',
        '!public/assets/css/**/*.min.css'
    ], gulp.parallel('styles'));

    done();
});

gulp.task('dev', gulp.series('clean', 'scripts', 'styles', 'watch'));

gulp.task('build', gulp.series('clean', 'scripts', 'styles'));

gulp.task('default', gulp.parallel('dev'));
