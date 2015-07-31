CHANGELOG
-----------
### Opis Colibri 0.10.1, 2015.07.31

* Added the `prefer-stable` option to `composer.json` file

### Opis Colibri 0.10.0, 2015.07.31

* Modified `colibri` file
* Modified dependencies
* Changed `system/install.php` file
* Added new composer commands
* The `opis-colibri/framework` library was renamed to `opis-colibri/core`
and the old `opis-colibri/core` was deleted
* The `COLIBRI_FRAMEWORK_PATH` constant was renamed to `COLIBRI_CORE_PATH`

### Opis Colibri 0.9.0, 2014.11.21

* Removed `system/lib`, `system/includes` and `system/replace` folders. The framework files were moved
into `opis-colibri/framework` library.
* The `system/includes/install.php` file was moved to `system/install.php`.
* Defined `COLIBRI_FRAMEWORK_PATH` constant in `bootstrap.php` and `colibri` files.

### Opis Colibri 0.8.0, 2014.11.20

* Changed how router works
* Removed `schema` method from `Opis\Colibri\Serializable\ConnectionList`
* Removed `systemSchema` method from `Opis\Colibri\App`

### Opis Colibri 0.7.2, 2014.11.20

* Changed dependencies
* Changed default module's namespace to `Opis\Colibri\Module\{ModuleName}`

### Opis Colibri 0.7.1, 2014.11.20

* Module installer(if any) is now executed before installing, uninstalling, enabling or disabling a module.
* Display module installer when executing a `php colibri about {module}` command.

### Opis Colibri 0.7.0, 2014.11.20

* Removed all modules files. Modules are now installed using composer.
* Changed dependencies.
* Modules that are enabled at install time are now defined in the `composer.json` file
* Added `Opis\Colibri\ModuleInstaller` class. Modules can now define an installer.

### Opis Colibri 0.6.0, 2014.11.17

* Updated `opis/cache` library dependency to version `2.0.*`

### Opis Colibri 0.5.4, 2014.11.13

* Fixed a bug that prevented a user to be logged in as system admin
* Removed 303 redirects from `manager` module.
* Module `manager` was marked as `hidden`.

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
