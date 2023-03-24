<?php

declare(strict_types=1);

namespace jasonwynn10\InventoryRollbacks\lang;

use pocketmine\lang\Translatable;

/**
 * This class contains factory methods for all the translations known to SimpleReplies.
 * This class is generated automatically, do NOT modify it by hand.
 *
 * @internal
 */
final class CustomKnownTranslationFactory{
	public static function command_rollbackinventory_description() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::COMMAND_ROLLBACKINVENTORY_DESCRIPTION, []);
	}

	public static function command_rollbackinventory_error() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::COMMAND_ROLLBACKINVENTORY_ERROR, []);
	}

	public static function command_rollbackinventory_success(Translatable|string $param0, Translatable|string $param1) : Translatable{
		return new Translatable(CustomKnownTranslationKeys::COMMAND_ROLLBACKINVENTORY_SUCCESS, [
			0 => $param0,
			1 => $param1,
		]);
	}

	public static function command_rollbackinventory_usage() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::COMMAND_ROLLBACKINVENTORY_USAGE, []);
	}

	public static function menu_armorinventory(Translatable|string $param0) : Translatable{
		return new Translatable(CustomKnownTranslationKeys::MENU_ARMORINVENTORY, [
			0 => $param0,
		]);
	}

	public static function menu_cursorslot() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::MENU_CURSORSLOT, []);
	}

	public static function menu_nextpage() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::MENU_NEXTPAGE, []);
	}

	public static function menu_offhandslot() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::MENU_OFFHANDSLOT, []);
	}

	public static function menu_previouspage() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::MENU_PREVIOUSPAGE, []);
	}

}
