# hamelp

Contributors: Takahashi_Fumiki, hametuha  
Tags: faq,help  
Requires at least: 4.7  
Tested up to: 4.9.8  
Stable tag: 1.0.2  
Requires PHP: 5.4  
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

You can use shortcode `hamelp-search` in page content.

<pre>
[hamelp-search labe='Enter your question here.'][/hamelp-search]
</pre>

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

### 1.0.2

* Fix taxonomy to be shown in Gutenberg.

### 1.0.1

* Fix no vendor directory bug.

### 1.0.0

* Initial release.