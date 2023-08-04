<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getPackName\\(\\) on pocketmine\\\\resourcepacks\\\\ZippedResourcePack\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$resourcePack of static method libCustomPack\\\\libCustomPack\\:\\:unregisterResourcePack\\(\\) expects pocketmine\\\\resourcepacks\\\\ResourcePack, pocketmine\\\\resourcepacks\\\\ZippedResourcePack\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method pocketmine\\\\plugin\\\\Plugin\\:\\:showTransactionsMenu\\(\\)\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/command/RollbackInventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 1 on array\\{player\\: pocketmine\\\\player\\\\OfflinePlayer\\|pocketmine\\\\player\\\\Player, time\\: string\\|null\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/command/RollbackInventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$sender \\(pocketmine\\\\player\\\\Player\\) of method jasonw4331\\\\InventoryRollbacks\\\\command\\\\RollbackInventory\\:\\:onRun\\(\\) should be contravariant with parameter \\$sender \\(pocketmine\\\\command\\\\CommandSender\\) of method CortexPE\\\\Commando\\\\BaseCommand\\:\\:onRun\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/command/RollbackInventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$args \\(array\\{player\\: pocketmine\\\\player\\\\OfflinePlayer\\|pocketmine\\\\player\\\\Player\\|null, time\\: string\\|null\\}\\) of method jasonw4331\\\\InventoryRollbacks\\\\command\\\\RollbackInventory\\:\\:onRun\\(\\) should be contravariant with parameter \\$args \\(array\\) of method CortexPE\\\\Commando\\\\BaseCommand\\:\\:onRun\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/command/RollbackInventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method jasonw4331\\\\InventoryRollbacks\\\\data\\\\InventoryRecordHolder\\:\\:getPreviousTimestamp\\(\\) should return int but returns int\\|string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/data/InventoryRecordHolder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$tag of static method jasonw4331\\\\InventoryRollbacks\\\\util\\\\CaptureConverter\\:\\:fromNBT\\(\\) expects pocketmine\\\\nbt\\\\tag\\\\CompoundTag, pocketmine\\\\nbt\\\\tag\\\\CompoundTag\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/data/InventoryRecordHolder.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
