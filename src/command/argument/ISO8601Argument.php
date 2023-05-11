<?php

declare(strict_types=1);

namespace jasonw4331\InventoryRollbacks\command\argument;

use CortexPE\Commando\args\BaseArgument;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use function preg_match;

final class ISO8601Argument extends BaseArgument{

	public function getNetworkType() : int{
		return AvailableCommandsPacket::ARG_TYPE_STRING;
	}

	public function canParse(string $testString, CommandSender $sender) : bool{
		// test ISO 8601 duration format
		return preg_match('/^P(?!$)(\d+(?:\.\d+)?Y)?(\d+(?:\.\d+)?M)?(\d+(?:\.\d+)?W)?(\d+(?:\.\d+)?D)?(T(?=\d)(\d+(?:\.\d+)?H)?(\d+(?:\.\d+)?M)?(\d+(?:\.\d+)?S)?)?$/', $testString) === 1;
	}

	public function parse(string $argument, CommandSender $sender) : mixed{
		return $argument;
	}

	public function getTypeName() : string{
		return "string";
	}
}
