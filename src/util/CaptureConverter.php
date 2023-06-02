<?php

declare(strict_types=1);

namespace jasonw4331\InventoryRollbacks\util;

use jasonw4331\InventoryRollbacks\data\InventoryRecordHolder;
use jasonw4331\InventoryRollbacks\data\MultiInventoryCapture;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

final class CaptureConverter{

	public static function toNBT(MultiInventoryCapture $capture, bool $cursor = false) : CompoundTag{
		$nbt = CompoundTag::create();
		// set inventories
		$inventoryTag = new ListTag([], NBT::TAG_Compound);
		$nbt->setTag('Inventory', $inventoryTag);
		//Normal inventory
		$slotCount = $capture->getInventory()->getSize() + 9;
		for($slot = 9; $slot < $slotCount; ++$slot){
			$item = $capture->getInventory()->getItem($slot - 9);
			if(!$item->isNull()){
				$inventoryTag->push($item->nbtSerialize($slot));
			}
		}

		//Armor
		for($slot = 100; $slot < 104; ++$slot){
			$item = $capture->getArmorInventory()->getItem($slot - 100);
			if(!$item->isNull()){
				$inventoryTag->push($item->nbtSerialize($slot));
			}
		}
		//Off Hand
		$offHandItem = $capture->getOffHandInventory()->getItem(0);
		if(!$offHandItem->isNull()){
			$nbt->setTag('OffHandItem', $offHandItem->nbtSerialize());
		}
		if($cursor){
			$cursorItem = $capture->getCursorInventory()->getItem(0);
			if(!$cursorItem->isNull()){
				$nbt->setTag('CursorItem', $cursorItem->nbtSerialize());
			}
		}
		return $nbt;
	}

	public static function fromNBT(CompoundTag $tag, bool $cursorTag = false) : MultiInventoryCapture{
		$playerInventory = new SimpleInventory(InventoryRecordHolder::PLAYER_INVENTORY_SIZE);
		$armorInventory = new SimpleInventory(InventoryRecordHolder::ARMOR_INVENTORY_SIZE);
		$cursorInventory = new SimpleInventory(InventoryRecordHolder::CURSOR_INVENTORY_SIZE);
		$offHandInventory = new SimpleInventory(InventoryRecordHolder::OFFHAND_INVENTORY_SIZE);

		$inventoryTag = $tag->getListTag('Inventory');
		if($inventoryTag !== null){
			$inventoryItems = [];
			$armorInventoryItems = [];

			/** @var CompoundTag $item */
			foreach($inventoryTag as $item){
				$slot = $item->getByte(SavedItemStackData::TAG_SLOT);
				if($slot >= 0 && $slot < 9){ //Hotbar
					//Old hotbar saving stuff, ignore it
				}elseif($slot >= 100 && $slot < 104){ //Armor
					$armorInventoryItems[$slot - 100] = Item::nbtDeserialize($item);
				}elseif($slot >= 9 && $slot < InventoryRecordHolder::PLAYER_INVENTORY_SIZE + 9){
					$inventoryItems[$slot - 9] = Item::nbtDeserialize($item);
				}
			}
			$playerInventory->setContents($inventoryItems);
			$armorInventory->setContents($armorInventoryItems);
		}
		$offHand = $tag->getCompoundTag('OffHandItem');
		if($offHand !== null){
			$offHandInventory->setContents([Item::nbtDeserialize($offHand)]);
		}
		if($cursorTag){
			$cursor = $tag->getCompoundTag('CursorItem');
			if($cursor !== null){
				$cursorInventory->setContents([Item::nbtDeserialize($cursor)]);
			}
		}
		return new MultiInventoryCapture($playerInventory, $armorInventory, $cursorInventory, $offHandInventory);
	}

	/**
	 * @return SimpleInventory[]
	 */
	public static function toInventories(MultiInventoryCapture $capture) : array{
		return [
			"inventory" => $capture->getInventory(),
			"armor" => $capture->getArmorInventory(),
			"cursor" => $capture->getCursorInventory(),
			"offhand" => $capture->getOffHandInventory()
		];
	}

}
