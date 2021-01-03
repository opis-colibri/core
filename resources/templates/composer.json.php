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
        "php": "^8.0",
        "opis-colibri/core": "2020.x-dev"
    },
    "config": {
        "preferred-install": "dist"
    },
    "prefer-stable": true,
    "minimum-stability": "dev",
    "extra": {
        "collector": "<?= $namespace ?>\\Collector",
        "branch-alias": {
            "dev-master": "1.0.x-dev"
        }
    }
}