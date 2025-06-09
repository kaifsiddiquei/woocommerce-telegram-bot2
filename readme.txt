=== WooCommerce Telegram Bot ===
Contributors: yourname
Tags: woocommerce, telegram, notifications, bot, orders
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Send WooCommerce order notifications to Telegram groups with customizable messages and multi-group support.

== Description ==

WooCommerce Telegram Bot automatically sends notifications to your Telegram groups when:
- New orders are received
- Orders are moved to processing status

Features:
- Multi-group support with category-based routing
- Customizable message templates with placeholders
- Support for product images, videos, and GIFs
- Inline buttons for direct product links
- Rich text formatting (bold, italic, emojis)
- Message logging and monitoring
- Easy setup with no hosting requirements

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/woocommerce-telegram-bot/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Create a Telegram bot using @BotFather
4. Configure the bot token in WooCommerce > Telegram Bot
5. Add your Telegram groups and configure categories
6. Test the connection and start receiving notifications!

== Setup Instructions ==

1. **Create a Telegram Bot:**
   - Message @BotFather on Telegram
   - Use /newbot command
   - Follow instructions to get your bot token

2. **Get Chat ID:**
   - Add your bot to the desired Telegram group
   - Use @userinfobot in the group to get the chat ID

3. **Configure Plugin:**
   - Go to WordPress Admin > Telegram Bot
   - Enter your bot token
   - Test the connection
   - Add groups with their chat IDs
   - Customize message templates

== Frequently Asked Questions ==

= How do I get a bot token? =
Message @BotFather on Telegram and use the /newbot command to create a new bot.

= How do I find my group's chat ID? =
Add @userinfobot to your group and it will show the chat ID.

= Can I send to multiple groups? =
Yes! You can add multiple groups and configure different categories for each.

= What placeholders are available? =
{product_name}, {quantity}, {order_total}, {stock_quantity}, {order_status}, {order_id}, {customer_name}, {product_price}, {order_date}

== Changelog ==

= 1.0.0 =
* Initial release
* Multi-group support
* Category-based routing
* Customizable message templates
* Media support (images, videos, GIFs)
* Inline buttons
* Message logging
