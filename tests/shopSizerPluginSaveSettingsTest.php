<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2026
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * saveSettings() — нормализация запятых в числах, сортировка упаковок и валидация габаритов.
 *
 * saveSettings() вызывает str_replace(',', '.', $value) без приведения типа, а файл объявляет
 * strict_types=1 — значит рассчитан на данные из HTML-формы, которые PHP всегда получает
 * строками. Фикстуры здесь намеренно передают числа строками, как это делает реальный POST;
 * это же ловит регрессию, если кто-то отправит настройки не через форму (JSON API, CLI) — тогда
 * str_replace() бросит TypeError, а не молча всё сломает.
 */
class shopSizerPluginSaveSettingsTest extends TestCase
{
    private const DELTA = 1e-9;

    protected function tearDown(): void
    {
        shopSizerPluginTestDouble::resetSettingsModel();
    }

    // Исключение бросается до parent::saveSettings(), подставная модель не нужна.

    public function testZeroPackDimensionThrowsException(): void
    {
        $plugin = new shopSizerPluginTestDouble();

        $this->expectException(waException::class);
        $this->expectExceptionMessage('Измерение у размера упаковки должно быть больше нуля!');

        $plugin->saveSettings([
            'sizes' => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => '1', 'width' => '0', 'height' => '10', 'length' => '10', 'unit' => 'cm', 'add_weight' => '0', 'add_weight_unit' => 'kg'],
            ]],
        ]);
    }

    public function testNegativePackDimensionThrowsException(): void
    {
        $plugin = new shopSizerPluginTestDouble();

        $this->expectException(waException::class);

        $plugin->saveSettings([
            'sizes' => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => '1', 'width' => '10', 'height' => '-5', 'length' => '10', 'unit' => 'cm', 'add_weight' => '0', 'add_weight_unit' => 'kg'],
            ]],
        ]);
    }

    public function testZeroDefaultSizeDimensionThrowsExceptionWithOwnMessage(): void
    {
        $plugin = new shopSizerPluginTestDouble();

        $this->expectException(waException::class);
        $this->expectExceptionMessage('Измерение у размера упаковки по умолчанию должно быть больше нуля!');

        $plugin->saveSettings([
            'default_size' => ['length' => '0', 'width' => '10', 'height' => '10', 'unit' => 'cm'],
        ]);
    }

    // Дальше — через подставную модель, проверяем, что именно записалось бы в БД.

    private function saveAndDecode(array $settings, string $key)
    {
        $fake = new shopSizerPluginTestAppSettingsModelFake();
        shopSizerPluginTestDouble::installSettingsModel($fake);

        $plugin = new shopSizerPluginTestDouble();
        $plugin->saveSettings($settings);

        $this->assertArrayHasKey($key, $fake->stored, "saveSettings() не записал ключ '$key'");

        return json_decode((string)$fake->stored[$key], true);
    }

    public function testPacksAreSortedByWeightAscendingOnSave(): void
    {
        $sizes = $this->saveAndDecode([
            'sizes' => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => '5', 'width' => '40', 'height' => '40', 'length' => '40', 'unit' => 'cm', 'add_weight' => '0.3', 'add_weight_unit' => 'kg'],
                ['weight' => '1.5', 'width' => '20', 'height' => '20', 'length' => '20', 'unit' => 'cm', 'add_weight' => '100', 'add_weight_unit' => 'g'],
            ]],
        ], 'sizes');

        // JSON не различает 5 и 5.0 при обратном декодировании — сравниваем как числа, а не типы.
        $this->assertEqualsWithDelta(1.5, $sizes['packs'][0]['weight'], self::DELTA);
        $this->assertEqualsWithDelta(5.0, $sizes['packs'][1]['weight'], self::DELTA);
    }

    public function testCommaDecimalsInPacksAreNormalizedToFloat(): void
    {
        $sizes = $this->saveAndDecode([
            'sizes' => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => '1,5', 'width' => '20', 'height' => '20', 'length' => '20', 'unit' => 'cm', 'add_weight' => '0,3', 'add_weight_unit' => 'kg'],
            ]],
        ], 'sizes');

        $this->assertSame(1.5, $sizes['packs'][0]['weight']);
        $this->assertSame(0.3, $sizes['packs'][0]['add_weight']);
    }

    public function testCommaDecimalsInDefaultSizeAreNormalizedToFloat(): void
    {
        $default_size = $this->saveAndDecode([
            'default_size' => ['length' => '10', 'width' => '10,5', 'height' => '10', 'unit' => 'cm'],
        ], 'default_size');

        $this->assertSame(10.5, $default_size['width']);
    }

    public function testCommaDecimalInDefaultAddWeightIsNormalizedToFloat(): void
    {
        $default_add_weight = $this->saveAndDecode([
            'default_add_weight' => ['value' => '0,5', 'unit' => 'kg'],
        ], 'default_add_weight');

        $this->assertSame(0.5, $default_add_weight['value']);
    }

    public function testFreshInstallMergesDefaultsFromSettingsConfig(): void
    {
        $fake = new shopSizerPluginTestAppSettingsModelFake();
        shopSizerPluginTestDouble::installSettingsModel($fake);

        $plugin = new shopSizerPluginTestDouble();

        $default_size = $plugin->getSettings('default_size');
        $sizes = $plugin->getSettings('sizes');

        $this->assertSame(['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'], $default_size);
        $this->assertSame(1, $sizes['packs'][0]['weight']);
    }
}
