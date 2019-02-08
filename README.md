# private-files
WP private files according capabilities and parent custom fields

## protects files
When downloading files from a WP website, test if the file can be downloaded. Test capabilities of connected user or test parent post (private or not) or custom fields of parent of attachment containing the file.

## Technical parts

needs a the file making the test in mu-plugins sub-folder.
needs some few modifications in .htaccess of the website. (better than permalink rules modifications)

/**
* RewriteCond %{REQUEST_URI} \.(pdf|zip)$ [NC]
* RewriteCond %{REQUEST_FILENAME} -s
* RewriteRule ^wp-content/uploads/(.*)$ wp-content/mu-plugins/files-protect/dl-file.php?file=$1 [QSA,L]
*/

Above only pdf and zip files are tested.


## References
WordPress multisite

Two posts on 

https://wordpress.stackexchange.com/questions/37144/how-to-protect-uploads-if-user-is-not-logged-in/37743#37743

https://wordpress.stackexchange.com/questions/281500/protecting-direct-access-to-pdf-and-zip-unless-user-logged-in-without-plugin
