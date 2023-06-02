<?php

declare(strict_types=1);

namespace jasonw4331\InventoryRollbacks\command\argument;

use CortexPE\Commando\args\BaseArgument;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\Server;

final class PlayerTargetArgument extends BaseArgument{

	public function getNetworkType() : int{
		return AvailableCommandsPacket::ARG_TYPE_TARGET;
	}

	public function canParse(string $testString, CommandSender $sender) : bool{
		// xbox username requirements
		return Player::isValidUserName($testString);
	}

	public function parse(string $argument, CommandSender $sender) : Player|OfflinePlayer|null{
		return Server::getInstance()->getOfflinePlayer($argument);
	}

	public function getTypeName() : string{
		return "target";
	}
}
