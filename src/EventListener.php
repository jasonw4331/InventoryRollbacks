<?php
declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginOwnedTrait;

class EventListener implements Listener {
	use PluginOwnedTrait;

	public function onTransaction(InventoryTransactionEvent $event) : void{
		// track all transactions as incremental changes
		// keep transactions cached in memory for duration of player session unless memory is low
		// when player session ends, save all transactions to nbt files
	}
}