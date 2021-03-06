# Virtual Pages with Templates

This is an initial WordPress plugin for displaying virtual pages which can use a template for selecting the contents.

**Warning: This plugin is still in beta**

[![Build Status](https://travis-ci.org/Link7/virtual-pages-with-templates.svg?branch=master)](https://travis-ci.org/Link7/virtual-pages-with-templates)

## Installation
1. Copy the plugin directory into your `wp-content/plugins` directory
2. Navigate to the *Plugins* dashboard page
3. Activate this plugin

## Recommended Tools
1. phpbay wordpress plugin
2. phpzon wordpress plugin

## Quick Start / Example
1. Create a post / page in wordpress
2. Add contents, may contain text, html and shortcodes (see example content show below)

```
Welcome to this website please find the products about <strong>%vpt-keyword%</strong> below and if you can’t find your item just use the search box!

[phpbay keywords="%vpt-keyword%" num="8" siteid="1" sortorder="BestMatch" templatename="columns" columns="4"]
```

3. save page as `draft` (note: virtual pages will use pages which are unpublished)
4. goto `Settings` > `Permalinks` - (optional), e.g. `/shop/%postname%/`
4. In the admin panel, open - `Virtual Page Settings`
5. update to your desired settings.

## Menu
- adding custom menus that will redirect to a virtual page, it will still be done as normal - `Appearance > Menu`
There are two ways with which you can add a virtual page:
1. Using the links metabox `Appearance > Menus > Links`. The value in the url field should either be http://*, /*, or *.
2. Using `Appearance > Menus > Virtual Page`, Just add the titles (separated by a new line) and it will add the menu items in bulk to the menu structure.

## Contributors
[@deanearlbartolabac](https://github.com/deanearlbartolabac)
