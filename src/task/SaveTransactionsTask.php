<?php

declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\task;

use jasonwynn10\InventoryRollbacks\data\InventoryRecordHolder;
use jasonwynn10\InventoryRollbacks\Main;
use pocketmine\player\Player;
use pocketmine\scheduler\AsyncTask;

class SaveTransactionsTask extends AsyncTask{

	public function __construct(Main $plugin, Player $player){
		// collect all transaction data to be written in private class properties

	}

	/**
	 * Actions to execute when run
	 */
	public function onRun() : void{
		InventoryRecordHolder::writeInventoriesToDisk();
	}

	/**
	 * Actions to execute when completed (on main thread)
	 * Implement this if you want to handle the data in your AsyncTask after it has been processed
	 */
	public function onCompletion() : void{

	}
}
