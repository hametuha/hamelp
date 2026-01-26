# Hamelp

Contributors: Takahashi_Fumiki, hametuha  
Tags: faq,help  
Tested up to: 6.9  
Stable Tag: 1.0.4  
License: GPL 3.0 or later  
License URI: https://www.gnu.org/licenses/gpl-3.0.html

FAQ template plugin by Hametuha.

## Description

This plugin add new custom post type 'FAQ'. With some functionality, you can build help center for your user.
What is help center? We collect examples at our [github wiki](https://github.com/hametuha/hamelp/wiki).

### Creating Portal

This plugin will provide...

* Custom post type with single page and archive page.
* Custom taxonomy associated to CPT.
* Incremental search box.
* AI Overview(Since 2.0.0)

### AI Overview

AI Overview answers user questions based on your FAQ content using a large language model.
It uses [wp-ai-client](https://packagist.org/packages/wordpress/wp-ai-client) (experimental) bundled via Composer, which requires an AI service to be configured in WordPress. Since wp-ai-client is still experimental, its API may change in future releases.

You can configure AI behavior and rate limiting from **Settings > Hamelp** in the admin panel.

#### Using the Block

Add the **AI FAQ Overview** block in the block editor. The block has the following options:

- **Placeholder** — Input placeholder text.
- **Button Text** — Submit button label.
- **Show Sources** — Display related FAQ links below the answer.

#### Using the Template Function

You can also use `hamelp_render_ai_overview()` in your theme templates:

```php
<?php echo hamelp_render_ai_overview(); ?>
```

The function accepts an optional array of arguments:

```php
<?php
echo hamelp_render_ai_overview( [
    'placeholder'  => 'Ask a question...',
    'button_text'  => 'Ask AI',
    'show_sources' => true,
] );
?>
```

The function automatically enqueues the required JavaScript and CSS assets.

### Search Box

You can use shortcode `hamelp-search` in page content.

```
[hamelp-search label='Enter your question here.'][/hamelp-search]
```

And you can call in your theme altenatively.

<pre>
&lt;?php echo do_shortcode( '[hamelp-search][/hamelp-search]' ) ?&gt;
</pre>

##  Installation

Install itself is easy. Auto install from admin panel is recommended. Search with `hamelp`.

1. Donwload and unpack plugin file, upload `hamelp` folder to `/wp-content/plugins` directory.
2. Activate it from admin panel.

## Frequently Asked Questions

> How can I contribute?

You can contribute to our github repo. Any [issues](https://github.com/hametuha/hamelp/issues) or [PRs](https://github.com/hametuha/hamelp/pulls) are welcomed.

## Changelog

### 2.0.0

* Add AI Overview Feature.
* Bump minimum requirements: PHP >=7.4, WordPress >= 6.6

### 1.0.4

* Add [structured data](https://developers.google.com/search/docs/data-types/faqpage) for FAQPage.

### 1.0.3

* Bugfix and change glocal functions.

### 1.0.2

* Fix taxonomy to be shown in Gutenberg.

### 1.0.1

* Fix no vendor directory bug.

### 1.0.0

* Initial release.
