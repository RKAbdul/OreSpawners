<?php

declare(strict_types=1);

namespace RKAbdul\OreSpawners\Events;

use pocketmine\event\Cancellable;
use pocketmine\Player;

use DenielWorld\EzTiles\EzTiles;
use DenielWorld\EzTiles\tile\SimpleTile;

class OreSpawnerBreakEvent extends OreSpawnerEvent implements Cancellable
{
    /**
     * @var int
     */
    private $count;
        
    /**
     * Initialize objects.
     *
     * @param  Player $player
     * @param  SimpleTile $spawnerTile
     * @param  int $count
     * @return void
     */
    public function __construct(Player $player, SimpleTile $spawnerTile, int $count)
    {
        $this->count = $count;

        parent::__construct($spawnerTile, $player);
    }
    
    /**
     * getCount
     *
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }
}