# The Aspire Dependency Injection Container -- part of the Aspire Framework (eventually)

For now, this library is based heavily on [Dice](https://github.com/Level-2/Dice)
because I've contributed to it before and have a good understanding of its workings.

However, my current goal is to catalogue the details of many DIC libraries,
lay out my opinions on each, and synthesize my favorite parts of all of them into
my ideal DIC library.
This is currently underway. See [this repo's wiki](https://github.com/garrettw/aspire-dic/wiki)
for more info.

## Desired features
This is a list of the features I have decided I want to be in this.
* Auto-wiring by default but allow it to be disabled
* Custom ctor params in config and at create-time
* Explicit class/interface substitutions
* Optional rule inheritance (Dice)
* Object creation delegation (AmPHP does it better); is this a good factory replacement?
* Internal function memoization, avoiding internal typehints, and other speed enhancements (Dice)
* Share an instance only within part of the object tree (Dice)
* In order to allow for multiple instances of one class to be shared:
  * Dice makes up names for instances which you then use in the configuration for "parent" classes (ones farther up the object tree).
  * AmPHP uses delegation functions and param name sniffing to do the same thing; I feel like this might be better.
* Dynamic rule adjustment (Dice)
* Explicitly reference container's instance of a class in configuration (Dice)
* Pass container into instances (maybe)
* Specifying custom ctor params using the param name, not just position (AmPHP)
* Able to perform auto-wiring on any PHP callable (AmPHP)
* Configure container using a separate object, pursuant to SRP
* A way to find the correct classnames based on applying a formula to interface names
* A way for modular code to provide rules/definitions to the container with a common interface (such as Joomla's service providers)
* Child containers for managing resolution scope (Joomla); children can selectively override definitions without affecting parent's definitions

## Undesired features
* explicit setter injection
* A self-binding global container instance (Cobalt)
