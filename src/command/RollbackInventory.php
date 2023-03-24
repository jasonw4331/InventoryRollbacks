<?php
declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\command;

use jasonwynn10\InventoryRollbacks\lang\CustomKnownTranslationFactory;
use jasonwynn10\InventoryRollbacks\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;

class RollbackInventory extends Command implements PluginOwned{
	use PluginOwnedTrait {
		__construct as setOwningPlugin;
	}

	public function __construct(private Main $plugin){
		parent::__construct(
			"rollbackinventory",
			CustomKnownTranslationFactory::command_rollbackinventory_description(),
			CustomKnownTranslationFactory::command_rollbackinventory_usage(), // TODO: add time command constraint for formatting time in standard time format
			["rbi"]
		);
		$this->setPermission("inventoryrollback.command");
		$this->setOwningPlugin($plugin);
	}

	/**
	 * @inheritDoc
	 */
	public function execute(CommandSender $sender, string $commandLabel, array $args): void{
		if(!$this->testPermission($sender)){
			return;
		}
		if(count($args) < 1){
			throw new InvalidCommandSyntaxException();
		}
		if(!$sender instanceof Player){
			$sender->sendMessage(KnownTranslationFactory::commands_generic_permission()); // TODO: replace with custom message about console not being able to use this command
			return;
		}
		$player = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
		if($player === null){
			$sender->sendMessage(KnownTranslationFactory::commands_generic_player_notFound());
			return;
		}

		$this->plugin->showTransactionsMenu(
			$sender,
			$player,
			isset($args[1]) ? (new \DateTime('now'))->sub(new \DateInterval($args[1])) : null
		);
	}
}