<?php
declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginOwnedTrait;

class EventListener implements Listener {
	use PluginOwnedTrait;

	/*public function onDataPacket(DataPacketSendEvent $event)  :void {
		// remove the inventory rollback resource pack from the resource pack stack if the player doesn't have permission to use it
		foreach($event->getPackets() as $packet) {
			if($packet instanceof ResourcePackStackPacket) {
				foreach($event->getTargets() as $networkSession) {
					unset($packet->resourcePackStack[count($packet->resourcePackStack)-2]); // TODO: potentially removed the wrong pack
					return;
				}
			}
		}
	}*/

	public function onTransaction(InventoryTransactionEvent $event) : void{
		// track all transactions as incremental changes
		// keep transactions cached in memory for duration of player session unless memory is low
		// when player session ends, save all transactions to nbt files
		// verbose mode: Logs all inventory changes
		// Backup mode: Logs only when items are created or destroyed
		// User-Select mode: Logs only when the action picked by the user occurs
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		// save all transactions to nbt files
		// clear all transactions from memory
	}

	public function onPlayerChat(PlayerChatEvent $event) : void{
		// after player closes menu, check if they confirm prompt Y/N
		// if Y, restore inventory
		// if N, do nothing
	}
}