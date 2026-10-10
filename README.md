# ItemEffect

ItemEffect is a historical PocketMine-MP plugin that stores effect names on items and applies them while players hold those items.

## Compatibility and status

The manifest declares PocketMine-MP API `3.0.0` and `3.0.0-ALPHA5`. The source uses legacy `Effect`, named tag and `PluginTask` APIs. It has not been ported to Axolotl-PM 5. Do not install it on a current server or use it with valuable player inventories.

The historical `/itemeffect` command has the alias `/ie` and can add an effect to the item held by a named online player. A future port needs a review of permission behavior, malformed command input tests, item data migration and gameplay verification before use.

The manifest credits KairusDarkSeeker and records a Vietnamese edit and translation by Ghast Noob. Preserve both credits. There is no LICENSE file, CI workflow, DevTools PHAR, Axolotl-PM 5 runtime test or verified current release. Resolve redistribution rights before publishing a modified build.

For questions about the NhanAZ ecosystem, use [NhanAZ Discord](https://discord.gg/j2X83ujT6c).
