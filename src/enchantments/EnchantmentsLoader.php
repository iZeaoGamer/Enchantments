<?php

declare(strict_types=1);

namespace enchantments;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\entity\projectile\Arrow;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;

class EnchantmentsLoader extends PluginBase implements Listener{

	public function onEnable(){
		Enchantment::registerEnchantment(new Enchantment(Enchantment::SHARPNESS, 'Sharpness', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD | Enchantment::SLOT_AXE, 5));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::FORTUNE, 'Fortune', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_PICKAXE | Enchantment::SLOT_AXE | Enchantment::SLOT_SHOVEL, 3));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::KNOCKBACK, 'Knockback', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, 2));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::POWER, 'Power', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, 5));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::PUNCH, 'Punch',  Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, 2));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::INFINITY, 'Infinity', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, 1));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::FIRE_ASPECT, 'Fire aspect', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, 2));
		//Убрано из-за одного бага (при отмененном событии все равно может немного поджигать).
		//Enchantment::registerEnchantment(new Enchantment(Enchantment::FLAME, 'Flame', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, 1)); 

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	/**
	 * @param EntityShootBowEvent $event
	 *
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onEntityShootBow(EntityShootBowEvent $event): void{
		$entity = $event->getEntity();

		if($entity instanceof Player){
			$projectile = $event->getProjectile();
			$bow = $event->getBow();
			
			if($bow->hasEnchantment(Enchantment::INFINITY)){
				if(!($entity->getGamemode() % 2)){
					$entity->getInventory()->addItem(Item::get(Item::BOW));
				}

				$projectile->namedtag->setShort('isInfinity', 1);
			}

			$level = $bow->getEnchantmentLevel(Enchantment::POWER);

			if($level > 0){
				$projectile->namedtag->setShort('PowerEnch', $level);
			}

			$level = $bow->getEnchantmentLevel(Enchantment::PUNCH);

			if($level > 0){
				$projectile->namedtag->setShort('PunchEnch', $level);
			}
		}
	}
	
	/**
	 * @param InventoryPickupArrowEvent $event
	 *
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onInventoryPickupArrow(InventoryPickupArrowEvent $event): void{
		$entity = $event->getArrow();

		if($entity->namedtag->getShort('isInfinity', 0)){
			$event->setCancelled();
			$entity->close();
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 *
	 * @priority LOWEST
	 * @ignoreCancelled true
	 */
	public function onBlockBreak(BlockBreakEvent $event): void{
		$drops = $event->getDrops();

		if(count($drops) > 0){
			$level = $event->getPlayer()->getInventory()->getItemInHand()->getEnchantmentLevel(Enchantment::FORTUNE);

			if($level > 0){
				switch($event->getBlock()->getId()){
					case Item::EMERALD_ORE: {
						$event->setDrops([Item::get(Item::EMERALD, 0, 1 + rand(0, $level))]);
						break;
					}

					case Item::DIAMOND_ORE: {
						$event->setDrops([Item::get(Item::DIMOND, 0, 1 + rand(0, $level))]);
						break;
					}

					case Item::LIT_REDSTONE_ORE: 
					case Item::REDSTONE_ORE: {
						$event->setDrops([Item::get(Item::REDSTONE_DUST, 0, rand(4, 5) + rand(0, $level + rand(0, 3)))]);
						break;
					}

					case Item::LAPIS_ORE: {
						$event->setDrops([Item::get(Item::DYE, 4, rand(4, 8) + rand(0, $level + rand(0, 5)))]);
						break;
					}

					case Item::COAL_ORE: {
						$event->setDrops([Item::get(Item::COAL, 0, 1 + rand(0, $level))]);
						break;
					}

					case Item::GRAVEL: {
						if($level >= 3){
							$ev->setDrops([Item::get(Item::FLINT)]);
							return;
						}
						break;
					}

					case Item::MELON_BLOCK: {
						$event->setDrops([Item::get(Item::MELON, 0, rand(3, 7) + rand(0, $level + rand(0, 4)))]);
						break;
					}

					case Item::GLOWSTONE: {
						$event->setDrops([Item::get(Item::GLOWSTONE_DUST, 0, rand(2, 4) + rand(0, $level + rand(0, 2)))]);
						break;
					}
				}
			}
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 *
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onEntityDamage(EntityDamageEvent $event): void{
		if($event instanceof EntityDamageByEntityEvent){
			$damager = $event->getDamager();

			if($damager instanceof Player){
				if($event instanceof EntityDamageByChildEntityEvent){
					$child = $event->getChild();

					if($child instanceof Arrow){
						$level = $child->namedtag->getShort('PowerEnch', 0);

						if($level > 0){
							$event->setDamage($event->getOriginalDamage() * 0.25 * ($level + 1) + $event->getDamage());
						}

						$level = $child->namedtag->getShort('PunchEnch', 0);

						if($level > 0){
							$event->setKnockBack(0.2 * $level + $event->getKnockBack());
						}
					}
				} else {
					$item = $damager->getInventory()->getItemInHand();
					$level = $item->getEnchantmentLevel(Enchantment::KNOCKBACK);

					if($level > 0){
						$event->setKnockBack(0.2 * $level + $event->getKnockBack());
					}

					$level = $item->getEnchantmentLevel(Enchantment::SHARPNESS);

					if($level > 0){
						$damage = 0;

						while($level-- > 0){
							$damage += rand(1, 3);
						}
						
						$event->setDamage($damage + $event->getDamage());
					}

					$level = $item->getEnchantmentLevel(Enchantment::FIRE_ASPECT);

					if($level > 0){
						$entity->setOnFire($level * 3 + 1);
					}
				} 
			}
		}
	}
}