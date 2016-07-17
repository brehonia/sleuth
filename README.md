# Sleuth
Character builder for Digimon Story: Cyber Sleuth

## Installation
Sleuth runs on PHP 5 with a MySQL backend.

* Run sleuthsetup.sql to initialise the database
* Create a config file for your credentials (see config.template.php)
* (Prime the path cache?? maybe)

## Features
Find the shortest path from:
 - [x] Digimon to Digimon
 - [x] Digimon to Skill
 - [x] Digimon to Digimon via Skill
 - [ ] Digimon to Skill via Skill
 - [ ] Digimon to Digimon via multiple Skills
 - [ ] Collection of Digimon to Digimon (ie. I have all this trash, use whichever one is already closest)

Pathfinding capabilities:
 - [x] Level requirements
 - [ ] Stat requirements (levels required per point of ATK, DEF, etc.)
 - [ ] Farm training for additional stat points
 - [ ] Stat reduction where necessary
 - [x] ABI/CAM requirements (weighting is a bit arbitrary though)
 - [ ] Exclude DLC
 - [ ] Exclude items/quests
 - [ ] Exclude unseen

Interface:
 - [x] Wanyamon (critically important!!)
 - [ ] Display out-of-scope requirements (ie. quests and items)
 - [ ] Results correction (add to unseen and recalculate)
 - [ ] Any prior training (might affect results if your starting mon already has XP/CAM/ABI under its belt)

## Notes
* Using Dijkstra's algorithm for pathfinding. Right now there's few enough mons/skills that we can generate all possible graphs beforehand, but that will change as features are added (esp. "exclude unseen" and "prior training").
* I scraped a lot of data out of [Draken70's FAQ](http://www.gamefaqs.com/vita/757436-digimon-story-cyber-sleuth/faqs/71778?single=1) and I really hope they're ok with that because I still haven't asked.
