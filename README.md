# Shift8 Google Business
* Contributors: shift8  
* Donate link: https://www.shift8web.ca  
* Tags: google business, google places, google my business, business hours, wordpress, google maps  
* Requires at least: 3.0.1  
* Tested up to: 6.5  
* Stable tag: 2.0.1  
* License: GPLv3  
* License URI: http://www.gnu.org/licenses/gpl-3.0.html  

Shift8 Google Business helps you automatically sync your business information and hours from Google My Business (Google Places) directly into your WordPress site. The plugin polls the Google Places API daily via a scheduled cron job, storing the data in a custom post type for easy viewing or display on the front end. Easily manage multiple locations by simply adding Place IDs to the plugin’s settings page.

For complete instructions and an in-depth overview of how the plugin works, stay tuned for a detailed article on our [Shift8 blog](https://shift8web.ca).

## Want to see the plugin in action?

You can check our demo or contact us at [Shift8web](https://shift8web.ca) for a live example.

## Features

- Polls Google My Business daily (or on-demand via WP CLI) to fetch location data
- Uses a custom post type to store and display business information
- Settings page to define your Google Places API key
- Easily manage multiple Place IDs in a single admin interface
- Automates updates so your WordPress site always has the most recent business hours and info

## Installation 

1. Upload the plugin files to the `/wp-content/plugins/shift8-google-business` directory, or install the plugin through the WordPress plugins screen directly.  
2. Activate the plugin through the 'Plugins' screen in WordPress.  
3. Navigate to **Settings > Shift8 Business** and enter your Google API key along with any Place IDs you want to track.  

## Frequently Asked Questions 

### What if I don’t see any updated hours or data?

Check that you have:
- A valid Google API key with Places API enabled.  
- A correct Place ID.  
- Cron events running properly in WordPress (some hosts may block WP Cron).  

### Can I trigger updates manually?

Yes, if you have WP-CLI installed, simply run:

```
wp shift8_store_hours_updater
```

This will immediately poll the Google API and update the data for all configured Place IDs.

### Does it store all data or just business hours?

Currently, the plugin dumps all the fetched Place data into the post content for simplicity. You can customize how to parse and display this data in your theme or use additional custom fields.

## Screenshots 

1. **Settings Page** – Configure your Google API key and add one or more Place IDs in the Shift8 Business settings section.

## Changelog 

### 1.0
* Initial plugin release

### 1.1
* Added WP CLI compatibility

### 1.2
* Minor fixes for error logging
* Improved admin interface

### 2.0
* Introduced custom post type for storing Google Places data
* Consolidated plugin settings and improved UI

### 2.0.1
* Updated readme and minor code cleanups  
* Tested for WordPress 6.5 compatibility  