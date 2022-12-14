<?php

declare(strict_types=1);

namespace skh6075\pmexpansion\expansion;

use Closure;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\tile\TileFactory;
use pocketmine\data\bedrock\block\convert\BlockObjectToStateSerializer;
use pocketmine\data\bedrock\block\convert\BlockStateToObjectDeserializer;
use pocketmine\data\bedrock\block\convert\BlockStateWriter;
use pocketmine\item\StringToItemParser;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use skh6075\pmexpansion\block\ExtraVanillaBlocks;
use skh6075\pmexpansion\block\tile\RegularCampfireTile;
use skh6075\pmexpansion\block\tile\SoulCampfireTile;
use skh6075\pmexpansion\block\utils\IBlockState;
use function strtolower;
use function str_replace;

final class BlockExpansion implements IExpansion{
	public static function synchronize() : void{
		foreach(ExtraVanillaBlocks::getAll() as $block){
			self::register($block);
		}

		/** @var TileFactory $tileFactory */
		$tileFactory = TileFactory::getInstance();
		$tileFactory->register(RegularCampfireTile::class, ["Campfire", "minecraft:campfire"]);
		$tileFactory->register(SoulCampfireTile::class, ["SoulCampfire", "minecraft:soul_campfire"]);
	}

	private static function register(
		Block|IBlockState $block,
		?Closure $serialize = null,
		?Closure $deserialize = null
	): void{
		$namespace = self::reprocess($block->getName());

		$serialize ??= $block->getStateSerialize() ?? static fn(): BlockStateWriter => BlockStateWriter::create($namespace);
		$deserialize ??= $block->getStateDeserialize() ?? static fn(): Block => clone $block;

		Closure::bind(
			closure: fn(BlockStateToObjectDeserializer $deserializer) => $deserializer->deserializeFuncs[$namespace] = $deserialize,
			newThis: null,
			newScope: BlockStateToObjectDeserializer::class
		)(GlobalBlockStateHandlers::getDeserializer());

		Closure::bind(
			closure: fn(BlockObjectToStateSerializer $serializer) => $serializer->serializers[$block->getTypeId()][get_class($block)] = $serialize,
			newThis: null,
			newScope: BlockObjectToStateSerializer::class
		)(GlobalBlockStateHandlers::getSerializer());

		StringToItemParser::getInstance()->override($namespace, fn() => clone $block->asItem());
		BlockFactory::getInstance()->register($block, true);
	}

	private static function reprocess(string $name): string{
		return "minecraft:" . strtolower(str_replace(' ', '_', $name));
	}
}