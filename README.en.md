Shipment Dimensions Calculator Plugin for Shop-Script
======================================================

[Русская версия](README.md)

## Requirements

- PHP 7.4+
- Shop-Script 8.0+

## How It Works

In the plugin settings you define a list of packaging options of different sizes and set a weight limit for each package.
The plugin retrieves the list of items in an order, calculates their total weight, and selects the appropriate package.
If any item in the order has a dimension that exceeds the largest dimension of the selected package, the plugin looks for
the next package for a heavier weight whose largest dimension is not less than that of the item. If there is no such
package, the one selected by weight is kept. The predefined weight of the selected packaging is also added to the total
order weight.

Item dimensions are taken into account only if product features for length, width and height are set in the store's
shipping settings. Otherwise the package is selected by weight only.

This calculation method is not highly precise, but works well for typical everyday cases.
