<?php

declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\event;

use jasonwynn10\InventoryRollbacks\data\InventoryRecordHolder;
use jasonwynn10\InventoryRollbacks\Main;
use jasonwynn10\InventoryRollbacks\task\SaveTransactionsTask;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\scheduler\ClosureTask;
use function get_class;

final class EventListener implements Listener{

	public function __construct(private Main $plugin){
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

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

	public function onPlayerJoin(PlayerJoinEvent $event) : void {
		$player = $event->getPlayer();
		// on join load most recent inventory record from disk and compare it to the current inventory
		// if the inventories are different, save the current inventory as a new record
		// if the inventories are the same, add to cache
	}

	public function onTransaction(InventoryTransactionEvent $event) : void{
		// track all transactions as incremental changes
		// keep transactions cached in memory for duration of player session unless memory is low
		// when player session ends, save all transactions to nbt files
		// verbose mode: Logs all inventory changes
		// Backup mode: Logs only when items are created or destroyed
		// User-Select mode: Logs only when the action picked by the user occurs

		$transaction = $event->getTransaction();
		$player = $transaction->getSource();
		$inventories = $transaction->getInventories();

		$clonedList = [];
		foreach($inventories as $inventory){
			if($inventory instanceof PlayerInventory ||
				$inventory instanceof PlayerCursorInventory ||
				$inventory instanceof ArmorInventory ||
				$inventory instanceof PlayerOffHandInventory
			) {
				$clonedList[] = get_class($inventory);
			}
		}

		$this->plugin->getScheduler()->scheduleTask(new ClosureTask(static function() use($player, $clonedList) {
			$inventories = [];
			foreach($clonedList as $class){
				if($class === PlayerInventory::class){
					$inventories[] = clone $player->getInventory();
				}elseif($class === ArmorInventory::class){
					$inventories[] = clone $player->getArmorInventory();
				}elseif($class === PlayerCursorInventory::class){
					$inventories[] = clone $player->getCursorInventory();
				}elseif($class === PlayerOffHandInventory::class){
					$inventories[] = clone $player->getOffHandInventory();
				}
			}
			InventoryRecordHolder::addInventoryChange($player->getName(), ...$inventories);
		}));
	}

	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		// save all transactions to nbt files
		// clear all transactions from memory

		$this->plugin->getServer()->getAsyncPool()->submitTask(new SaveTransactionsTask($this->plugin, $player));
	}
}
