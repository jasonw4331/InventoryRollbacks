<?php
declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\command\argument;

use CortexPE\Commando\args\BaseArgument;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\Server;

final class PlayerTargetArgument extends BaseArgument{

	public function getNetworkType() : int{
		return AvailableCommandsPacket::ARG_TYPE_TARGET;
	}

	public function canParse(string $testString, CommandSender $sender) : bool{
		// xbox username requirements
		if(strlen($testString) > 16){
			return false;
		}
		if(!preg_match('/^[a-zA-Z0-9_]+$/', $testString)){
			return false;
		}
		return true;
	}

	public function parse(string $argument, CommandSender $sender) : mixed{
		return Server::getInstance()->getPlayerExact($argument);
	}

	public function getTypeName() : string{
		return "target";
	}
}