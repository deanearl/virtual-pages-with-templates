# Virtual Pages with Templates

This is an initial WordPress plugin for displaying virtual pages which can use a template for selecting the contents.

**Warning: This plugin is still in beta**

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

## Contributors
[@deanearlbartolabac](https://github.com/deanearlbartolabac)
