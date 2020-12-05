<?php

declare(strict_types=1);

namespace RKAbdul\OreSpawners\Events;

use pocketmine\event\block\Block;
use pocketmine\event\Cancellable;
use pocketmine\Player;

use DenielWorld\EzTiles\EzTiles;
use DenielWorld\EzTiles\tile\SimpleTile;

class OreSpawnerGenerateEvent extends OreSpawnerEvent implements Cancellable
{
    /**
     * @var object
     */
    private $block;
        
    /**
     * Initialize objects.
     *
     * @param  SimpleTile $spawnerTile
     * @param  object $block
     * @return void
     */
    public function __construct(SimpleTile $spawnerTile, Block $block)
    {
        $this->block = $block;

        parent::__construct($spawnerTile, null);
    }
    
    /**
     * getBlock
     *
     * @return object
     */
    public function getBlock(): Block
    {
        return $this->block;
    }
}