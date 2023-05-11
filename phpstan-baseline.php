<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Anonymous function has invalid return type muqsit\\\\invmenu\\\\transaction\\\\InvMenuTransactionResult\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method discard\\(\\) on an unknown class muqsit\\\\invmenu\\\\transaction\\\\InvMenuTransaction\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to method getOut\\(\\) on an unknown class muqsit\\\\invmenu\\\\transaction\\\\InvMenuTransaction\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method ACTOR_FIXED\\(\\) on an unknown class muqsit\\\\invmenu\\\\type\\\\util\\\\InvMenuTypeBuilders\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method create\\(\\) on an unknown class muqsit\\\\invmenu\\\\InvMenu\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method generatePackFromResources\\(\\) on an unknown class libCustomPack\\\\libCustomPack\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method getTypeRegistry\\(\\) on an unknown class muqsit\\\\invmenu\\\\InvMenuHandler\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method register\\(\\) on an unknown class muqsit\\\\invmenu\\\\InvMenuHandler\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method registerResourcePack\\(\\) on an unknown class libCustomPack\\\\libCustomPack\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to static method unregisterResourcePack\\(\\) on an unknown class libCustomPack\\\\libCustomPack\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method getPackName\\(\\) on pocketmine\\\\resourcepacks\\\\ZippedResourcePack\\|null\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method setContents\\(\\) on pocketmine\\\\inventory\\\\Inventory\\|null\\.$#',
	'count' => 2,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$transaction of anonymous function has invalid type muqsit\\\\invmenu\\\\transaction\\\\InvMenuTransaction\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Main.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset 1 on array\\{player\\: pocketmine\\\\player\\\\OfflinePlayer\\|pocketmine\\\\player\\\\Player, time\\: string\\|null\\} in isset\\(\\) does not exist\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/command/RollbackInventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$sender \\(pocketmine\\\\player\\\\Player\\) of method jasonwynn10\\\\InventoryRollbacks\\\\command\\\\RollbackInventory\\:\\:onRun\\(\\) should be contravariant with parameter \\$sender \\(pocketmine\\\\command\\\\CommandSender\\) of method CortexPE\\\\Commando\\\\BaseCommand\\:\\:onRun\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/command/RollbackInventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$args \\(array\\{player\\: pocketmine\\\\player\\\\OfflinePlayer\\|pocketmine\\\\player\\\\Player\\|null, time\\: string\\|null\\}\\) of method jasonwynn10\\\\InventoryRollbacks\\\\command\\\\RollbackInventory\\:\\:onRun\\(\\) should be contravariant with parameter \\$args \\(array\\) of method CortexPE\\\\Commando\\\\BaseCommand\\:\\:onRun\\(\\)$#',
	'count' => 1,
	'path' => __DIR__ . '/src/command/RollbackInventory.php',
];
$ignoreErrors[] = [
	'message' => '#^Method jasonwynn10\\\\InventoryRollbacks\\\\data\\\\InventoryRecordHolder\\:\\:getPreviousTimestamp\\(\\) should return int but returns int\\|string\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/data/InventoryRecordHolder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$tag of static method jasonwynn10\\\\InventoryRollbacks\\\\util\\\\CaptureConverter\\:\\:fromNBT\\(\\) expects pocketmine\\\\nbt\\\\tag\\\\CompoundTag, pocketmine\\\\nbt\\\\tag\\\\CompoundTag\\|null given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/data/InventoryRecordHolder.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$array of function array_filter expects array, array\\<int, string\\>\\|false given\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/event/EventListener.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
