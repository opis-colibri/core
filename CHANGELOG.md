CHANGELOG
-----------
### Opis Colibri 0.5.4, 2014.11.13

* Fixed a bug that prevented a user to be logged in as system admin
* Removed 303 redirects from `manager` module.

### Opis Colibri 0.5.3, 2014.11.12

* Fixed a bug in `Colibri\Module\System\Views\Alerts` class.

### Opis Colibri 0.5.2, 2014.11.12

* Fixed alerts. Modified `Colibri\Module\System\Views\Alerts` class.
* Cleanup session after a new instance of Opis Colibri was installed.

### Opis Colibri 0.5.1, 2014.11.12

* Fixed a bug that prevented the proper installation of Opis Colibri when a default database was specified.

### Opis Colibri 0.5.0, 2014.11.11

* Changed welcome page desing
* Modified `Opis\Colibri\ClassLoader` class. The autoload function is now prepended.
* Added `Opis\Colibri\Serialize\ClosureList` class
* Overwritten the `Opis\Closure\SerializableClosure` class and altered the way closures are
handled and serialized.

### Opis Colibri 0.4.0, 2014.10.24

* Changed `Connections` collector
* Removed `Opis\Colibri\Serializable\DSNConnection` class.
* Removed `Opis\Colibri\Serializable\GenericConnection` class.
* Updated `opis/database` library dependency to version `2.0.*`
* Updated `opis/container` library dependency to version `2.2.*`
* Updated `opis/http-routing` library dependency to version `2.4.*`
* Updated `opis/views` library dependency to version `2.4.*`
* Updated `opis/events` library dependency to version `2.4.*`
* Updated `opis/config` library dependency to version `1.4.*`
* Updated `opis/cache` library dependency to version `1.7.*`
* Updated `opis/session` library dependency to version `2.2.*`
* Changed how default database connections are declared in `install` module

### Opis Colibri 0.3.0, 2014.07.16

* Removed `system/includes/define.php` file.
* All collectables are now collected using the `Opis\Colibri\Define` class.
* Removed `httpRoutes`, `httpDispatchers`, `httpRouteAliases`, `contracts`, `cache`,
`session`, `events`, `configs`, `viewCollection`,  `viewEngineResolvers` and  `connections`
methods from `Opis\Colibri\App` class.
* `Opis\Colibri\App::view` method was renamed to `Opis\Colibri\App::systemView`
* `Opis\Colibri\ModuleInfo` doesn't throws an exception anymore if the specified module doesn't exist.
You can use the newly added `exists` method to check if the module exists.
* `Opis\Colibri\EventCollectorInterface` was renamed to `Opis\Colibri\EventHandlerCollectorInterface`
* `Opis\Colibri\Collectors\EventCollector` was renamed to `Opis\Colibri\Collectors\EventHandlerCollector`

### Opis Colibri 0.2.11, 2014.06.26

* Updated `opis/config` library dependency to version `1.3.*`

### Opis Colibri 0.2.10, 2014.06.26

* Updated `opis/cache` library dependency to version `1.6.*`
* Updated `opis/session` library dependency to version `2.1.*`

### Opis Colibri 0.2.9, 2014.06.26

* Routes that intercept POST, PUT, PATCH or DELETE requests can be now named.

### Opis Colibri 0.2.8, 2014.06.20

* Fixed a bug that prevented Opis Colibri to properly run on PHP 5.3

### Opis Colibri 0.2.7, 2014.06.11

* Updated `opis/http-routing` library to version `2.3.*`
* Updated `opis/events` library to version `2.3.*`
* Updated `opis/view` library to version `2.3.*`

### Opis Colibri 0.2.6, 2014.06.10

* Bugfix in `Manager` module

### Opis Colibri 0.2.5, 2014.06.06

* Updated module `Manager` to use proper route filters.

### Opis Colibri 0.2.4, 2014.06.05

* Added `AccessDenied` handler to `Opis\Colibri\Route`

### Opis Colibri 0.2.3, 2014.06.04

* Updated `opis/http-routing` library to version `2.2.*`
* Updated `opis/events` library to version `2.2.*`
* Updated `opis/view` library to version `2.2.*`

### Opis Colibri 0.2.2, 2014.06.01

* Updated `opis/config` library to version `1.2.*`

### Opis Colibri 0.2.1, 2014.05.28

* Bugfix in welcome module
* Updated core version

### Opis Colibri 0.2.0, 2014.05.28

* Multiple modules can now be declared inside one folder.
Modules files must now be named like `{module-name}.module.json` where `{module-name}`
is the name of the module. Default collector file for a module must be named like `{module-name}.module.php`

* Updated `Opis\Colibri\Module` class to reflect changes
* Updated modules to reflect changes

### Opis Colibri 0.1.3, 2014.05.27

* Fixed several bugs related to storage collectors

### Opis Colibri 0.1.2, 2014.05.27

* Added support for opis/session 2.0.0

### Opis Colibri 0.1.1

* Started CHANGELOG
