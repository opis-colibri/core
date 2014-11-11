Opis Colibri
============
[![Latest Stable Version](https://poser.pugx.org/opis/colibri/version.svg)](https://packagist.org/packages/opis/colibri)
[![Latest Unstable Version](https://poser.pugx.org/opis/colibri/v/unstable.svg)](//packagist.org/packages/opis/colibri)
[![License](https://poser.pugx.org/opis/colibri/license.svg)](https://packagist.org/packages/opis/colibri)

A different kind of framework
-------------

### Installation

```bash

#Install composer

curl -sS https://getcomposer.org/installer | php

#Make it globally available (optional)

sudo mv composer.phar /usr/local/bin/composer

#Install Opis Colibri

cd /var/www

composer create-project opis/colibri <project-name>

```

### Apache configuration

```apache
<VirtualHost *:80>

    ServerName  colibri.dev
    DocumentRoot /var/www/<project-name>/public

    <Directory /var/www/<project-name>/public>

        Options -Indexes +FollowSymLinks -MultiViews
        AllowOverride All
        Order allow,deny
        allow from all

        # URL rewrite

        RewriteEngine on

        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php/$1 [L]

    </Directory>

</VirtualHost>
```

### Documentation

No documentation available yet.