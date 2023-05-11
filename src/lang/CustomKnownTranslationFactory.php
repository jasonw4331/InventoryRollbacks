<?php

declare(strict_types=1);

namespace jasonw4331\InventoryRollbacks\lang;

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

	public static function rollbackinventory_menu_boots() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::ROLLBACKINVENTORY_MENU_BOOTS, []);
	}

	public static function rollbackinventory_menu_chestplate() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::ROLLBACKINVENTORY_MENU_CHESTPLATE, []);
	}

	public static function rollbackinventory_menu_confirmation(Translatable|string $param0) : Translatable{
		return new Translatable(CustomKnownTranslationKeys::ROLLBACKINVENTORY_MENU_CONFIRMATION, [
			0 => $param0,
		]);
	}

	public static function rollbackinventory_menu_cursorslot() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::ROLLBACKINVENTORY_MENU_CURSORSLOT, []);
	}

	public static function rollbackinventory_menu_helmet() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::ROLLBACKINVENTORY_MENU_HELMET, []);
	}

	public static function rollbackinventory_menu_leggings() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::ROLLBACKINVENTORY_MENU_LEGGINGS, []);
	}

	public static function rollbackinventory_menu_nextpage() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::ROLLBACKINVENTORY_MENU_NEXTPAGE, []);
	}

	public static function rollbackinventory_menu_offhandslot() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::ROLLBACKINVENTORY_MENU_OFFHANDSLOT, []);
	}

	public static function rollbackinventory_menu_previouspage() : Translatable{
		return new Translatable(CustomKnownTranslationKeys::ROLLBACKINVENTORY_MENU_PREVIOUSPAGE, []);
	}

}
