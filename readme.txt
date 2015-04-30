=== HeadlineEnvy - headline testing with Optimizely ===
Contributors: borkweb, zbtirrell, methnen
Tags: a/b testing, headline, optimization, optimizely, post, page, testing, title
Requires at least: 3.9
Tested up to: 4.2
Stable tag: 1.1
License: MIT
License URI: http://opensource.org/licenses/MIT

WordPress headline testing with Optimizely

== Description ==

HeadlineEnvy adds the ability to A/B test the titles of your content
through integration with Optimizely.

__Features include:__

* __Unrestricted headline variations.__ When creating the test for a headline, you can add as many or as few as you wish.
* __Test headlines anywhere.__ Headline variations are applied to any place that your titles are output regardless of whether they appear on a post page, in the navigation, etc.
* __Experiment results.__ Revisit posts in the dashboard to check the status of headline statistical significance while your headline experiments are executed.
* __Automatic headline selection.__ Optionally choose to let HeadlineEnvy and Optimizely detect statistically significant winners and automatically update the post with the winning title.

To contribute, report issues, or make feature requests use [Github](https://github.com/FunnelEnvy/headlineenvy).

Visit
[http://www.funnelenvy.com/headlineenvy-wordpress-optimizely](http://www.funnelenvy.com/headlineenvy-wordpress-optimizely) for official support of this plugin.

== Installation ==

1. Install HeadlineEnvy either via the WordPress.org plugin directory, or by uploading the files to your server.
2. After activating HeadlineEnvy, head over to the HeadlineEnvy settings page and enter your Optimizely API key.
3. Once your Optimizely API key has been entered, select the Optimizely project that you wish HeadlineEnvy to use for creating its experiments.
4. Select the post types you wish to allow for headline testing.
5. Visit any post edit page and you will see a new button prompting you to add additional headlines! Enter a few and hit Save/Update/Publish.
6. That's it!

Note:
HeadlineEnvy works by attaching to the [the_title](https://codex.wordpress.org/Plugin_API/Filter_Reference/the_title) filter hook.  If your theme or plugin uses a method of getting post titles that does not trigger that hook HeadlineEnvy will be unable to A/B test those cases.

== Screenshots ==

1. Add an alternate title to begin an experiment.
2. Multiple headlines added to experiment.
3. Winning headline!

== Changelog ==

= 1.1 =

* Fixed a few PHP warnings
* Normalized naming conventions to match Optimizely
* Normalized number values so they match Optimizely (i.e. 32.5% vs. 0.3247859996)
* Fixed a reference to the conversion value
* When deleting title variations confirmation now uses the variation 'title' instead of the variation id #
* Fixed an issue where some default options were lost of user tried to use an invalid API Key
* Return empty array for $primary_results when there are none yet

= 1.0 =

* Initial release