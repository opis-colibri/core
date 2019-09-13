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
        "opis/colibri": "2.0.x-dev"
    },
    "config": {
        "preferred-install": "dist"
    },
    "prefer-stable": true,
    "minimum-stability": "dev",
    "extra": {
        "module": {
            "title": "<?= $title ?>",
<?php if ($assets): ?>
            "assets": "<?= $assets ?>",
<?php endif; ?>
            "collector": "<?= $namespace ?>\\Collector",
            "installer": "<?= $namespace ?>\\Installer"
        },
        "branch-alias": {
            "dev-master": "1.0.x-dev"
        }
    }
}