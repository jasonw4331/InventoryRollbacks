<?php

declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\data;

use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Server;
use function array_keys;
use function time;

final class InventoryRecordHolder{
	private function __construct(){ } // NOOP

	public CONST PLAYER_INVENTORY_SIZE = 36;
	public CONST ARMOR_INVENTORY_SIZE = 4;
	public CONST CURSOR_INVENTORY_SIZE = 1;
	public CONST OFFHAND_INVENTORY_SIZE = 1;

	/** @var Item[][][] */
	private static array $playerInventories = [], $armorInventories = [], $cursorInventories = [], $offHandInventories = [];
	/** @var MultiInventoryCapture[][] $captureCache */
	private static array $captureCache = [];

	public static function addInventoryChange(string $playerName, PlayerInventory|ArmorInventory|PlayerCursorInventory|PlayerOffHandInventory ...$inventories) : void{
		// store inventories indexed by player name and timestamp
		$timestamp = time();
		self::prepareLists($playerName);

		foreach($inventories as $inventory){
			if($inventory instanceof PlayerInventory){
				self::$playerInventories[$playerName][$timestamp] = $inventory->getContents();
			}elseif($inventory instanceof ArmorInventory){
				self::$armorInventories[$playerName][$timestamp] = $inventory->getContents();
			}elseif($inventory instanceof PlayerCursorInventory){
				self::$cursorInventories[$playerName][$timestamp] = $inventory->getContents();
			}elseif($inventory instanceof PlayerOffHandInventory){
				self::$offHandInventories[$playerName][$timestamp] = $inventory->getContents();
			}
		}
	}

	public static function getInventoriesNearTime(string $playerName, int $timestamp) : MultiInventoryCapture{

		$capture = self::getCachedCaptureNearTime(self::$captureCache[$playerName] ?? [], $timestamp);
		if($capture !== null){
			return $capture;
		}

		$playerInventory = null;
		$armorInventory = null;
		$cursorInventory = null;
		$offHandInventory = null;
		if($timestamp === time()) {
			$player = Server::getInstance()->getPlayerExact($playerName);
			if($player !== null) {
				$playerInventoryItems = $player->getInventory()->getContents(true);
				$armorInventoryItems = $player->getArmorInventory()->getContents(true);
				$cursorInventoryItems = $player->getCursorInventory()->getContents(true);
				$offHandInventoryItems = $player->getOffHandInventory()->getContents(true);

				$playerInventory = new SimpleInventory(self::PLAYER_INVENTORY_SIZE);
				$playerInventory->setContents($playerInventoryItems);
				$armorInventory = new SimpleInventory(self::ARMOR_INVENTORY_SIZE);
				$armorInventory->setContents($armorInventoryItems);
				$cursorInventory = new SimpleInventory(self::CURSOR_INVENTORY_SIZE);
				$cursorInventory->setContents($cursorInventoryItems);
				$offHandInventory = new SimpleInventory(self::OFFHAND_INVENTORY_SIZE);
				$offHandInventory->setContents($offHandInventoryItems);
			}else{
				$offlineData = Server::getInstance()->getOfflinePlayerData($playerName);
				if($offlineData !== null) {
					$inventoryTag = $offlineData->getListTag('Inventory');
					if($inventoryTag !== null){
						$inventoryItems = [];
						$armorInventoryItems = [];

						/** @var CompoundTag $item */
						foreach($inventoryTag as $i => $item){
							$slot = $item->getByte(Item::TAG_SLOT);
							if($slot >= 0 && $slot < 9){ //Hotbar
								//Old hotbar saving stuff, ignore it
							}elseif($slot >= 100 && $slot < 104){ //Armor
								$armorInventoryItems[$slot - 100] = Item::nbtDeserialize($item);
							}elseif($slot >= 9 && $slot < 36 + 9){
								$inventoryItems[$slot - 9] = Item::nbtDeserialize($item);
							}
						}
						$playerInventory = new SimpleInventory(self::PLAYER_INVENTORY_SIZE);
						$playerInventory->setContents($inventoryItems);
						$armorInventory = new SimpleInventory(self::ARMOR_INVENTORY_SIZE);
						$armorInventory->setContents($armorInventoryItems);
					}
					$offHand = $offlineData->getCompoundTag('OffHandItem');
					$offHandInventory = new SimpleInventory(self::OFFHAND_INVENTORY_SIZE);
					if($offHand !== null){
						$offHandInventory->setContents([Item::nbtDeserialize($offHand)]);
					}
				}
			}
		}else{
			// get inventories that are closest to the given timestamp
			// if there are no inventories of same type find next timestamp
			// if there are no inventories of same type or next timestamp, return empty inventory
			$playerInventories = self::$playerInventories[$playerName] ?? [];
			$armorInventories = self::$armorInventories[$playerName] ?? [];
			$cursorInventories = self::$cursorInventories[$playerName] ?? [];
			$offHandInventories = self::$offHandInventories[$playerName] ?? [];

			$playerInventory = self::getInventoryNearTime($timestamp, $playerInventories, self::PLAYER_INVENTORY_SIZE); // player inventory size
			$armorInventory = self::getInventoryNearTime($timestamp, $armorInventories, self::ARMOR_INVENTORY_SIZE); // armor inventory size
			$cursorInventory = self::getInventoryNearTime($timestamp, $cursorInventories, self::CURSOR_INVENTORY_SIZE); // cursor inventory size
			$offHandInventory = self::getInventoryNearTime($timestamp, $offHandInventories, self::OFFHAND_INVENTORY_SIZE); // offhand inventory size
		}

		self::$captureCache[$playerName][$timestamp] = $capture = new MultiInventoryCapture(
			$playerInventory,
			$armorInventory,
			$cursorInventory,
			$offHandInventory
		);

		return $capture;
	}

	public static function importTimestampedIntoCache(string $playerName, int $timestamp, MultiInventoryCapture $inventory) : void{
		self::prepareLists($playerName);
		self::$playerInventories[$playerName][$timestamp] = $inventory->getInventory()->getContents();
		self::$armorInventories[$playerName][$timestamp] = $inventory->getArmorInventory()->getContents();
		self::$cursorInventories[$playerName][$timestamp] = $inventory->getCursorInventory()->getContents();
		self::$offHandInventories[$playerName][$timestamp] = $inventory->getOffHandInventory()->getContents();
	}

	/**
	 * @return MultiInventoryCapture[]
	 */
	public static function extractCachedInventories(string $playerName) : array{
		$inventories = [];
		$playerInventories = self::$playerInventories[$playerName] ?? [];
		$armorInventories = self::$armorInventories[$playerName] ?? [];
		$cursorInventories = self::$cursorInventories[$playerName] ?? [];
		$offHandInventories = self::$offHandInventories[$playerName] ?? [];

		$timestamps = array_unique(array_keys($playerInventories) + array_keys($armorInventories) + array_keys($cursorInventories) + array_keys($offHandInventories), SORT_NUMERIC);
		foreach($timestamps as $timestamp){
			$playerInventory = self::getInventoryNearTime($timestamp, $playerInventories, self::PLAYER_INVENTORY_SIZE); // player inventory size
			$armorInventory = self::getInventoryNearTime($timestamp, $armorInventories, self::ARMOR_INVENTORY_SIZE); // armor inventory size
			$cursorInventory = self::getInventoryNearTime($timestamp, $cursorInventories, self::CURSOR_INVENTORY_SIZE); // cursor inventory size
			$offHandInventory = self::getInventoryNearTime($timestamp, $offHandInventories, self::OFFHAND_INVENTORY_SIZE); // offhand inventory size

			$inventories[$timestamp] = new MultiInventoryCapture($playerInventory, $armorInventory, $cursorInventory, $offHandInventory);
		}

		return $inventories;
	}

	public static function getNextTimestamp(string $playerName, int $timestamp) : int{
		assert($timestamp > 0, "Timestamp must be greater than 0, got $timestamp");
		$timestamps = array_unique(array_keys(self::$playerInventories[$playerName] ?? []) + array_keys(self::$armorInventories[$playerName] ?? []) + array_keys(self::$cursorInventories[$playerName] ?? []) + array_keys(self::$offHandInventories[$playerName] ?? []), SORT_NUMERIC);
		foreach($timestamps as $t){
			if($t > $timestamp){
				return $t;
			}
		}
		return time();
	}

	public static function getPreviousTimestamp(string $playerName, int $timestamp) : int{
		assert($timestamp > 0, "Timestamp must be greater than 0, got $timestamp");
		$timestamps = array_unique(array_keys(self::$playerInventories[$playerName] ?? []) + array_keys(self::$armorInventories[$playerName] ?? []) + array_keys(self::$cursorInventories[$playerName] ?? []) + array_keys(self::$offHandInventories[$playerName] ?? []), SORT_NUMERIC);
		$return = -1;
		foreach($timestamps as $t){
			if($t > $return && $t < $timestamp){
				$return = $t;
			}
			if($t >= $timestamp){
				break; // early loop exit
			}
		}
		return $return;
	}

	private static function prepareLists(string $playerName) : void{
		if(!isset(self::$playerInventories[$playerName])){
			self::$playerInventories[$playerName] = [];
		}
		if(!isset(self::$armorInventories[$playerName])){
			self::$armorInventories[$playerName] = [];
		}
		if(!isset(self::$cursorInventories[$playerName])){
			self::$cursorInventories[$playerName] = [];
		}
		if(!isset(self::$offHandInventories[$playerName])){
			self::$offHandInventories[$playerName] = [];
		}
	}

	/**
	 * @param Item[][] $itemArray
	 */
	private static function getInventoryNearTime(int $timestamp, array $itemArray, int $slots) : SimpleInventory{
		$inventory = new SimpleInventory($slots);
		if(isset($itemArray[$timestamp])){
			$inventory->setContents($itemArray[$timestamp]);
			return $inventory;
		}
		$timestamps = array_keys($itemArray);
		$closestTimestamp = null;
		foreach($timestamps as $t){
			if($t < $timestamp){
				$closestTimestamp = $t;
			}
			if($t > $timestamp)
				break; // early loop exit
		}
		if($closestTimestamp === null){
			return $inventory;
		}
		$inventory->setContents($itemArray[$closestTimestamp]);
		return $inventory;
	}

	/**
	 * @param MultiInventoryCapture[]  $cachedCaptures
	 */
	private static function getCachedCaptureNearTime(array $cachedCaptures, int $timestamp) : ?MultiInventoryCapture{
		if(isset($cachedCaptures[$timestamp])){
			return $cachedCaptures[$timestamp];
		}
		$timestamps = array_keys($cachedCaptures);
		$closestTimestamp = null;
		foreach($timestamps as $t){
			if($t < $timestamp){
				$closestTimestamp = $t;
			}
			if($t > $timestamp)
				break; // early loop exit
		}
		if($closestTimestamp === null){
			return null;
		}
		return $cachedCaptures[$closestTimestamp];
	}
}
