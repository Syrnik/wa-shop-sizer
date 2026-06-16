Shipment Dimensions Calculator Plugin for Shop-Script
======================================================

[Русская версия](README.md)

## Requirements

- PHP 8.2+
- Shop-Script 8.0+

## How It Works

In the plugin settings you define a list of packaging options of different sizes and set a weight limit for each package.
The plugin retrieves the list of items in an order, calculates their total weight, and selects the appropriate package.
If any item in the order has a dimension that exceeds the largest dimension of the selected package, the plugin looks for
the next larger package whose dimensions accommodate the order. The predefined weight of the packaging is also added to
the total order weight.

This calculation method is not highly precise, but works well for typical everyday cases.
