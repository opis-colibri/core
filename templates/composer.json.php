{
    "name": "<?= $vendor ?>/<?= $module ?>",
    "type": "opis-colibri-module",
    "license": "proprietary",
    "description": "<?= json_encode($description) ?>",
    "keywords": [
        "opis",
        "colibri",
        "framework",
        "local module"
    ],
    "autoload": {
        "psr-4": {
            "<?= json_encode($namespace) ?>\\": "src/"
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
            "title": "<?= json_encode($title) ?>",
            <?php if ($assets): ?>
            "assets": "<?= json_encode($assets) ?>"
            <?php endif; ?>
            "collector": "<?= json_encode($namespace) ?>\\Collector",
            "installer": "<?= json_encode($namespace) ?>\\Installer"
        },
        "branch-alias": {
            "dev-master": "1.0.x-dev"
        }
    }
}