<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
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

namespace pocketmine\network\mcpe\protocol;

#include <rules/DataPacket.h>


use pocketmine\network\mcpe\handler\SessionHandler;
use pocketmine\network\mcpe\NetworkBinaryStream;
use pocketmine\resourcepacks\ResourcePack;
use function count;

class ResourcePacksInfoPacket extends DataPacket implements ClientboundPacket{
	public const NETWORK_ID = ProtocolInfo::RESOURCE_PACKS_INFO_PACKET;

	/** @var bool */
	public $mustAccept = false; //if true, forces client to use selected resource packs
	/** @var bool */
	public $hasScripts = false; //if true, causes disconnect for any platform that doesn't support scripts yet
	/** @var ResourcePack[] */
	public $behaviorPackEntries = [];
	/** @var ResourcePack[] */
	public $resourcePackEntries = [];

	/**
	 * @param ResourcePack[] $resourcePacks
	 * @param ResourcePack[] $behaviorPacks
	 * @param bool           $mustAccept
	 * @param bool           $hasScripts
	 *
	 * @return ResourcePacksInfoPacket
	 */
	public static function create(array $resourcePacks, array $behaviorPacks, bool $mustAccept, bool $hasScripts = false) : self{
		(function(ResourcePack ...$_){})($resourcePacks);
		(function(ResourcePack ...$_){})($behaviorPacks);
		$result = new self;
		$result->mustAccept = $mustAccept;
		$result->hasScripts = $hasScripts;
		$result->resourcePackEntries = $resourcePacks;
		$result->behaviorPackEntries = $behaviorPacks;
		return $result;
	}

	protected function decodePayload(NetworkBinaryStream $in) : void{
		$this->mustAccept = $in->getBool();
		$this->hasScripts = $in->getBool();
		$behaviorPackCount = $in->getLShort();
		while($behaviorPackCount-- > 0){
			$in->getString();
			$in->getString();
			$in->getLLong();
			$in->getString();
			$in->getString();
			$in->getString();
			$in->getBool();
		}

		$resourcePackCount = $in->getLShort();
		while($resourcePackCount-- > 0){
			$in->getString();
			$in->getString();
			$in->getLLong();
			$in->getString();
			$in->getString();
			$in->getString();
			$in->getBool();
		}
	}

	protected function encodePayload(NetworkBinaryStream $out) : void{
		$out->putBool($this->mustAccept);
		$out->putBool($this->hasScripts);
		$out->putLShort(count($this->behaviorPackEntries));
		foreach($this->behaviorPackEntries as $entry){
			$out->putString($entry->getPackId());
			$out->putString($entry->getPackVersion());
			$out->putLLong($entry->getPackSize());
			$out->putString(""); //TODO: encryption key
			$out->putString(""); //TODO: subpack name
			$out->putString(""); //TODO: content identity
			$out->putBool(false); //TODO: has scripts (?)
		}
		$out->putLShort(count($this->resourcePackEntries));
		foreach($this->resourcePackEntries as $entry){
			$out->putString($entry->getPackId());
			$out->putString($entry->getPackVersion());
			$out->putLLong($entry->getPackSize());
			$out->putString(""); //TODO: encryption key
			$out->putString(""); //TODO: subpack name
			$out->putString(""); //TODO: content identity
			$out->putBool(false); //TODO: seems useless for resource packs
		}
	}

	public function handle(SessionHandler $handler) : bool{
		return $handler->handleResourcePacksInfo($this);
	}
}
