## WordPress CLI Tools

[![Flattr this git repo](http://api.flattr.com/button/flattr-badge-large.png)](https://flattr.com/submit/auto?user_id=herewithme&url=https://github.com/herewithme/wordpress-cli-tools&title=WordPress CLI Tools&language=en_GB&tags=github&category=software) 

=========================

Some tools for WordPress, use it with PHP-CLI for no limitation (memory, timeout)

Caution : PHP-CLI isn't possible for WordPress with multisite enabled.

### Rebuild thumbs

This script allow to regenerate all thumbs for default and custom images sizes.

Usage : php5-cli /path to wordpress/rebuild-thumbs.php > messages.log

### Relink medias

This script allow to replace in your post content, link on direct media file by the link for attachment view.

Usage : php5-cli /path to wordpress/relink-medias.php > messages.log