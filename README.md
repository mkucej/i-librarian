# I, Librarian Instructions
## Contents
  - Automated installation using installers
  - Windows manual installation
  - Linux manual installation
  - Mac OS X manual installation
  - First use
  - Un-installation

### Automated installation using installers
You can download and execute installers for Windows Vista, 7, 8, and 10 plus a DEB
package and a console installer for Ubuntu, Debian, and its derivatives. An installer
for Mac OS X is not available. These installers will install and/or configure Apache
and PHP for you. If you don't want that, follow the instructions below to install manually.

### Windows manual installation
**Before you start, disable Microsoft IIS, close Skype or any other software using port 80.**
  * Install *Apache 2.4+* and *PHP 5.5+* using a Windows installer like WAMPServer.
  * Edit Apache configuration file (httpd.conf). Append this at the end using Notepad:

```apache_conf
Alias /librarian "C:\I, Librarian"
<Directory "C:\I, Librarian">
    AllowOverride None
    # Allow access from this computer
    Require local
    # Allow access from intranet computers
    Require ip 10
    Require ip 172.16 172.17 172.18 172.19 172.20
    Require ip 172.21 172.22 172.23 172.24 172.25
    Require ip 172.26 172.27 172.28 172.29 172.30 172.31
    Require ip 192.168
    # Insert Allow from directives here to allow access from the internet
    # "Require all granted" opens access to everybody
    <IfModule mod_php5.c>
        php_value upload_max_filesize 400M
        php_value post_max_size 800M
    </IfModule>
    <FilesMatch "\.(ini|conf)$">
        Require all denied
    </FilesMatch>
</Directory>
<Directory "C:\I, Librarian\library">
    Require all denied
</Directory>
```

You may wish to alter who has access (e.g. to allow access from more IP numbers or domain names) - see the Apache [Authentication and Authorization HOWTO](https://httpd.apache.org/docs/2.4/howto/auth.html) for details.

  * You may change `C:\I, Librarian` to any directory where you want to have *I, Librarian*,
    including an external drive. For a groupware use, you need to allow access to more IP
    numbers or domain names.
  * Restart the server.
  * Unzip I, Librarian files into the directory defined by `Alias` in `httpd.conf`.

### Linux manual installation
* If you did not use the DEB package, make sure you have installed these packages from repositories:
  - **apache2 (may be named httpd)**: a web server (you may run *I, Librarian* with a different web server).
  - **php5 (may be named php)**: *I, Librarian* requires PHP5.4.
  - **php5-sqlite (may be named php-pdo)**: SQLite database for PHP5.
  - **php5-gd (may be named php-gd)**: GD library for PHP5.
  - **php5-curl (may be named php-curl)**: curl library for PHP5.
  - **poppler-utils**: required for PDF indexing and for the built-in PDF viewer.
  - **ghostscript**: required for the built-in PDF viewer.
  - **tesseract-ocr**: required for OCR.
  - **libreoffice**: required for conversion of office files to PDF.
* If you are installing from the tar.gz, login as `root` or use `sudo`, and extract files
  into 'librarian' directory in your web sever's root directory. Example:

```bash
  tar zxf I,-Librarian-*.tar.gz -C /var/www/html/librarian
```
* Change the owner of the library sub-folder to Apache. Example:

```bash
  chown -R apache:apache /var/www/html/librarian/library
  chown root:root /var/www/html/librarian/library/.htaccess
```
* Insert a safe setting like this example into your Apache configuration file:

```apache_conf
<Directory "/var/www/html/librarian">
    AllowOverride None
    # Allow access from this computer
    Require local
    # Allow access from intranet computers
    Require ip 10
    Require ip 172.16 172.17 172.18 172.19 172.20
    Require ip 172.21 172.22 172.23 172.24 172.25
    Require ip 172.26 172.27 172.28 172.29 172.30 172.31
    Require ip 192.168
    # Insert Allow from directives here to allow access from the internet
    # "Require all granted" opens access to everybody
    <IfModule mod_php5.c>
        php_value upload_max_filesize 400M
        php_value post_max_size 800M
    </IfModule>
    <FilesMatch "\.(ini|conf)$">
        Require all denied
    </FilesMatch>
</Directory>
<Directory "/var/www/html/librarian/library">
    Require all denied
</Directory>
```
You may wish to alter who has access (e.g. to allow access from more IP numbers or domain names) - see the Apache [Authentication and Authorization HOWTO](https://httpd.apache.org/docs/2.4/howto/auth.html) for details.

* Restart the server.

### Mac OS X manual installation

You will need to have an Apache + PHP stack installed. Details may vary depending on which PHP stack you are using.

Prior to Mac OS 10.10.1 (Yosemite), the default install of Mac OS included Apache and PHP built with the GD library. However, the PHP installed with     Yosemite does not include GD, so you will need to install one that does:     it is simplest to use the one line installation instructions at [http://php-osx.liip.ch/](http://php-osx.liip.ch/.).

Edit  /etc/apache2/httpd.conf using a text editor (e.g. TextEdit). You must make two changes:

* Enabling php, by removing the initial hash symbol from the line beginning "#LoadModule php5_module" (pre-yosemite), or adding a similar line with the path to wherever you installed PHP, eg:
    
    LoadModule php5_module    /usr/local/php5-5.3.29-20141019-211753/libphp5.so

* Adding a new Directory directive, by inserting: 

```apache_conf
Alias /librarian /Users/yourusername/Sites/librarian
<Directory /Users/Yourusername/Sites/librarian>
    AllowOverride None
    # Allow access from this computer
    Require local
    # Allow access from intranet computers
    Require ip 10
    Require ip 172.16 172.17 172.18 172.19 172.20
    Require ip 172.21 172.22 172.23 172.24 172.25
    Require ip 172.26 172.27 172.28 172.29 172.30 172.31
    Require ip 192.168
    # Insert Allow from directives here to allow access from the internet
    # "Require all granted" opens access to everybody
    <IfModule mod_php5.c>
        php_value upload_max_filesize 400M
        php_value post_max_size 800M
    </IfModule>
    <FilesMatch "\.(ini|conf)$">
        Require all denied
    </FilesMatch>
</Directory>
<Directory /Users/Yourusername/Sites/librarian/library>
    Require all denied
</Directory>
```
*Don't forget to change "yourusername" to your actual user name. You can find out your user name by typing `whoami` in Terminal.*

You may wish to alter who has access (e.g. to allow access from more IP numbers or domain names) - see the Apache [Authentication and Authorization HOWTO](https://httpd.apache.org/docs/2.4/howto/auth.html) for details.

* Restart Apache, by typing `sudo apachectl restart` in Terminal
* Install LibreOffice, Tesseract OCR, Ghostscript, and Poppler.
* Download *I, Librarian* for Mac OSX and double-click the file to extract its contents. Rename the extracted directory to 'librarian' and move it to your *Sites* folder. (alternatively, you could `git clone` this repository).
* Make sure that your *Sites* directory is accessible to *Others*. Use the `Get Info` dialog of the *Sites* directory to change permissions for *Everyone* to access and read (alternatively, run `chmod o+r ~/Sites/` at the terminal). You also need to make sure *Everyone* has **Execute** permissions for your home directory.
* Change the owner of the 'library' sub-folder to the Apache user (_www for the default install). You can do this at the Terminal: `chown -R _www ~/Sites/librarian/library/`.)
* Open your web browser and go to [http://127.0.0.1/librarian](http://127.0.0.1/librarian).

### First use
* In order to start *I, Librarian*, open your web browser, and visit:
  [http://127.0.0.1/librarian](http://127.0.0.1/librarian)
* Replace `127.0.0.1` with your static IP, or qualified server domain name, if you have either one.
* Create an account and head to *Tools->Installation Details* to check if everything checks fine.
* You should also check *Tools->Settings* to run *I, Librarian* the way you want.

**Thank you for installing *I, Librarian*!**

### Un-installation
* If you used the DEB package, execute the `uninstall.sh` un-installer.
* Otherwise un-install all programs that you installed solely to use *I, Librarian*.
* These may include Apache and PHP. **Note: You might have other programs using these. Only remove if sure.**
* Delete *I, Librarian* ('librarian') directory.
