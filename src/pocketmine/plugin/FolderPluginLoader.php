<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___/     |_|  |_|_|
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

namespace pocketmine\plugin;

use pocketmine\event\plugin\PluginDisableEvent;
use pocketmine\event\plugin\PluginEnableEvent;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

/**
 * Loads a plugin directly from its unarchived source folder.
 *
 * A source plugin folder must contain a "plugin.yml" and a "src" directory:
 *   plugins/MyPlugin/plugin.yml
 *   plugins/MyPlugin/src/...
 *   plugins/MyPlugin/resources/... (optional)
 */
class FolderPluginLoader implements PluginLoader{

	/** @var Server */
	private $server;

	public function __construct(Server $server){
		$this->server = $server;
	}

	/**
	 * @param string $file
	 *
	 * @return Plugin|null
	 */
	public function loadPlugin(string $file){
		if(!$this->canLoadPlugin($file)){
			return \null;
		}

		if(($description = $this->getPluginDescription($file)) instanceof PluginDescription){
			$this->server->getLogger()->info(TextFormat::LIGHT_PURPLE . "Loading (Source) " . $description->getFullName());

			$dataFolder = \dirname($file) . DIRECTORY_SEPARATOR . $description->getName();
			if(\file_exists($dataFolder) and !\is_dir($dataFolder)){
				throw new \InvalidStateException("Projected dataFolder '" . $dataFolder . "' for " . $description->getName() . " exists and is not a directory");
			}

			$className = $description->getMain();
			$this->server->getLoader()->addPath($file . "/src");

			if(\class_exists($className, \true)){
				$plugin = new $className();
				if(!($plugin instanceof PluginBase)){
					throw new PluginException("Couldn't load source plugin " . $description->getName() . ": main class does not extend PluginBase");
				}

				$this->initPlugin($plugin, $description, $dataFolder, $file);

				return $plugin;
			}else{
				throw new PluginException("Couldn't load source plugin " . $description->getName() . ": main class not found");
			}
		}

		return \null;
	}

	/**
	 * @param string $file
	 *
	 * @return null|PluginDescription
	 */
	public function getPluginDescription(string $file){
		if(!\is_dir($file) or !\file_exists($file . "/plugin.yml")){
			return \null;
		}

		$yaml = @\file_get_contents($file . "/plugin.yml");
		if($yaml === \false or \trim($yaml) === ""){
			return \null;
		}

		return new PluginDescription($yaml);
	}

	/**
	 * Folder plugins have no fixed extension, so every entry containing a
	 * non-dot character is a candidate. canLoadPlugin() does the real check.
	 *
	 * @return string
	 */
	public function getPluginFilters() : string{
		return "/[^\\.]/";
	}

	/**
	 * Returns whether this loader can load the plugin in the given path.
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	public function canLoadPlugin(string $path) : bool{
		return \is_dir($path) and \file_exists($path . "/plugin.yml") and \is_dir($path . "/src");
	}

	/**
	 * @param PluginBase        $plugin
	 * @param PluginDescription $description
	 * @param string            $dataFolder
	 * @param string            $file
	 */
	private function initPlugin(PluginBase $plugin, PluginDescription $description, string $dataFolder, string $file){
		$plugin->init($this, $this->server, $description, $dataFolder, $file);
		$plugin->onLoad();
	}

	/**
	 * @param Plugin $plugin
	 */
	public function enablePlugin(Plugin $plugin){
		if($plugin instanceof PluginBase and !$plugin->isEnabled()){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.enable", [$plugin->getDescription()->getFullName()]));

			$plugin->setEnabled(\true);

			$this->server->getPluginManager()->callEvent(new PluginEnableEvent($plugin));
		}
	}

	/**
	 * @param Plugin $plugin
	 */
	public function disablePlugin(Plugin $plugin){
		if($plugin instanceof PluginBase and $plugin->isEnabled()){
			$this->server->getLogger()->info($this->server->getLanguage()->translateString("pocketmine.plugin.disable", [$plugin->getDescription()->getFullName()]));

			$this->server->getPluginManager()->callEvent(new PluginDisableEvent($plugin));

			$plugin->setEnabled(\false);
		}
	}
}
