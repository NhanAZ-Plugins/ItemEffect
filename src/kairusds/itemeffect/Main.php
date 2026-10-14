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
		$this->getLogger()->info("Đã Hoạt Động");
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if(!$sender->hasPermission("pocketmine.command.ie")){
			$sender->sendMessage(TextFormat::RED . "Bạn không có quyền dùng lệnh này.");
			return true;
		}

		$subCommand = strtolower($args[0] ?? "");
		if($subCommand === "list" && count($args) === 1){
			$sender->sendMessage(TextFormat::GREEN . "Hiệu ứng có thể gắn: " . implode(TextFormat::WHITE . ", " . TextFormat::AQUA, array_keys($this->supportedEffects)));
			return true;
		}

		if($subCommand !== "add" || !isset($args[1]) || count($args) > 3){
			$this->sendUsage($sender);
			return true;
		}

		$effectName = strtolower(trim($args[1]));
		if(!isset($this->supportedEffects[$effectName])){
			$sender->sendMessage(TextFormat::RED . "Hiệu ứng không hợp lệ hoặc không thể duy trì. Dùng /ie list để xem danh sách.");
			return true;
		}

		$target = $sender instanceof Player ? $sender : null;
		if(isset($args[2])){
			$target = $this->getServer()->getPlayerByPrefix($args[2]);
			if($target === null){
				$sender->sendMessage(TextFormat::RED . "Người chơi không trực tuyến hoặc tên không khớp duy nhất.");
				return true;
			}
		}elseif($target === null){
			$this->sendUsage($sender);
			return true;
		}

		$item = $target->getInventory()->getItemInHand();
		if($item->isNull()){
			$sender->sendMessage(TextFormat::RED . "Người chơi cần cầm một vật phẩm trước khi gắn hiệu ứng.");
			return true;
		}

		$namedTag = $item->getNamedTag();
		$existingTag = $namedTag->getTag("Effects");
		if($existingTag !== null && (!$existingTag instanceof ListTag || $existingTag->getTagType() !== NBT::TAG_String)){
			$sender->sendMessage(TextFormat::RED . "Dữ liệu hiệu ứng trên vật phẩm không hợp lệ; vật phẩm chưa được sửa.");
			return true;
		}

		$effectList = $existingTag instanceof ListTag ? $existingTag : new ListTag();
		foreach($effectList->getValue() as $storedEffect){
			if($storedEffect instanceof StringTag && strtolower(trim($storedEffect->getValue())) === $effectName){
				$sender->sendMessage(TextFormat::YELLOW . "Vật phẩm đã có hiệu ứng " . $effectName . ".");
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
		$sender->sendMessage(TextFormat::GREEN . "Đã gắn hiệu ứng " . $effectName . " vào vật phẩm của " . $target->getName() . ".");
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
		$sender->sendMessage(TextFormat::YELLOW . "Cách dùng: /ie list | /ie add <hiệu ứng> [người chơi]");
		$sender->sendMessage(TextFormat::GRAY . "Người chơi có thể bỏ tên để gắn hiệu ứng vào vật phẩm đang cầm.");
	}
}
