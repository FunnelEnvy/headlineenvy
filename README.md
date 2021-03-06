# HeadlineEnvy
WordPress headline testing with Optimizely

## Description

Requires: PHP 5.4+

HeadlineEnvy adds the ability to A/B test the titles of your content
through integration with Optimizely.

__Features include:__

* __Unrestricted headline variations.__ When creating the test for a headline, you can add as many or as few as you wish.
* __Test headlines anywhere.__ Headline variations are applied to any place that your titles are output regardless of whether they appear on a post page, in the navigation, etc.
* __Experiment results.__ Revisit posts in the dashboard to check the status of headline statistical significance while your headline experiments are executed.
* __Automatic headline selection.__ Optionally choose to let HeadlineEnvy and Optimizely detect statistically significant winners and automatically update the post with the winning title.

To contribute, report issues, or make feature requests use [Github](https://github.com/FunnelEnvy/headlineenvy).

## Installation

1. Install HeadlineEnvy either via the WordPress.org plugin directory, or by uploading the files to your server.
2. After activating HeadlineEnvy, head over to the HeadlineEnvy settings page and enter your Optimizely API key.
3. Once your Optimizely API key has been entered, select the Optimizely project that you wish HeadlineEnvy to use for creating its experiments.
4. Select the post types you wish to allow for headline testing.
5. Visit any post edit page and you will see a new button prompting you to add additional headlines! Enter a few and hit Save/Update/Publish.
6. That's it!

## Note

HeadlineEnvy works by attaching to the [the_title](https://codex.wordpress.org/Plugin_API/Filter_Reference/the_title) filter hook.  If your theme or plugin uses a method of getting post titles that does not trigger that hook HeadlineEnvy will be unable to A/B test those cases.

## Support

Visit [http://www.funnelenvy.com/headlineenvy-wordpress-optimizely](http://www.funnelenvy.com/headlineenvy-wordpress-optimizely) for official support of this plugin.
