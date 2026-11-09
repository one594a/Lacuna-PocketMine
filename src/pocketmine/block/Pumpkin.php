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

namespace pocketmine\block;

use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Pumpkin extends Solid{

	protected $id = self::PUMPKIN;

	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}

	public function getHardness() : float{
		return 1;
	}

	public function getToolType() : int{
		return BlockToolType::TYPE_AXE;
	}

	public function getName() : string{
		return "Pumpkin";
	}

	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = \null) : bool{
		if($player instanceof Player){
			$this->meta = ((int) $player->getDirection() + 1) % 4;
		}
		$level = $this->getLevel();
		$level->setBlock($blockReplace, $this, \true, \true);

		//Advanced config: server.allow-snow-golem / server.allow-iron-golem (ported from GenisysPro)
		//LitPumpkin inherits this, so both pumpkin types can build golems.
		if($player !== \null and $level !== \null){
			$below = $level->getBlock($blockReplace->add(0, -1, 0));
			$below2 = $level->getBlock($blockReplace->add(0, -2, 0));
			$server = $level->getServer();

			if($server->allowSnowGolem and $below->getId() === Block::SNOW_BLOCK and $below2->getId() === Block::SNOW_BLOCK){
				$this->clearBlocks([$blockReplace, $below, $below2]);
				$this->spawnGolem("SnowGolem", $below->add(0.5, 0, 0.5));
			}elseif($server->allowIronGolem and $below->getId() === Block::IRON_BLOCK and $below2->getId() === Block::IRON_BLOCK){
				$west = $level->getBlock($blockReplace->add(-1, -1, 0));
				$east = $level->getBlock($blockReplace->add(1, -1, 0));
				$north = $level->getBlock($blockReplace->add(0, -1, -1));
				$south = $level->getBlock($blockReplace->add(0, -1, 1));

				$arms = \null;
				if($west->getId() === Block::IRON_BLOCK and $east->getId() === Block::IRON_BLOCK and
					$north->getId() === Block::AIR and $south->getId() === Block::AIR){
					$arms = [$west, $east];
				}elseif($north->getId() === Block::IRON_BLOCK and $south->getId() === Block::IRON_BLOCK and
					$west->getId() === Block::AIR and $east->getId() === Block::AIR){
					$arms = [$north, $south];
				}

				if($arms !== \null){
					$this->clearBlocks(\array_merge([$blockReplace, $below, $below2], $arms));
					$this->spawnGolem("IronGolem", $below->add(0.5, 0, 0.5));
				}
			}
		}

		return \true;
	}

	/** @param Vector3[] $blocks */
	private function clearBlocks(array $blocks) : void{
		foreach($blocks as $block){
			$this->getLevel()->setBlock($block, BlockFactory::get(Block::AIR), \true, \true);
		}
	}

	private function spawnGolem(string $type, Vector3 $pos) : void{
		$entity = Entity::createEntity($type, $this->getLevel(), Entity::createBaseNBT($pos));
		if($entity !== \null){
			$entity->spawnToAll();
		}
	}

	public function getVariantBitmask() : int{
		return 0;
	}
}
