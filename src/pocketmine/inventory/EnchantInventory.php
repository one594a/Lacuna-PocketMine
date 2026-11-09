<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\inventory;

use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\Player;

class EnchantInventory extends ContainerInventory{

	/** @var Position */
	protected $holder;

	/** @var int Number of bookshelves around the table (0-15) */
	private $bookshelfAmount = 0;

	public function __construct(Position $pos){
		parent::__construct($pos->asPosition());
		$this->bookshelfAmount = $this->countBookshelf();
	}

	/**
	 * Advanced config: enchantment.count-bookshelf.
	 * This core has no enchantment-level logic, so the value is exposed for plugins to use.
	 */
	public function getBookshelfAmount() : int{
		return $this->bookshelfAmount;
	}

	private function countBookshelf() : int{
		$level = $this->getHolder()->getLevel();
		if(!($level instanceof Level) or !$level->getServer()->countBookshelf){
			//When disabled, GenisysPro picks a random enchantment power between 0 and 15.
			return \mt_rand(0, 15);
		}

		$bx = (int) $this->holder->x;
		$by = (int) $this->holder->y;
		$bz = (int) $this->holder->z;
		$count = 0;

		for($dy = 0; $dy <= 1; ++$dy){
			for($dx = -2; $dx <= 2; ++$dx){
				for($dz = -2; $dz <= 2; ++$dz){
					if($dy === 0 and \abs($dx) <= 1 and \abs($dz) <= 1){
						continue; //the table's own layer is blocked by the table / adjacent blocks
					}
					if(\max(\abs($dx), \abs($dz)) !== 2){
						continue; //vanilla only looks at the outer ring
					}
					if($level->getBlockIdAt($bx + $dx, $by + $dy, $bz + $dz) === Block::BOOKSHELF){
						if(++$count >= 15){
							return 15;
						}
					}
				}
			}
		}

		return $count;
	}

	public function getNetworkType() : int{
		return WindowTypes::ENCHANTMENT;
	}

	public function getName() : string{
		return "Enchantment Table";
	}

	public function getDefaultSize() : int{
		return 2; //1 input, 1 lapis
	}

	/**
	 * This override is here for documentation and code completion purposes only.
	 * @return Position
	 */
	public function getHolder(){
		return $this->holder;
	}

	public function onClose(Player $who) : void{
		parent::onClose($who);

		$this->dropContents($this->holder->getLevel(), $this->holder->add(0.5, 0.5, 0.5));
	}
}
