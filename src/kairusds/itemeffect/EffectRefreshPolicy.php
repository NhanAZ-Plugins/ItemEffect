<?php

declare(strict_types=1);

namespace kairusds\itemeffect;

final class EffectRefreshPolicy{
	private const REFRESH_THRESHOLD_TICKS = 5;

	public static function shouldRefresh(?int $duration, ?int $amplifier) : bool{
		if($duration === null || $amplifier === null){
			return true;
		}

		return $amplifier === 0 && $duration <= self::REFRESH_THRESHOLD_TICKS;
	}
}
