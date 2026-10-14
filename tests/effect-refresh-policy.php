<?php

declare(strict_types=1);

require_once __DIR__ . "/../src/kairusds/itemeffect/EffectRefreshPolicy.php";

use kairusds\itemeffect\EffectRefreshPolicy;

$cases = [
	["missing effect is added", null, null, true],
	["active effect is not reset every tick", 20, 0, false],
	["effect is refreshed near expiry", 5, 0, true],
	["stronger existing effect is preserved", 1, 1, false],
];

foreach($cases as [$label, $duration, $amplifier, $expected]){
	$actual = EffectRefreshPolicy::shouldRefresh($duration, $amplifier);
	if($actual !== $expected){
		fwrite(STDERR, $label . ": expected " . var_export($expected, true) . ", got " . var_export($actual, true) . PHP_EOL);
		exit(1);
	}
}

echo "Effect refresh policy cases passed." . PHP_EOL;
