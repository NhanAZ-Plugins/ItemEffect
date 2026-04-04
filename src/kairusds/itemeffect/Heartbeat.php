<?php

namespace kairusds\itemeffect;

use pocketmine\scheduler\PluginTask;

class Heartbeat extends PluginTask {
	
	private $plugin;
	
	public function __construct(Main $plugin) {
		parent::__construct($plugin);
		$this->plugin = $plugin;
	}
	
	public function onRun($currentTick) {
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player) {
			$itemInHand = $player->getInventory()->getItemInHand();
			if(Main::hasEffects($itemInHand)) {
				Main::addEffects($player, Main::getEffects($itemInHand->getNamedTag()->Effects));
			}
		}
	}
	
}