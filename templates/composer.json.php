{
    "name": "<?= $vendor ?>/<?= $module ?>",
    "type": "opis-colibri-module",
    "license": "proprietary",
    "description": "<?= $description ?>",
    "keywords": [
        "opis",
        "colibri",
        "framework",
        "local module"
    ],
    "autoload": {
        "psr-4": {
            "<?= $namespace ?>\\": "src/"
        }
    },
    "require": {
        "php": "^7.4",
        "opis/colibri": "2020.x-dev"
    },
    "config": {
        "preferred-install": "dist"
    },
    "prefer-stable": true,
    "minimum-stability": "dev",
    "extra": {
        "module": {
        "collector": "<?= $namespace ?>\\Collector"
        "branch-alias": {
            "dev-master": "1.0.x-dev"
        }
    }
}