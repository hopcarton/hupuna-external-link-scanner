=== Hupuna External Link Scanner ===
Contributors: MaiSyDat
Tags: links, scanner, external links, seo, audit, security
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scans your entire WordPress website for external links. Optimized for large databases with efficient batch processing.

== Description ==

Hupuna External Link Scanner is a powerful tool that helps you identify all external links across your WordPress website. It scans posts, pages, comments, and options to find every external link, making it perfect for SEO audits, security reviews, and content management.

= Key Features =

* **Comprehensive Scanning**: Scans posts, pages, comments, and WordPress options
* **Batch Processing**: Optimized for large databases with efficient batch processing to prevent timeouts
* **Smart Filtering**: Automatically excludes system domains (WordPress.org, WooCommerce, Gravatar, etc.)
* **Detailed Results**: Shows link location, tag type, and provides quick edit/view links
* **User-Friendly Interface**: Clean admin interface with progress indicators and organized results
* **Performance Optimized**: Handles large websites without performance issues

= How It Works =

1. Navigate to **Link Scanner** in your WordPress admin menu
2. Click **Start Scan** to begin scanning your website
3. The plugin processes your content in batches to ensure smooth operation
4. View results grouped by URL or see all occurrences
5. Click edit/view links to quickly access content containing external links

= What Gets Scanned =

* All public post types (posts, pages, custom post types)
* Post content and excerpts
* Comments
* WordPress options (excluding transients and system options)

= What Gets Excluded =

The plugin automatically excludes links to:
* WordPress.org domains
* WooCommerce domains
* Gravatar domains
* Your own website domain

You can also filter the whitelist using the `hupuna_els_whitelist` filter hook.

= Use Cases =

* **SEO Audits**: Identify all external links for SEO analysis
* **Security Reviews**: Find external links that may need review
* **Content Management**: Track where external links are used across your site
* **Link Building**: Discover existing external link patterns

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins → Add New
3. Search for "Hupuna External Link Scanner"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Upload the `hupuna-external-link-scanner` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Link Scanner in the admin menu to start using

== Frequently Asked Questions ==

= Does this plugin slow down my website? =

No. The plugin only runs when you manually trigger a scan from the admin panel. It uses efficient batch processing to handle large databases without impacting site performance.

= What happens if I have a very large website? =

The plugin uses batch processing to handle websites of any size. It processes content in small batches to prevent server timeouts and ensure smooth operation.

= Can I customize which domains are excluded? =

Yes. You can use the `hupuna_els_whitelist` filter to customize the list of excluded domains.

= Does the plugin scan private or draft content? =

Yes, the plugin scans all post statuses to give you a complete picture of external links in your content.

= Can I export the scan results? =

Currently, results are displayed in the admin interface. Export functionality may be added in future versions.

== Screenshots ==

1. Main scanning interface with progress indicator
2. Results grouped by URL for easy review
3. Detailed view showing all link occurrences

== Changelog ==

= 2.0.0 =
* Initial release
* Batch processing for large databases
* Comprehensive scanning of posts, comments, and options
* Smart domain filtering
* User-friendly admin interface

== Upgrade Notice ==

= 2.0.0 =
Initial release of Hupuna External Link Scanner.

