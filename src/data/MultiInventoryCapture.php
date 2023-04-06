<?php

declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\data;

use jasonwynn10\InventoryRollbacks\util\CaptureConverter;
use pocketmine\inventory\SimpleInventory;
use pocketmine\nbt\tag\CompoundTag;
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
			$nbt->merge(CaptureConverter::toNBT($this));
			// write to disk
			Server::getInstance()->saveOfflinePlayerData($player->getName(), $nbt);
		}
	}

}
