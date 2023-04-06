<?php

declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\data;

use pocketmine\inventory\SimpleInventory;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\player\IPlayer;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\Server;

final class MultiInventoryCapture{
	public function __construct(
		private SimpleInventory $inventory,
		private SimpleInventory $armorInventory,
		private SimpleInventory $cursorInventory,
		private SimpleInventory $offHandInventory
	){
	}

	public function getInventory() : SimpleInventory{
		return $this->inventory;
	}

	public function getArmorInventory() : SimpleInventory{
		return $this->armorInventory;
	}

	public function getCursorInventory() : SimpleInventory{
		return $this->cursorInventory;
	}

	public function getOffHandInventory() : SimpleInventory{
		return $this->offHandInventory;
	}

	public function restore(IPlayer $player) : void{
		if($player instanceof Player){
			$player->getInventory()->setContents($this->inventory->getContents());
			$player->getArmorInventory()->setContents($this->armorInventory->getContents());
			$player->getCursorInventory()->setContents($this->cursorInventory->getContents());
			$player->getOffHandInventory()->setContents($this->offHandInventory->getContents());
		}
		if($player instanceof OfflinePlayer){
			// get namedtag from reflection class
			$refClass = (new \ReflectionClass($player))->getProperty("namedtag");
			$refClass->setAccessible(true);
			/** @var CompoundTag $nbt */
			$nbt = $refClass->getValue($player);
			// set inventories
			$inventoryTag = new ListTag([], NBT::TAG_Compound);
			$nbt->setTag('Inventory', $inventoryTag);
			if($this->inventory !== null){
				//Normal inventory
				$slotCount = $this->inventory->getSize() + 9;
				for($slot = 9; $slot < $slotCount; ++$slot){
					$item = $this->inventory->getItem($slot - 9);
					if(!$item->isNull()){
						$inventoryTag->push($item->nbtSerialize($slot));
					}
				}

				//Armor
				for($slot = 100; $slot < 104; ++$slot){
					$item = $this->armorInventory->getItem($slot - 100);
					if(!$item->isNull()){
						$inventoryTag->push($item->nbtSerialize($slot));
					}
				}
			}
			$offHandItem = $this->offHandInventory->getItem(0);
			if(!$offHandItem->isNull()){
				$nbt->setTag('OffHandItem', $offHandItem->nbtSerialize());
			}
			// write to disk
			Server::getInstance()->saveOfflinePlayerData($player->getName(), $nbt);
		}
	}

	public function getCompoundTag() : CompoundTag{
		$nbt = CompoundTag::create();

	}

}
