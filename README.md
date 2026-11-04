# ItemEffect

Held item effects plugin for Axolotl-PM API 5.

[![CI](https://github.com/NhanAZ-Plugins/ItemEffect/actions/workflows/build.yml/badge.svg)](https://github.com/NhanAZ-Plugins/ItemEffect/actions/workflows/build.yml)
[![Latest release](https://img.shields.io/github/v/release/NhanAZ-Plugins/ItemEffect)](https://github.com/NhanAZ-Plugins/ItemEffect/releases/latest)
[![Discord](https://img.shields.io/discord/986553214889517088?label=Discord&logo=discord&logoColor=white&color=5865F2)](https://discord.gg/j2X83ujT6c)

## Overview

This plugin targets the PocketMine-MP and Axolotl-PM API 5 ecosystem and is maintained under [NhanAZ-Plugins](https://github.com/NhanAZ-Plugins).

## Commands

- `/ie list` lists effects that can be maintained while an item is held.
- `/ie add <effect> [player]` attaches one listed effect to the held item. Without a player name, the command sender must be a player. The command requires `pocketmine.command.ie`, which defaults to operators.

Instant effects such as instant damage and instant health are excluded because repeatedly applying an instant effect while an item is held would repeatedly trigger its one-shot result. Refreshable effects are reapplied only when their remaining duration is low, and a stronger active effect is preserved. Switching items lets the prior effect expire naturally within 20 ticks.

The manifest retains KairusDarkSeeker as author and the historical Vietnamese edit/translation credit to Ghast Noob. The repository has no LICENSE file, so no stable release or separately distributed PHAR is published. The repository is archived after this API 5 source port and its CI gates.

CI runs PHPStan at maximum level and builds and inspects the PHAR against pinned Axolotl-PM 5.0.0 and 5.49.1 source revisions. It also starts the built plugin on Axolotl-PM 5.49.1. Those checks confirm the tested points, not every 5.x release. A connected-player command, item update, or gameplay effect was not tested.
