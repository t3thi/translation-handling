/* eslint-env node, commonjs */
/* eslint-disable @typescript-eslint/no-var-requires */

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

module.exports = function (grunt) {

  const sass = require('sass');

  // Project configuration.
  grunt.initConfig({
    paths: {
      sources: 'Sources/',
      root: '../',
      sass: '<%= paths.sources %>Sass/',
      translation_handling: '<%= paths.root %>/Resources/',
      node_modules: 'node_modules/',
    },
    stylelint: {
      options: {
        configFile: '<%= paths.root %>/Build/.stylelintrc',
      },
      sass: ['<%= paths.sass %>**/*.scss']
    },
    sass: {
      options: {
        implementation: sass,
        outputStyle: 'expanded',
        precision: 8
      },
      translationHandling: {
        files: {
          '<%= paths.translation_handling %>Public/Css/translation-handling.css': '<%= paths.sass %>translation-handling.scss'
        }
      }
    },
    postcss: {
      options: {
        map: false,
        processors: [
          require('autoprefixer')(),
          require('postcss-clean')({
            rebase: false,
            format: 'keep-breaks',
            level: {
              1: {
                specialComments: 0
              }
            }
          }),
          require('postcss-banner')({
            banner: 'This file is part of the TYPO3 CMS project.\n' +
              '\n' +
              'It is free software; you can redistribute it and/or modify it under\n' +
              'the terms of the GNU General Public License, either version 2\n' +
              'of the License, or any later version.\n' +
              '\n' +
              'For the full copyright and license information, please read the\n' +
              'LICENSE.txt file that was distributed with this source code.\n' +
              '\n' +
              'The TYPO3 project - inspiring people to share!',
            important: true,
            inline: false
          })
        ]
      },
      translationHandling: {
        src: '<%= paths.translation_handling %>Public/Css/*.css'
      }
    },
    exec: {
      'npm-install': 'npm install'
    },
    watch: {
      options: {
        livereload: true
      },
      sass: {
        files: '<%= paths.sass %>**/*.scss',
        tasks: ['css', 'bell']
      },
    },
    copy: {
      options: {
        punctuation: ''
      },
      translationHandling: {
        files: [{
          src: [
            '<%= paths.node_modules %>@popperjs/core/dist/umd/popper.min.js',
          ],
          dest: '<%= paths.translation_handling %>Public/JavaScript/popper.min.js',
        },{
          src: [
            '<%= paths.node_modules %>bootstrap/dist/js/bootstrap.min.js',
          ],
          dest: '<%= paths.translation_handling %>Public/JavaScript/bootstrap.min.js',
        }]
      }
    },
    newer: {
      options: {
        cache: './.cache/grunt-newer/'
      }
    },
    concurrent: {
      lint: ['stylelint'],
      copy_static: ['copy:translationHandling'],
      compile_assets: ['css'],
    },
  });

  // Register tasks
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('@lodder/grunt-postcss');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-exec');
  grunt.loadNpmTasks('grunt-stylelint');
  grunt.loadNpmTasks('grunt-newer');
  grunt.loadNpmTasks('grunt-concurrent');

  /**
   * grunt lint
   *
   * call "$ grunt lint"
   *
   * this task does the following things:
   * - eslint
   * - stylelint
   */
  grunt.registerTask('lint', ['concurrent:lint']);

  /**
   * grunt css task
   *
   * call "$ grunt css"
   *
   * this task does the following things:
   * - sass
   * - postcss
   */
  grunt.registerTask('css', ['newer:sass', 'newer:postcss']);

  /**
   * Outputs a "bell" character. When output, modern terminals flash shortly or produce a notification (usually configurable).
   * This Grunt config uses it after the "watch" task finished compiling, signaling to the developer that her/his changes
   * are now compiled.
   */
  grunt.registerTask('bell', () => console.log('\u0007'));

  /**
   * grunt default task
   *
   * call "$ grunt default"
   *
   * this task does the following things:
   * - execute update task
   * - execute copy task
   * - compile sass files
   * - uglify js files
   * - minifies svg files
   * - compiles TypeScript files
   */
  grunt.registerTask('default', ['concurrent:lint', 'concurrent:copy_static', 'concurrent:compile_assets']);

  /**
   * grunt build task (legacy, for those used to it). Use `grunt default` instead.
   *
   * call "$ grunt build"
   *
   * this task does the following things:
   * - execute exec:npm-install task
   * - execute all task
   */
  grunt.registerTask('build', ['exec:npm-install', 'default']);
};
