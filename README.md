Simple converter xml, yml, json, csv files
=======================

Introduction
------------
This application provides a simple functional to convert files between the following formats : xml, yml, json, csv.

Installation
------------

Clone the repository and manually invoke `composer` using the shipped
`composer.phar`:

    cd my/project/dir
    git clone https://github.com/Okeanrst/TextfileConverter.git
    cd TextfileConverter
    php composer.phar self-update
    php composer.phar install
    cd vendor
    git clone https://github.com/bastman/json2xml.git

Web Server Setup
----------------

### Apache Setup

To setup apache, setup a virtual host to point to the public/ directory of the
project and you should be ready to go! It should look something like below:

    <VirtualHost *:80>
        ServerName converter.localhost
        DocumentRoot /path/to/converter/public
        <Directory /path/to/bookcollection/public>
            DirectoryIndex index.php
            AllowOverride All
            Order allow,deny
            Allow from all
        </Directory>
    </VirtualHost>
