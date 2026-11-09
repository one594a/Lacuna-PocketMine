<?php

declare(strict_types=1);

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use function count;

class PingCommand extends VanillaCommand {

	public function __construct(string $name){
		parent::__construct(
			$name,
			"%pocketmine.command.ping.description",
			"%pocketmine.command.ping.usage"
		);
		$this->setPermission("pocketmine.command.ping");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		$target = null;

		if(count($args) === 1){
			$target = $sender->getServer()->getPlayer($args[0]);
		}

		if($target === null){
			if($sender instanceof Player){
				$target = $sender;
			}else{
				$sender->sendMessage(TextFormat::RED . "Please provide a player!");

				return true;
			}
		}

		$ping = $target->getPing();
		$color = TextFormat::GREEN;

		if($ping >= 150 && $ping <= 250){
			$color = TextFormat::GOLD;
		}elseif($ping > 250){
			$color = TextFormat::RED;
		}

		$sender->sendMessage($target->getName() . "'s Ping: " . $color . $ping . "ms");
		return true;
	}
}
