<?php

declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\event;

use jasonwynn10\InventoryRollbacks\data\InventoryRecordHolder;
use jasonwynn10\InventoryRollbacks\Main;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use Symfony\Component\Filesystem\Path;
use function get_class;
use function zlib_encode;
use const ZLIB_ENCODING_GZIP;

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

	public function onPlayerJoin(PlayerJoinEvent $event) : void{
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
			){
				$clonedList[] = get_class($inventory);
			}
		}

		$this->plugin->getScheduler()->scheduleTask(new ClosureTask(static function() use ($player, $clonedList){
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
		// acquire cached inventory captures
		$inventoryCaptures = InventoryRecordHolder::extractInventoryCaptures($event->getPlayer()->getName());
		// clear cached inventory captures from memory
		InventoryRecordHolder::clearCaches($event->getPlayer()->getName());
		// convert inventory captures to compound tag
		$captures = [];
		foreach($inventoryCaptures as $timestamp => $inventoryCapture){
			$tag = CaptureConverter::toNBT($inventoryCapture, true);
			$tag->setInt('timestamp', $timestamp);
			$captures[] = $tag;
		}
		// write tag to NBT file
		$contents = Utils::assumeNotFalse(zlib_encode(
			(new BigEndianNbtSerializer())->write(new TreeRoot(new ListTag($captures, NBT::TAG_Compound))),
			ZLIB_ENCODING_GZIP
		), "zlib_encode() failed unexpectedly");
		try{
			Filesystem::safeFilePutContents(Path::join($this->plugin->getDataFolder(), 'captures', $event->getPlayer()->getName() . '.nbt'), $contents);
		}catch(\RuntimeException $e){
			// TODO
		}
	}
}
