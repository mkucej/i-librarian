I, Librarian 3 Instructions

Contents:
    ### Automated installation using installers ###
    ### Windows manual installation ###
    ### Linux manual installation ###
    ### Mac OS X manual installation ###
    ### First use ###
    ### Uninstallation ###

### Automated installation using installers ###

    You can download and execute installers for Windows Vista, 7, and 8 plus
    a DEB package and a console installer for Ubuntu, Debian, and its derivatives.
    An installer for Mac OS X is not available. These installers will
    install and/or configure Apache and PHP for you. If you don't want that,
    follow the instructions below to install manually.

### Windows manual installation ###

    Before you start, uninstall Microsoft IIS, close Skype or any other software
    using port 80.

    Install Apache 2.4 and PHP 5.5 using a Windows installer like WAMPServer or
    ZendServer.

    Edit Apache configuration file (httpd.conf). Append this at the end using
    Notepad:

    Alias /librarian "C:\I, Librarian"
    <Directory "C:\I, Librarian">
        AllowOverride None
        Order deny,allow
        Deny from all
        Allow from 127.0.0.1
        <IfModule mod_php5.c>
            php_value upload_max_filesize 400M
            php_value post_max_size 800M
        </IfModule>
    </Directory>
    <Directory "C:\I, Librarian\library">
        Order allow,deny
    </Directory>
    <Files "C:\I, Librarian\ilibrarian.ini">
        Order allow,deny
    </Files>

    You may change "C:\I, Librarian" to any directory where you want to have
    I, Librarian, including an external drive. For a groupware use, you need to
    allow access to more IP numbers or domain names. Just add more Allow from
    directives (Allow from mydomain.net).

    Restart either Apache server or the computer.

    Unzip I, Librarian files into the directory defined by Alias in httpd.conf.

### Linux manual installation ###

    Linux users, if you did not use the DEB package, make sure you have installed
    these packages from repositories:

    apache2 (may also be named httpd)
        - a web server (you may run I, Librarian with a different web server)
    php5 (may also be called php)
        - I, Librarian is written in PHP5
    php5-sqlite (may also be named php-pdo)
        - SQLite database for PHP5
    php5-gd (may also be named php-gd)
        -GD library for PHP5
    poppler-utils
        -required for PDF indexing and for the built-in PDF viewer
    tesseract-ocr
        - required for OCR
    ghostscript
        - required for the built-in PDF viewer
    pdftk
        - required for PDF bookmarks, attachments and watermarking

    If you are installing from the tar.gz, login as root or use sudo, and
    extract files into "librarian" directory in your web sever root directory.

        Example:
        tar zxf I,-Librarian-*.tar.gz -C /var/www/html/librarian

    Change the owner of the library subfolder to Apache.

        Example:
        chown -R apache:apache /var/www/html/librarian/library
        chown root:root /var/www/html/librarian/library/.htaccess

    Insert a safe setting like this example into your Apache configuration file:

    <Directory "/var/www/html/librarian">
        AllowOverride None
        Order deny,allow
        Deny from all
        Allow from 127.0.0.1
        <IfModule mod_php5.c>
            php_value upload_max_filesize 400M
            php_value post_max_size 800M
        </IfModule>
    </Directory>
    <Directory "/var/www/html/librarian/library">
        Order allow,deny
    </Directory>
    <Files "/var/www/html/librarian/ilibrarian.ini">
        Order allow,deny
    </Files>

    To enable access from the Network, you need to allow access to more
    IP numbers or domain names. Just add more Allow from directives (Allow from
    mydomain.net).

    Restart Apache or the computer.

### Mac OS X manual installation ###

    Download and install an Apache + PHP stack. These instructions are generic.
    Details may very depending on which PHP stack are you using.

    Edit httpd.conf using TextEdit:

    Alias /librarian /Users/yourusername/Sites/librarian
    <Directory /Users/Yourusername/Sites/librarian>
        AllowOverride None
        Order deny,allow
        Deny from all
        Allow from 127.0.0.1
        <IfModule mod_php5.c>
            php_value upload_max_filesize 400M
            php_value post_max_size 800M
        </IfModule>
    </Directory>
    <Directory /Users/Yourusername/Sites/librarian/library>
        Order allow,deny
    </Directory>
    <Files "/Users/Yourusername/Sites/librarian/ilibrarian.ini">
        Order allow,deny
    </Files>

    Don't forget to change "yourusername" to your actual user name. You can find
    out your user name by typing whoami in Terminal.

    Restart Apache.

    Download and install Pdftk and Tesseract OCR.

    Download I, Librarian for Mac OS X and double-click the file to extract its
    contents.

    Rename the extracted directory to "librarian" and move it to your Sites folder.
    Make sure that your Sites folder is accessible to Others. Use the Get Info
    dialog of the Sites directory to change permissions for Everyone to access
    and read. You also need to make sure Everyone has Execute permissions for
    your home directory.

    Change the owner of the "library" subfolder to Apache.

    Open your web browser and go to http://127.0.0.1/librarian. 

### First use ###

    In order to start I, Librarian, open your web browser, and visit:

    http://127.0.0.1/librarian

    Replace 127.0.0.1 with your static IP, or qualified server domain name, if
    you have either one.

    Create an account and head to Tools->Installation Details to check if
    everything checks fine.

    You should also check Tools->Settings to run I, Librarian the way you want.

    Thank you for installing I, Librarian.

### Uninstallation ###

    If you used the DEB package, execute the uninstall.sh uninstaller.

    Otherwise uninstall all programs that you installed solely to use I, Librarian.
    These may include Apache and PHP.

    Delete I, Librarian directory.
