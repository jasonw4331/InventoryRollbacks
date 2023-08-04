<?php

declare(strict_types=1);

namespace jasonw4331\InventoryRollbacks\command;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use DateInterval;
use DateTime;
use jasonw4331\InventoryRollbacks\command\argument\ISO8601Argument;
use jasonw4331\InventoryRollbacks\command\argument\PlayerTargetArgument;
use jasonw4331\InventoryRollbacks\lang\CustomKnownTranslationFactory;
use jasonw4331\InventoryRollbacks\Main;
use pocketmine\command\CommandSender;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\player\OfflinePlayer;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function time;

final class RollbackInventory extends BaseCommand{

	public function __construct(Main $plugin){
		parent::__construct(
			$plugin,
			"rollbackinventory",
			CustomKnownTranslationFactory::command_rollbackinventory_description(),
			["rbi"]
		);
	}

	protected function prepare() : void{
		$this->setPermission("inventoryrollback.command");
		$this->registerArgument(0, new PlayerTargetArgument('player', false));
		$this->registerArgument(1, new ISO8601Argument('time', true));

		$this->addConstraint(new InGameRequiredConstraint($this));
	}

	/**
	 * @phpstan-param Player $sender
	 * @phpstan-param array{
	 *   "player": OfflinePlayer|Player|null,
	 *   "time": string|null
	 * }                     $args
	 */
	public function onRun(CommandSender $sender, string $aliasUsed, array $args) : void{
		if($args['player'] === null){
			$sender->sendMessage(KnownTranslationFactory::commands_generic_player_notFound()->prefix(TextFormat::RED));
			return;
		}

		$this->plugin->showTransactionsMenu(
			$sender,
			$args['player'],
			isset($args[1]) ? (new DateTime())->sub(new DateInterval($args['time']))->getTimestamp() : time()
		);
	}
}
