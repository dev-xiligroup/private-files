# private-files
In Wordpress, defines private access to files according to capabilities (of connected visitors) and parent (post linked to the attachment describing the file in WP database) custom fields. It is possible to define mime type of files that can be downloaded (or displayed) by non-connected visitors.

## protects files
When downloading files from a WP website, tests if the file can be downloaded. Test capabilities of connected user or test parent post (private or not) or custom fields of parent of attachment containing the file.

## prerequisites
some knowledges in .htaccess and in WP core files.


## Technical parts

needs a php file making the tests in mu-plugins sub-folder.
needs some few modifications in .htaccess of the website. (better than permalink rules modifications)



> RewriteCond %{REQUEST_URI} \.(pdf|zip)$ [NC]
> 
> RewriteCond %{REQUEST_FILENAME} -s
> 
> RewriteRule ^wp-content/uploads/(.*)$ wp-content/mu-plugins/files-protect/xili-protect-files.php?file=$1 [QSA,L]



Above only pdf and zip files are tested. Comment the line if not required.

## Comments
This is an example that must be adapted to your context.
## FAQ
### Why the file is in a sub-folder inside mu-plugins ?
Because this file is called only if rules inside .htaccess are met. In mu-plugins folder, only files in root are fired during starting of WP.

### Why not a plugin ?
This choice to avoid unexpected deactivation.

### Why introducing capabilities in this example ?
The posts found in [stackexchange](https://wordpress.stackexchange.com) only select connected and non connected user.
By introducing capabilities, we can refine the selection according specific “group”.
Here capability “read_xili_protect_content” is tested if post custom field ‘xili_protect_content’ of parent field is set to “1”.

### When the file is not downloadable, how to adapt redirection ?
In this example, the redirection is done to home of website with variable: ?message=UNAUTHORIZED... Other solutions are possible.

### no image is visible when no user connected.
It is because the private rulers are too hard. You can subselect in .htaccess or in check_user_authorization() function with $mime type.

## Plugin
No extra plugin are required to describe capabilities (if you want to use this way to select connected user). For tests, I use [Members](https://wordpress.org/plugins/members/) from Justin Tadlock.

## References
WordPress multisite - wp-includes/ms-files.php

Two posts on 

https://wordpress.stackexchange.com/questions/37144/how-to-protect-uploads-if-user-is-not-logged-in/37743#37743

https://wordpress.stackexchange.com/questions/281500/protecting-direct-access-to-pdf-and-zip-unless-user-logged-in-without-plugin

## Versions
### 0.3 - 190218
more accurate tests - if not connected can define public attached file.
### 0.2 - 190215
Test added to override file checking when post editing
### 0.0 - 190208
First public shipping.
