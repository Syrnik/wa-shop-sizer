<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2026
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Проверяет сам bootstrap: класс плагина доступен, дубль создаётся без обращения к БД.
 */
class shopSizerPluginBootstrapTest extends TestCase
{
    public function testPluginClassIsAutoloaded(): void
    {
        $this->assertTrue(class_exists(shopSizerPlugin::class));
    }

    public function testDoubleCanBeInstantiatedWithoutDatabaseInstall(): void
    {
        $plugin = new shopSizerPluginTestDouble();

        $this->assertSame('sizer', $plugin->getId());
    }
}
