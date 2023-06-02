<?php

declare(strict_types=1);

namespace jasonw4331\InventoryRollbacks;

use CortexPE\Commando\PacketHooker;
use jasonw4331\InventoryRollbacks\data\InventoryRecordHolder;
use jasonw4331\InventoryRollbacks\data\MultiInventoryCapture;
use jasonw4331\InventoryRollbacks\event\EventListener;
use jasonw4331\InventoryRollbacks\lang\CustomKnownTranslationFactory;
use libCustomPack\libCustomPack;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\EventPriority;
use pocketmine\event\HandlerListManager;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\RegisteredListener;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\StringToItemParser;
use pocketmine\lang\Language;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\IPlayer;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use ReflectionClass;
use Symfony\Component\Filesystem\Path;
use function array_fill;
use function array_map;
use function array_merge;
use function array_pad;
use function array_slice;
use function array_values;
use function filter_var;
use function mb_strtolower;
use function pathinfo;
use function scandir;
use function str_repeat;
use function unlink;
use function yaml_parse_file;
use const FILTER_NULL_ON_FAILURE;

final class Main extends PluginBase{

	public const TYPE_ROLLBACKS_VIEW = 'inventoryrollbacks:rollbacks_view';
	private static ?ZippedResourcePack $pack;

	public function onEnable() : void{
		InvMenuHandler::register($this);
		PacketHooker::register($this);

		// register custom inventory menu compatible with increased inventory size
		$menuType = InvMenuTypeBuilders::ACTOR_FIXED()->setNetworkWindowType(WindowTypes::CONTAINER)->setSize(6 * 13); // 6 rows of 13 slots meant to hold the inventory of a player inventory with 2 slots padding at top 3 sides
		$menuType->getActorMetadata()->setString(EntityMetadataProperties::NAMETAG, str_repeat(TextFormat::RESET, 10));
		InvMenuHandler::getTypeRegistry()->register(self::TYPE_ROLLBACKS_VIEW, $menuType->build());

		// Build and register resource pack
		libCustomPack::registerResourcePack(self::$pack = libCustomPack::generatePackFromResources($this));
		$this->getLogger()->debug('Resource pack installed');

		// register command
		$this->getServer()->getCommandMap()->register($this->getName(), new command\RollbackInventory($this));

		// register event listener
		new EventListener($this);

		$this->saveResource('/lang/config.yml', true);
		/** @var string[][] $contents */
		$contents = yaml_parse_file(Path::join($this->getDataFolder(), "lang", 'config.yml'));
		$languageAliases = [];
		foreach($contents as $language => $aliases){
			$mini = mb_strtolower($aliases['mini']);
			$this->saveResource('/lang/data/' . $mini . '.ini', true);
			$languageAliases[$mini] = $language;
		}

		$languages = [];
		$dir = scandir(Path::join($this->getDataFolder(), "lang", "data"));
		if($dir !== false){
			foreach($dir as $file){
				/** @phpstan-var array{dirname: string, basename: string, extension?: string, filename: string} $fileData */
				$fileData = pathinfo($file);
				if(!isset($fileData["extension"]) || $fileData["extension"] !== "ini"){
					continue;
				}
				$languageName = mb_strtolower($fileData["filename"]);
				$language = new Language(
					$languageName,
					Path::join($this->getDataFolder(), "lang", "data")
				);
				$languages[$languageName] = $language;
				foreach(Utils::stringifyKeys($languageAliases) as $languageAlias => $alias){
					if(mb_strtolower($alias) === $languageName){
						$languages[mb_strtolower($languageAlias)] = $language;
						unset($languageAliases[$languageAlias]);
					}
				}
			}
		}

		// add translations to existing server language instance
		$languageA = $this->getServer()->getLanguage();
		$refClass = new ReflectionClass($languageA::class);
		$refPropA = $refClass->getProperty('lang');
		/** @var string[] $langA */
		$langA = $refPropA->getValue($languageA);
		/** @var string[] $langB */
		$langB = $refClass->getProperty('lang')->getValue($languages[$languageA->getLang()]);
		$refPropA->setValue($languageA, array_merge($langA, $langB));
	}

	public function onDisable() : void{
		libCustomPack::unregisterResourcePack(self::$pack);
		$this->getLogger()->debug('Resource pack uninstalled');

		unlink(Path::join($this->getDataFolder(), self::$pack->getPackName() . '.mcpack'));
		$this->getLogger()->debug('Resource pack file deleted');
	}

	public function showTransactionsMenu(Player $viewer, IPlayer $player, int $timestamp) : void{
		$lang = $viewer->getLanguage();

		// identify padding item
		$itemString = filter_var($this->getConfig()->get('Filler Item', 'black_stained_glass_pane'), options: FILTER_NULL_ON_FAILURE);
		$item = $itemString === null ? VanillaBlocks::STAINED_GLASS_PANE()->setColor(DyeColor::BLACK())->asItem() :
			(StringToItemParser::getInstance()->parse($itemString) ?? LegacyStringToItemParser::getInstance()->parse($itemString));
		$fillerItem = $item->setCustomName(' ');

		// identify navigation items
		$itemString = filter_var($this->getConfig()->get('Next Page Item', 'lime_concrete'), options: FILTER_NULL_ON_FAILURE);
		$item = $itemString === null ? VanillaBlocks::CONCRETE()->setColor(DyeColor::LIME())->asItem() :
			(StringToItemParser::getInstance()->parse($itemString) ?? LegacyStringToItemParser::getInstance()->parse($itemString));
		$nextPageItem = $item->setCustomName($lang->translate(CustomKnownTranslationFactory::rollbackinventory_menu_nextpage()));
		$itemString = filter_var($this->getConfig()->get('Previous Page Item', 'red_concrete'), options: FILTER_NULL_ON_FAILURE);
		$item = $itemString === null ? VanillaBlocks::CONCRETE()->setColor(DyeColor::RED())->asItem() :
			(StringToItemParser::getInstance()->parse($itemString) ?? LegacyStringToItemParser::getInstance()->parse($itemString));
		$previousPageItem = $item->setCustomName($lang->translate(CustomKnownTranslationFactory::rollbackinventory_menu_previouspage()));

		// create menu
		$menu = InvMenu::create(self::TYPE_ROLLBACKS_VIEW);

		// populate with padding items and inventory contents
		$menu->getInventory()->setContents($this->compileMenuItems(
			$fillerItem, $nextPageItem, $previousPageItem, $player, $timestamp,
			InventoryRecordHolder::getInventoriesNearTime($player->getName(), $timestamp)
		));

		$menu->setListener(
			function(InvMenuTransaction $transaction) use ($fillerItem, $nextPageItem, $previousPageItem, $player, &$timestamp) : InvMenuTransactionResult{
				$result = $transaction->discard();
				if($transaction->getOut()->equals($previousPageItem, true, false)){
					$result->then(function(Player $viewer) use ($fillerItem, $nextPageItem, $previousPageItem, $player, &$timestamp) : void{
						$timestamp = InventoryRecordHolder::getPreviousTimestamp($player->getName(), $timestamp);
						$viewer->getCurrentWindow()?->setContents($this->compileMenuItems(
							$fillerItem, $nextPageItem, $previousPageItem, $player, $timestamp,
							InventoryRecordHolder::getInventoriesNearTime($player->getName(), $timestamp)
						));
					});
				}elseif($transaction->getOut()->equals($nextPageItem, true, false)){
					$result->then(function(Player $viewer) use ($fillerItem, $nextPageItem, $previousPageItem, $player, &$timestamp) : void{
						$timestamp = InventoryRecordHolder::getNextTimestamp($player->getName(), $timestamp);
						$viewer->getCurrentWindow()?->setContents($this->compileMenuItems(
							$fillerItem, $nextPageItem, $previousPageItem, $player, $timestamp,
							InventoryRecordHolder::getInventoriesNearTime($player->getName(), $timestamp)
						));
					});
				}
				return $result;
			}
		);

		$menu->setInventoryCloseListener(static function(Player $viewer, Inventory $inventory) use ($player){
			$viewer->sendMessage(
				$viewer->getLanguage()->translate(
					CustomKnownTranslationFactory::rollbackinventory_menu_confirmation($player->getName())
				)
			);
		});

		$menu->send($viewer);

		/** @var RegisteredListener $listener */
		$listener = null;
		$listener = $this->getServer()->getPluginManager()->registerEvent(PlayerChatEvent::class, function(PlayerChatEvent $event) use ($viewer, $player, &$timestamp, &$listener){
			if($event->getPlayer() === $viewer){
				if(mb_strtolower($event->getMessage()) === 'y'){
					// handle rollback
					InventoryRecordHolder::getInventoriesNearTime($player->getName(), $timestamp)->restore($player);
					// clear cache after timestamp
					InventoryRecordHolder::clearCaches($player->getName(), $timestamp);
				}
				$event->cancel();
				// unregister event listener
				HandlerListManager::global()->getListFor($event::class)->unregister($listener);
			}
		}, EventPriority::LOWEST, $this, true);
	}

	/**
	 * @return Item[]
	 */
	private function compileMenuItems(Item $fillerItem, Item $nextPageItem, Item $previousPageItem, IPlayer $player, int $timestamp, MultiInventoryCapture $capture) : array{
		$lang = Server::getInstance()->getLanguage();

		$armorInventory = $capture->getArmorInventory()->getContents(true);
		$cursorItem = $capture->getCursorInventory()->getItem(0);
		$offHandItem = $capture->getOffHandInventory()->getItem(0);

		$unorderedItemRows = [];
		// separate player inventory into rows of 9
		for($i = 0; $i < 4; $i++){
			$unorderedItemRows[] = array_slice($capture->getInventory()->getContents(true), $i * 9, 9);
		}

		// name armor slots if air
		foreach($armorInventory as $slot => $item){
			if($item->isNull()){
				$armorInventory[$slot] = VanillaBlocks::INVISIBLE_BEDROCK()->asItem()->setCustomName(match ($slot) {
					0 => $lang->translate(CustomKnownTranslationFactory::rollbackinventory_menu_helmet()),
					1 => $lang->translate(CustomKnownTranslationFactory::rollbackinventory_menu_chestplate()),
					2 => $lang->translate(CustomKnownTranslationFactory::rollbackinventory_menu_leggings()),
					3 => $lang->translate(CustomKnownTranslationFactory::rollbackinventory_menu_boots()),
					default => throw new AssumptionFailedError('Invalid armor slot: ' . $slot)
				});
			}
		}

		// name cursor slot if air
		if($cursorItem->isNull()){
			$cursorItem = VanillaBlocks::INVISIBLE_BEDROCK()->asItem()->setCustomName(
				$lang->translate(CustomKnownTranslationFactory::rollbackinventory_menu_cursorslot())
			);
		}

		// name offhand slot if air
		if($offHandItem->isNull()){
			$offHandItem = VanillaBlocks::INVISIBLE_BEDROCK()->asItem()->setCustomName(
				$lang->translate(CustomKnownTranslationFactory::rollbackinventory_menu_offhandslot())
			);
		}

		// offset each row by 2 slots to account for padding
		$unorderedItemRows = array_map(static fn(array $row) => array_pad($row, -11, $fillerItem), $unorderedItemRows);
		// add 2 padding items to end of each row
		$unorderedItemRows = array_map(static fn(array $row) => array_merge($row, [$fillerItem, $fillerItem]), $unorderedItemRows);

		// add 2 rows of padding items to top
		$unorderedItemRows[] = array_fill(0, 13, $fillerItem);
		$unorderedItemRows[] = array_fill(0, 13, $fillerItem);

		// add page items to top row at indexes 3 and 10
		$unorderedItemRows[4][3] = InventoryRecordHolder::getPreviousTimestamp($player->getName(), $timestamp) > -1 ? $previousPageItem : $fillerItem;
		$unorderedItemRows[4][9] = InventoryRecordHolder::getNextTimestamp($player->getName(), $timestamp) !== -1 ? $nextPageItem : $fillerItem;

		// add armor at right-most side of 2nd row and below
		$unorderedItemRows[1][12] = $armorInventory[0];
		$unorderedItemRows[2][12] = $armorInventory[1];
		$unorderedItemRows[3][12] = $armorInventory[2];
		$unorderedItemRows[0][12] = $armorInventory[3];

		// add armor items at left-most side of 3rd row
		$unorderedItemRows[1][0] = $offHandItem;

		// add cursor items at left-most side of 5th row
		$unorderedItemRows[3][0] = $cursorItem;

		// consolidate rows into single inventory
		return [
			...array_values($unorderedItemRows[4]),
			...array_values($unorderedItemRows[5]),
			...array_values($unorderedItemRows[1]),
			...array_values($unorderedItemRows[2]),
			...array_values($unorderedItemRows[3]),
			...array_values($unorderedItemRows[0]),
		];
	}
}
