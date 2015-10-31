CHANGELOG
-----------
### v0.15.0, 2015.10.31

* Removed the constraint that prevented modules to execute commands when the
framework is in install mode.
* `opis-colibri/core` dependency was updated to version `^1.7.0`

### v0.14.2, 2015.10.26

* Addes support for PHP's built in server

### v0.14.1, 2015.09.24

* Made `colibri` file executable and added hashbang

### v0.14.0, 2015.09.24

* Removed `opis-colibri/commands`dependency
* Updated `opis-colibri/core` dependency to version `^1.4.0`

### v0.13.0, 2015.09.14

* Modified how bootstrap is performed. `\Opis\Colibri\App::run()` is now invoked from the `index.php` file.
* Modified `colibri` file to reflect changes
* Updated dependencies

### v0.12.0, 2015.08.16

* Updated dependnencies

### v0.11.0, 2015.08.10

* Updated dependencies
* Removed `install.php` file
* Changed `bootstrap.php` file

### v0.10.0, 2015.07.31

* Modified `colibri` file
* Modified dependencies
* Changed `system/install.php` file
* Added new composer commands
* The `opis-colibri/framework` library was renamed to `opis-colibri/core`
and the old `opis-colibri/core` was deleted
* The `COLIBRI_FRAMEWORK_PATH` constant was renamed to `COLIBRI_CORE_PATH`

### v0.9.0, 2014.11.21

* Removed `system/lib`, `system/includes` and `system/replace` folders. The framework files were moved
into `opis-colibri/framework` library.
* The `system/includes/install.php` file was moved to `system/install.php`.
* Defined `COLIBRI_FRAMEWORK_PATH` constant in `bootstrap.php` and `colibri` files.

### v0.8.0, 2014.11.20

* Changed how router works
* Removed `schema` method from `Opis\Colibri\Serializable\ConnectionList`
* Removed `systemSchema` method from `Opis\Colibri\App`

### v0.7.2, 2014.11.20

* Changed dependencies
* Changed default module's namespace to `Opis\Colibri\Module\{ModuleName}`

### v0.7.1, 2014.11.20

* Module installer(if any) is now executed before installing, uninstalling, enabling or disabling a module.
* Display module installer when executing a `php colibri about {module}` command.

### v0.7.0, 2014.11.20

* Removed all modules files. Modules are now installed using composer.
* Changed dependencies.
* Modules that are enabled at install time are now defined in the `composer.json` file
* Added `Opis\Colibri\ModuleInstaller` class. Modules can now define an installer.

### v0.6.0, 2014.11.17

* Updated `opis/cache` library dependency to version `2.0.*`

### v0.5.4, 2014.11.13

* Fixed a bug that prevented a user to be logged in as system admin
* Removed 303 redirects from `manager` module.
* Module `manager` was marked as `hidden`.

### v0.5.3, 2014.11.12

* Fixed a bug in `Colibri\Module\System\Views\Alerts` class.

### v0.5.2, 2014.11.12

* Fixed alerts. Modified `Colibri\Module\System\Views\Alerts` class.
* Cleanup session after a new instance of Opis Colibri was installed.

### v0.5.1, 2014.11.12

* Fixed a bug that prevented the proper installation of Opis Colibri when a default database was specified.

### v0.5.0, 2014.11.11

* Changed welcome page desing
* Modified `Opis\Colibri\ClassLoader` class. The autoload function is now prepended.
* Added `Opis\Colibri\Serialize\ClosureList` class
* Overwritten the `Opis\Closure\SerializableClosure` class and altered the way closures are
handled and serialized.

### v0.4.0, 2014.10.24

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

### v0.3.0, 2014.07.16

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

### v0.2.11, 2014.06.26

* Updated `opis/config` library dependency to version `1.3.*`

### Opis Colibri 0.2.10, 2014.06.26

* Updated `opis/cache` library dependency to version `1.6.*`
* Updated `opis/session` library dependency to version `2.1.*`

### v0.2.9, 2014.06.26

* Routes that intercept POST, PUT, PATCH or DELETE requests can be now named.

### v0.2.8, 2014.06.20

* Fixed a bug that prevented Opis Colibri to properly run on PHP 5.3

### v0.2.7, 2014.06.11

* Updated `opis/http-routing` library to version `2.3.*`
* Updated `opis/events` library to version `2.3.*`
* Updated `opis/view` library to version `2.3.*`

### v0.2.6, 2014.06.10

* Bugfix in `Manager` module

### v0.2.5, 2014.06.06

* Updated module `Manager` to use proper route filters.

### Opis Colibri 0.2.4, 2014.06.05

* Added `AccessDenied` handler to `Opis\Colibri\Route`

### v0.2.3, 2014.06.04

* Updated `opis/http-routing` library to version `2.2.*`
* Updated `opis/events` library to version `2.2.*`
* Updated `opis/view` library to version `2.2.*`

### v0.2.2, 2014.06.01

* Updated `opis/config` library to version `1.2.*`

### v0.2.1, 2014.05.28

* Bugfix in welcome module
* Updated core version

### v0.2.0, 2014.05.28

* Multiple modules can now be declared inside one folder.
Modules files must now be named like `{module-name}.module.json` where `{module-name}`
is the name of the module. Default collector file for a module must be named like `{module-name}.module.php`

* Updated `Opis\Colibri\Module` class to reflect changes
* Updated modules to reflect changes

### v0.1.3, 2014.05.27

* Fixed several bugs related to storage collectors

### v0.1.2, 2014.05.27

* Added support for opis/session 2.0.0

### v0.1.1

* Started CHANGELOG
