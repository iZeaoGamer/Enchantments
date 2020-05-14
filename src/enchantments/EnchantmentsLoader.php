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
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\Human;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\block\TNT;

class EnchantmentsLoader extends PluginBase implements Listener{

	public function onEnable(){
	//	Enchantment::registerEnchantment(new Enchantment(Enchantment::SHARPNESS, 'Sharpness', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD | Enchantment::SLOT_AXE, Enchantment::SLOT_NONE, 5));
	//	Enchantment::registerEnchantment(new Enchantment(Enchantment::FORTUNE, 'Fortune', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_PICKAXE | Enchantment::SLOT_AXE | Enchantment::SLOT_SHOVEL, Enchantment::SLOT_NONE, 3));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::KNOCKBACK, 'Knockback', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_NONE, 2));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::POWER, 'Power', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 5));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::PUNCH, 'Punch',  Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 2));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::INFINITY, 'Infinity', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 1));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::FIRE_ASPECT, 'Fire aspect', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_NONE, 2));
		Enchantment::registerEnchantment(new Enchantment(Enchantment::FLAME, 'Flame', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_BOW, Enchantment::SLOT_NONE, 1)); 
		//Enchantment::registerEnchantment(new Enchantment(Enchantment::LOOTING, 'Looting', Enchantment::RARITY_UNCOMMON, Enchantment::SLOT_SWORD, Enchantment::SLOT_NONE, 3));

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
					$entity->getInventory()->addItem(Item::get(Item::ARROW));
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

			$level = $bow->getEnchantmentLevel(Enchantment::FLAME);

			if($level > 0){
				$projectile->setOnFire($level * 4);
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
							$event->setBaseDamage($event->getOriginalBaseDamage() * 0.25 * ($level + 1) + $event->getBaseDamage());
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
						
						$event->setBaseDamage($damage + $event->getBaseDamage());
					}

					$level = $item->getEnchantmentLevel(Enchantment::FIRE_ASPECT);

					if($level > 0){
						$entity->setOnFire($level * 3 + 1);
					}
				} 
			}
		}
	}

	/**
	 * @param ProjectileHitBlockEvent $event
	 *
	 * @priority LOWEST
	 */
	public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void{
		$entity = $event->getEntity();

		if($entity instanceof Arrow && $entity->isOnFire()){
			$block = $event->getBlockHit();

			if($block instanceof TNT){
				$block->ignite();
			}
		}
	}
}
