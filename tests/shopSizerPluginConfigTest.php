<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2026
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Проверяет проводку lib/config/plugin.php: обработчик события shipping_package должен
 * существовать как публичный метод класса — защита от переименования метода без правки конфига.
 */
class shopSizerPluginConfigTest extends TestCase
{
    public function testShippingPackageHandlerFromPluginConfigExistsAsPublicMethod(): void
    {
        $info = include dirname(__DIR__) . '/lib/config/plugin.php';

        $this->assertArrayHasKey('shipping_package', $info['handlers']);

        $method = $info['handlers']['shipping_package'];
        $reflection = new ReflectionMethod(shopSizerPlugin::class, $method);

        $this->assertTrue($reflection->isPublic());
    }
}
