<?php

declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\data;

use jasonwynn10\InventoryRollbacks\util\CaptureConverter;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\PlayerOffHandInventory;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\Server;
use function array_keys;
use function array_shift;
use function array_unique;
use function assert;
use function count;
use function time;
use const SORT_NUMERIC;

final class InventoryRecordHolder{

	private function __construct(){ } // NOOP

	public const PLAYER_INVENTORY_SIZE = 36;
	public const ARMOR_INVENTORY_SIZE = 4;
	public const CURSOR_INVENTORY_SIZE = 1;
	public const OFFHAND_INVENTORY_SIZE = 1;

	private const CAPTURE_CACHE_SIZE = 5;

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

		if($timestamp === time()){
			$player = Server::getInstance()->getPlayerExact($playerName);
			if($player === null){
				$offlineData = Server::getInstance()->getOfflinePlayerData($playerName);
				self::pushCaptureToCache($playerName, $timestamp, $capture = CaptureConverter::fromNBT($offlineData, true));
				return $capture;
			}
			$playerInventory = new SimpleInventory(self::PLAYER_INVENTORY_SIZE);
			$playerInventory->setContents($player->getInventory()->getContents());
			$armorInventory = new SimpleInventory(self::ARMOR_INVENTORY_SIZE);
			$armorInventory->setContents($player->getArmorInventory()->getContents());
			$cursorInventory = new SimpleInventory(self::CURSOR_INVENTORY_SIZE);
			$cursorInventory->setContents($player->getCursorInventory()->getContents());
			$offHandInventory = new SimpleInventory(self::OFFHAND_INVENTORY_SIZE);
			$offHandInventory->setContents($player->getOffHandInventory()->getContents());
		}else{
			// get inventories that are closest to the given timestamp
			// if there are no inventories of same type find next timestamp
			// if there are no inventories of same type or next timestamp, return empty inventory
			$playerInventory = self::getInventoryNearTime($timestamp, self::$playerInventories[$playerName] ?? [], self::PLAYER_INVENTORY_SIZE); // player inventory size
			$armorInventory = self::getInventoryNearTime($timestamp, self::$armorInventories[$playerName] ?? [], self::ARMOR_INVENTORY_SIZE); // armor inventory size
			$cursorInventory = self::getInventoryNearTime($timestamp, self::$cursorInventories[$playerName] ?? [], self::CURSOR_INVENTORY_SIZE); // cursor inventory size
			$offHandInventory = self::getInventoryNearTime($timestamp, self::$offHandInventories[$playerName] ?? [], self::OFFHAND_INVENTORY_SIZE); // offhand inventory size
		}

		self::pushCaptureToCache($playerName, $timestamp, $capture = new MultiInventoryCapture(
			$playerInventory,
			$armorInventory,
			$cursorInventory,
			$offHandInventory
		));

		return $capture;
	}

	public static function importTimestampedIntoCache(string $playerName, int $timestamp, MultiInventoryCapture $inventory) : void{
		self::prepareLists($playerName);

		$playerInventory = new SimpleInventory(self::PLAYER_INVENTORY_SIZE);
		$playerInventory->setContents(
			self::$playerInventories[$playerName][$timestamp] = $inventory->getInventory()->getContents(true)
		);
		$armorInventory = new SimpleInventory(self::ARMOR_INVENTORY_SIZE);
		$armorInventory->setContents(
			self::$armorInventories[$playerName][$timestamp] = $inventory->getArmorInventory()->getContents(true)
		);
		$cursorInventory = new SimpleInventory(self::CURSOR_INVENTORY_SIZE);
		$cursorInventory->setContents(
			self::$cursorInventories[$playerName][$timestamp] = $inventory->getCursorInventory()->getContents(true)
		);
		$offHandInventory = new SimpleInventory(self::OFFHAND_INVENTORY_SIZE);
		$offHandInventory->setContents(
			self::$offHandInventories[$playerName][$timestamp] = $inventory->getOffHandInventory()->getContents(true)
		);

		self::pushCaptureToCache($playerName, $timestamp, new MultiInventoryCapture(
			$playerInventory,
			$armorInventory,
			$cursorInventory,
			$offHandInventory
		));
	}

	/**
	 * @return MultiInventoryCapture[]
	 */
	public static function extractInventoryCaptures(string $playerName) : array{
		$inventories = [];
		$playerInventories = self::$playerInventories[$playerName] ?? [];
		$armorInventories = self::$armorInventories[$playerName] ?? [];
		$cursorInventories = self::$cursorInventories[$playerName] ?? [];
		$offHandInventories = self::$offHandInventories[$playerName] ?? [];

		$timestamps = array_unique(array_keys($playerInventories) + array_keys($armorInventories) + array_keys($cursorInventories) + array_keys($offHandInventories), SORT_NUMERIC);
		foreach($timestamps as $timestamp){
			$inventories[$timestamp] = self::getInventoriesNearTime($playerName, $timestamp);
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
		return -1;
	}

	public static function getPreviousTimestamp(string $playerName, int $timestamp) : int{
		assert($timestamp > 0, "Timestamp must be greater than 0, got $timestamp");
		$timestamps = array_unique(array_keys(self::$playerInventories[$playerName] ?? []) + array_keys(self::$armorInventories[$playerName] ?? []) + array_keys(self::$cursorInventories[$playerName] ?? []) + array_keys(self::$offHandInventories[$playerName] ?? []), SORT_NUMERIC);
		$return = -1;
		foreach($timestamps as $t){
			if($t < $timestamp && $t > $return){
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

	public static function clearCaches(string $playerName, ?int $afterTimestamp = null) : void{
		if($afterTimestamp !== null){
			foreach(self::$playerInventories[$playerName] ?? [] as $timestamp => $inventory){
				if($timestamp >= $afterTimestamp){
					unset(self::$playerInventories[$playerName][$timestamp]);
				}
			}
			foreach(self::$armorInventories[$playerName] ?? [] as $timestamp => $inventory){
				if($timestamp >= $afterTimestamp){
					unset(self::$armorInventories[$playerName][$timestamp]);
				}
			}
			foreach(self::$cursorInventories[$playerName] ?? [] as $timestamp => $inventory){
				if($timestamp >= $afterTimestamp){
					unset(self::$cursorInventories[$playerName][$timestamp]);
				}
			}
			foreach(self::$offHandInventories[$playerName] ?? [] as $timestamp => $inventory){
				if($timestamp >= $afterTimestamp){
					unset(self::$offHandInventories[$playerName][$timestamp]);
				}
			}
			foreach(self::$captureCache[$playerName] ?? [] as $timestamp => $capture){
				if($timestamp >= $afterTimestamp){
					unset(self::$captureCache[$playerName][$timestamp]);
				}
			}
			return;
		}
		unset(self::$playerInventories[$playerName]);
		unset(self::$armorInventories[$playerName]);
		unset(self::$cursorInventories[$playerName]);
		unset(self::$offHandInventories[$playerName]);
		unset(self::$captureCache[$playerName]);
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

	private static function pushCaptureToCache(string $playerName, int $timestamp, MultiInventoryCapture $capture) : void{
		if(!isset(self::$captureCache[$playerName])){
			self::$captureCache[$playerName] = [];
		}
		if(count(self::$captureCache[$playerName]) >= self::CAPTURE_CACHE_SIZE){
			array_shift(self::$captureCache[$playerName]);
		}
		self::$captureCache[$playerName][$timestamp] = $capture;
	}

	/**
	 * @param MultiInventoryCapture[] $cachedCaptures
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
