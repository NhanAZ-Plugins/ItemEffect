<?php

declare(strict_types=1);

namespace kairusds\itemeffect;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\InstantEffect;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\Listener;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;

final class Main extends PluginBase implements Listener{
	/** @var array<string, Effect> */
	private array $supportedEffects = [];

	protected function onEnable() : void{
		foreach(VanillaEffects::getAll() as $name => $effect){
			if(!$effect instanceof InstantEffect){
				$this->supportedEffects[strtolower($name)] = $effect;
			}
		}
		ksort($this->supportedEffects, SORT_STRING);

		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void{
			$this->applyHeldItemEffects();
		}), 1);
		$this->getLogger()->info("ItemEffect enabled.");
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender->hasPermission("pocketmine.command.ie")){
			$sender->sendMessage(TextFormat::RED . "You do not have permission to use this command.");
			return true;
		}

		$subCommand = strtolower($args[0] ?? "");
		if($subCommand === "list" && count($args) === 1){
			$sender->sendMessage(TextFormat::GREEN . "Available effects: " . implode(TextFormat::WHITE . ", " . TextFormat::AQUA, array_keys($this->supportedEffects)));
			return true;
		}

		if($subCommand !== "add" || !isset($args[1]) || count($args) > 3){
			$this->sendUsage($sender);
			return true;
		}

		$effectName = strtolower(trim($args[1]));
		if(!isset($this->supportedEffects[$effectName])){
			$sender->sendMessage(TextFormat::RED . "Invalid or unsupported effect. Use /ie list to see available effects.");
			return true;
		}

		$target = $sender instanceof Player ? $sender : null;
		if(isset($args[2])){
			$target = $this->getServer()->getPlayerByPrefix($args[2]);
			if($target === null){
				$sender->sendMessage(TextFormat::RED . "The player is offline or the name is not unique.");
				return true;
			}
		}elseif($target === null){
			$this->sendUsage($sender);
			return true;
		}

		$item = $target->getInventory()->getItemInHand();
		if($item->isNull()){
			$sender->sendMessage(TextFormat::RED . "The player must hold an item before adding an effect.");
			return true;
		}

		$namedTag = $item->getNamedTag();
		$existingTag = $namedTag->getTag("Effects");
		if($existingTag !== null && (!$existingTag instanceof ListTag || $existingTag->getTagType() !== NBT::TAG_String)){
			$sender->sendMessage(TextFormat::RED . "The item's effect data is invalid; the item was not changed.");
			return true;
		}

		$effectList = $existingTag instanceof ListTag ? $existingTag : new ListTag();
		foreach($effectList->getValue() as $storedEffect){
			if($storedEffect instanceof StringTag && strtolower(trim($storedEffect->getValue())) === $effectName){
				$sender->sendMessage(TextFormat::YELLOW . "The item already has the " . $effectName . " effect.");
				return true;
			}
		}

		$effectList->push(new StringTag($effectName));
		$namedTag->setTag("Effects", $effectList);
		$item->setNamedTag($namedTag);

		if(!$item->hasCustomName()){
			$item->setCustomName(TextFormat::RESET . TextFormat::AQUA . $item->getVanillaName());
		}

		$loreLine = TextFormat::RESET . TextFormat::LIGHT_PURPLE . ucfirst(str_replace("_", " ", $effectName));
		$lore = $item->getLore();
		if(!in_array($loreLine, $lore, true)){
			$lore[] = $loreLine;
			$item->setLore($lore);
		}

		$target->getInventory()->setItemInHand($item);
		$sender->sendMessage(TextFormat::GREEN . "Added the " . $effectName . " effect to " . $target->getName() . "'s item.");
		return true;
	}

	private function applyHeldItemEffects() : void{
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$namedTag = $player->getInventory()->getItemInHand()->getNamedTag();
			$effectsTag = $namedTag->getTag("Effects");
			if(!$effectsTag instanceof ListTag || $effectsTag->getTagType() !== NBT::TAG_String){
				continue;
			}

			foreach($effectsTag->getValue() as $storedEffect){
				if(!$storedEffect instanceof StringTag){
					continue;
				}

				$effectName = strtolower(trim($storedEffect->getValue()));
				$effect = $this->supportedEffects[$effectName] ?? null;
				if($effect === null){
					continue;
				}

				$current = $player->getEffects()->get($effect);
				if(!EffectRefreshPolicy::shouldRefresh($current?->getDuration(), $current?->getAmplifier())){
					continue;
				}

				$player->getEffects()->add(new EffectInstance($effect, 20, 0));
			}
		}
	}

	private function sendUsage(CommandSender $sender) : void{
		$sender->sendMessage(TextFormat::YELLOW . "Usage: /ie list | /ie add <effect> [player]");
		$sender->sendMessage(TextFormat::GRAY . "Omit the player name to add an effect to the item you are holding.");
	}
}
