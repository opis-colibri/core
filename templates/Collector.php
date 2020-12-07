<?= '<?php', PHP_EOL ?>
namespace <?= $namespace ?>;

use Opis\Colibri\Attributes\Module;
use Opis\Colibri\Collector as ModuleCollector;

<?php if ($installer && $assets): ?>
#[Module('<?= $title ?>', installer: Installer::class, assets: 'assets')]
<?php elseif (!$assets): ?>
#[Module('<?= $title ?>', installer: Installer::class)]
<?php elseif(!$installer): ?>
#[Module('<?= $title ?>', assets: 'assets')]
<?php else: ?>
#[Module('<?= $title ?>')]
<?php endif ?>
class Collector extends ModuleCollector
{

}