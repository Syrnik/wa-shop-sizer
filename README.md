Плагин расчёта габаритов отправления для Shop-Script
====================================================

## Требования

 - PHP 8.0+
 - Shop-Script 8.0+

## Как работает

В настройках задаётся список упаковок разных размеров и устанавливается ограничение, для какого веса отправления
подходит указанная упаковка. Плагин получает список товаров в заказе, считает их общий вес, выбирает подходящую упаковку.
Если в заказе есть товар, максимальный габарит которого превышает максимальный габарит упаковки, ищется следующая 
упаковка, для большего веса, такая, чтобы её габарит превышал габарит заказа. Заодно к общему весу заказа добавляется 
заранее указанный вес упаковки.

Какой-то особенной точности этот вариант расчёта не обеспечивает, но вполне подходит для заурядных случаев.
