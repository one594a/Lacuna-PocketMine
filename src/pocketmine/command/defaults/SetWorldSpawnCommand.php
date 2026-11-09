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

namespace pocketmine\command\defaults;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class SetWorldSpawnCommand extends VanillaCommand{

	public function __construct(string $name){
		parent::__construct(
			$name,
			"%pocketmine.command.setworldspawn.description",
			"%commands.setworldspawn.usage"
		);
		$this->setPermission("pocketmine.command.setworldspawn");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return \true;
		}

		// 客户端会多传尾部空格的空参数，先过滤掉
		$args = \array_values(\array_filter($args, function($arg) : bool{
			return \trim((string) $arg) !== "";
		}));

		if(\count($args) === 0){
			// 没给坐标就用玩家当前位置
			if(!($sender instanceof Player)){
				$sender->sendMessage(TextFormat::RED . "控制台执行时必须指定坐标：/setworldspawn <x> <y> <z>");

				return \true;
			}

			$level = $sender->getLevel() ?? $sender->getServer()->getDefaultLevel();
			$pos = (new Vector3($sender->x, $sender->y, $sender->z))->round();
		}elseif(\count($args) === 3){
			$level = $sender->getServer()->getDefaultLevel();
			$pos = new Vector3($this->getInteger($sender, $args[0]), $this->getInteger($sender, $args[1]), $this->getInteger($sender, $args[2]));
		}else{
			throw new InvalidCommandSyntaxException();
		}

		if($level === \null){
			$sender->sendMessage(TextFormat::RED . "无法获取世界，请稍后重试");

			return \true;
		}

		$level->setSpawnLocation($pos);

		Command::broadcastCommandMessage($sender, new TranslationContainer("commands.setworldspawn.success", [\round($pos->x, 2), \round($pos->y, 2), \round($pos->z, 2)]));

		return \true;
	}
}
