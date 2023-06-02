<?php

declare(strict_types=1);

namespace jasonw4331\InventoryRollbacks\event;

use Exception;
use jasonw4331\InventoryRollbacks\data\InventoryRecordHolder;
use jasonw4331\InventoryRollbacks\Main;
use jasonw4331\InventoryRollbacks\util\CaptureConverter;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\TreeRoot;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use Symfony\Component\Filesystem\Path;
use function array_filter;
use function array_slice;
use function get_class;
use function is_dir;
use function mkdir;
use function rename;
use function rsort;
use function scandir;
use function zlib_decode;
use function zlib_encode;
use const SORT_NATURAL;
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
		$name = $event->getPlayer()->getName();
		$path = Path::join($this->plugin->getDataFolder(), 'captures', $name);
		@mkdir($path, 0777, true);
		//load all inventory records from disk
		if(!is_dir($path)){
			return;
		}
		// find 5 files with most recent timestamp
		/** @phpstan-var list<string> $files */
		$files = ErrorToExceptionHandler::trapAndRemoveFalse(static fn() => scandir($path));
		$files = array_filter($files, static fn(string $file) => $file !== '.' && $file !== '..');
		rsort($files, SORT_NATURAL);
		$files = array_slice($files, 0, 5);
		// load records from files
		foreach($files as $file){
			try{
				$contents = Filesystem::fileGetContents($file);
			}catch(\RuntimeException $e){
				throw new Exception("Failed to read player inventory capture file \"$path\": " . $e->getMessage(), 0, $e);
			}

			try{
				/** @phpstan-var string $decompressed */
				$decompressed = ErrorToExceptionHandler::trapAndRemoveFalse(static fn() => zlib_decode($contents));
			}catch(\ErrorException $e){
				rename($path, $path . '.bak');
				throw new Exception("Failed to decompress raw player inventory capture for \"$name\": " . $e->getMessage(), 0, $e);
			}

			try{
				$tag = (new BigEndianNbtSerializer())->read($decompressed)->mustGetCompoundTag();
			}catch(NbtDataException $e){ //corrupt data
				rename($path, $path . '.bak');
				throw new Exception("Failed to decode NBT inventory capture for \"$name\": " . $e->getMessage(), 0, $e);
			}

			// convert and cache capture object
			InventoryRecordHolder::importTimestampedIntoCache($name, $tag->getInt('timestamp'), CaptureConverter::fromNBT($tag, true));
		}
		// TODO: check for offline inventory edits
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
		foreach($inventoryCaptures as $timestamp => $inventoryCapture){
			$tag = CaptureConverter::toNBT($inventoryCapture, true);
			$tag->setInt('timestamp', $timestamp);
			// write tag to NBT file
			$contents = Utils::assumeNotFalse(zlib_encode(
				(new BigEndianNbtSerializer())->write(new TreeRoot($tag)),
				ZLIB_ENCODING_GZIP
			), "zlib_encode() failed unexpectedly");
			try{
				Filesystem::safeFilePutContents(Path::join($this->plugin->getDataFolder(), 'captures', $event->getPlayer()->getName(), $timestamp . '.nbt'), $contents);
			}catch(\RuntimeException $e){
				// TODO
			}
		}
	}
}
