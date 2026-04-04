<?php

namespace kairusds\itemeffect;

use pocketmine\Player;
use pocketmine\nbt\NBT;
use pocketmine\item\Item;
use pocketmine\entity\Effect;
use pocketmine\nbt\tag\ListTag;
use pocketmine\utils\TextFormat;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\command\CommandSender;

class Main extends PluginBase {
	
	public function onEnable() {
		$this->getLogger()->info("Đã Hoạt Động");
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new Heartbeat($this), 1);
	}
	
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		if(!isset($args[0])) {
			$sender->sendMessage(TextFormat::RED . "§f==========§eCách Sử Dụng Plugin§f==========\n§f§a§f•§aGhi: /ie add (Tên Hiệu Ứng) §f|§b Để Thêm Hiệu Ứng Vào Vật Phẩm\n§aVD: /ie add speed\n\n§f•§aGhi: /ie add (Tên Hiệu Ứng) (Người Chơi) §f|§b Để Thêm Hiệu Ứng Vào Người Chơi Nào Đó\n§aVD: /ie add speed Steve\n\n§f•§aGhi /ie list §f|§b Để Xem Danh Sách Hiệu Ứng\n§f=======================================");
			return true;
		}
		
		if(!in_array($args[0], ["list", "add"])) {
			$sender->sendMessage(TextFormat::RED . "§f==========§eCách Sử Dụng Plugin§f==========\n§f§a§f•§aGhi: /ie add (Tên Hiệu Ứng) §f|§b Để Thêm Hiệu Ứng Vào Vật Phẩm\n§aVD: /ie add speed\n\n§f•§aGhi: /ie add (Tên Hiệu Ứng) (Người Chơi) §f|§b Để Thêm Hiệu Ứng Vào Người Chơi Nào Đó\n§aVD: /ie add speed Steve\n\n§f•§aGhi /ie list §f|§b Để Xem Danh Sách Hiệu Ứng\n§f=======================================");
			return true;
		}
		
		if($args[0] == "list") {
			$constants = (new \ReflectionClass(Effect::class))->getConstants();
			$effectNames = [];
			foreach($constants as $effect => $id) {
				if($effect == "LEVITATION") {
					continue;
				}
				$effectNames[] = strtolower($effect);
			}
			
			$sender->sendMessage(TextFormat::GREEN . "§f•§aDanh Sách Hiệu Ứng:§b " . implode("§f, §b", $effectNames));
			return true;
		}
		
		if($args[0] == "add") {
			$player = $sender;
			if(isset($args[2])) {
				$player = $this->getServer()->getPlayer($args[2]);
				if($player == null) {
					$sender->sendMessage(TextFormat::RED . "§f•§aNgười Chơi Hiện Không Trực Tuyến");
					return true;
				}
			}elseif(!$sender instanceof Player) {
				$sender->sendMessage(TextFormat::RED . "§f•§aGhi: /ie add (Tên Hiệu Ứng) (Tên Người Chơi)\n§aVD: /ie add speed Steve");
				return true;
			}
			
			if(Effect::getEffectByName($args[1]) == null) {
				$sender->sendMessage(TextFormat::RED . "§f•§aVui Lòng Ghi Tên Hiệu Ứng. Ghi: /ie list Để Xem Danh Sách Hiệu Ứng");
				return true;
			}
			
			$itemInHand = $player->getInventory()->getItemInHand();
			if($itemInHand->getId() == Item::AIR) {
				if($player != $sender) {
					$sender->sendMessage(TextFormat::RED . "§f•§a{$player->getName()} Hiện Không Cầm Vật Phẩm Trên Tay");
					return true;
				}
				$sender->sendMessage(TextFormat::RED . "§f•§aVui Lòng Cầm Vật Phẩm Muốn Thêm Hiệu Ứng Vào Tay. Để Thêm Hiệu Ứng Vào Vật Phẩm");
				return true;
			}
			
			$tag = $itemInHand->getNamedTag() ?? new CompoundTag("", []);
			if(!isset($tag->Effects)) {
				$tag->Effects = new ListTag("Effects");
			}
			$tag->Effects->setTagType(NBT::TAG_String);
			$tag->Effects->{$tag->Effects->getCount()} = new StringTag("", $args[1]);
			$itemInHand->setNamedTag($tag);
			$itemInHand->setCustomName(($itemInHand->hasCustomName() ? $itemInHand->getCustomName() : TextFormat::RESET . TextFormat::AQUA . $itemInHand->getName()));
			$this->setItemLore($itemInHand);
			$player->getInventory()->setItemInHand($itemInHand);
			
			if($player != $sender) {
				$sender->sendMessage(TextFormat::GREEN . "§f•§aĐã Thêm Hiệu Ứng:§b {$args[1]} §aVào Vật Phẩm Của§e {$player->getName()}" . (substr($player->getName(), -1) == "s" ? "'" : "'s") . " {$itemInHand->getName()}");
				return true;
			}
			$sender->sendMessage(TextFormat::GREEN . "§f•§aĐã Thêm Hiệu Ứng:§b {$args[1]} §aVào Vật Phẩm:§b {$itemInHand->getName()}§a Của Bạn");
		}
	}
	
	public function setItemLore(Item $item) {
		$lore = [];
		foreach(self::getEffects($item->getNamedTag()->Effects) as $effectName) {
			$lore[] = str_replace("_", " ", TextFormat::RESET . TextFormat::LIGHT_PURPLE . ucfirst($effectName));
		}
		
		$item->setLore($lore);
	}
	
	public static function hasEffects(Item $item) {
		$tag = $item->getNamedTag();
		return isset($tag->Effects) && $tag->Effects instanceof ListTag;
	}
	
	public static function getEffects(ListTag $tag) {
		$effects = [];
		foreach($tag->getValue() as $effect) {
			$effects[] = $effect->getValue();
		}
		return $effects;
	}
	
	public static function addEffects(Player $player, array $effects) {
		foreach($effects as $effect) {
			$player->addEffect(Effect::getEffectByName($effect)->setDuration(20)->setAmplifier(0));
		}
	}
	
}