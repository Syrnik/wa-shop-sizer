<?php
/**
 * Bootstrap для PHPUnit.
 *
 * Самодостаточный: composer install и dev-зависимости не нужны.
 * Запуск из корня плагина глобально установленным PHPUnit: phpunit -c phpunit.xml
 *
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2026
 */

error_reporting(E_ALL | E_NOTICE);

require_once dirname(__FILE__, 6) . '/wa-config/SystemConfig.class.php';
waSystem::getInstance(null, new SystemConfig());

// sizer — плагин приложения shop, а не системный плагин: инициализация wa('shop') регистрирует
// автозагрузку классов приложения и его плагинов (в т.ч. shopSizerPlugin и shopDimension) сама,
// поэтому подключать файлы плагина вручную не нужно. Цена — соединение с базой открывается сразу.
wa('shop');

// tests/ в автозагрузку плагина не входит (она сканирует только lib/), поэтому тестовые помощники
// требуют явно.
require_once dirname(__FILE__) . '/shopSizerPluginTestDouble.php';
require_once dirname(__FILE__) . '/shopSizerPluginTestAppSettingsModelFake.php';
