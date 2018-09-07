---
layout: project
version: 1.x
title: Installation
description: Learn how to install the Opis Colibri framework
---
# Installation

* [Prerequisites](#prerequisites)
* [Installing the framework](#app-shell)

## Prerequisites

Before proceeding with framework installation, make sure that you have [NodeJS], [Composer] and [Yarn] installed and
globally available. 
Opis Colibri use Composer to manage its dependencies, while NodeJS and Yarn are used for automatic asset management. 

## Installing the framework

Installing the Opis Colibri framework is done with the help of Composer, by issuing a `create-project` command.

```bash
composer create-project opis-colibri/app website.test
```

Once the project have been installed, you can go into its folder and start a server.

```bash
cd website.test
php -S localhost:8080 -t public router.php
```


[apache_license]: http://www.apache.org/licenses/LICENSE-2.0 "Project license" 
{:rel="nofollow" target="_blank"}
[Packagist]: https://packagist.org/packages/{{page.lib.name}} "Packagist" 
{:rel="nofollow" target="_blank"}
[Composer]: http://getcomposer.org "Composer" 
{:ref="nofollow" target="_blank"}
[NodeJS]: https://nodejs.org "NodeJS"
{:ref="nofollow" target="_blank"}
[Yarn]: https://yarnpkg.com "NodeJS"
{:ref="nofollow" target="_blank"}