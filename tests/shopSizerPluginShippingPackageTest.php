<?php
/**
 * @author Serge Rodovnichenko <serge@syrnik.com>
 * @copyright Serge Rodovnichenko, 2026
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * handlerShippingPackage() — подбор упаковки для хука shipping_package.
 *
 * Базовые единицы установки — m и kg (wa-apps/shop/lib/config/data/dimension.php,
 * переопределений в wa-config/apps/shop/ нет), поэтому все ожидаемые значения ниже — в метрах
 * и килограммах.
 */
class shopSizerPluginShippingPackageTest extends TestCase
{
    private const DELTA = 1e-9;

    private function makePlugin(array $settings): shopSizerPluginTestDouble
    {
        $plugin = new shopSizerPluginTestDouble();
        $plugin->setTestSettings($settings);

        return $plugin;
    }

    public function testTotalWeightSumsQuantityTimesWeightWithCommaAsDecimalSeparator(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 2, 'weight' => '1,0'],
        ]);

        $this->assertSame(2.0, $result['weight']);
    }

    public function testItemWithoutWeightKeyIsTreatedAsZero(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1],
        ]);

        $this->assertSame(0.0, $result['weight']);
    }

    public function testEmptyItemsListUsesOnlyDefaultAddWeightAndDefaultSize(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([]);

        $this->assertSame(0.0, $result['weight']);
        $this->assertEqualsWithDelta(0.1, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['height'], self::DELTA);
    }

    public function testNoPacksConfiguredFallsBackToDefaultSizeAndAddWeight(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 30, 'unit' => 'g'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1],
        ]);

        $this->assertEqualsWithDelta(1.03, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['height'], self::DELTA);
    }

    public function testHeaviestPackWithThresholdNotExceedingTotalWeightIsSelected(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 5, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'cm', 'add_weight' => 300, 'add_weight_unit' => 'g'],
                ['weight' => 1, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1],
        ]);

        $this->assertEqualsWithDelta(2.1, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['height'], self::DELTA);
    }

    public function testWeightBelowLightestThresholdFallsBackToDefaultSize(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 5, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'cm', 'add_weight' => 300, 'add_weight_unit' => 'g'],
                ['weight' => 1, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 0.5],
        ]);

        $this->assertEqualsWithDelta(0.5, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.1, $result['height'], self::DELTA);
    }

    public function testPacksAreSortedByWeightBeforeSelectionRegardlessOfConfiguredOrder(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            // Настроены не по возрастанию веса: тяжёлая упаковка первой.
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 5, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'cm', 'add_weight' => 300, 'add_weight_unit' => 'g'],
                ['weight' => 1, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1],
        ]);

        // При правильной сортировке для веса 2 кг должна выбраться упаковка с порогом 1 кг (20 см),
        // а не 5 кг (40 см).
        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
    }

    public function testThresholdsInNonBaseWeightUnitAreConverted(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'g', 'packs' => [
                ['weight' => 500, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
                ['weight' => 2000, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'mm', 'add_weight' => 0.2, 'add_weight_unit' => 'kg'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 0.6],
        ]);

        $this->assertEqualsWithDelta(0.7, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
    }

    public function testWeightExactlyEqualToThresholdSelectsThatPack(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'g', 'packs' => [
                ['weight' => 500, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
                ['weight' => 2000, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'mm', 'add_weight' => 0.2, 'add_weight_unit' => 'kg'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 2],
        ]);

        $this->assertEqualsWithDelta(2.2, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.04, $result['length'], self::DELTA);
    }

    public function testPackDimensionsAreConvertedToBaseLinearUnit(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 1, 'width' => 40, 'height' => 40, 'length' => 40, 'unit' => 'mm', 'add_weight' => 0, 'add_weight_unit' => 'kg'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1],
        ]);

        $this->assertEqualsWithDelta(0.04, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.04, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.04, $result['height'], self::DELTA);
    }

    public function testAddWeightInGramsIsConvertedAndAddedToTotal(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 1, 'width' => 20, 'height' => 20, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1],
        ]);

        $this->assertEqualsWithDelta(1.1, $result['weight'], self::DELTA);
    }

    public function testResultHasExactlyWeightLengthWidthHeightKeys(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => []],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1],
        ]);

        $this->assertSame(['weight', 'length', 'width', 'height'], array_keys($result));
    }

    /**
     * Три упаковки по возрастанию порога веса: 20 см (от 1 кг), 40 см (от 5 кг), 60 см (от 10 кг).
     * Максимальный габарит каждой — length, по нему видно, какая упаковка выбрана.
     * Габариты товаров в тестах — в метрах (базовая единица, как их передаёт Shop-Script).
     */
    private function makePluginWithThreePacks(): shopSizerPluginTestDouble
    {
        return $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 1, 'width' => 10, 'height' => 10, 'length' => 20, 'unit' => 'cm', 'add_weight' => 100, 'add_weight_unit' => 'g'],
                ['weight' => 5, 'width' => 20, 'height' => 20, 'length' => 40, 'unit' => 'cm', 'add_weight' => 300, 'add_weight_unit' => 'g'],
                ['weight' => 10, 'width' => 30, 'height' => 30, 'length' => 60, 'unit' => 'cm', 'add_weight' => 500, 'add_weight_unit' => 'g'],
            ]],
        ]);
    }

    public function testItemsWithoutDimensionsKeepWeightBasedSelection(): void
    {
        $result = $this->makePluginWithThreePacks()->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1],
        ]);

        $this->assertEqualsWithDelta(2.1, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
    }

    public function testItemThatFitsWeightBasedPackKeepsIt(): void
    {
        $result = $this->makePluginWithThreePacks()->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1, 'length' => 0.15, 'width' => 0.1, 'height' => 0.05],
        ]);

        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
    }

    public function testOversizedItemSelectsNextHeavierPackThatFits(): void
    {
        // По весу (2 кг) — упаковка 20 см, но товар 30 см: берётся следующая, 40 см, вместе с её весом.
        $result = $this->makePluginWithThreePacks()->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1, 'length' => 0.3, 'width' => 0.1, 'height' => 0.1],
        ]);

        $this->assertEqualsWithDelta(2.3, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.4, $result['length'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['width'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['height'], self::DELTA);
    }

    public function testHeavierPackThatIsStillTooSmallIsSkipped(): void
    {
        $result = $this->makePluginWithThreePacks()->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1, 'length' => 0.5],
        ]);

        $this->assertEqualsWithDelta(2.5, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.6, $result['length'], self::DELTA);
    }

    public function testLongestDimensionOfAnyItemIsCompared(): void
    {
        // Максимальный габарит заказа — height второго товара, а не length.
        $result = $this->makePluginWithThreePacks()->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1, 'length' => 0.1, 'width' => 0.1, 'height' => 0.1],
            ['quantity' => 1, 'weight' => 1, 'length' => 0.1, 'width' => 0.1, 'height' => 0.35],
        ]);

        $this->assertEqualsWithDelta(0.4, $result['length'], self::DELTA);
    }

    public function testItemDimensionEqualToPackDimensionFits(): void
    {
        $result = $this->makePluginWithThreePacks()->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1, 'length' => 0.2],
        ]);

        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
    }

    public function testOversizedItemWithDefaultSizeSearchesFromLightestPack(): void
    {
        // Вес 0,5 кг меньше самого лёгкого порога — по весу упаковка по умолчанию (10 см),
        // товар 15 см в неё не лезет, подходит уже первая упаковка из списка (20 см).
        $result = $this->makePluginWithThreePacks()->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 0.5, 'length' => 0.15],
        ]);

        $this->assertEqualsWithDelta(0.6, $result['weight'], self::DELTA);
        $this->assertEqualsWithDelta(0.2, $result['length'], self::DELTA);
    }

    public function testWeightBasedPackIsKeptWhenNoHeavierPackFits(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                ['weight' => 1, 'width' => 5, 'height' => 5, 'length' => 5, 'unit' => 'cm', 'add_weight' => 0, 'add_weight_unit' => 'kg'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1, 'length' => 2],
        ]);

        $this->assertEqualsWithDelta(0.05, $result['length'], self::DELTA);
    }

    public function testLighterPacksAreNotConsideredForOversizedItem(): void
    {
        // По весу выбрана упаковка 60 см; товар 70 см не влезает никуда. Более лёгкие упаковки
        // не рассматриваются — остаётся выбранная по весу.
        $result = $this->makePluginWithThreePacks()->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 12, 'length' => 0.7],
        ]);

        $this->assertEqualsWithDelta(0.6, $result['length'], self::DELTA);
    }

    public function testPackDimensionsInOtherUnitsAreComparedInBaseUnit(): void
    {
        $plugin = $this->makePlugin([
            'default_size'       => ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'cm'],
            'default_add_weight' => ['value' => 0, 'unit' => 'kg'],
            'sizes'              => ['weight_unit' => 'kg', 'packs' => [
                // 250 мм = 0,25 м — товар 0,3 м не влезает, хотя 250 > 0,3 без перевода единиц.
                ['weight' => 1, 'width' => 100, 'height' => 100, 'length' => 250, 'unit' => 'mm', 'add_weight' => 0, 'add_weight_unit' => 'kg'],
                ['weight' => 5, 'width' => 0.2, 'height' => 0.2, 'length' => 0.35, 'unit' => 'm', 'add_weight' => 0, 'add_weight_unit' => 'kg'],
            ]],
        ]);

        $result = $plugin->handlerShippingPackage([
            ['quantity' => 1, 'weight' => 1, 'length' => 0.3],
        ]);

        $this->assertEqualsWithDelta(0.35, $result['length'], self::DELTA);
    }

    public function testItemDimensionWithCommaAsDecimalSeparator(): void
    {
        $result = $this->makePluginWithThreePacks()->handlerShippingPackage([
            ['quantity' => 2, 'weight' => 1, 'length' => '0,3'],
        ]);

        $this->assertEqualsWithDelta(0.4, $result['length'], self::DELTA);
    }
}
