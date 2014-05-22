Opis Colibri
============
A different kind of framework
-------------

### Installation

```console

#Install composer

curl -sS https://getcomposer.org/installer | php

#Make it globaly available (optional)

mv composer.phar /usr/local/bin/composer

#Install Opis Colibri

cd /var/www

composer create-project opis/colibri <project-name>

```

### Apache configuration

```apache
<VirtualHost *:80>

    DocumentRoot /var/www/<project-name>/public

    <Directory /var/www/<project-name>/public>

        Options -Indexes FollowSymLinks -MultiViews
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
