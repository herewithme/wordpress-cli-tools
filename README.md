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

### Move WordPress Multisite

This script allow to change domain for a WordPress Multisites Network.

Place this file into master folder of WordPress

Usage :
 * CLI : 				php5-cli -f move-wordpress-ms.php old-domain.com new-domain.com /old-path/ /new-path/ 1
 * Web Params : 		http://old-domain.com/move-wordpress-ms.php?old_domain=old-domain.com&new_domain=new-domain.com&old_path=/old_path/&new_path=/new_path/&site_id=1
 * Hardcoded values : 	http://old-domain.com/move-wordpress-ms.php