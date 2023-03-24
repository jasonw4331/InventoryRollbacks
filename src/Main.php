<?php

declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks;

use jasonwynn10\InventoryRollbacks\lang\CustomKnownTranslationFactory;
use jasonwynn10\InventoryRollbacks\task\SaveTransactionsTask;
use libCustomPack\libCustomPack;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use muqsit\invmenu\type\util\InvMenuTypeBuilders;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\SimpleInventory;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\StringToItemParser;
use pocketmine\lang\Language;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\inventory\WindowTypes;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\utils\TextFormat;
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
	private static array $languages = [];
	private static ?ZippedResourcePack $pack;

	public function onEnable() : void{
		if(!InvMenuHandler::isRegistered()){
			InvMenuHandler::register($this);
		}

		// register custom inventory menu compatible with increased inventory size
		$menuType = InvMenuTypeBuilders::ACTOR_FIXED()->setNetworkWindowType(WindowTypes::CONTAINER)->setSize(6 * 13); // 6 rows of 13 slots meant to hold the 4x9 inventory of a player inventory with 2 slots padding all sides
		$menuType->getActorMetadata()->setString(EntityMetadataProperties::NAMETAG, str_repeat(TextFormat::RESET, 10));
		InvMenuHandler::getTypeRegistry()->register(self::TYPE_ROLLBACKS_VIEW, $menuType->build());

		// Build and register resource pack
		libCustomPack::registerResourcePack(self::$pack = libCustomPack::generatePackFromResources($this));
		$this->getLogger()->debug('Resource pack installed');

		// register command
		$this->getServer()->getCommandMap()->register($this->getName(), new command\RollbackInventory($this));

		// register event listener
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

		$this->saveResource('/lang/config.yml');
		/** @var string[][] $contents */
		$contents = yaml_parse_file(Path::join($this->getDataFolder(), "lang", 'config.yml'));
		$languageAliases = [];
		foreach($contents as $language => $aliases){
			$mini = mb_strtolower($aliases['mini']);
			$this->saveResource('/lang/data/' . $mini . '.ini');
			$languageAliases[$mini] = $language;
		}

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
				self::$languages[$languageName] = $language;
				foreach($languageAliases as $languageAlias => $alias){
					if(mb_strtolower($alias) === $languageName){
						self::$languages[mb_strtolower($languageAlias)] = $language;
						unset($languageAliases[$languageAlias]);
					}
				}
			}
		}

		// add translations to existing server language instance
		$languageA = $this->getServer()->getLanguage();
		$refClass = new \ReflectionClass($languageA);
		$refPropA = $refClass->getProperty('lang');
		$refPropA->setAccessible(true);
		/** @var string[] $langA */
		$langA = $refPropA->getValue($languageA);

		$languageB = self::$languages[$languageA->getLang()];
		$refClass = new \ReflectionClass($languageB);
		$refPropB = $refClass->getProperty('lang');
		$refPropB->setAccessible(true);
		/** @var string[] $langB */
		$langB = $refPropB->getValue($languageB);

		$refPropA->setValue($languageA, array_merge($langA, $langB));
	}

	public function onDisable() : void{
		libCustomPack::unregisterResourcePack(self::$pack);
		$this->getLogger()->debug('Resource pack uninstalled');

		unlink(\Webmozart\PathUtil\Path::join($this->getDataFolder(), self::$pack->getPackName() . '.mcpack'));
		$this->getLogger()->debug('Resource pack file deleted');
		// TODO: handle saving of incremental transaction data
	}

	public function showTransactionsMenu(Player $viewer, Player $player, ?\DateTime $timestamp = null) : void{
		$lang = $player->getLanguage();

		// identify padding item
		$itemString = filter_var($this->getConfig()->get('Filler Item', 'black_stained_glass_pane'), options: FILTER_NULL_ON_FAILURE);
		$item = StringToItemParser::getInstance()->parse($itemString) ?? LegacyStringToItemParser::getInstance()->parse($itemString);
		$fillerItem = $item->setCustomName(' ');

		// identify navigation items
		$itemString = filter_var($this->getConfig()->get('Next Page Item', 'green_concrete'), options: FILTER_NULL_ON_FAILURE);
		$item = StringToItemParser::getInstance()->parse($itemString) ?? LegacyStringToItemParser::getInstance()->parse($itemString);
		$nextPageItem = $item->setCustomName($lang->translate(CustomKnownTranslationFactory::menu_nextpage()));
		$itemString = filter_var($this->getConfig()->get('Previous Page Item', 'red_concrete'), options: FILTER_NULL_ON_FAILURE);
		$item = StringToItemParser::getInstance()->parse($itemString) ?? LegacyStringToItemParser::getInstance()->parse($itemString);
		$previousPageItem = $item->setCustomName($lang->translate(CustomKnownTranslationFactory::menu_previouspage()));

		// identify inventory contents
		$inventoryContents = $timestamp === null ? $player->getInventory()->getContents(true) : ($this->getTimestampedInventories($player, $timestamp)[0]?->getContents(true) ?? []);
		$armorInventoryContents = $timestamp === null ? $player->getArmorInventory()->getContents(true) : ($this->getTimestampedInventories($player, $timestamp)[1]?->getContents(true) ?? []);
		$cursorInventoryContents = $timestamp === null ? $player->getCursorInventory()->getContents(true) : ($this->getTimestampedInventories($player, $timestamp)[2]?->getContents(true) ?? []);
		$offHandInventoryContents = $timestamp === null ? $player->getOffHandInventory()->getContents(true) : ($this->getTimestampedInventories($player, $timestamp)[3]?->getContents(true) ?? []);

		// create menu
		$menu = InvMenu::create(self::TYPE_ROLLBACKS_VIEW);

		// populate with padding items and previous inventories
		$menu->getInventory()->setContents($this->compileMenuItems($fillerItem, $nextPageItem, $previousPageItem, $inventoryContents, $armorInventoryContents, $cursorInventoryContents, $offHandInventoryContents));

		$menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) use ($nextPageItem, $previousPageItem, $viewer, $player, $timestamp) : void{
			if($transaction->getItemClicked()->equals($nextPageItem, false, false)){ // when next page item is clicked, show next page
				// change bottom 4 row items to show next page inventory
				$this->showTransactionsMenu($viewer, $player, $this->findPreviousTimestamp($player, $timestamp));
			}elseif($transaction->getItemClicked()->equals($previousPageItem, false, false)){ // when previous page item is clicked, show previous page
				// change bottom 4 row items to show previous inventory
				$this->showTransactionsMenu($viewer, $player, $this->findNextTimestamp($player, $timestamp));
			}
		}));

		$menu->send($viewer);
	}

	public function saveTransactionsOfPlayer(Player $player) : bool{
		// submit an async task which will save the player's transactions to disk as an NBT file
		$this->getServer()->getAsyncPool()->submitTask(new SaveTransactionsTask($this, $player));
		return true;
	}

	/**
	 * @phpstan-param Item[] $playerInventoryItems
	 * @phpstan-param array{
	 *  0: Item,
	 *  1: Item,
	 *  2: Item,
	 *  3: Item
	 * }                     $armorInventoryItems
	 * @phpstan-param array{
	 *  0: Item,
	 * }                     $cursorInventoryItems
	 * @phpstan-param array{
	 *  0: Item,
	 * }
	 *
	 * @return Item[]
	 */
	private function compileMenuItems(Item $fillerItem, Item $nextPageItem, Item $previousPageItem, array $playerInventoryItems, array $armorInventoryItems, array $cursorInventoryItems, array $offHandItems) : array{
		$unorderedItemRows = [];
		// separate player inventory into rows of 9
		for($i = 0; $i < 4; $i++){
			$unorderedItemRows[] = array_slice($playerInventoryItems, $i * 9, 9);
		}

		// name armor slots if air
		foreach($armorInventoryItems as $slot => $item){
			if($item->isNull()){
				$armorInventoryItems[$slot] = VanillaBlocks::INVISIBLE_BEDROCK()->asItem()->setCustomName('Armor Slot ' . ($slot + 1));
			}
		}

		// name cursor slot if air
		if($cursorInventoryItems[0]->isNull()){
			$cursorInventoryItems[0] = VanillaBlocks::INVISIBLE_BEDROCK()->asItem()->setCustomName('Cursor Slot');
		}

		// name offhand slot if air
		if($offHandItems[0]->isNull()){
			$offHandItems[0] = VanillaBlocks::INVISIBLE_BEDROCK()->asItem()->setCustomName('Offhand Slot');
		}

		// offset each row by 2 slots to account for padding
		$unorderedItemRows = array_map(static fn(array $row) => array_pad($row, -11, $fillerItem), $unorderedItemRows);
		// add 2 padding items to end of each row
		$unorderedItemRows = array_map(static fn(array $row) => array_merge($row, [$fillerItem, $fillerItem]), $unorderedItemRows);

		// add 2 rows of padding items to top
		$unorderedItemRows[] = array_fill(0, 13, $fillerItem);
		$unorderedItemRows[] = array_fill(0, 13, $fillerItem);

		// add page items to top row at indexes 3 and 10
		$unorderedItemRows[4][3] = $previousPageItem;
		$unorderedItemRows[4][9] = $nextPageItem;

		// add armor at right-most side of 2nd row and below
		$unorderedItemRows[1][12] = $armorInventoryItems[0];
		$unorderedItemRows[2][12] = $armorInventoryItems[1];
		$unorderedItemRows[3][12] = $armorInventoryItems[2];
		$unorderedItemRows[0][12] = $armorInventoryItems[3];

		// add armor items at left-most side of 3rd row
		$unorderedItemRows[1][0] = $offHandItems[0];

		// add cursor items at left-most side of 5th row
		$unorderedItemRows[3][0] = $cursorInventoryItems[0];

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

	/**
	 * @phpstan-return array{
	 *     0: SimpleInventory|null,
	 *     1: SimpleInventory|null,
	 *     2: SimpleInventory|null,
	 *     3: SimpleInventory|null
	 * }
	 */
	private function getTimestampedInventories(Player $player, \DateTime $timestamp) : array{
		return [];
	}

	private function findPreviousTimestamp(Player $player, ?\DateTime $timestamp = null) : ?\DateTime{
		return null;
	}

	private function findNextTimestamp(Player $player, ?\DateTime $timestamp = null) : ?\DateTime{
		return null;
	}
}
