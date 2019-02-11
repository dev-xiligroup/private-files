# private-files
WP private files according capabilities and parent (post linked to the attachment describing the file in WP database) custom fields

## protects files
When downloading files from a WP website, test if the file can be downloaded. Test capabilities of connected user or test parent post (private or not) or custom fields of parent of attachment containing the file.

## prerequisite
some knowledges in .htaccess and in WP core files.


## Technical parts

needs a the file making the test in mu-plugins sub-folder.
needs some few modifications in .htaccess of the website. (better than permalink rules modifications)



> RewriteCond %{REQUEST_URI} \.(pdf|zip)$ [NC]
> 
> RewriteCond %{REQUEST_FILENAME} -s
> 
> RewriteRule ^wp-content/uploads/(.*)$ wp-content/mu-plugins/files-protect/xili-protect-files.php?file=$1 [QSA,L]



Above only pdf and zip files are tested. Comment the line if not mandatory

## Comments
This is an example that must be adapted to your context.

## Plugin
No extra plugin are mandatory to describe capabilities (if you want to use this way to select connected user). For tests, I use [Members](https://wordpress.org/plugins/members/) from Justin Tadlock.

## References
WordPress multisite

Two posts on 

https://wordpress.stackexchange.com/questions/37144/how-to-protect-uploads-if-user-is-not-logged-in/37743#37743

https://wordpress.stackexchange.com/questions/281500/protecting-direct-access-to-pdf-and-zip-unless-user-logged-in-without-plugin
