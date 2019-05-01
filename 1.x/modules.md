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
{% capture content %}
```php
echo 123;
```
{% endcapture %}
{% include tab.html id=tab_id name='php' title='PHP' content=content checked=true %}
{% capture content%}
```json
[1,2,3]
```
{% endcapture %}
{% include tab.html id=tab_id name='json' title='JSON' content=content %}
{% endcapture %}
{% include tabs.html content=tabs %}
