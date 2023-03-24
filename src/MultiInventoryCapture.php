<?php
declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks;

use pocketmine\inventory\SimpleInventory;

final class MultiInventoryCapture{
	public function __construct(
		private SimpleInventory $inventory,
		private SimpleInventory $armorInventory,
		private SimpleInventory $cursorInventory,
		private SimpleInventory $offHandInventory
	){}

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

}