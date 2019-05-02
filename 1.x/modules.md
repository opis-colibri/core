---
layout: project
version: 1.x
title: Modules
description: Learn about Opis Colibri modules
---

Modules are the building blocks that stand at the foundation of the Opis Colibri framework.
Every single functionality of your app, every single line of code must be part of a module.

This is an alert. The text here is very important for users
{:.alert.alert-warning data-title="Important"}

This is an alert. The text here is very important for users
{:.alert.alert-success data-title="Remember"}

{% capture tab_id %}{% increment tab_id %}{% endcapture %}
{% capture tabs %}
{% capture php %}
```php
echo 123;
```
{% endcapture %}
{% capture json%}
```json
[1,2,3]
```
{% endcapture %}
{% include tab.html id=tab_id title='PHP' content=php checked=true %}
{% include tab.html id=tab_id title='JSON' content=json %}
{% endcapture %}
{% include tabs.html content=tabs %}

Another 

{% capture tab_id %}{% increment tab_id %}{% endcapture %}
{% capture tabs %}
{% capture php %}
```php
echo 'Hello world';
```
{% endcapture %}
{% capture json%}
```json
{"name": "Opis Colibri"}
```
{% endcapture %}
{% include tab.html id=tab_id title='PHP' content=php checked=true %}
{% include tab.html id=tab_id title='JSON' content=json %}
{% endcapture %}
{% include tabs.html content=tabs %}